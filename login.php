<?php
session_start();
include("connection.php");

// --- 1. INITIALIZE VARIABLES ---
$login_message = '';
$redirect_script = '';
$username = '';

// --- Redirect Already Logged In Users ---
if (isset($_SESSION['u_id']) && isset($_SESSION['role'])) {
    $role_pages = [
        'admin' => 'system_admin.php',
        'officer' => 'e_officer.php',
        'department' => 'dep.php',
        'discipline_committee' => 'discipline_committee.php',
        'candidate' => 'candidate_view.php'
    ];
    
    if (isset($role_pages[$_SESSION['role']])) {
        header("Location: " . $role_pages[$_SESSION['role']]);
        exit();
    } else {
        session_unset();
        session_destroy();
        header("Location: login.php?error=invalid_role");
        exit();
    }
}

// --- Login Attempt Handling ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['log'])) {
    $username = filter_var($_POST['user'], FILTER_SANITIZE_STRING);
    $password = $_POST['pass'];
    
    $max_attempts = 3;
    $lockout_time = 300;

    if (empty($username) || empty($password)) {
        $login_message = '<div class="alert alert-error">Please fill in both username and password.</div>';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $login_message = '<div class="alert alert-error">Username must be between 3 and 50 characters.</div>';
    } elseif ($conn) {
        $stmt = $conn->prepare("SELECT u_id, username, password, role, status, login_attempts, last_attempt FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['u_id'];
            $attempts = $row['login_attempts'];
            $last_attempt_time = strtotime($row['last_attempt']);
            $current_time = time();

            // Check lockout
            $is_locked_out = ($attempts >= $max_attempts) && (($current_time - $last_attempt_time) < $lockout_time);
            
            if ($is_locked_out) {
                $time_remaining = $lockout_time - ($current_time - $last_attempt_time);
                $minutes = floor($time_remaining / 60);
                $seconds = $time_remaining % 60;
                $login_message = '<div class="alert alert-error">Account locked. Try again in ' . $minutes . 'm ' . $seconds . 's.</div>';
            } elseif (password_verify($password, $row['password'])) {
                // Successful login
                $reset_stmt = $conn->prepare("UPDATE user SET login_attempts = 0, last_attempt = NULL WHERE u_id = ?");
                $reset_stmt->bind_param("i", $user_id);
                $reset_stmt->execute();
                $reset_stmt->close();
                
                if ($row['status'] == 1) {
                    $_SESSION['u_id'] = $user_id;
                    $_SESSION['role'] = $row['role'];
                    
                    $role_pages = [
                        'admin' => ['page' => 'system_admin.php', 'name' => 'Admin'],
                        'officer' => ['page' => 'e_officer.php', 'name' => 'Officer'],
                        'department' => ['page' => 'dep.php', 'name' => 'Department'],
                        'discipline_committee' => ['page' => 'discipline_committee.php', 'name' => 'Discipline Committee'],
                        'candidate' => ['page' => 'candidate_view.php', 'name' => 'Candidate']
                    ];
                    
                    if (isset($role_pages[$row['role']])) {
                        $role = $role_pages[$row['role']];
                        $login_message = '<div class="alert alert-success">Login successful! Redirecting to ' . $role['name'] . ' Panel...</div>';
                        $redirect_script = '<script>setTimeout(function(){ window.location="' . $role['page'] . '"; }, 1500);</script>';
                    } else {
                        session_unset();
                        session_destroy();
                        $login_message = '<div class="alert alert-error">Invalid role configuration.</div>';
                    }
                } else {
                    $login_message = '<div class="alert alert-error">Your account is disabled.</div>';
                }
            } else {
                // Failed login
                $new_attempts = $attempts + 1;
                $update_stmt = $conn->prepare("UPDATE user SET login_attempts = ?, last_attempt = NOW() WHERE u_id = ?");
                $update_stmt->bind_param("ii", $new_attempts, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                if ($new_attempts >= $max_attempts) {
                    $login_message = '<div class="alert alert-error">Too many failed attempts. Account locked for 5 minutes.</div>';
                } else {
                    $attempts_left = $max_attempts - $new_attempts;
                    $login_message = '<div class="alert alert-error">Incorrect password. ' . $attempts_left . ' attempts left.</div>';
                }
            }
        } else {
            $login_message = '<div class="alert alert-error">Invalid credentials.</div>';
        }
        $stmt->close();
    } else {
        $login_message = '<div class="alert alert-error">Database connection error.</div>';
    }
} elseif (isset($_GET['error']) && $_GET['error'] == 'invalid_role') {
    $login_message = '<div class="alert alert-error">Invalid session role. Please log in again.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | DTUSU Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #006400;
            --secondary: #FFD700;
            --accent: #FF0000;
            --dark: #1a2a6c;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
            --radius: 16px;
            --radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #006400 0%, #1a2a6c 50%, #004d40 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 100, 0, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }

        .glass-container {
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position: relative;
        }

        .glass-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s ease-in-out infinite;
        }

        @keyframes shine {
            0%, 100% { transform: translateX(-100%) rotate(45deg); }
            50% { transform: translateX(100%) rotate(45deg); }
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            padding: 10px;
            box-shadow: var(--shadow);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .title-container h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
            background: linear-gradient(45deg, #fff, var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .title-container .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 1px;
        }

        .nav-container {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            padding: 0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            justify-content: center;
            gap: 2px;
            padding: 0;
        }

        .nav-menu li {
            flex: 1;
            text-align: center;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 18px 20px;
            display: block;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-menu a:hover::before {
            left: 100%;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--secondary);
        }

        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 3px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            min-height: 600px;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--light) 0%, #ffffff 100%);
            padding: 40px 30px;
            border-right: 1px solid var(--gray-light);
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .info-card {
            background: white;
            border-radius: var(--radius-sm);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            font-size: 1.25rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--gray-light);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-item p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-badge {
            display: inline-block;
            background: linear-gradient(45deg, var(--primary), var(--dark));
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 5px;
            box-shadow: 0 4px 6px rgba(0, 100, 0, 0.2);
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 4px 6px rgba(0, 100, 0, 0.2); }
            50% { box-shadow: 0 4px 15px rgba(0, 100, 0, 0.4); }
        }

        .quick-links .info-item a {
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            transition: all 0.3s ease;
        }

        .quick-links .info-item a:hover {
            background: var(--gray-light);
            color: var(--primary);
            transform: translateX(5px);
        }

        .login-section {
            padding: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(0, 100, 0, 0.3);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-icon i {
            font-size: 32px;
            color: white;
        }

        .login-header h2 {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, var(--primary), var(--dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--gray);
            font-size: 1rem;
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: linear-gradient(135deg, #fee, #fff5f5);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fff4, #f0fff8);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 1.1rem;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 100, 0, 0.1);
        }

        .form-control.error {
            border-color: var(--error);
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 16px 32px;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            color: white;
            box-shadow: 0 4px 15px rgba(0, 100, 0, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 100, 0, 0.4);
        }

        .btn-reset {
            background: white;
            color: var(--dark);
            border: 2px solid var(--gray-light);
        }

        .btn-reset:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .forgot-link {
            text-align: center;
            margin-top: 25px;
        }

        .forgot-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .forgot-link a:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links a {
            color: var(--secondary);
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--gray-light);
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-menu li {
                flex: 1 0 auto;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .footer-links {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .footer-links a {
                margin: 0;
            }
            
            .login-card {
                padding: 30px 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .glass-container {
                border-radius: 0;
            }
            
            header, .login-section, .sidebar {
                padding: 20px;
            }
            
            .login-icon {
                width: 60px;
                height: 60px;
            }
            
            .login-icon i {
                font-size: 24px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Animation for form elements */
        .form-group {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Security indicator */
        .security-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding: 10px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            color: var(--gray);
        }

        .security-indicator i {
            color: var(--success);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle
            const togglePassword = document.getElementById('password-toggle');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                    }
                });
            }

            // Real-time validation
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameError = document.getElementById('username-error');
            const passwordError = document.getElementById('password-error');

            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    validateField(this, usernameError, 'Username must be between 3 and 50 characters');
                });
            }

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    validateField(this, passwordError, 'Password is required');
                });
            }

            // Add focus animations
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });

        function validateForm() {
            const username = document.getElementById('username')?.value.trim();
            const password = document.getElementById('password')?.value.trim();
            const usernameError = document.getElementById('username-error');
            const passwordError = document.getElementById('password-error');
            let isValid = true;

            // Reset errors
            [usernameError, passwordError].forEach(error => {
                if (error) error.style.display = 'none';
            });

            document.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('error');
            });

            // Validate username
            if (!username) {
                showError('username', 'Username is required');
                isValid = false;
            } else if (username.length < 3 || username.length > 50) {
                showError('username', 'Username must be between 3 and 50 characters');
                isValid = false;
            }

            // Validate password
            if (!password) {
                showError('password', 'Password is required');
                isValid = false;
            }

            return isValid;
        }

        function validateField(field, errorElement, message) {
            if (field.value.trim() === '') {
                showError(field.id, message);
            } else {
                clearError(field.id);
            }
        }

        function showError(fieldId, message) {
            const errorElement = document.getElementById(fieldId + '-error');
            const inputElement = document.getElementById(fieldId);
            
            if (errorElement && inputElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
                
                // Add shake animation
                inputElement.style.animation = 'none';
                setTimeout(() => {
                    inputElement.style.animation = 'shake 0.5s ease';
                }, 10);
            }
        }

        function clearError(fieldId) {
            const errorElement = document.getElementById(fieldId + '-error');
            const inputElement = document.getElementById(fieldId);
            
            if (errorElement && inputElement) {
                errorElement.style.display = 'none';
                inputElement.classList.remove('error');
            }
        }

        // Prevent form submission on Enter in input fields except for the form
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT' && !e.target.form) {
                e.preventDefault();
            }
        });
    </script>
</head>
<body>
    <div class="glass-container">
        <header>
            <div class="header-content">
                <div class="logo-container">
                    <div class="logo">
                        <img src="img/logo.JPG" alt="DTUSU Logo">
                    </div>
                    <div class="title-container">
                        <h1>DTUSU Voting System</h1>
                        <p class="subtitle">Secure & Transparent Online Elections</p>
                    </div>
                </div>
            </div>
        </header>
        
        <nav class="nav-container">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote</a></li>
                <li><a class="active" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </nav>
        
        <div class="main-grid">
            <aside class="sidebar">
                <div class="info-card">
                    <h3><i class="fas fa-calendar-alt"></i> Election Schedule</h3>
                    <?php
                    $electionDate = "No date set";
                    $voterReg = "Not scheduled";
                    $candidateReg = "Not scheduled";
                    
                    if ($conn) {
                        $stmt = $conn->prepare("SELECT date FROM election_date LIMIT 1");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $electionDate = $row['date'];
                        }
                        $stmt->close();
                        
                        $stmt = $conn->prepare("SELECT start, end FROM voter_reg_date LIMIT 1");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $voterReg = date('M d, Y', strtotime($row['start'])) . " - " . date('M d, Y', strtotime($row['end']));
                        }
                        $stmt->close();
                        
                        $stmt = $conn->prepare("SELECT start, end FROM candidate_reg_date LIMIT 1");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $candidateReg = date('M d, Y', strtotime($row['start'])) . " - " . date('M d, Y', strtotime($row['end']));
                        }
                        $stmt->close();
                    }
                    ?>
                    <div class="info-item">
                        <p><i class="far fa-calendar-check"></i> Voter Registration</p>
                        <div class="date-badge"><?php echo htmlspecialchars($voterReg); ?></div>
                    </div>
                    <div class="info-item">
                        <p><i class="fas fa-user-tie"></i> Candidate Registration</p>
                        <div class="date-badge"><?php echo htmlspecialchars($candidateReg); ?></div>
                    </div>
                    <div class="info-item">
                        <p><i class="fas fa-vote-yea"></i> Election Day</p>
                        <div class="date-badge"><?php echo htmlspecialchars($electionDate); ?></div>
                    </div>
                </div>
                
                <div class="info-card quick-links">
                    <h3><i class="fas fa-link"></i> Quick Links</h3>
                    <div class="info-item">
                        <a href="help.php"><i class="fas fa-question-circle"></i> How to Vote Guide</a>
                    </div>
                    <div class="info-item">
                        <a href="candidate.php"><i class="fas fa-users"></i> View Candidates</a>
                    </div>
                    <div class="info-item">
                      <!--  <a href="h_result.php"><i class="fas fa-chart-line"></i> Previous Results</a>-->
                    </div>
                    <div class="info-item">
                        <a href="advert.php"><i class="fas fa-bullhorn"></i> Election Adverts</a>
                    </div>
                </div>
                
                <div class="security-indicator">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure SSL Connection Active</span>
                </div>
            </aside>
            
            <main class="login-section">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h2>Secure Login</h2>
                        <p>Enter your credentials to access the voting system</p>
                    </div>
                    
                    <?php
                    echo $login_message;
                    echo $redirect_script;
                    ?>
                    
                    <form action="login.php" method="POST" onsubmit="return validateForm()" aria-label="Login Form">
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="username" name="user" class="form-control" 
                                       placeholder="Enter your username" required 
                                       aria-describedby="username-error" 
                                       value="<?php echo htmlspecialchars($username); ?>"
                                       autocomplete="username">
                                <div id="username-error" class="error-message"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <i class="fas fa-key input-icon"></i>
                                <input type="password" id="password" name="pass" class="form-control" 
                                       placeholder="Enter your password" required 
                                       aria-describedby="password-error"
                                       autocomplete="current-password">
                                <i class="fas fa-eye password-toggle" id="password-toggle"></i>
                                <div id="password-error" class="error-message"></div>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" name="log" class="btn btn-login" aria-label="Login Button">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                            <button type="reset" class="btn btn-reset" aria-label="Reset Button">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                        
                        <div class="forgot-link">
                            <a href="forget.php" aria-label="Forgot Password Link">
                                <i class="fas fa-key"></i> Forgot your password?
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
        
        <footer>
            <div class="footer-content">
                <div class="copyright">
                    <p>&copy; <?php echo date("Y"); ?> DTUSU Electoral Commission. All rights reserved.</p>
                </div>
                <div class="footer-links">
                    <a href="dev.php" aria-label="Developer Information"><i class="fas fa-code"></i> Developer Info</a>
                    <a href="privacy.php" aria-label="Privacy Policy"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                    <a href="terms.php" aria-label="Terms of Service"><i class="fas fa-file-contract"></i> Terms of Service</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
<?php
if ($conn) {
    $conn->close();
}
?>