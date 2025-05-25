<?php
// Include API keys (this file is not tracked in version control)
require_once __DIR__ . '/api_keys.php';
require_once __DIR__ . '/youtube_oauth.php';

// YouTube API endpoints
define('YOUTUBE_API_BASE_URL', 'https://www.googleapis.com/youtube/v3');

// Function to check if access token is valid or needs refresh
function getValidAccessToken() {
    // If no access token in session or token has expired
    if (!isset($_SESSION['youtube_access_token']) || 
        !isset($_SESSION['youtube_token_expires']) || 
        $_SESSION['youtube_token_expires'] < time()) {
        
        // Try to refresh using refresh token if available
        if (isset($_SESSION['youtube_refresh_token'])) {
            $tokenResponse = refreshYouTubeAccessToken($_SESSION['youtube_refresh_token']);
            
            if (isset($tokenResponse['access_token'])) {
                $_SESSION['youtube_access_token'] = $tokenResponse['access_token'];
                $_SESSION['youtube_token_expires'] = time() + $tokenResponse['expires_in'];
                return $_SESSION['youtube_access_token'];
            }
        }
        
        // If no refresh token in session, try to get from database
        try {
            if (isset($_SESSION['user_id'])) {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $conn->prepare("SELECT refresh_token FROM oauth_tokens WHERE user_id = ? AND provider = 'youtube'");
                $stmt->execute([$_SESSION['user_id']]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && isset($result['refresh_token'])) {
                    $tokenResponse = refreshYouTubeAccessToken($result['refresh_token']);
                    
                    if (isset($tokenResponse['access_token'])) {
                        $_SESSION['youtube_access_token'] = $tokenResponse['access_token'];
                        $_SESSION['youtube_token_expires'] = time() + $tokenResponse['expires_in'];
                        $_SESSION['youtube_refresh_token'] = $result['refresh_token'];
                        return $_SESSION['youtube_access_token'];
                    }
                }
            }
        } catch (Exception $e) {
            // Return null if there's an error
            return null;
        }
        
        // If no valid token could be obtained, return null
        return null;
    }
    
    // Return existing valid token
    return $_SESSION['youtube_access_token'];
}

// Function to make YouTube API requests (OAuth only)
function youtubeApiRequest($endpoint, $params = []) {
    // Get a valid access token
    $accessToken = getValidAccessToken();
    
    if (!$accessToken) {
        // No valid OAuth token available
        return [
            'error' => true,
            'message' => 'OAuth authentication required'
        ];
    }
    
    // Use OAuth token for authentication
    $url = YOUTUBE_API_BASE_URL . '/' . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Function to search YouTube videos
function searchYouTubeVideos($query, $maxResults = 10) {
    $params = [
        'part' => 'snippet',
        'q' => $query,
        'type' => 'video',
        'maxResults' => $maxResults
    ];
    
    return youtubeApiRequest('search', $params);
}

// Function to get video details by ID
function getYouTubeVideoDetails($videoId) {
    $params = [
        'part' => 'snippet,contentDetails,statistics',
        'id' => $videoId
    ];
    
    return youtubeApiRequest('videos', $params);
}

// Function to check if user is authenticated with YouTube
function isYouTubeAuthenticated() {
    return getValidAccessToken() !== null;
}
?> 