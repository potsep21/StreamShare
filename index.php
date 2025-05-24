<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamShare - Share Your Content</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>Welcome to StreamShare</h1>
        <p>Create and share your content lists</p>
    </header>

    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="php/dashboard.php">Dashboard</a></li>
                <li><a href="php/profile.php">Profile</a></li>
                <li><a href="php/search.php">Search</a></li>
                <li><a href="php/logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="about.php">About</a></li>
                <li><a href="help.php">Help</a></li>
                <li><a href="php/register.php">Register</a></li>
                <li><a href="php/login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <section class="hero">
            <h2>Share Your Favorite Content</h2>
            <p>Create curated lists of YouTube videos and share them with the world.</p>
            <?php if (!isLoggedIn()): ?>
                <div class="cta-buttons">
                    <a href="php/register.php" class="button">Get Started</a>
                    <a href="php/login.php" class="button button-secondary">Login</a>
                </div>
            <?php else: ?>
                <div class="cta-buttons">
                    <a href="php/dashboard.php" class="button">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 StreamShare. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 