<?php
// Include and start session ONCE at the top
include("connection.php");
session_start();

// Variable to store messages
$page_message = '';
$email = $phone = $username = '';

// Check if the form was submitted
if (isset($_POST['forg'])) {
    // Sanitize and validate inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']); // Remove non-numeric characters
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    
    // Validate password requirements
    if (strlen($new_password) < 6 || !preg_match('/\d/', $new_password)) {
        $page_message = "<div class='alert alert-error'>Password must be at least 6 characters and include a number.</div>";
    } else {
        // Use password_hash for security (not MD5)
        $newpass_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Secure logic using prepared statements
        if ($conn) {
            // 1. Prepare a SELECT statement to find the user
            $stmt = mysqli_prepare($conn, "SELECT vid FROM voter WHERE email = ? AND phone = ? AND username = ?");
            mysqli_stmt_bind_param($stmt, "sss", $email, $phone, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // 2. Check if exactly one user was found
            if (mysqli_num_rows($result) == 1) {
                // 3. Update with new password hash
                $stmt_update = mysqli_prepare($conn, "UPDATE voter SET password = ? WHERE email = ? AND phone = ? AND username = ?");
                
                if ($stmt_update) {
                    mysqli_stmt_bind_param($stmt_update, "ssss", $newpass_hash, $email, $phone, $username);
                    mysqli_stmt_execute($stmt_update);
                    
                    // 4. Check if the update was successful
                    if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                        $page_message = "<div class='alert alert-success'>Password changed successfully! Redirecting to login...</div>";
                        header("refresh:3;url=vote.php");
                    } else {
                        $page_message = "<div class='alert alert-error'>Error: Could not change password. Please try again.</div>";
                    }
                    mysqli_stmt_close($stmt_update);
                } else {
                    $page_message = "<div class='alert alert-error'>Database update preparation error.</div>";
                }
            } else {
                // User not found
                $page_message = "<div class='alert alert-error'>Error: The Email, Phone, or Username provided is incorrect!</div>";
                header("refresh:3;url=forgetp.php");
            }
            mysqli_stmt_close($stmt);
        } else {
            $page_message = "<div class='alert alert-error'>Database connection failed.</div>";
        }
    }
}

// Get election date
$election_date = "No Election Date Set";
if ($conn) {
    $result = mysqli_query($conn, "SELECT date FROM election_date ORDER BY date DESC LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $election_date = htmlspecialchars($row['date']);
    }
    if ($result) {
        mysqli_free_result($result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Forgot Password</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG">
    <link rel="stylesheet" href="main.css" type="text/css" media="screen">
    <link rel="stylesheet" href="menu.css" type="text/css" media="screen">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2F4F4F;
            --secondary-color: #4CAF50;
            --accent-color: #2196F3;
            --light-gray: #F5F5F5;
            --dark-gray: #333;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --warning-color: #ff9800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(to right, var(--primary-color), #3a5f5f);
            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            height: 80px;
            width: auto;
        }

        .system-title {
            font-size: 1.8rem;
            font-weight: 600;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        /* Navigation */
        .nav-menu {
            background: var(--primary-color);
            padding: 0;
        }

        .nav-menu ul {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            justify-content: center;
            margin: 0;
            padding: 0;
        }

        .nav-menu ul li {
            margin: 0;
        }

        .nav-menu ul li a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-menu ul li a:hover,
        .nav-menu ul li.active a {
            background-color: rgba(255,255,255,0.1);
            border-bottom: 3px solid var(--secondary-color);
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .sidebar {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
        }

        .content {
            flex: 3;
            min-width: 300px;
        }

        .developer-card {
            text-align: center;
            padding: 20px;
            background: var(--light-gray);
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .developer-card img {
            width: 100%;
            max-width: 250px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .election-date {
            background: linear-gradient(135deg, var(--primary-color), #3a5f5f);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .election-date h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .date-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFD700;
        }

        /* Form Styles */
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(to right, var(--primary-color), #3a5f5f);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-btn {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .close-btn:hover {
            transform: scale(1.2);
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            outline: none;
        }

        .form-control:invalid {
            border-color: var(--error-color);
        }

        .password-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            display: block;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--secondary-color), #45a049);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #45a049, #4CAF50);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: var(--dark-gray);
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .alert-warning {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
            border-left: 4px solid var(--warning-color);
        }

        /* Footer */
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
            }
            
            .sidebar {
                max-width: 100%;
            }
            
            .nav-menu ul {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-menu ul li {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .form-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .main-content {
                margin: 15px auto;
                padding: 15px;
            }
            
            .system-title {
                font-size: 1.4rem;
            }
        }

        /* Utility Classes */
        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 30px;
        }

        .mb-3 {
            margin-bottom: 30px;
        }

        .icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-content">
            <div class="logo-section">
                <img src="img/logo.jpg" alt="Logo" class="logo">
                <div class="system-title">Debre Tabor University
Student Union   Voting System</div>
            </div>
            <div class="system-banner">
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav-menu">
        <div class="container">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-address-book"></i> Contact Us</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="advert.php"><i class="fas fa-bullhorn"></i> Advert</a></li>
                <li><a href="dev.php"><i class="fas fa-code"></i> Developer</a></li>
                <li><a href="candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li class="active"><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote</a></li>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="developer-card">
                <img src="deve/dt.PNG" alt="Developer Image">
                <h3>System Developer</h3>
                <p>Online Voting System</p>
            </div>
            
            <div class="election-date">
                <h3><i class="fas fa-calendar-alt"></i> Next Election Date</h3>
                <div class="date-display"><?php echo $election_date; ?></div>
                <p class="mt-3">Be ready to exercise your democratic right!</p>
            </div>
        </aside>

        <!-- Main Content Area -->
        <section class="content">
            <div class="form-container">
                <!-- Messages Display -->
                <?php if ($page_message): ?>
                    <div class="message-container">
                        <?php echo $page_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Password Reset Form -->
                <div class="form-card">
                    <div class="form-header">
                        <h2><i class="fas fa-key"></i> Voter Password Reset</h2>
                        <a href="index.php" class="close-btn" title="Close">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    
                    <div class="form-body">
                        <form action="forgetp.php" method="POST" name="frm" id="resetForm">
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="Enter your registered email" 
                                       required
                                       value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-control" 
                                       placeholder="Enter your phone number" 
                                       required
                                       pattern="[0-9]{10,15}"
                                       title="Please enter a valid phone number (digits only)"
                                       value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="username"><i class="fas fa-user"></i> Username</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control" 
                                       placeholder="Enter your username" 
                                       required
                                       value="<?php echo htmlspecialchars($username); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password" 
                                       class="form-control" 
                                       placeholder="Enter new password" 
                                       required
                                       pattern="(?=.*\d).{6,}"
                                       title="Must be at least 6 characters and include a number">
                                <span class="password-hint">Minimum 6 characters with at least one number</span>
                            </div>
                            
                            <div class="form-actions">
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Clear
                                </button>
                                <button type="submit" name="forg" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Remember your password? <a href="login.php" style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> Electoral Commission. All Rights Reserved.</p>
            <p>Online Voting System - Secure Democratic Process</p>
        </div>
    </footer>

    <!-- JavaScript for Enhanced Form Validation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetForm');
            const passwordInput = document.getElementById('new_password');
            
            // Real-time password validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const isValid = password.length >= 6 && /\d/.test(password);
                
                if (password) {
                    if (isValid) {
                        this.style.borderColor = '#4CAF50';
                    } else {
                        this.style.borderColor = '#f44336';
                    }
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                const phoneInput = document.getElementById('phone');
                const phone = phoneInput.value.replace(/\D/g, '');
                
                // Validate phone number
                if (phone.length < 10 || phone.length > 15) {
                    e.preventDefault();
                    alert('Please enter a valid phone number (10-15 digits)');
                    phoneInput.focus();
                    return false;
                }
                
                // Validate password
                const password = passwordInput.value;
                if (password.length < 6 || !/\d/.test(password)) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters and include at least one number');
                    passwordInput.focus();
                    return false;
                }
                
                return true;
            });
            
            // Auto-format phone number
            document.getElementById('phone').addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    </script>
</body>
</html>
<?php
// Close the single, main connection at the end
if ($conn) {
    mysqli_close($conn);
}
?>