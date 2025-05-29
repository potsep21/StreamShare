<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize variables
$profile_user = null;
$content_lists = [];
$followers_count = 0;
$following_count = 0;
$is_following = false;
$is_own_profile = false;
$error_message = '';
$success_message = '';

// Check if user ID is provided
if (isset($_GET['id'])) {
    $profile_id = (int)$_GET['id'];
} else if (isLoggedIn()) {
    // Default to current user's profile if no ID provided
    $profile_id = $_SESSION['user_id'];
} else {
    // Redirect to login if not logged in and no profile specified
    redirect('login.php');
}

// Process follow/unfollow actions
if (isLoggedIn() && isset($_POST['action'])) {
    try {
        $conn = getDBConnection();
        $current_user_id = $_SESSION['user_id'];
        
        // Cannot follow yourself
        if ($current_user_id == $profile_id) {
            $error_message = "You cannot follow yourself";
        } else {
            if ($_POST['action'] === 'follow') {
                // Check if already following
                $checkStmt = $conn->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
                $checkStmt->execute([$current_user_id, $profile_id]);
                
                if ($checkStmt->rowCount() === 0) {
                    // Add follow relationship
                    $followStmt = $conn->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
                    $followStmt->execute([$current_user_id, $profile_id]);
                    $success_message = "You are now following this user";
                } else {
                    $error_message = "You are already following this user";
                }
            } else if ($_POST['action'] === 'unfollow') {
                // Remove follow relationship
                $unfollowStmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
                $unfollowStmt->execute([$current_user_id, $profile_id]);
                $success_message = "You have unfollowed this user";
            }
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get profile data
try {
    $conn = getDBConnection();
    
    // Get user data
    $stmt = $conn->prepare("
        SELECT id, username, firstname, lastname, email, bio, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$profile_id]);
    $profile_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile_user) {
        // User not found, redirect to home
        redirect('../index.php');
    }
    
    // Check if this is the current user's profile
    if (isLoggedIn() && $_SESSION['user_id'] == $profile_user['id']) {
        $is_own_profile = true;
    }
    
    // Get public content lists (and private if own profile)
    $listsStmt = $conn->prepare("
        SELECT cl.id, cl.title, cl.description, cl.is_private, cl.created_at,
               (SELECT COUNT(*) FROM list_items WHERE list_id = cl.id) as item_count
        FROM content_lists cl
        WHERE cl.user_id = ? 
        " . (!$is_own_profile ? "AND cl.is_private = 0" : "") . "
        ORDER BY cl.created_at DESC
    ");
    $listsStmt->execute([$profile_id]);
    $content_lists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get followers count
    $followersStmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $followersStmt->execute([$profile_id]);
    $followers_count = $followersStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get following count
    $followingStmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
    $followingStmt->execute([$profile_id]);
    $following_count = $followingStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Check if current user is following this profile
    if (isLoggedIn() && !$is_own_profile) {
        $checkFollowStmt = $conn->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
        $checkFollowStmt->execute([$_SESSION['user_id'], $profile_id]);
        $is_following = ($checkFollowStmt->rowCount() > 0);
    }
    
    // Get user activities (public lists created, videos added to public lists, and new followers)
    $activities = [];
    
    // 1. Get public lists created by people the profile owner follows
    $publicListsStmt = $conn->prepare("
        SELECT 'list_created' as activity_type, cl.id, cl.title, cl.created_at, 
               u.id as user_id, u.username
        FROM content_lists cl
        JOIN users u ON cl.user_id = u.id
        JOIN follows f ON cl.user_id = f.following_id
        WHERE f.follower_id = ? AND cl.is_private = 0 
        ORDER BY cl.created_at DESC 
        LIMIT 15
    ");
    $publicListsStmt->execute([$profile_id]);
    $publicListsCreated = $publicListsStmt->fetchAll(PDO::FETCH_ASSOC);
    $activities = array_merge($activities, $publicListsCreated);
    
    // 2. Get videos added to public lists by people the profile owner follows
    $videosAddedStmt = $conn->prepare("
        SELECT 'video_added' as activity_type, li.id, li.title, li.created_at, 
               cl.id as list_id, cl.title as list_title,
               u.id as user_id, u.username
        FROM list_items li
        JOIN content_lists cl ON li.list_id = cl.id
        JOIN users u ON cl.user_id = u.id
        JOIN follows f ON cl.user_id = f.following_id
        WHERE f.follower_id = ? AND cl.is_private = 0
        ORDER BY li.created_at DESC
        LIMIT 15
    ");
    $videosAddedStmt->execute([$profile_id]);
    $videosAdded = $videosAddedStmt->fetchAll(PDO::FETCH_ASSOC);
    $activities = array_merge($activities, $videosAdded);
    
    // 3. Get new followers (only visible to the profile owner)
    if ($is_own_profile) {
        $newFollowersStmt = $conn->prepare("
            SELECT 'new_follower' as activity_type, f.id, f.created_at, 
                   u.id as follower_id, u.username as follower_username
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC
            LIMIT 10
        ");
        $newFollowersStmt->execute([$profile_id]);
        $newFollowers = $newFollowersStmt->fetchAll(PDO::FETCH_ASSOC);
        $activities = array_merge($activities, $newFollowers);
    }
    
    // Sort activities by created_at in descending order
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to most recent 15 activities
    $activities = array_slice($activities, 0, 15);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profile_user ? htmlspecialchars($profile_user['username']) : 'Profile'; ?> - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Profile page specific styles */
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
            background-color: #121212;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #0078ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            text-shadow: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
            word-wrap: break-word;
            overflow: hidden;
            width: 100%;
        }
        
        .profile-username {
            font-size: 2.5rem;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .profile-fullname {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 25px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
        }
        
        .profile-bio {
            background-color: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.9);
            font-style: italic;
            max-width: 600px;
        }
        
        .profile-actions {
            margin-top: 10px;
        }
        
        .follow-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,123,255,0.3);
        }
        
        .follow-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .unfollow-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .unfollow-btn:hover {
            background-color: #dc3545;
        }
        
        .edit-profile-btn {
            background-color: rgba(70, 70, 70, 0.8);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: normal;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-sizing: border-box;
            max-width: 100%;
            text-align: center;
            word-break: break-word;
        }
        
        .edit-profile-btn:hover {
            background-color: rgba(90, 90, 90, 0.9);
        }
        
        .content-section {
            margin-top: 40px;
        }
        
        .section-heading {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-icon {
            font-size: 1.5rem;
        }
        
        .activity-list {
            background-color: rgba(30, 30, 30, 0.7);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.2s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #1e88e5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-icon.list-created {
            background-color: #43a047;
        }
        
        .activity-icon.video-added {
            background-color: #e53935;
        }
        
        .activity-icon.new-follower {
            background-color: #9c27b0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-size: 1.1rem;
            color: white;
            margin-bottom: 5px;
        }
        
        .activity-title a {
            color: #64b5f6;
            text-decoration: none;
            font-weight: bold;
        }
        
        .activity-title a:hover {
            text-decoration: underline;
        }
        
        .activity-meta {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: rgba(255,255,255,0.7);
            font-style: italic;
            background-color: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 20px;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>StreamShare</h1>
        <p>User Profile</p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php" class="<?php echo $is_own_profile ? 'active' : ''; ?>">Profile</a></li>
                <li><a href="export_data.php">Export Data</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="../about.php">About</a></li>
                <li><a href="../help.php">Help</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($profile_user): ?>
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                    // Get first character of username for avatar
                    $avatar_char = strtoupper(substr($profile_user['username'], 0, 1)); 
                    echo $avatar_char;
                    ?>
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-username"><?php echo htmlspecialchars($profile_user['username']); ?></h2>
                    
                    <?php if ($profile_user['firstname'] || $profile_user['lastname']): ?>
                        <div class="profile-fullname">
                            <?php echo htmlspecialchars($profile_user['firstname'] . ' ' . $profile_user['lastname']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($content_lists); ?></div>
                            <div class="stat-label">Lists</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $followers_count; ?></div>
                            <div class="stat-label">Followers</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $following_count; ?></div>
                            <div class="stat-label">Following</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($profile_user['bio'])): ?>
                        <div class="profile-bio">
                            <?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
                        <?php elseif (isLoggedIn()): ?>
                            <?php if ($is_following): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unfollow">
                                    <button type="submit" class="unfollow-btn">Unfollow</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="follow">
                                    <button type="submit" class="follow-btn">Follow</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h3 class="section-heading">
                    <span class="section-icon">📊</span>
                    Activity
                </h3>
                
                <?php if (count($activities) > 0): ?>
                    <div class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <?php if ($activity['activity_type'] === 'list_created'): ?>
                                    <div class="activity-icon list-created">📋</div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <a href="profile.php?id=<?php echo $activity['user_id']; ?>"><?php echo htmlspecialchars($activity['username']); ?></a> created a new public list: <a href="view_list.php?id=<?php echo $activity['id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></a>
                                        </div>
                                        <div class="activity-meta" data-datetime="<?php echo $activity['created_at']; ?>">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                <?php elseif ($activity['activity_type'] === 'video_added'): ?>
                                    <div class="activity-icon video-added">🎬</div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <a href="profile.php?id=<?php echo $activity['user_id']; ?>"><?php echo htmlspecialchars($activity['username']); ?></a> added video: <a href="view_list.php?id=<?php echo $activity['list_id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></a> to list <a href="view_list.php?id=<?php echo $activity['list_id']; ?>"><?php echo htmlspecialchars($activity['list_title']); ?></a>
                                        </div>
                                        <div class="activity-meta" data-datetime="<?php echo $activity['created_at']; ?>">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                <?php elseif ($activity['activity_type'] === 'new_follower'): ?>
                                    <div class="activity-icon new-follower">👤</div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <a href="profile.php?id=<?php echo $activity['follower_id']; ?>"><?php echo htmlspecialchars($activity['follower_username']); ?></a> started following you
                                        </div>
                                        <div class="activity-meta" data-datetime="<?php echo $activity['created_at']; ?>">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No activity to display yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script>
        // Function to update timestamps in real-time
        function updateTimestamps() {
            const timestamps = document.querySelectorAll('.activity-meta');
            
            timestamps.forEach(timestamp => {
                const datetime = timestamp.getAttribute('data-datetime');
                if (datetime) {
                    timestamp.textContent = calculateTimeAgo(datetime);
                }
            });
        }
        
        // JavaScript version of the PHP timeAgo function
        function calculateTimeAgo(datetime) {
            const timestamp = new Date(datetime).getTime();
            const now = new Date().getTime();
            const diff = Math.floor((now - timestamp) / 1000); // difference in seconds
            
            if (diff < 60) {
                return "just now";
            } else if (diff < 3600) {
                const minutes = Math.floor(diff / 60);
                return minutes + " minute" + (minutes > 1 ? "s" : "") + " ago";
            } else if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                return hours + " hour" + (hours > 1 ? "s" : "") + " ago";
            } else if (diff < 604800) {
                const days = Math.floor(diff / 86400);
                return days + " day" + (days > 1 ? "s" : "") + " ago";
            } else if (diff < 2592000) {
                const weeks = Math.floor(diff / 604800);
                return weeks + " week" + (weeks > 1 ? "s" : "") + " ago";
            } else if (diff < 31536000) {
                const months = Math.floor(diff / 2592000);
                return months + " month" + (months > 1 ? "s" : "") + " ago";
            } else {
                const years = Math.floor(diff / 31536000);
                return years + " year" + (years > 1 ? "s" : "") + " ago";
            }
        }
        
        // Update timestamps immediately and then every minute
        document.addEventListener('DOMContentLoaded', function() {
            updateTimestamps();
            setInterval(updateTimestamps, 60000); // Update every minute
        });
    </script>
</body>
</html> 