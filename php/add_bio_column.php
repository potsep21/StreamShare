<?php
require_once '../config/database.php';

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Check if the bio column already exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'bio'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;
    
    // If the column doesn't exist, add it
    if (!$column_exists) {
        $conn->exec("ALTER TABLE users ADD COLUMN bio TEXT AFTER email");
        echo "Successfully added 'bio' column to users table.";
    } else {
        echo "The 'bio' column already exists in the users table.";
    }
    
    // Also check for the 'follows' table vs 'followers' table mismatch
    $stmt = $conn->prepare("SHOW TABLES LIKE 'follows'");
    $stmt->execute();
    $follows_exists = $stmt->rowCount() > 0;
    
    $stmt = $conn->prepare("SHOW TABLES LIKE 'followers'");
    $stmt->execute();
    $followers_exists = $stmt->rowCount() > 0;
    
    // If follows table exists but followers doesn't, create followers table
    if ($follows_exists && !$followers_exists) {
        $conn->exec("CREATE TABLE followers LIKE follows");
        $conn->exec("INSERT INTO followers SELECT * FROM follows");
        echo "<br>Created 'followers' table from 'follows' table.";
    } 
    // If neither exists, create the followers table
    else if (!$followers_exists) {
        $conn->exec("CREATE TABLE IF NOT EXISTS followers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_follow (follower_id, following_id),
            FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        echo "<br>Created new 'followers' table.";
    }
    
    echo "<br><br><a href='../index.php'>Return to homepage</a>";
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 