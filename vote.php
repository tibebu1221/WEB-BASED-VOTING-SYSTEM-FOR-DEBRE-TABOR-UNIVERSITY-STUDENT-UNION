<?php
include("connection.php");
session_start();

// Set timezone to EAT
date_default_timezone_set('Africa/Nairobi');

// Check if the user is already logged in
if (isset($_SESSION['u_id'])) {
    header('Location: voter.php');
    exit();
}

// Variable to store login messages
$login_message = '';
$message_type = ''; // 'success' or 'error'

// Handle login form submission
if (isset($_POST['log'])) {
    $username = trim($_POST['UserName']);
    $password = trim($_POST['pass']);

    // Basic validation
    if (empty($username) || empty($password)) {
        $login_message = 'Please enter both username and password.';
        $message_type = 'error';
    } else {
        // Prepare SQL to fetch user based on username
        $sql = "SELECT vid, password FROM voter WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $stored_password = $row['password'];
                $vid = $row['vid'];

                // Verify password
                if (password_verify($password, $stored_password)) {
                    $_SESSION['u_id'] = $vid;
                    $_SESSION['user_type'] = 'voter';
                    $_SESSION['login_time'] = time();
                    
                    // Success: Redirect to voter panel
                    header('Location: voter.php');
                    exit();
                } else {
                    // Incorrect password
                    $login_message = 'Incorrect password. Please try again.';
                    $message_type = 'error';
                    error_log("Login failed for username: $username - Incorrect password");
                }
            } else {
                // Username not found
                $login_message = 'Invalid username or password.';
                $message_type = 'error';
                error_log("Login failed for username: $username - No matching user");
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_message = 'Database error. Please try again later.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #1a2a6c;
            --secondary-blue: #2F4F4F;
            --accent-green: #51a351;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e6ef;
            --dark-gray: #4a5568;
            --text-dark: #2d3748;
            --text-light: #718096;
            --white: #ffffff;
            --danger: #dc3545;
            --success: #28a745;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            padding: 1.2rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .logo {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .university-logo {
            height: 110px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        /* Navigation */
        .navbar {
            background: var(--secondary-blue);
            padding: 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            color: var(--white);
            text-decoration: none;
            padding: 16px 22px;
            display: block;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .nav-menu .active a {
            background: var(--accent-green);
            color: var(--white);
            border-radius: 6px;
            margin: 4px 2px;
        }

        /* Main Content Layout */
        .main-content {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            gap: 40px;
            flex: 1;
        }

        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
            }
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            flex-shrink: 0;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
            }
        }

        .sidebar-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .sidebar-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid var(--accent-green);
        }

        .info-card {
            padding: 25px;
            background: var(--white);
        }

        .info-card h3 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }

        .info-card h3 i {
            color: var(--accent-green);
        }

        .date-display {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid rgba(26, 42, 108, 0.1);
        }

        .date-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 5px;
            display: block;
        }

        .date-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
            display: block;
        }

        /* Login Form */
        .login-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            color: var(--primary-blue);
            font-size: 2.2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-blue), #4f6bc9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-top: 5px solid var(--accent-green);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-green), var(--primary-blue));
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: var(--primary-blue);
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success);
            border: 1px solid #c3e6cb;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(81, 163, 81, 0.1);
        }

        .form-control.error {
            border-color: var(--danger);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
        }

        .password-toggle:hover {
            color: var(--primary-blue);
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0d1e5a, #1a2a6c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.2);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--text-dark);
            border: 1px solid var(--medium-gray);
        }

        .btn-secondary:hover {
            background: var(--medium-gray);
            transform: translateY(-2px);
        }

        .forgot-password {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }

        .forgot-password a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
        }

        .forgot-password a:hover {
            color: var(--accent-green);
            text-decoration: underline;
        }

        /* Voting Info Section */
        .voting-info {
            background: linear-gradient(135deg, #f8faff 0%, #f0f5ff 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 40px;
            border-left: 5px solid var(--accent-green);
        }

        .voting-info h3 {
            color: var(--primary-blue);
            font-size: 1.4rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .voting-info ul {
            list-style: none;
            padding: 0;
        }

        .voting-info li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
            color: var(--text-dark);
        }

        .voting-info li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-weight: bold;
        }

        /* Footer */
        .footer {
            background: var(--secondary-blue);
            color: var(--white);
            padding: 25px 0;
            margin-top: 50px;
        }

        .footer-content {
            text-align: center;
        }

        .footer p {
            margin: 0;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                justify-content: center;
                text-align: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .login-card {
                padding: 25px 20px;
            }
            
            .page-title h1 {
                font-size: 1.8rem;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .nav-menu {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-menu li {
                width: 100%;
            }
            
            .nav-menu a {
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-title h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="img/logo.JPG" alt="Debre Tabor University Logo" class="logo">
<h1>Debre Tabor University
Student Union   Voting System</h1>                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="advert.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="candidate.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
                <li class="active"><a href="vote.php"><i class="fas fa-vote-yea"></i> Cast Your Vote</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> General Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <img src="deve/dt.PNG" alt="Debre Tabor University Campus" class="sidebar-image">
            </div>

            <div class="info-card sidebar-card">
                <h3><i class="fas fa-calendar-alt"></i> Election Date</h3>
                <?php
                $sql_date = "SELECT date FROM election_date WHERE YEAR(date) = 2025 LIMIT 1";
                $result = mysqli_query($conn, $sql_date);
                
                if ($result && mysqli_num_rows($result) > 0):
                    $row = mysqli_fetch_assoc($result);
                ?>
                <div class="date-display">
                    <span class="date-label">Election Date for 2025</span>
                    <span class="date-value"><?php echo htmlspecialchars(date('F j, Y', strtotime($row['date']))); ?></span>
                </div>
                <?php else: ?>
                <div class="date-display">
                    <span class="date-value">To be announced</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="voting-info sidebar-card">
                <h3><i class="fas fa-info-circle"></i> Voting Information</h3>
                <ul>
                    <li>Login with your registered credentials</li>
                    <li>Review candidates before voting</li>
                    <li>Each vote is confidential and secure</li>
                    <li>You can only vote once</li>
                    <li>Results will be announced after voting closes</li>
                </ul>
            </div>
        </aside>

        <!-- Login Form -->
        <div class="login-container">
            <div class="page-title">
                <h1><i class="fas fa-vote-yea"></i> Cast Your Vote</h1>
                <p>Login with your voter credentials to participate in the student union election and make your voice heard.</p>
            </div>

            <div class="login-card">
                <div class="login-header">
                    <h2><i class="fas fa-user-check"></i> Voter Login</h2>
                </div>

                <?php if ($login_message && $message_type === 'error'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
                <?php endif; ?>

                <form action="vote.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="UserName"><i class="fas fa-user"></i> Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="UserName" 
                                   name="UserName" 
                                   class="form-control" 
                                   placeholder="Enter your username" 
                                   required 
                                   autofocus
                                   onkeypress="return validateAlpha(event)">
                        </div>
                        <div class="error-message" id="usernameError"></div>
                    </div>

                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   id="password" 
                                   name="pass" 
                                   class="form-control" 
                                   placeholder="Enter your password" 
                                   required
                                   pattern="(?=.*\d).{6,}"
                                   title="Password must be at least 6 characters and include a number">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-requirements">
                            <i class="fas fa-info-circle"></i>
                            Password must be at least 6 characters including a number
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="log" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Vote
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Clear Form
                        </button>
                    </div>

                    <div class="forgot-password">
                        <a href="forgetp.php">
                            <i class="fas fa-key"></i> Forgot your password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date("Y"); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-shield-alt"></i> Secure Voting Platform - Your Vote is Confidential
            </p>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'fas fa-eye';
                this.setAttribute('aria-label', 'Show password');
            }
        });

        // Username validation (alphabets only)
        function validateAlpha(event) {
            const charCode = event.which || event.keyCode;
            const errorElement = document.getElementById('usernameError');
            
            // Allow alphabets (A-Z, a-z), numbers (0-9), and Backspace (8)
            if ((charCode >= 48 && charCode <= 57) || // Numbers
                (charCode >= 65 && charCode <= 90) || // Uppercase
                (charCode >= 97 && charCode <= 122) || // Lowercase
                charCode === 8 || charCode === 46 || // Backspace and Delete
                charCode === 37 || charCode === 39 || // Left/Right arrows
                charCode === 9) { // Tab
                errorElement.textContent = '';
                return true;
            } else {
                errorElement.textContent = 'Only letters and numbers are allowed!';
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> Only letters and numbers are allowed!`;
                return false;
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('UserName').value.trim();
            const password = document.getElementById('password').value.trim();
            const usernameError = document.getElementById('usernameError');
            
            // Reset errors
            usernameError.textContent = '';
            
            // Validate username
            if (!username) {
                e.preventDefault();
                usernameError.innerHTML = `<i class="fas fa-exclamation-circle"></i> Please enter your username.`;
                return;
            }
            
            // Check for special characters in username
            const usernameRegex = /^[a-zA-Z0-9]+$/;
            if (!usernameRegex.test(username)) {
                e.preventDefault();
                usernameError.innerHTML = `<i class="fas fa-exclamation-circle"></i> Username can only contain letters and numbers.`;
                return;
            }
            
            // Validate password
            if (!password) {
                e.preventDefault();
                showError('Please enter your password.');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters long.');
                return;
            }
            
            if (!/\d/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one number.');
                return;
            }
        });

        function showError(message) {
            // Create or update error alert
            let errorAlert = document.querySelector('.alert-error');
            if (!errorAlert) {
                errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-error';
                const loginCard = document.querySelector('.login-card');
                const form = document.querySelector('#loginForm');
                loginCard.insertBefore(errorAlert, form);
            }
            
            errorAlert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        }
    </script>

    <?php
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
    ?>
</body>
</html>