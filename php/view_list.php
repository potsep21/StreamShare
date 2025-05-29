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
    if (!$list) {
        // List not found
        $error_message = "List not found.";
        redirect('dashboard.php');
    } else if ($list['is_private'] == 1 && $list['user_id'] != $_SESSION['user_id']) {
        // Private list and not the owner
        $error_message = "You don't have permission to access this private list.";
        redirect('dashboard.php?error=' . urlencode($error_message));
    }
    
    // Get videos in the list
    $stmt = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? ORDER BY position");
    $stmt->execute([$list_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submissions (only check for authentication messages now)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
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
                <li><a href="search.php">Search</a></li>
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>This list has no videos yet.</p>
            <?php endif; ?>

            <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="edit_list.php?id=<?php echo $list_id; ?>" class="button" style="padding: 10px 20px; font-size: 16px;">Edit This List</a>
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