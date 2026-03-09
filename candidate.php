<?php
include("connection.php");
session_start();

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set security headers
header('Content-Type: text/html; charset=UTF-8');

// Initialize variables
$error_message = '';
$success_message = '';

// Handle login form submission
if (isset($_POST['log'])) {
    $username = trim($_POST['user']);
    $password = trim($_POST['pass']);

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Using prepared statements for security
        $sql = "SELECT c_id, password FROM candidate WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                
                // Using password_verify for securely checking hashed passwords
                if (password_verify($password, $row['password'])) {
                    $_SESSION['u_id'] = $row['c_id'];
                    $_SESSION['user_type'] = 'candidate';
                    header('Location: candidate_view.php');
                    exit();
                } else {
                    $error_message = 'Invalid username or password!';
                }
            } else {
                $error_message = 'Invalid username or password!';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Login - Online Voting System</title>
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

        /* Animated Banner */
        .banner-container {
            background: var(--secondary-blue);
            padding: 15px 0;
            overflow: hidden;
            position: relative;
        }

        .banner-content {
            display: flex;
            animation: scroll 30s linear infinite;
            white-space: nowrap;
            padding: 0 20px;
        }

        .banner-text {
            color: var(--white);
            font-size: 1.1rem;
            font-weight: 500;
            padding: 0 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .banner-text i {
            color: var(--accent-green);
        }

        @keyframes scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .banner-container:hover .banner-content {
            animation-play-state: paused;
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 500px;
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

        .login-header h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .login-header p {
            color: var(--text-light);
            font-size: 1rem;
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

        /* Candidate Info Section */
        .candidate-info {
            background: linear-gradient(135deg, #f8faff 0%, #f0f5ff 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 40px;
            border-left: 5px solid var(--accent-green);
        }

        .candidate-info h3 {
            color: var(--primary-blue);
            font-size: 1.4rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .candidate-info ul {
            list-style: none;
            padding: 0;
        }

        .candidate-info li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
            color: var(--text-dark);
        }

        .candidate-info li:before {
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
            
            .login-header h1 {
                font-size: 1.8rem;
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
            
            .banner-text {
                font-size: 0.9rem;
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

    <!-- Animated Banner -->
    <div class="banner-container">
        <div class="banner-content">
            <div class="banner-text">
                <i class="fas fa-vote-yea"></i>
                Welcome to Debre Tabor University Student Union Voting System! 
                Cast your vote today and shape the future of our campus.
            </div>
        </div>
    </div>

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
                <li class="active"><a href="candidate.php"><i class="fas fa-user-tie"></i> Candidate Login</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
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
                $result = mysqli_query($conn, "SELECT * FROM election_date LIMIT 1");
                if ($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="date-display">
                    <span class="date-label">Upcoming Election</span>
                    <span class="date-value"><?php echo htmlspecialchars($row['date']); ?></span>
                </div>
                <?php else: ?>
                <div class="date-display">
                    <span class="date-value">To be announced</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="candidate-info sidebar-card">
                <h3><i class="fas fa-info-circle"></i> Candidate Access</h3>
                <ul>
                    <li>Login to view your campaign dashboard</li>
                    <li>Track election progress and results</li>
                    <li>Update your candidate profile</li>
                    <li>View voter statistics and trends</li>
                </ul>
            </div>
        </aside>

        <!-- Login Form -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1><i class="fas fa-user-tie"></i> Candidate Login</h1>
                    <p>Access your candidate dashboard to manage your campaign</p>
                </div>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <form action="candidate.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" 
                                   id="username" 
                                   name="user" 
                                   class="form-control" 
                                   placeholder="Enter your username" 
                                   required 
                                   autofocus>
                        </div>
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
                                   required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-requirements">
                            <i class="fas fa-info-circle"></i>
                            Password must contain at least 6 characters including a number
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="log" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>

                    <div class="forgot-password">
                        <a href="forgetc.php">
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
            <p><i class="far fa-copyright"></i> <?php echo date('Y'); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-shield-alt"></i> Secure Login Portal for Registered Candidates
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

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username) {
                e.preventDefault();
                showError('Please enter your username.');
                return;
            }
            
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