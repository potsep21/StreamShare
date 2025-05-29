<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/youtube.php';
require_once '../config/youtube_oauth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    storeIntendedUrl();
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
        :root {
            --primary-color: #0066cc;
            --primary-hover: #0056b3;
            --light-bg-card: #ffffff;
            --light-border-card: #e0e0e0;
            --dark-bg-card: #2d2d2d;
            --dark-border-card: #444444;
        }
        
        body {
            background-color: var(--light-bg);
            transition: all 0.3s ease;
        }
        
        body.dark-theme {
            background-color: var(--dark-bg);
        }
        
        .page-header {
            background-color: var(--primary-color);
            padding: 1.5rem 0;
            color: white;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        body.dark-theme .page-header {
            background-color: var(--dark-accent);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .page-title {
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .list-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin: 0.5rem 0 0;
            font-size: 1.1rem;
        }
        
        .list-info a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s;
        }
        
        .list-info a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .privacy-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .private-badge {
            background-color: rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .public-badge {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .list-description {
            background-color: var(--light-bg-card);
            border: 1px solid var(--light-border-card);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            font-size: 1.1rem;
            line-height: 1.6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        body.dark-theme .list-description {
            background-color: var(--dark-bg-card);
            border-color: var(--dark-border-card);
            color: var(--dark-text);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .videos-section {
            margin: 2.5rem 0;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        body.dark-theme .section-header {
            border-color: var(--dark-accent);
        }
        
        .section-title {
            font-size: 1.8rem;
            margin: 0;
            color: var(--primary-color);
        }
        
        body.dark-theme .section-title {
            color: var(--dark-accent);
        }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .video-card {
            background-color: var(--light-bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .video-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }
        
        body.dark-theme .video-card {
            background-color: var(--dark-bg-card);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
        }
        
        body.dark-theme .video-card:hover {
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
        }
        
        .video-frame {
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
        }
        
        .video-details {
            padding: 1.2rem;
        }
        
        .video-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #333;
            line-height: 1.4;
        }
        
        body.dark-theme .video-title {
            color: #f0f0f0;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: var(--light-bg-card);
            border-radius: 12px;
            margin: 2rem 0;
            border: 1px dashed var(--light-border-card);
        }
        
        body.dark-theme .empty-state {
            background-color: var(--dark-bg-card);
            border-color: var(--dark-border-card);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ccc;
        }
        
        body.dark-theme .empty-state-icon {
            color: #555;
        }
        
        .empty-state-text {
            font-size: 1.2rem;
            color: #777;
            margin-bottom: 1.5rem;
        }
        
        body.dark-theme .empty-state-text {
            color: #aaa;
        }
        
        .edit-button {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 102, 204, 0.3);
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }
        
        .edit-button:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 15px rgba(0, 102, 204, 0.4);
            transform: translateY(-2px);
        }
        
        body.dark-theme .edit-button {
            background-color: var(--dark-accent);
            box-shadow: 0 4px 10px rgba(0, 86, 179, 0.4);
        }
        
        body.dark-theme .edit-button:hover {
            background-color: #004494;
            box-shadow: 0 6px 15px rgba(0, 86, 179, 0.5);
        }
        
        .action-button-container {
            text-align: center;
            margin: 2.5rem 0;
        }
        
        .auth-notice {
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin: 1.5rem 0;
            color: #0050b3;
        }
        
        body.dark-theme .auth-notice {
            background-color: #112236;
            border-color: #1890ff;
            color: #4fadf7;
        }
        
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .list-info {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['lastTheme']) && $_COOKIE['lastTheme'] === 'dark' ? 'dark-theme' : ''; ?>">
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>StreamShare</h1>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="export_data.php">Export Data</a></li>
            <li><a href="search.php">Search</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="page-header">
        <div class="content-container">
            <h1 class="page-title"><?php echo htmlspecialchars($list['title']); ?></h1>
            <div class="list-info">
                <span>Created by: <a href="profile.php?username=<?php echo htmlspecialchars($list['username']); ?>"><?php echo htmlspecialchars($list['username']); ?></a></span>
                <span class="privacy-badge <?php echo $list['is_private'] ? 'private-badge' : 'public-badge'; ?>">
                    <?php echo $list['is_private'] ? '🔒 Private' : '🌐 Public'; ?>
                </span>
            </div>
        </div>
    </div>

    <main class="content-container">
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
        
        <?php if ($list['description']): ?>
            <div class="list-description">
                <?php echo nl2br(htmlspecialchars($list['description'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="videos-section">
            <div class="section-header">
                <h2 class="section-title">Videos</h2>
            </div>
            
            <?php if (count($videos) > 0): ?>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-card">
                            <iframe 
                                class="video-frame"
                                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                title="<?php echo htmlspecialchars($video['title']); ?>"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                            <div class="video-details">
                                <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📺</div>
                    <p class="empty-state-text">This list has no videos yet.</p>
                    <?php if ($list['user_id'] === $_SESSION['user_id']): ?>
                        <a href="edit_list.php?id=<?php echo $list_id; ?>" class="edit-button">Add Videos</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($list['user_id'] === $_SESSION['user_id'] && count($videos) > 0): ?>
            <div class="action-button-container">
                <a href="edit_list.php?id=<?php echo $list_id; ?>" class="edit-button">Edit This List</a>
            </div>
        <?php endif; ?>
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