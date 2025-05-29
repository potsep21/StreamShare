<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize variables
$search_query = '';
$search_results = [];
$error_message = '';
$message = '';
$start_date = '';
$end_date = '';
$user_query = '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 25]) ? (int)$_GET['per_page'] : 10;

// Process search query if submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['query']) || isset($_GET['start_date']) || isset($_GET['end_date']) || isset($_GET['user_query']))) {
    $search_query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
    $user_query = isset($_GET['user_query']) ? sanitize($_GET['user_query']) : '';
    
    // Check if at least one search parameter is provided
    if (empty($search_query) && empty($start_date) && empty($end_date) && empty($user_query)) {
        $error_message = 'Please enter at least one search parameter';
    } else if (!empty($search_query) && strlen($search_query) < 3) {
        $error_message = 'Search term must be at least 3 characters';
    } else {
        try {
            // Connect to database
            $conn = getDBConnection();
            
            // Initialize params array
            $params = [];
            
            // Build the query based on search parameters
            $sql = "
                SELECT cl.id, cl.title, cl.description, cl.created_at, cl.is_private,
                       u.username, u.id as user_id, u.firstname, u.lastname, u.email,
                       (SELECT COUNT(*) FROM list_items WHERE list_id = cl.id) as item_count
                FROM content_lists cl
                JOIN users u ON cl.user_id = u.id
                WHERE (cl.is_private = 0 ";
            
            // If user is logged in, they can see their own private lists
            if (isLoggedIn()) {
                $sql .= "OR (cl.is_private = 1 AND cl.user_id = ?) ";
                $params[] = $_SESSION['user_id'];
            }
            
            $sql .= ") ";
            
            // Add search term condition
            if (!empty($search_query)) {
                $sql .= " AND (cl.title LIKE ? OR cl.description LIKE ? OR EXISTS (
                    SELECT 1 FROM list_items li WHERE li.list_id = cl.id AND li.title LIKE ?
                ))";
                $search_term = "%{$search_query}%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Add date range conditions
            if (!empty($start_date)) {
                $sql .= " AND cl.created_at >= ?";
                $params[] = $start_date . " 00:00:00";
            }
            
            if (!empty($end_date)) {
                $sql .= " AND cl.created_at <= ?";
                $params[] = $end_date . " 23:59:59";
            }
            
            // Add user search condition
            if (!empty($user_query)) {
                $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
                $user_term = "%{$user_query}%";
                $params[] = $user_term;
                $params[] = $user_term;
                $params[] = $user_term;
                $params[] = $user_term;
            }
            
            // Count total results for pagination
            $countSql = str_replace("cl.id, cl.title, cl.description, cl.created_at, cl.is_private,
                       u.username, u.id as user_id, u.firstname, u.lastname, u.email,
                       (SELECT COUNT(*) FROM list_items WHERE list_id = cl.id) as item_count", "COUNT(*) as total", $sql);
            
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $total_results = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Add ordering and pagination
            $sql .= " ORDER BY cl.created_at DESC LIMIT " . (($current_page - 1) * $items_per_page) . ", " . $items_per_page;
            
            // Execute the final query
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_pages = ceil($total_results / $items_per_page);
            
            if (count($search_results) === 0) {
                $message = "No results found for your search criteria";
            }
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Custom styles for this page */
        .search-container {
            background-color: #222222;
            border-radius: 10px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto 30px auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .search-title {
            color: white;
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .search-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .search-input-wrapper {
            position: relative;
            width: 100%;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
            font-size: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: none;
            border-radius: 30px;
            background-color: rgba(40, 40, 40, 0.8);
            color: white;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 3px 12px rgba(0,123,255,0.3);
            background-color: rgba(50, 50, 50, 0.9);
        }
        
        .search-input::placeholder {
            color: #aaaaaa;
        }
        
        .search-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 0;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .search-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        
        .results-container {
            margin-top: 30px;
        }
        
        .results-heading {
            color: white;
            margin-bottom: 20px;
            font-size: 20px;
            text-align: center;
            background-color: rgba(0,0,0,0.3);
            padding: 12px;
            border-radius: 8px;
        }
        
        .results-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .result-item {
            background-color: rgba(255,255,255,0.9);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .result-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        
        .result-title {
            font-size: 22px;
            color: #0056b3;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .result-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .result-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .result-username {
            color: #0056b3;
            font-weight: bold;
            text-decoration: none;
        }
        
        .result-username:hover {
            text-decoration: underline;
        }
        
        .view-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .view-button:hover {
            background-color: #218838;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Advanced search styles */
        .advanced-search {
            margin-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            width: 100%;
        }
        
        .advanced-search-toggle {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 14px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin: 0 auto;
            width: 100%;
            text-align: center;
        }
        
        .advanced-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .form-field label {
            color: white;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .date-input {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background-color: rgba(40, 40, 40, 0.8);
            color: white;
            width: 100%;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        body.dark-theme .date-input {
            background-color: rgba(40, 40, 40, 0.9);
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Select styling */
        select.search-input {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            background-color: rgba(40, 40, 40, 0.8);
            color: white;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
            padding-right: 30px;
            width: 100%;
        }
        
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-link {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 8px;
            background-color: rgba(255,255,255,0.9);
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #007bff;
            color: white;
        }
        
        .page-link.active {
            background-color: #007bff;
            color: white;
        }
        
        body.dark-theme .page-link {
            background-color: rgba(40,40,40,0.9);
            color: #4da3ff;
        }
        
        body.dark-theme .page-link:hover,
        body.dark-theme .page-link.active {
            background-color: #0056b3;
            color: white;
        }
        
        /* Dark theme adjustments */
        body.dark-theme .search-input {
            background-color: rgba(40,40,40,0.9);
            color: white;
        }
        
        body.dark-theme .result-item {
            background-color: rgba(40,40,40,0.9);
            color: #ddd;
        }
        
        body.dark-theme .result-title {
            color: #4da3ff;
        }
        
        body.dark-theme .result-meta {
            color: #aaa;
        }
        
        body.dark-theme .result-username {
            color: #4da3ff;
        }
        
        /* Message styling */
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .error-message {
            background-color: rgba(220,53,69,0.2);
            color: #dc3545;
        }
        
        .info-message {
            background-color: rgba(108,117,125,0.2);
            color: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .search-container {
                padding: 20px;
            }
            
            .result-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .advanced-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>StreamShare</h1>
        <p>Find content lists</p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="export_data.php">Export Data</a></li>
                <li><a href="search.php" class="active">Search</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="../about.php">About</a></li>
                <li><a href="../help.php">Help</a></li>
                <li><a href="search.php" class="active">Search</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <div class="search-container">
            <h2 class="search-title">Search Content Lists</h2>
            
            <form method="GET" action="search.php" class="search-form">
                <div class="search-input-wrapper">
                    <div class="search-icon">🔍</div>
                    <input 
                        type="text" 
                        name="query" 
                        id="query"
                        class="search-input"
                        placeholder="Search for content lists..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                        minlength="3"
                    >
                </div>
                
                <div class="advanced-search">
                    <button type="button" class="advanced-search-toggle" onclick="toggleAdvancedSearch()">
                        <span id="toggle-icon">▼</span> Advanced Search Options
                    </button>
                    
                    <div id="advanced-fields" class="advanced-fields" style="display: none;">
                        <div class="form-field">
                            <label for="start_date">From Date:</label>
                            <input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                class="date-input"
                                value="<?php echo htmlspecialchars($start_date); ?>"
                            >
                        </div>
                        
                        <div class="form-field">
                            <label for="end_date">To Date:</label>
                            <input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                class="date-input"
                                value="<?php echo htmlspecialchars($end_date); ?>"
                            >
                        </div>
                        
                        <div class="form-field" style="grid-column: span 2;">
                            <label for="user_query">User (name, username or email):</label>
                            <input 
                                type="text" 
                                name="user_query" 
                                id="user_query"
                                class="search-input"
                                placeholder="Search by user..." 
                                value="<?php echo htmlspecialchars($user_query); ?>"
                            >
                        </div>
                        
                        <div class="form-field" style="grid-column: span 2;">
                            <label for="per_page">Results per page:</label>
                            <select name="per_page" id="per_page" class="search-input">
                                <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10 items</option>
                                <option value="25" <?php echo $items_per_page == 25 ? 'selected' : ''; ?>>25 items</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="search-button">SEARCH</button>
            </form>

            <?php if ($error_message): ?>
                <div class="message-box error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="message-box info-message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($search_results) > 0): ?>
            <div class="results-container">
                <h3 class="results-heading">
                    Found <?php echo $total_results; ?> results 
                    <?php if (!empty($search_query)): ?>
                        for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php endif; ?>
                    <?php if (!empty($start_date) || !empty($end_date)): ?>
                        in the selected date range
                    <?php endif; ?>
                    <?php if (!empty($user_query)): ?>
                        by user "<?php echo htmlspecialchars($user_query); ?>"
                    <?php endif; ?>
                </h3>
                
                <div style="text-align: center; margin-bottom: 20px; color: rgba(255,255,255,0.8);">
                    Showing results <?php echo (($current_page - 1) * $items_per_page) + 1; ?> - 
                    <?php echo min($current_page * $items_per_page, $total_results); ?> 
                    of <?php echo $total_results; ?> 
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                </div>
                
                <div class="results-list">
                    <?php foreach ($search_results as $list): ?>
                        <div class="result-item">
                            <div class="result-title">
                                <?php echo htmlspecialchars($list['title']); ?>
                                <?php if ($list['is_private'] == 1): ?>
                                    <span style="font-size: 0.7em; background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">Private</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="result-meta">
                                <span>
                                    <span>👤</span> By: 
                                    <a href="profile.php?id=<?php echo $list['user_id']; ?>" class="result-username">
                                        <?php echo htmlspecialchars($list['username']); ?>
                                    </a>
                                </span>
                                
                                <span>
                                    <span>🎬</span> <?php echo $list['item_count']; ?> videos
                                </span>
                                
                                <span>
                                    <span>📅</span> Created: <?php echo formatDate($list['created_at']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($list['description'])): ?>
                                <p><?php echo htmlspecialchars(substr($list['description'], 0, 120)) . (strlen($list['description']) > 120 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            
                            <?php 
                            // Show View List button only for public lists or user's own private lists
                            if ($list['is_private'] == 0 || (isLoggedIn() && $list['user_id'] == $_SESSION['user_id'])): 
                            ?>
                                <a href="view_list.php?id=<?php echo $list['id']; ?>" class="view-button">View List</a>
                            <?php else: ?>
                                <span class="view-button" style="background-color: #6c757d; cursor: not-allowed;">Private List</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&user_query=<?php echo urlencode($user_query); ?>&per_page=<?php echo $items_per_page; ?>&page=<?php echo $current_page - 1; ?>" class="page-link">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&user_query=<?php echo urlencode($user_query); ?>&per_page=<?php echo $items_per_page; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?query=<?php echo urlencode($search_query); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&user_query=<?php echo urlencode($user_query); ?>&per_page=<?php echo $items_per_page; ?>&page=<?php echo $current_page + 1; ?>" class="page-link">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script>
        function toggleAdvancedSearch() {
            const advancedFields = document.getElementById('advanced-fields');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (advancedFields.style.display === 'none') {
                advancedFields.style.display = 'grid';
                toggleIcon.textContent = '▲';
            } else {
                advancedFields.style.display = 'none';
                toggleIcon.textContent = '▼';
            }
        }
        
        // Show advanced search if any of those fields are filled
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = "<?php echo $start_date; ?>";
            const endDate = "<?php echo $end_date; ?>";
            const userQuery = "<?php echo $user_query; ?>";
            
            if (startDate || endDate || userQuery) {
                document.getElementById('advanced-fields').style.display = 'grid';
                document.getElementById('toggle-icon').textContent = '▲';
            }
        });
    </script>
    <script src="../js/main.js"></script>
</body>
</html> 