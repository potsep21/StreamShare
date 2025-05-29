<?php
require_once 'includes/functions_fixed.php';
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About StreamShare</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>About StreamShare</h1>
        <p>Learn More About Our Platform</p>
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
                <li><a href="php/search.php">Search</a></li>
                <li><a href="php/register.php">Register</a></li>
                <li><a href="php/login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <section class="accordion-section">
            <div class="accordion">
                <div class="accordion-header">
                    What is StreamShare?
                </div>
                <div class="accordion-content">
                    <p>StreamShare is a dynamic web platform that allows users to create, manage, and share their favorite streaming content lists. Our platform focuses on YouTube content, enabling users to curate personalized collections of videos that can be easily shared with the community or kept private for personal use.</p>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Why Join StreamShare?
                </div>
                <div class="accordion-content">
                    <ul>
                        <li>Create personalized content lists from YouTube</li>
                        <li>Discover new content through other users' public lists</li>
                        <li>Follow your favorite content curators</li>
                        <li>Stream content directly from your profile</li>
                        <li>Choose between private and public lists</li>
                        <li>Build your own community of followers</li>
                    </ul>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    How to Register
                </div>
                <div class="accordion-content">
                    <p>Registration is quick and easy:</p>
                    <ol>
                        <li>Click the "Register" button in the navigation menu</li>
                        <li>Fill in your personal details:
                            <ul>
                                <li>First Name</li>
                                <li>Last Name</li>
                                <li>Username</li>
                                <li>Email Address</li>
                                <li>Password</li>
                            </ul>
                        </li>
                        <li>Submit the registration form</li>
                        <li>Start creating your content lists!</li>
                    </ol>
                    <p><a href="php/register.php">Register now</a> to join our community!</p>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Privacy and Security
                </div>
                <div class="accordion-content">
                    <p>At StreamShare, we take your privacy seriously:</p>
                    <ul>
                        <li>Your personal information is securely stored</li>
                        <li>You have full control over your content lists' visibility</li>
                        <li>Private lists are only visible to you</li>
                        <li>Public lists can be viewed by other users</li>
                        <li>You can delete your account and all associated data at any time</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 