<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Function to convert data to YAML format
function arrayToYaml($array, $indent = 0) {
    $yaml = '';
    $indentStr = str_repeat(' ', $indent);
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $yaml .= $indentStr . "$key:\n" . arrayToYaml($value, $indent + 2);
        } else {
            $yaml .= $indentStr . "$key: " . (is_string($value) ? "\"$value\"" : $value) . "\n";
        }
    }
    
    return $yaml;
}

// Create anonymous user ID by hashing user info
function anonymizeUser($userId, $username) {
    return hash('sha256', $userId . $username . 'streamshare-salt');
}

try {
    $conn = getDBConnection();
    
    // Get all public lists with their items
    $stmt = $conn->prepare("
        SELECT 
            cl.id as list_id, 
            cl.title as list_title, 
            cl.description as list_description,
            cl.created_at as list_created,
            u.id as user_id,
            u.username
        FROM content_lists cl
        JOIN users u ON cl.user_id = u.id
        WHERE cl.is_private = 0
        ORDER BY cl.created_at DESC
    ");
    
    $stmt->execute();
    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare the data structure for YAML
    $exportData = [
        'streamshare_data' => [
            'export_date' => date('Y-m-d H:i:s'),
            'lists_count' => count($lists),
            'lists' => []
        ]
    ];
    
    // For each list, get its items
    foreach ($lists as $list) {
        $listId = $list['list_id'];
        
        // Get items for this list
        $itemsStmt = $conn->prepare("
            SELECT 
                title, 
                youtube_id,
                position,
                created_at
            FROM list_items 
            WHERE list_id = ? 
            ORDER BY position
        ");
        
        $itemsStmt->execute([$listId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Anonymize user data
        $anonymousUserId = anonymizeUser($list['user_id'], $list['username']);
        
        // Add to export data
        $exportData['streamshare_data']['lists'][] = [
            'id' => $listId,
            'title' => $list['list_title'],
            'description' => $list['list_description'],
            'created_at' => $list['list_created'],
            'anonymous_user_id' => $anonymousUserId,
            'items_count' => count($items),
            'items' => $items
        ];
    }
    
    // Convert to YAML
    $yamlData = "---\n" . arrayToYaml($exportData);
    
    // Set headers for YAML download
    header('Content-Type: application/x-yaml');
    header('Content-Disposition: attachment; filename="streamshare_data.yaml"');
    
    // Output the YAML
    echo $yamlData;
    exit;
    
} catch(PDOException $e) {
    die("Error exporting data: " . $e->getMessage());
}
?> 