<?php
// YouTube API Configuration
define('YOUTUBE_API_KEY', 'AIzaSyDvIZsjAP5n393XMg_L9HlZ2vKyuk3gF7Y'); // Your YouTube Data API key goes here

// YouTube API endpoints
define('YOUTUBE_API_BASE_URL', 'https://www.googleapis.com/youtube/v3');

// Function to make YouTube API requests
function youtubeApiRequest($endpoint, $params = []) {
    $params['key'] = YOUTUBE_API_KEY;
    $url = YOUTUBE_API_BASE_URL . '/' . $endpoint . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
?> 