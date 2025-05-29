<?php
require_once 'includes/functions_fixed.php';
require_once 'config/database.php';

// Initialize message variables
$error_message = '';
$success_message = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamShare - Share Your Content</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Home page specific styles */
        .hero {
            background-color: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            color: rgba(255,255,255,0.9);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }
        
        .feature-card {
            background-color: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        body.dark-theme .feature-card {
            background-color: rgba(40,40,40,0.9);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            color: #0056b3;
        }
        
        body.dark-theme .feature-title {
            color: #4da3ff;
        }
        
        .feature-desc {
            font-size: 1rem;
            color: #555;
            line-height: 1.5;
        }
        
        body.dark-theme .feature-desc {
            color: #ccc;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .cta-primary {
            background-color: #007bff;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .cta-primary:hover {
            background-color: #0056b3;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,123,255,0.4);
        }
        
        .cta-secondary {
            background-color: rgba(255,255,255,0.15);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .cta-secondary:hover {
            background-color: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }
        
        .trending-section {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .trending-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #fff;
        }
        
        .trending-lists {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .trending-card {
            background-color: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        body.dark-theme .trending-card {
            background-color: rgba(40,40,40,0.9);
        }
        
        .trending-card:hover {
            transform: translateY(-5px);
        }
        
        .trending-title-card {
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            color: #0056b3;
        }
        
        body.dark-theme .trending-title-card {
            color: #4da3ff;
        }
        
        .trending-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .trending-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #333;
        }
        
        .trending-username {
            color: #0056b3;
            text-decoration: none;
            font-weight: bold;
        }
        
        body.dark-theme .trending-username {
            color: #4da3ff;
        }
        
        .trending-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        body.dark-theme .trending-stats {
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            .features {
                grid-template-columns: 1fr;
            }
            
            .trending-lists {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
        }
        
        /* Search form styling */
        .hero-search {
            display: flex;
            gap: 10px;
            max-width: 600px;
            margin: 1.5rem auto;
        }
        
        .search-container {
            margin: 1.5rem auto;
            max-width: 650px;
            transition: all 0.3s ease;
        }
        
        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px;
            background-color: rgba(255,255,255,0.9);
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 6px 16px rgba(0,123,255,0.25);
            transform: translateY(-2px);
        }
        
        body.dark-theme .search-input {
            background-color: rgba(40,40,40,0.9);
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .search-button {
            padding: 15px 25px;
            border-radius: 30px;
            background-color: #007bff;
            color: white;
            border: none;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .search-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,123,255,0.4);
        }
        
        body.dark-theme .search-button {
            background-color: #0056b3;
            box-shadow: 0 4px 12px rgba(0,86,179,0.4);
        }
        
        body.dark-theme .search-button:hover {
            background-color: #004494;
            box-shadow: 0 6px 16px rgba(0,86,179,0.5);
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>StreamShare</h1>
        <p>Create and share your streaming content lists</p>
    </header>

    <nav>
        <ul>
            <li><a href="index.php" class="active">Home</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="php/dashboard.php">Dashboard</a></li>
                <li><a href="php/profile.php">Profile</a></li>
                <li><a href="php/export_data.php">Export Data</a></li>
                <li><a href="php/search.php">Search</a></li>
                <li><a href="php/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="about.php">About</a></li>
                <li><a href="help.php">Help</a></li>
                <li><a href="php/search.php">Search</a></li>
                <li><a href="php/register.php">Register</a></li>
                <li><a href="php/login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <section class="hero">
            <h2>Share Your Favorite YouTube Content</h2>
            <p>Create curated lists of videos, follow other users, and discover new content from the community. StreamShare makes it easy to organize and share your favorite YouTube videos in one place.</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="search-container">
                    <form action="php/search.php" method="GET" class="hero-search">
                        <input type="text" name="query" placeholder="Search for content lists..." minlength="3" required class="search-input">
                        <button type="submit" class="button search-button">Search</button>
                    </form>
                </div>
                <div class="cta-buttons">
                    <a href="php/register.php" class="cta-primary">Get Started</a>
                    <a href="php/login.php" class="cta-secondary">Login</a>
                </div>
            <?php else: ?>
                <div class="cta-buttons">
                    <a href="php/dashboard.php" class="cta-primary">Go to Dashboard</a>
                    <a href="php/search.php" class="cta-secondary">Search Content</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title">Create Content Lists</h3>
                <p class="feature-desc">Organize your favorite YouTube videos into public or private lists that you can access from anywhere.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Follow Users</h3>
                <p class="feature-desc">Follow other users to discover new content and stay updated with their latest public lists.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">▶️</div>
                <h3 class="feature-title">Stream Content</h3>
                <p class="feature-desc">Watch videos directly from your profile without leaving the site. Create playlists for continuous playback.</p>
            </div>
        </section>

        <?php
        // Display trending lists only when user is logged in (not when logged out)
        if (isLoggedIn()):
            $trending_lists = [];
            $displayTrending = false;
            
            try {
                // Connect to database
                $conn = getDBConnection();
                
                // Get some public lists to display
                $stmt = $conn->prepare("
                    SELECT cl.id, cl.title, cl.created_at, 
                           u.username, u.id as user_id,
                           (SELECT COUNT(*) FROM list_items WHERE list_id = cl.id) as item_count
                    FROM content_lists cl
                    JOIN users u ON cl.user_id = u.id
                    WHERE cl.is_private = 0 
                    ORDER BY cl.created_at DESC
                    LIMIT 3
                ");
                
                $stmt->execute();
                $trending_lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($trending_lists) > 0) {
                    $displayTrending = true;
                }
            } catch(PDOException $e) {
                // Silently fail - this is just a nice-to-have feature
                $displayTrending = false;
            }
            
            if ($displayTrending && count($trending_lists) > 0):
        ?>
        <section class="trending-section">
            <h2 class="trending-title">Discover Popular Lists</h2>
            
            <div class="trending-lists">
                <?php foreach ($trending_lists as $list): ?>
                    <div class="trending-card">
                        <h3 class="trending-title-card"><?php echo htmlspecialchars($list['title']); ?></h3>
                        
                        <div class="trending-user">
                            <div class="trending-avatar"><?php echo strtoupper(substr($list['username'], 0, 1)); ?></div>
                            <a href="php/profile.php?id=<?php echo $list['user_id']; ?>" class="trending-username">
                                <?php echo htmlspecialchars($list['username']); ?>
                            </a>
                        </div>
                        
                        <div class="trending-stats">
                            <span><?php echo $list['item_count']; ?> videos</span>
                            <span>Created: <?php echo formatDate($list['created_at']); ?></span>
                        </div>
                        
                        <a href="php/search.php?query=<?php echo urlencode($list['title']); ?>" class="button" style="margin-top: 1rem; display: inline-block;">View Similar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php 
            endif;
        endif; 
        ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['account_deleted']) && $_GET['account_deleted'] == 1): ?>
            <div class="success-message" style="margin-bottom: 20px;">Your account has been successfully deleted. All your data has been removed from our system.</div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 