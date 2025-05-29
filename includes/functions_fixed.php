<?php
// Include session helper
require_once __DIR__ . '/session_helper.php';

// Function to sanitize user input to prevent SQL injection
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to redirect user
function redirect($location) {
    header("Location: $location");
    exit();
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to check if email is valid
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate YouTube video ID
function isValidYouTubeID($id) {
    return preg_match('/^[a-zA-Z0-9_-]{11}$/', $id);
}

// Function to extract YouTube video ID from URL
function extractYouTubeID($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return false;
}

// Function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to get user data
function getUserData($userId) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error fetching user data: " . $e->getMessage());
    }
}

// Function to check if user is following another user
function isFollowing($followerId, $followingId) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        die("Error checking follow status: " . $e->getMessage());
    }
}

// Function to get user's followers count
function getFollowersCount($userId) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        die("Error counting followers: " . $e->getMessage());
    }
}

// Function to get user's following count
function getFollowingCount($userId) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        die("Error counting following: " . $e->getMessage());
    }
}

// Function to get user's public lists count
function getPublicListsCount($userId) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM content_lists WHERE user_id = ? AND is_private = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        die("Error counting public lists: " . $e->getMessage());
    }
}

// Function to display error message
function displayError($message) {
    return "<div class='error-message'>$message</div>";
}

// Function to display success message
function displaySuccess($message) {
    return "<div class='success-message'>$message</div>";
}

// Function to convert datetime to "time ago" format
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "just now";
    } else if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } else if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
    } else if ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . " month" . ($months > 1 ? "s" : "") . " ago";
    } else {
        $years = floor($diff / 31536000);
        return $years . " year" . ($years > 1 ? "s" : "") . " ago";
    }
}

// Function to store the intended redirect URL
function storeIntendedUrl() {
    // Don't store login or register pages as intended URLs
    if (!isset($_SERVER['REQUEST_URI']) || 
        strpos($_SERVER['REQUEST_URI'], 'login.php') !== false || 
        strpos($_SERVER['REQUEST_URI'], 'register.php') !== false) {
        return;
    }
    
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
}

// Function to get and clear the intended URL
function getIntendedUrl() {
    $url = isset($_SESSION['intended_url']) ? $_SESSION['intended_url'] : 'dashboard.php';
    unset($_SESSION['intended_url']);
    return $url;
} 