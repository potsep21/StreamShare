<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/youtube_oauth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("../index.php");
}

// Initialize variables
$error = null;
$success = false;

// Process OAuth callback
if (isset($_GET['code'])) {
    try {
        // Get database connection
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Exchange authorization code for access token
        $tokenResponse = getYouTubeAccessToken($_GET['code']);
        
        if (isset($tokenResponse['access_token'])) {
            // Store tokens in session for immediate use
            $_SESSION['youtube_access_token'] = $tokenResponse['access_token'];
            $_SESSION['youtube_token_expires'] = time() + $tokenResponse['expires_in'];
            
            // Store refresh token in the database if available
            if (isset($tokenResponse['refresh_token'])) {
                $_SESSION['youtube_refresh_token'] = $tokenResponse['refresh_token'];
                
                // Check if user already has a refresh token
                $stmt = $conn->prepare("SELECT id FROM oauth_tokens WHERE user_id = ? AND provider = 'youtube'");
                $stmt->execute([$_SESSION['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing token
                    $updateStmt = $conn->prepare("UPDATE oauth_tokens SET refresh_token = ?, created_at = NOW() WHERE user_id = ? AND provider = 'youtube'");
                    $updateStmt->execute([$tokenResponse['refresh_token'], $_SESSION['user_id']]);
                } else {
                    // Insert new token
                    $insertStmt = $conn->prepare("INSERT INTO oauth_tokens (user_id, provider, refresh_token, created_at) VALUES (?, 'youtube', ?, NOW())");
                    $insertStmt->execute([$_SESSION['user_id'], $tokenResponse['refresh_token']]);
                }
            }
            
            $success = true;
        } else {
            $error = "Failed to get access token: " . (isset($tokenResponse['error']) ? $tokenResponse['error'] : "Unknown error");
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
} else if (isset($_GET['error'])) {
    $error = "Authorization failed: " . $_GET['error'];
} else {
    $error = "Invalid callback request";
}

// Redirect to appropriate page
if ($success) {
    // If there was a redirect_after_auth parameter, use it
    if (isset($_SESSION['redirect_after_auth'])) {
        $redirect = $_SESSION['redirect_after_auth'];
        unset($_SESSION['redirect_after_auth']);
        redirect($redirect);
    } else {
        redirect("dashboard.php?youtube_auth=success");
    }
} else {
    redirect("dashboard.php?youtube_auth=error&message=" . urlencode($error));
}
?> 