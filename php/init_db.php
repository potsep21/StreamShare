<?php
require_once '../config/database.php';

try {
    // Initialize database and create tables
    initializeDatabase();
    echo "Database initialized successfully!";
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage());
}
?> 