<?php
// Database configuration - same as in config/database.php
define('DB_HOST', 'db');
define('DB_USER', 'streamshare');
define('DB_PASS', 'streamshare_password');
define('DB_NAME', 'streamshare');

try {
    // Connect to MySQL without database selected
    $conn = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE " . DB_NAME);

    // Create users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        bio TEXT,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Create content_lists table
    $conn->exec("CREATE TABLE IF NOT EXISTS content_lists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        is_private BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create list_items table for storing YouTube videos
    $conn->exec("CREATE TABLE IF NOT EXISTS list_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        list_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        youtube_id VARCHAR(20) NOT NULL,
        position INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (list_id) REFERENCES content_lists(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create followers table
    $conn->exec("CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create follows view (if followers table exists but follows doesn't)
    $stmt = $conn->prepare("SHOW TABLES LIKE 'follows'");
    $stmt->execute();
    $follows_exists = $stmt->rowCount() > 0;
    
    if (!$follows_exists) {
        $conn->exec("CREATE VIEW follows AS SELECT * FROM followers");
    }

    // Create oauth_tokens table for storing OAuth access and refresh tokens
    $conn->exec("CREATE TABLE IF NOT EXISTS oauth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        provider VARCHAR(50) NOT NULL,
        refresh_token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_provider (user_id, provider),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    echo "Database and tables created successfully!";
} catch(PDOException $e) {
    die("Error creating database: " . $e->getMessage());
}
?> 