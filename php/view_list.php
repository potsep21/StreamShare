<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/youtube.php';
require_once '../config/youtube_oauth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if list ID is provided
if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$list_id = (int)$_GET['id'];
$error_message = null;
$videos = array();

try {
    $conn = getDBConnection();
    
    // Get list details
    $stmt = $conn->prepare("
        SELECT l.*, u.username 
        FROM content_lists l
        JOIN users u ON l.user_id = u.id
        WHERE l.id = ?
    ");
    $stmt->execute([$list_id]);
    $list = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if list exists and user has access
    if (!$list || ($list['is_private'] == 1 && $list['user_id'] != $_SESSION['user_id'])) {
        redirect('dashboard.php');
    }
    
    // Get videos in the list
    $stmt = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? ORDER BY position");
    $stmt->execute([$list_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }
        
        if ($_POST['action'] === 'add_video') {
            $youtube_url = sanitize($_POST['youtube_url']);
            
            // Check if user is authenticated with YouTube
            if (!isYouTubeAuthenticated()) {
                // Store the current URL to redirect back after authentication
                $_SESSION['redirect_after_auth'] = "view_list.php?id=" . $list_id;
                
                // Redirect to the OAuth authorization URL
                redirect(getYouTubeAuthUrl());
            }
            
            // Extract YouTube ID from URL
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
            if (!empty($matches[1])) {
                $youtube_id = $matches[1];
                
                // Get video details from YouTube API
                $videoDetails = getYouTubeVideoDetails($youtube_id);
                if (isset($videoDetails['items'][0])) {
                    $videoData = $videoDetails['items'][0]['snippet'];
                    $video_title = $videoData['title'];
                    
                    // Get the next position
                    $position = count($videos) + 1;

                    // Insert video
                    $stmt = $conn->prepare("INSERT INTO list_items (list_id, title, youtube_id, position) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$list_id, $video_title, $youtube_id, $position]);
                    
                    // Refresh the page to show new video
                    redirect("view_list.php?id=" . $list_id);
                } else if (isset($videoDetails['error']) && $videoDetails['error'] === true) {
                    // OAuth authentication required
                    $_SESSION['redirect_after_auth'] = "view_list.php?id=" . $list_id;
                    redirect(getYouTubeAuthUrl());
                } else {
                    $error_message = "Could not fetch video details from YouTube";
                }
            } else {
                $error_message = "Invalid YouTube URL";
            }
        } elseif ($_POST['action'] === 'remove_video') {
            $video_id = (int)$_POST['video_id'];
            
            // Verify user owns the list before removing video
            if ($list['user_id'] === $_SESSION['user_id']) {
                $stmt = $conn->prepare("DELETE FROM list_items WHERE id = ? AND list_id = ?");
                $stmt->execute([$video_id, $list_id]);
                
                // Refresh the page
                redirect("view_list.php?id=" . $list_id);
            }
        } elseif ($_POST['action'] === 'search_video') {
            $search_query = sanitize($_POST['search_query']);
            
            // Check if user is authenticated with YouTube
            if (!isYouTubeAuthenticated()) {
                // Store the current URL to redirect back after authentication
                $_SESSION['redirect_after_auth'] = "view_list.php?id=" . $list_id;
                
                // Redirect to the OAuth authorization URL
                redirect(getYouTubeAuthUrl());
            } else {
                // User is authenticated, perform the search
                $searchResults = searchYouTubeVideos($search_query);
            }
        }
    }

} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

// Check for YouTube authentication messages
$youtube_auth_message = "";
if (isset($_GET['youtube_auth'])) {
    if ($_GET['youtube_auth'] === 'success') {
        $youtube_auth_message = "Successfully authenticated with YouTube!";
    } elseif ($_GET['youtube_auth'] === 'error') {
        $youtube_auth_message = "YouTube authentication failed: " . (isset($_GET['message']) ? $_GET['message'] : "Unknown error");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($list['title']); ?> - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .video-container {
            margin: 2rem 0;
            padding: 1rem;
            background-color: #f0f2f5;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        body.dark-theme .video-container {
            background-color: var(--dark-secondary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .video-card {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .video-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body.dark-theme .video-card {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme .video-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
        }

        .video-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }

        body.dark-theme .video-thumbnail {
            border-color: var(--dark-border);
        }

        .video-title {
            margin: 0.5rem 0;
            font-size: 1.1rem;
            color: #111827;
            font-weight: 600;
        }

        body.dark-theme .video-title {
            color: var(--dark-text);
        }

        .video-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .preview-container {
            margin: 1rem 0;
            display: none;
            background-color: #ffffff;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }

        body.dark-theme .preview-container {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
        }

        #videoPreview {
            width: 100%;
            max-width: 560px;
            aspect-ratio: 16/9;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        body.dark-theme #videoPreview {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            max-width: 100%;
            overflow: hidden;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            word-wrap: break-word;
        }

        body.dark-theme .form-group label {
            color: var(--dark-text);
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background-color: #ffffff;
            color: #111827;
            transition: border-color 0.2s;
        }

        .form-group input[type="text"]:focus {
            border-color: var(--light-accent);
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        body.dark-theme .form-group input[type="text"] {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
            color: var(--dark-text);
        }

        body.dark-theme .form-group input[type="text"]:focus {
            border-color: var(--dark-accent);
            box-shadow: 0 0 0 2px rgba(0, 86, 179, 0.2);
        }

        .search-results {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }

        body.dark-theme .search-results {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
        }

        .video-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            line-height: 1.4;
        }

        body.dark-theme .video-description {
            color: var(--dark-text-secondary);
        }

        #searchForm {
            margin-bottom: 2rem;
        }
        
        .auth-notice {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            color: #0050b3;
        }
        
        body.dark-theme .auth-notice {
            background-color: #112236;
            border-color: #153450;
            color: #4fadf7;
        }
        
        .auth-button {
            background-color: #1890ff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            margin-top: 1rem;
        }
        
        .auth-button:hover {
            background-color: #096dd9;
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['lastTheme']) && $_COOKIE['lastTheme'] === 'dark' ? 'dark-theme' : ''; ?>">
    <header>
        <div class="logo">
            <h1>StreamShare</h1>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="discover.php">Discover</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
                <li>
                    <button class="theme-toggle" aria-label="Toggle Theme">
                        <span class="icon-light">☀️</span>
                        <span class="icon-dark">🌙</span>
                    </button>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1><?php echo htmlspecialchars($list['title']); ?></h1>
            <p class="list-info">Created by: <a href="profile.php?username=<?php echo htmlspecialchars($list['username']); ?>"><?php echo htmlspecialchars($list['username']); ?></a> | <?php echo $list['is_private'] ? 'Private' : 'Public'; ?> list</p>
            
            <?php if ($list['description']): ?>
                <p class="list-description"><?php echo nl2br(htmlspecialchars($list['description'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($youtube_auth_message)): ?>
                <div class="auth-notice">
                    <?php echo htmlspecialchars($youtube_auth_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($videos) > 0): ?>
                <h2>Videos</h2>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-card">
                            <iframe 
                                width="100%" 
                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                title="<?php echo htmlspecialchars($video['title']); ?>"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                            <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            
                            <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
                                <div class="video-actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="remove_video">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <button type="submit" class="button button-danger">Remove</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>This list has no videos yet.</p>
            <?php endif; ?>

            <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
                <div class="video-container">
                    <h2>Add New Video</h2>
                    
                    <?php if (!isYouTubeAuthenticated()): ?>
                        <div class="auth-notice">
                            <p>YouTube OAuth authentication is required for searching videos. Please authenticate with YouTube to proceed.</p>
                            <a href="<?php echo getYouTubeAuthUrl(); ?>" class="auth-button">Authenticate with YouTube</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $list_id); ?>" id="searchForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="search_video">
                            
                            <div class="form-group">
                                <label for="search_query">Search YouTube Videos</label>
                                <input type="text" id="search_query" name="search_query" required>
                                <button type="submit" class="button">Search</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (isset($searchResults) && isset($searchResults['items'])): ?>
                        <div class="search-results">
                            <h3>Search Results</h3>
                            <div class="video-grid">
                                <?php foreach ($searchResults['items'] as $result): ?>
                                    <div class="video-card">
                                        <img src="<?php echo htmlspecialchars($result['snippet']['thumbnails']['medium']['url']); ?>" 
                                             alt="<?php echo htmlspecialchars($result['snippet']['title']); ?>" 
                                             class="video-thumbnail">
                                        <h3 class="video-title"><?php echo htmlspecialchars($result['snippet']['title']); ?></h3>
                                        <p class="video-description"><?php echo htmlspecialchars(substr($result['snippet']['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="add_video">
                                            <input type="hidden" name="youtube_url" value="https://www.youtube.com/watch?v=<?php echo $result['id']['videoId']; ?>">
                                            <button type="submit" class="button">Add to List</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script>
        // Sync localStorage with cookie theme
        const lastTheme = localStorage.getItem('lastTheme');
        if (lastTheme === 'dark') {
            document.body.classList.add('dark-theme');
            document.cookie = "lastTheme=dark; path=/; max-age=31536000"; // 1 year
        } else if (lastTheme === 'light') {
            document.body.classList.remove('dark-theme');
            document.cookie = "lastTheme=light; path=/; max-age=31536000";
        }

        // YouTube URL validation and preview
        document.getElementById('youtube_url')?.addEventListener('input', function() {
            const url = this.value;
            const previewContainer = document.querySelector('.preview-container');
            const videoPreview = document.getElementById('videoPreview');
            
            // Extract YouTube ID
            const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            
            if (match && match[1]) {
                const videoId = match[1];
                
                // Show preview
                previewContainer.style.display = 'block';
                videoPreview.src = `https://www.youtube.com/embed/${videoId}`;
                
                // Auto-fill title if empty
                const titleInput = document.getElementById('video_title');
                if (titleInput && !titleInput.value) {
                    // Fetch video title using noembed.com API
                    fetch(`https://noembed.com/embed?url=${url}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.title) {
                                titleInput.value = data.title;
                            }
                        })
                        .catch(error => console.error('Error fetching video title:', error));
                }
            } else if (previewContainer && videoPreview) {
                previewContainer.style.display = 'none';
                videoPreview.src = '';
            }
        });
        
        // Handle theme toggling
        document.querySelector('.theme-toggle')?.addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            const isDark = document.body.classList.contains('dark-theme');
            localStorage.setItem('lastTheme', isDark ? 'dark' : 'light');
            document.cookie = `lastTheme=${isDark ? 'dark' : 'light'}; path=/; max-age=31536000`;
        });
    </script>

    <script src="../js/main.js"></script>
</body>
</html> 