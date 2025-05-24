<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$user = getUserData($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Handle list creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_list') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }

        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $is_private = isset($_POST['is_private']) ? 1 : 0;

        if (!empty($title)) {
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("INSERT INTO content_lists (user_id, title, description, is_private) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $description, $is_private]);
                $success_message = "List created successfully!";
            } catch(PDOException $e) {
                $error_message = "Error creating list: " . $e->getMessage();
            }
        } else {
            $error_message = "List title is required";
        }
    }
}

// Get user's lists
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM content_lists WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching lists: " . $e->getMessage();
    $lists = [];
}

// Get user's stats
$followers_count = getFollowersCount($_SESSION['user_id']);
$following_count = getFollowingCount($_SESSION['user_id']);
$public_lists_count = getPublicListsCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
        }

        .profile-sidebar {
            background-color: var(--light-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            height: fit-content;
        }

        body.dark-theme .profile-sidebar {
            background-color: var(--dark-secondary);
        }

        .profile-stats {
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid var(--light-border);
            border-bottom: 1px solid var(--light-border);
        }

        body.dark-theme .profile-stats {
            border-color: var(--dark-border);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
        }

        .content-area {
            background-color: var(--light-secondary);
            padding: 1.5rem;
            border-radius: 8px;
        }

        body.dark-theme .content-area {
            background-color: var(--dark-secondary);
        }

        .create-list-form {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--light-border);
        }

        body.dark-theme .create-list-form {
            border-color: var(--dark-border);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--light-border);
            border-radius: 4px;
            background-color: var(--light-bg);
            color: var(--light-text);
        }

        body.dark-theme .form-group input[type="text"],
        body.dark-theme .form-group textarea {
            background-color: var(--dark-bg);
            color: var(--dark-text);
            border-color: var(--dark-border);
        }

        .lists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .list-card {
            background-color: var(--light-bg);
            border: 1px solid var(--light-border);
            border-radius: 8px;
            padding: 1rem;
        }

        body.dark-theme .list-card {
            background-color: var(--dark-bg);
            border-color: var(--dark-border);
        }

        .list-card h3 {
            margin: 0 0 0.5rem 0;
        }

        .list-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        body.dark-theme .list-meta {
            color: #aaa;
        }

        .list-actions {
            display: flex;
            gap: 0.5rem;
        }

        .button {
            background-color: var(--light-accent);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        body.dark-theme .button {
            background-color: var(--dark-accent);
        }

        .button:hover {
            opacity: 0.9;
        }

        .button-secondary {
            background-color: #6c757d;
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background-color: #f8d7da;
        }

        .success-message {
            color: #28a745;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid #28a745;
            border-radius: 4px;
            background-color: #d4edda;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p>Manage your content lists and profile</p>
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
        <div class="dashboard-container">
            <aside class="profile-sidebar">
                <h2><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h2>
                <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span>Followers</span>
                        <span><?php echo $followers_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Following</span>
                        <span><?php echo $following_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Public Lists</span>
                        <span><?php echo $public_lists_count; ?></span>
                    </div>
                </div>

                <a href="profile.php" class="button" style="width: 100%; text-align: center; margin-top: 1rem;">Edit Profile</a>
            </aside>

            <section class="content-area">
                <div class="create-list-form">
                    <h2>Create New List</h2>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="error-message"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="create_list">
                        
                        <div class="form-group">
                            <label for="title">List Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_private">
                                Make this list private
                            </label>
                        </div>

                        <button type="submit" class="button">Create List</button>
                    </form>
                </div>

                <div class="lists-section">
                    <h2>Your Lists</h2>
                    
                    <?php if (empty($lists)): ?>
                        <p>You haven't created any lists yet. Create your first list above!</p>
                    <?php else: ?>
                        <div class="lists-grid">
                            <?php foreach ($lists as $list): ?>
                                <div class="list-card">
                                    <h3><?php echo htmlspecialchars($list['title']); ?></h3>
                                    <div class="list-meta">
                                        <p><?php echo $list['is_private'] ? '🔒 Private' : '🌐 Public'; ?></p>
                                        <p>Created: <?php echo formatDate($list['created_at']); ?></p>
                                    </div>
                                    <p><?php echo htmlspecialchars($list['description']); ?></p>
                                    <div class="list-actions">
                                        <a href="view_list.php?id=<?php echo $list['id']; ?>" class="button">View</a>
                                        <a href="edit_list.php?id=<?php echo $list['id']; ?>" class="button button-secondary">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html> 