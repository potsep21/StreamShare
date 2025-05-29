<?php
require_once 'includes/functions_fixed.php';
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - StreamShare</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>StreamShare Help</h1>
        <p>Get Help and Support</p>
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
                    Getting Started
                </div>
                <div class="accordion-content">
                    <h3>Basic Steps to Get Started:</h3>
                    <ol>
                        <li>Create your account through the registration page</li>
                        <li>Log in to your account</li>
                        <li>Complete your profile information</li>
                        <li>Start creating your first content list</li>
                        <li>Follow other users to discover their content</li>
                    </ol>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Managing Your Profile
                </div>
                <div class="accordion-content">
                    <h3>Profile Management:</h3>
                    <ul>
                        <li>Update your personal information in the profile settings</li>
                        <li>Change your password regularly for security</li>
                        <li>Add a profile picture to personalize your account</li>
                        <li>Manage your privacy settings</li>
                        <li>View your followers and following lists</li>
                    </ul>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Creating Content Lists
                </div>
                <div class="accordion-content">
                    <h3>How to Create and Manage Lists:</h3>
                    <ol>
                        <li>Click on "Create New List" from your profile</li>
                        <li>Give your list a name and description</li>
                        <li>Choose the privacy setting (public or private)</li>
                        <li>Search for YouTube videos to add to your list</li>
                        <li>Arrange videos in your preferred order</li>
                        <li>Save and share your list</li>
                    </ol>
                    <p>You can edit or delete your lists at any time from your profile.</p>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Following Other Users
                </div>
                <div class="accordion-content">
                    <h3>Connecting with Others:</h3>
                    <ul>
                        <li>Search for users by username or email</li>
                        <li>Visit user profiles to see their public lists</li>
                        <li>Click "Follow" to start following a user</li>
                        <li>Access followed users' content from your feed</li>
                        <li>Manage your following list from your profile</li>
                    </ul>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Playing Content
                </div>
                <div class="accordion-content">
                    <h3>How to Play Content:</h3>
                    <ul>
                        <li>Click on any video in a list to start playback</li>
                        <li>Use the player controls to manage playback</li>
                        <li>Videos will play in sequence automatically</li>
                        <li>Create playlists for continuous playback</li>
                        <li>Adjust video quality based on your connection</li>
                    </ul>
                </div>
            </div>

            <div class="accordion">
                <div class="accordion-header">
                    Troubleshooting
                </div>
                <div class="accordion-content">
                    <h3>Common Issues and Solutions:</h3>
                    <ul>
                        <li>If videos won't play, check your internet connection</li>
                        <li>Clear your browser cache if experiencing display issues</li>
                        <li>Make sure you're logged in to access private lists</li>
                        <li>Contact support if you can't access your account</li>
                        <li>Report inappropriate content through the report button</li>
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