<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get list ID from URL
if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}
$list_id = (int)$_GET['id'];

// Get list details
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM content_lists WHERE id = ?");
    $stmt->execute([$list_id]);
    $list = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if list exists and user has access
    if (!$list || ($list['is_private'] && $list['user_id'] !== $_SESSION['user_id'])) {
        redirect('dashboard.php');
    }

    // Get list items (videos)
    $stmt = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? ORDER BY position ASC");
    $stmt->execute([$list_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle video addition
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }

        if ($_POST['action'] === 'add_video') {
            $youtube_url = sanitize($_POST['youtube_url']);
            $video_title = sanitize($_POST['video_title']);
            
            // Extract YouTube ID from URL
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
            if (!empty($matches[1])) {
                $youtube_id = $matches[1];
                
                // Get the next position
                $position = count($videos) + 1;

                // Insert video
                $stmt = $conn->prepare("INSERT INTO list_items (list_id, title, youtube_id, position) VALUES (?, ?, ?, ?)");
                $stmt->execute([$list_id, $video_title, $youtube_id, $position]);
                
                // Refresh the page to show new video
                redirect("view_list.php?id=" . $list_id);
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
        }
    }

} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
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
            background-color: var(--light-secondary);
            border-radius: 8px;
        }

        body.dark-theme .video-container {
            background-color: var(--dark-secondary);
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .video-card {
            background-color: var(--light-bg);
            border: 1px solid var(--light-border);
            border-radius: 8px;
            padding: 1rem;
        }

        body.dark-theme .video-card {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
        }

        .video-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .video-title {
            margin: 0.5rem 0;
            font-size: 1.1rem;
        }

        .video-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .preview-container {
            margin: 1rem 0;
            display: none;
        }

        #videoPreview {
            width: 100%;
            max-width: 560px;
            aspect-ratio: 16/9;
            border: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1><?php echo htmlspecialchars($list['title']); ?></h1>
        <p><?php echo htmlspecialchars($list['description']); ?></p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="search.php">Search</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
            <div class="video-container">
                <h2>Add New Video</h2>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $list_id); ?>" id="addVideoForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add_video">
                    
                    <div class="form-group">
                        <label for="youtube_url">YouTube Video URL</label>
                        <input type="text" id="youtube_url" name="youtube_url" required>
                    </div>

                    <div class="form-group">
                        <label for="video_title">Video Title</label>
                        <input type="text" id="video_title" name="video_title" required>
                    </div>

                    <div class="preview-container">
                        <h3>Preview</h3>
                        <iframe id="videoPreview" src="" frameborder="0" allowfullscreen></iframe>
                    </div>

                    <button type="submit" class="button">Add Video</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="video-container">
            <h2>Videos</h2>
            <?php if (empty($videos)): ?>
                <p>No videos in this list yet.</p>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-card">
                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/maxresdefault.jpg" 
                                 alt="<?php echo htmlspecialchars($video['title']); ?>" 
                                 class="video-thumbnail">
                            <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            
                            <div class="video-actions">
                                <a href="https://www.youtube.com/watch?v=<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                   target="_blank" 
                                   class="button">Watch on YouTube</a>
                                
                                <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="remove_video">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <button type="submit" class="button button-secondary">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script>
        // YouTube URL validation and preview
        document.getElementById('youtube_url').addEventListener('input', function() {
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
                if (!titleInput.value) {
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
            } else {
                previewContainer.style.display = 'none';
                videoPreview.src = '';
            }
        });
    </script>

    <script src="../js/main.js"></script>
</body>
</html> 