<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$username = '';
$firstname = '';
$lastname = '';
$email = '';
$bio = '';
$error_message = '';
$success_message = '';

try {
    $conn = getDBConnection();
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $firstname = sanitize($_POST['firstname'] ?? '');
        $lastname = sanitize($_POST['lastname'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Check if current password was provided
        if (!empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                $error_message = "Current password is required to change password";
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user || !password_verify($current_password, $user['password'])) {
                    $error_message = "Current password is incorrect";
                } else if (empty($new_password)) {
                    $error_message = "New password cannot be empty";
                } else if ($new_password !== $confirm_password) {
                    $error_message = "New passwords do not match";
                } else if (strlen($new_password) < 8) {
                    $error_message = "New password must be at least 8 characters";
                }
            }
        }
        
        // If no errors, update profile
        if (empty($error_message)) {
            // Prepare SQL statement based on whether password is being updated
            if (!empty($new_password)) {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET firstname = ?, lastname = ?, bio = ?, password = ? 
                    WHERE id = ?
                ");
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->execute([$firstname, $lastname, $bio, $hashed_password, $user_id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET firstname = ?, lastname = ?, bio = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$firstname, $lastname, $bio, $user_id]);
            }
            
            $success_message = "Profile updated successfully";
        }
    }
    
    // Get current user data
    $stmt = $conn->prepare("
        SELECT username, firstname, lastname, email, bio 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $username = $user['username'];
        $firstname = $user['firstname'];
        $lastname = $user['lastname'];
        $email = $user['email'];
        $bio = $user['bio'];
    } else {
        // User not found, redirect to login
        redirect('logout.php');
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Edit profile specific styles */
        .profile-form {
            background-color: rgba(0,0,0,0.3);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .profile-form h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 24px;
            text-align: center;
        }
        
        .form-section {
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 20px;
        }
        
        .form-section h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: none;
            background-color: rgba(255,255,255,0.9);
            font-size: 16px;
            color: #333;
        }
        
        .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.5);
        }
        
        .form-control-textarea {
            height: 120px;
            resize: vertical;
        }
        
        body.dark-theme .form-control {
            background-color: rgba(40,40,40,0.9);
            color: white;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        
        .form-note {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
            font-style: italic;
        }
        
        .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,123,255,0.3);
        }
        
        .btn-submit:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,123,255,0.4);
        }
        
        .btn-cancel {
            background-color: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background-color: rgba(255,255,255,0.25);
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-submit, .btn-cancel {
                width: 100%;
                text-align: center;
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
        <p>Edit Your Profile</p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php" class="active">Profile</a></li>
            <li><a href="search.php">Search</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <main class="container">
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="profile-form">
            <h2>Edit Your Profile</h2>
            
            <div class="form-section">
                <h3>Basic Information</h3>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                    <p class="form-note">Username cannot be changed</p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" class="form-control" value="<?php echo htmlspecialchars($firstname); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" class="form-control" value="<?php echo htmlspecialchars($lastname); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    <p class="form-note">Contact an administrator to change your email</p>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="form-control form-control-textarea" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Change Password</h3>
                <p class="form-note">Leave blank if you don't want to change your password</p>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="profile.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html> 