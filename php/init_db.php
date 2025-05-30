<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Initialize database and create tables
    initializeDatabase();
    
    // Now check for the follows table and create it if needed
    $conn = getDBConnection();
    
    // Check if follows table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'follows'");
    $stmt->execute();
    $follows_exists = $stmt->rowCount() > 0;
    
    // Check if followers table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'followers'");
    $stmt->execute();
    $followers_exists = $stmt->rowCount() > 0;
    
    if ($follows_exists) {
        echo "<p>The 'follows' table already exists.</p>";
    } else if ($followers_exists) {
        // Create a view named 'follows' that points to the 'followers' table
        $conn->exec("CREATE VIEW follows AS SELECT * FROM followers");
        echo "<p>Created a view named 'follows' that points to the 'followers' table.</p>";
    } else {
        // Neither table exists, create the follows table
        $conn->exec("CREATE TABLE IF NOT EXISTS follows (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (follower_id, following_id),
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        echo "<p>Created new 'follows' table.</p>";
    }
    
    echo "<p>Database initialized successfully with OAuth support!</p>";
    echo "<p>Make sure to add your YouTube OAuth credentials to config/api_keys.php</p>";
    echo "<p>Required credentials: YOUTUBE_OAUTH_CLIENT_ID and YOUTUBE_OAUTH_CLIENT_SECRET</p>";
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage());
}
?> 