<?php
require_once '../config/database.php';

try {
    // Get database connection
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
        echo "The 'follows' table already exists.<br>";
    } else if ($followers_exists) {
        // Create a view named 'follows' that points to the 'followers' table
        $conn->exec("CREATE VIEW follows AS SELECT * FROM followers");
        echo "Created a view named 'follows' that points to the 'followers' table.<br>";
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
        echo "Created new 'follows' table.<br>";
    }
    
    echo "<br><a href='../index.php'>Return to homepage</a>";
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 