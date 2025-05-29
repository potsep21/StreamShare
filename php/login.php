<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Initialize variables
$username = '';
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    // Sanitize and validate user input
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // If no errors, attempt login
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Get user from database
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && verifyPassword($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to intended URL or dashboard
                redirect(getIntendedUrl());
            } else {
                $errors[] = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $errors[] = "Login failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StreamShare</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* CSS Reset for Form Elements */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--light-form-container-bg); /* Use theme variable */
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            color: var(--light-text); /* Adjust text color for light theme */
        }

        body.dark-theme .form-container {
            background-color: var(--dark-form-container-bg); /* Use dark theme variable */
            color: var(--dark-text);
        }

        .form-group {
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .form-group label {
            display: block;
            width: 100%;
            margin-bottom: 0.5rem;
            color: #000000; /* Set to black for light theme */
            font-size: 16px;
            font-weight: normal;
        }

        body.dark-theme .form-group label {
            color: #f0f0f0; /* Light color for dark theme */
        }

        /* Very specific rules to ensure identical appearance for all input types */
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            display: block;
            /* width: 100% !important; */ /* Let specific rules below handle width */
            height: 45px !important;
            padding: 12px 15px !important;
            border: 1px solid var(--light-border) !important; /* Use theme variable */
            border-radius: 8px !important;
            background-color: var(--light-bg) !important; /* Use theme variable */
            color: var(--light-text) !important; /* Use theme variable */
            font-size: 16px !important;
            font-family: inherit !important;
            margin: 0 !important;
            box-shadow: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }

        body.dark-theme .form-group input[type="text"],
        body.dark-theme .form-group input[type="email"],
        body.dark-theme .form-group input[type="password"] {
            border-color: var(--dark-border) !important;
            background-color: #3d3d3d !important; /* Keep dark theme specific color */
            color: var(--dark-text) !important;
        }

        /* Additional specific targeting for login form */
        #login-form .form-group input[type="text"],
        #login-form .form-group input[type="password"] {
            width: 320px !important;
            max-width: 320px !important;
            min-width: 320px !important;
            box-sizing: border-box !important;
        }

        .form-group input:focus {
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5) !important;
        }

        .error-message {
            color: #ff6b6b;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            border: 1px solid #ff6b6b;
            border-radius: 8px;
            background-color: rgba(255, 107, 107, 0.1);
        }

        .submit-button {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: #0066cc;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .submit-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        a {
            color: #0066cc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" aria-label="Toggle theme">
        🌓
    </button>

    <header>
        <h1>Login to StreamShare</h1>
        <p>Access your account</p>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../about.php">About</a></li>
            <li><a href="../help.php">Help</a></li>
            <li><a href="search.php">Search</a></li>
            <li><a href="register.php">Register</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>

    <main class="container">
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="submit-button">Login</button>
            </form>

            <p style="margin-top: 1rem; text-align: center;">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html> 