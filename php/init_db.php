<?php
require_once '../config/database.php';

try {
    // Initialize database and create tables
    initializeDatabase();
    echo "Database initialized successfully with OAuth support!";
    echo "<p>Make sure to add your YouTube OAuth credentials to config/api_keys.php</p>";
    echo "<p>Required credentials: YOUTUBE_OAUTH_CLIENT_ID and YOUTUBE_OAUTH_CLIENT_SECRET</p>";
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage());
}
?> 