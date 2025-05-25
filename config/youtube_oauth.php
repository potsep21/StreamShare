<?php
// YouTube OAuth 2.0 Configuration
require_once __DIR__ . '/api_keys.php';

// OAuth 2.0 settings
define('YOUTUBE_OAUTH_CLIENT_ID', isset($YOUTUBE_OAUTH_CLIENT_ID) ? $YOUTUBE_OAUTH_CLIENT_ID : '');
define('YOUTUBE_OAUTH_CLIENT_SECRET', isset($YOUTUBE_OAUTH_CLIENT_SECRET) ? $YOUTUBE_OAUTH_CLIENT_SECRET : '');
define('YOUTUBE_OAUTH_REDIRECT_URI', 'http://localhost/streamshare/php/oauth_callback.php');

// YouTube OAuth authorization endpoints
define('YOUTUBE_OAUTH_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('YOUTUBE_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('YOUTUBE_OAUTH_REVOKE_URL', 'https://oauth2.googleapis.com/revoke');

// Required scopes for YouTube search
define('YOUTUBE_OAUTH_SCOPE', 'https://www.googleapis.com/auth/youtube.readonly');

// Check for required OAuth credentials
if (empty(YOUTUBE_OAUTH_CLIENT_ID) || empty(YOUTUBE_OAUTH_CLIENT_SECRET)) {
    die('YouTube OAuth credentials are required. Please set them in config/api_keys.php');
}

// Function to get the authorization URL
function getYouTubeAuthUrl() {
    $params = [
        'client_id' => YOUTUBE_OAUTH_CLIENT_ID,
        'redirect_uri' => YOUTUBE_OAUTH_REDIRECT_URI,
        'scope' => YOUTUBE_OAUTH_SCOPE,
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];

    return YOUTUBE_OAUTH_AUTH_URL . '?' . http_build_query($params);
}

// Function to exchange authorization code for access token
function getYouTubeAccessToken($code) {
    $ch = curl_init(YOUTUBE_OAUTH_TOKEN_URL);
    
    $params = [
        'code' => $code,
        'client_id' => YOUTUBE_OAUTH_CLIENT_ID,
        'client_secret' => YOUTUBE_OAUTH_CLIENT_SECRET,
        'redirect_uri' => YOUTUBE_OAUTH_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Function to refresh an access token
function refreshYouTubeAccessToken($refreshToken) {
    $ch = curl_init(YOUTUBE_OAUTH_TOKEN_URL);
    
    $params = [
        'client_id' => YOUTUBE_OAUTH_CLIENT_ID,
        'client_secret' => YOUTUBE_OAUTH_CLIENT_SECRET,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Function to revoke access token
function revokeYouTubeAccessToken($token) {
    $ch = curl_init(YOUTUBE_OAUTH_REVOKE_URL);
    
    $params = [
        'token' => $token
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?> 