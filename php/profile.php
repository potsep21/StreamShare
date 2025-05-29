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
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            background-color: rgba(0,0,0,0.3);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-username {
            font-size: 2.5rem;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .profile-fullname {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 15px;
        }
        
        .profile-stats {
            display: flex;
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
        }
        
        .profile-actions {
            margin-top: 20px;
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
            background-color: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .edit-profile-btn:hover {
            background-color: rgba(255,255,255,0.25);
            transform: translateY(-2px);
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
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .content-card {
            background-color: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        body.dark-theme .content-card {
            background-color: rgba(40,40,40,0.9);
        }
        
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        
        .private-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .content-title {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #0056b3;
        }
        
        body.dark-theme .content-title {
            color: #4da3ff;
        }
        
        .content-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        body.dark-theme .content-meta {
            color: #aaa;
        }
        
        .content-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .view-btn, .edit-btn {
            flex: 1;
            padding: 8px 0;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .view-btn {
            background-color: #28a745;
            color: white;
        }
        
        .view-btn:hover {
            background-color: #218838;
        }
        
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #0056b3;
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
            
            .content-grid {
                grid-template-columns: 1fr;
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
                    <?php echo strtoupper(substr($profile_user['username'], 0, 1)); ?>
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
                    <span class="section-icon">📋</span>
                    Content Lists
                    <?php if ($is_own_profile): ?>
                        <span style="font-size: 1rem; color: rgba(255,255,255,0.7); margin-left: 10px;">
                            (includes private lists)
                        </span>
                    <?php endif; ?>
                </h3>
                
                <?php if (count($content_lists) > 0): ?>
                    <div class="content-grid">
                        <?php foreach ($content_lists as $list): ?>
                            <div class="content-card">
                                <?php if ($list['is_private']): ?>
                                    <div class="private-badge">Private</div>
                                <?php endif; ?>
                                
                                <h4 class="content-title"><?php echo htmlspecialchars($list['title']); ?></h4>
                                
                                <?php if (!empty($list['description'])): ?>
                                    <p>
                                        <?php echo htmlspecialchars(substr($list['description'], 0, 100)) . (strlen($list['description']) > 100 ? '...' : ''); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="content-meta">
                                    <span><?php echo $list['item_count']; ?> videos</span>
                                    <span>Created: <?php echo formatDate($list['created_at']); ?></span>
                                </div>
                                
                                <div class="content-actions">
                                    <a href="view_list.php?id=<?php echo $list['id']; ?>" class="view-btn">View</a>
                                    <?php if ($is_own_profile): ?>
                                        <a href="edit_list.php?id=<?php echo $list['id']; ?>" class="edit-btn">Edit</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <?php if ($is_own_profile): ?>
                            You haven't created any content lists yet.
                            <br><br>
                            <a href="create_list.php" class="button">Create Your First List</a>
                        <?php else: ?>
                            This user hasn't created any public content lists yet.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html> 