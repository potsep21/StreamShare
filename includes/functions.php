<?php
session_start();

// Function to sanitize user input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect user
function redirect($location) {
    header("Location: $location");
    exit();
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
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
?> 