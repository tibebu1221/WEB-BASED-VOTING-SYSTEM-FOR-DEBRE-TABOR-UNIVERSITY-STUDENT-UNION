<?php
// Include and start session at the top
session_start();
include("connection.php");

// Process form submit
$success_message = $error_message = '';
if(isset($_POST['forg'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $newpass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    // Check if user exists
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ? AND phone = ? AND username = ?");
    mysqli_stmt_bind_param($stmt, "sss", $email, $phone, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1) {
        // Update password
        $stmt_update = mysqli_prepare($conn, "UPDATE user SET password = ? WHERE email = ? AND phone = ? AND username = ?");
        mysqli_stmt_bind_param($stmt_update, "ssss", $newpass, $email, $phone, $username);
        mysqli_stmt_execute($stmt_update);

        if(mysqli_stmt_affected_rows($stmt_update) > 0) {
            $success_message = "Password changed successfully! Redirecting to login...";
            header("refresh:3;url=login.php");
        } else {
            $error_message = "Error: Could not change password. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $error_message = "The Email, Phone, or Username provided is incorrect!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --light-gray: #e5e7eb;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }
        
        .logo-text h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }
        
        /* Navigation */
        .nav-menu {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link.active {
            background: rgba(139, 92, 246, 0.2);
            color: white;
            border: 1px solid rgba(139, 92, 246, 0.4);
        }
        
        .nav-link i {
            font-size: 0.9rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 40px 0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        /* Left Sidebar */
        .sidebar {
            flex: 1;
            min-width: 300px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .developer-image {
            width: 100%;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border: 3px solid var(--light-gray);
        }
        
        .election-date {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-sm);
            color: white;
            margin-bottom: 20px;
        }
        
        .election-date h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .election-date .date {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        /* Right Content */
        .content {
            flex: 2;
            min-width: 300px;
        }
        
        /* Forgot Password Card */
        .forgot-card {
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--card-shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--light);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
        }
        
        .close-btn:hover {
            background: var(--light-gray);
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .password-strength.valid {
            color: var(--success);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            flex: 2;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--gray);
            flex: 1;
        }
        
        .btn-secondary:hover {
            background: var(--light-gray);
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }
        
        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
        
        .message-icon {
            font-size: 1.2rem;
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        .footer-content {
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .copyright {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .logo-section {
                justify-content: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .main-content {
                flex-direction: column;
            }
            
            .forgot-card {
                padding: 30px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Animation */
        .animate-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
                    <div class="logo">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Online Voting System</h1>
                        <p>Ethiopian Electoral Commission</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="about.php" class="nav-link">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                    <a href="help.php" class="nav-link">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                    <a href="contacts.php" class="nav-link">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                                  <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->

    
                    <a href="candidate.php" class="nav-link">
                        <i class="fas fa-users"></i> Candidates
                    </a>
                    <a href="vote.php" class="nav-link">
                        <i class="fas fa-check-circle"></i> Vote
                    </a>
                    <a href="login.php" class="nav-link active">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container main-content">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card animate-in">
                <img src="deve/dt.PNG" alt="Developer" class="developer-image">
                <div class="election-date">
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM election_date");
                    if($row = mysqli_fetch_array($result)) {
                        $date = htmlspecialchars($row['date']);
                        echo "<h3>Next Election Date</h3>";
                        echo "<div class='date'>" . date('F j, Y', strtotime($date)) . "</div>";
                    }
                    ?>
                </div>
                <div style="text-align: center;">
                    <a href="advert.php" class="btn btn-secondary" style="margin-bottom: 10px;">
                        <i class="fas fa-bullhorn"></i> View Adverts
                    </a>
                    <a href="dev.php" class="btn btn-secondary">
                        <i class="fas fa-code"></i> Developer Info
                    </a>
                </div>
            </div>
        </aside>

        <!-- Right Content -->
        <section class="content">
            <div class="forgot-card animate-in">
                <button class="close-btn" onclick="window.location.href='index.php'" title="Close">
                    <i class="fas fa-times"></i>
                </button>
                
                <div class="form-header">
                    <h2>Reset Your Password</h2>
                    <p>Enter your account details to set a new password</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if($success_message): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle message-icon"></i>
                        <span><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle message-icon"></i>
                        <span><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Forgot Password Form -->
                <form action="forget.php" method="POST" id="forgotForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Enter your registered email" 
                               required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="Enter your phone number" 
                               required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Enter your username" 
                               required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               placeholder="Enter new password" 
                               required
                               pattern='(?=.*\d).{6,}'
                               oninput="checkPasswordStrength(this.value)">
                        <div id="passwordStrength" class="password-strength">
                            Password must be at least 6 characters with at least 1 number
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="forg" class="btn btn-primary">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Clear Form
                        </button>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <p>Remember your password? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">Login here</a></p>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="fas fa-shield-alt"></i> Secure Online Voting Platform</p>
            <p class="copyright">Copyright © <?php echo date("Y"); ?> Ethiopian Electoral Commission. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Password strength indicator
        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('passwordStrength');
            let strength = '';
            let color = 'var(--gray)';
            
            if (password.length === 0) {
                strength = 'Password must be at least 6 characters with at least 1 number';
            } else if (password.length < 6) {
                strength = 'Too short (minimum 6 characters)';
                color = 'var(--danger)';
            } else if (!/\d/.test(password)) {
                strength = 'Needs at least 1 number';
                color = 'var(--warning)';
            } else if (password.length >= 8 && /\d/.test(password)) {
                strength = 'Strong password ✓';
                color = 'var(--success)';
                strengthElement.classList.add('valid');
            } else {
                strength = 'Good password';
                color = 'var(--warning)';
            }
            
            strengthElement.textContent = strength;
            strengthElement.style.color = color;
            
            if (color === 'var(--success)') {
                strengthElement.classList.add('valid');
            } else {
                strengthElement.classList.remove('valid');
            }
        }
        
        // Form validation
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const passwordRegex = /^(?=.*\d).{6,}$/;
            
            if (!passwordRegex.test(password)) {
                e.preventDefault();
                alert('Please enter a valid password (at least 6 characters with at least 1 number)');
                return false;
            }
            return true;
        });
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });
    </script>

    <?php
    mysqli_close($conn);
    ?>
</body>
</html>