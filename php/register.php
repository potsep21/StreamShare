<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    // Sanitize and validate input
    $firstname = sanitize($_POST['firstname']);
    $lastname = sanitize($_POST['lastname']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate firstname
    if (empty($firstname)) {
        $errors[] = "First name is required";
    }

    // Validate lastname
    if (empty($lastname)) {
        $errors[] = "Last name is required";
    }

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $conn = getDBConnection();

            // Check if username already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username already exists";
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Email already exists";
            }

            if (empty($errors)) {
                // Hash password
                $hashed_password = hashPassword($password);

                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$firstname, $lastname, $username, $email, $hashed_password]);

                $success = true;
            }
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StreamShare</title>
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

        /* Additional specific targeting for register form */
        #register-form .form-group input[type="text"],
        #register-form .form-group input[type="email"],
        #register-form .form-group input[type="password"] {
            width: 320px !important;
            max-width: 320px !important;
            min-width: 320px !important;
            box-sizing: border-box !important;
        }

        /* Target specific inputs by ID */
        #firstname, #lastname, #username {
            width: 320px !important;
            max-width: 320px !important;
            min-width: 320px !important;
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

        .success-message {
            color: #4cd964;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            border: 1px solid #4cd964;
            border-radius: 8px;
            background-color: rgba(76, 217, 100, 0.1);
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
        <h1>Register for StreamShare</h1>
        <p>Create your account to start sharing content</p>
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
            <?php if ($success): ?>
                <div class="success-message">
                    Registration successful! You can now <a href="login.php">login</a>.
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="register-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-button">Register</button>
                </form>

                <p style="margin-top: 1rem; text-align: center;">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 StreamShare. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html> 