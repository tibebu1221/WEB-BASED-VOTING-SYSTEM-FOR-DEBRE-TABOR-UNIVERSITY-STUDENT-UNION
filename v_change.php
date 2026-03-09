<?php
include("connection.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    echo '<script>
        Swal.fire({
            icon: "warning",
            title: "Login Required",
            text: "You are not logged in! Please login to access this page.",
            confirmButtonColor: "#1a2a6c"
        }).then(() => {
            window.location = "login.php";
        });
    </script>';
    exit();
}

// Fetch user data
$user_id = $_SESSION['u_id'];

// Get user details
$stmt = $conn->prepare("SELECT fname, mname, password FROM voter WHERE vid = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    $oldpassword = $row['password']; // Hashed password from database
} else {
    die("Error: User not found in the database.");
}
$stmt->close();

// Handle password change
$error_message = '';
$success_message = '';

if (isset($_POST['changepassword'])) {
    $oldpass = trim($_POST['old_password']);
    $newpass = trim($_POST['new_password']);
    $confirmpass = trim($_POST['confirm_password']);

    // Validation
    if (empty($oldpass) || empty($newpass) || empty($confirmpass)) {
        $error_message = 'All fields are required.';
    } else {
        // Verify old password
        if (!password_verify($oldpass, $oldpassword)) {
            $error_message = 'Incorrect old password. Please try again.';
        } elseif ($newpass !== $confirmpass) {
            $error_message = 'New password and confirm password do not match.';
        } elseif (strlen($newpass) < 8) {
            $error_message = 'New password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $newpass)) {
            $error_message = 'New password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $newpass)) {
            $error_message = 'New password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $newpass)) {
            $error_message = 'New password must contain at least one number.';
        } elseif (!preg_match('/[\W]/', $newpass)) {
            $error_message = 'New password must contain at least one special character.';
        } elseif ($oldpass === $newpass) {
            $error_message = 'New password must be different from old password.';
        } else {
            // Hash the new password
            $hashed_newpass = password_hash($newpass, PASSWORD_DEFAULT);

            // Update password
            $stmt = $conn->prepare("UPDATE voter SET password = ? WHERE vid = ?");
            $stmt->bind_param("ss", $hashed_newpass, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Your password has been changed successfully!';
                // Update local variable for verification if user tries to change again
                $oldpassword = $hashed_newpass;
            } else {
                $error_message = 'Unable to change password. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
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
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
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
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            padding: 1rem 0;
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
            gap: 20px;
        }

        .logo {
            height: 60px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .voter-logo {
            height: 80px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        /* User Welcome */
        .user-welcome {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
        }

        .user-welcome i {
            font-size: 1.5rem;
            color: var(--accent-green);
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
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

        /* Main Content */
        .main-content {
            display: flex;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            gap: 40px;
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
            box-shadow: var(--card-shadow);
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

        .security-tips {
            padding: 25px;
            background: var(--white);
        }

        .security-tips h3 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }

        .security-tips h3 i {
            color: var(--accent-green);
        }

        .security-tips ul {
            list-style: none;
            padding: 0;
        }

        .security-tips li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .security-tips li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--accent-green);
            font-weight: bold;
        }

        /* Change Password Form */
        .change-password-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            width: 100%;
        }

        .page-header h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .change-password-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 500px;
            border-top: 5px solid var(--accent-green);
            position: relative;
            overflow: hidden;
        }

        .change-password-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-green), var(--primary-blue));
        }

        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .card-header h2 {
            color: var(--primary-blue);
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .card-header p {
            color: var(--text-light);
            font-size: 0.95rem;
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
            padding: 5px;
        }

        .password-toggle:hover {
            color: var(--primary-blue);
        }

        .password-strength {
            margin-top: 10px;
        }

        .strength-meter {
            height: 5px;
            background: var(--light-gray);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 5px;
        }

        .strength-text {
            font-size: 0.85rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .password-requirements {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .password-requirements h4 {
            margin-bottom: 10px;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            transition: var(--transition);
        }

        .requirement.met {
            color: var(--accent-green);
        }

        .requirement i {
            font-size: 0.9rem;
        }

        .submit-btn {
            width: 100%;
            padding: 15px 25px;
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
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
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #0d1e5a, #1a2a6c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.2);
        }

        .submit-btn:disabled {
            background: var(--medium-gray);
            color: var(--text-light);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
                flex-direction: column;
                text-align: center;
            }
            
            .user-welcome {
                margin-top: 10px;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .change-password-card {
                padding: 25px 20px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .card-header h2 {
                font-size: 1.5rem;
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
            
            .page-header h1 {
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
                    <a href="voter.php">
                        <img src="img/logo.JPG" alt="Debre Tabor University Logo" class="logo">
                    </a>
                    <img src="img/voter.png" alt="Voter Dashboard" class="voter-logo">
                </div>
                <div class="user-welcome">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Welcome</div>
                        <div class="user-name"><?php echo $FirstName . ' ' . $middleName; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="voter.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="active"><a href="v_change.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="cast.php"><i class="fas fa-vote-yea"></i> Cast Vote</a></li>
                <li><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="voter_comment.php"><i class="fas fa-comment"></i> Comment</a></li>
                <li><a href="v_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
              <!--  <img src="deve/v.JPG" alt="Voter Security" class="sidebar-image">-->
            </div>

            <div class="security-tips sidebar-card">
                <h3><i class="fas fa-shield-alt"></i> Security Tips</h3>
                <ul>
                    <li>Use a strong, unique password</li>
                    <li>Never share your password with anyone</li>
                    <li>Change your password regularly</li>
                    <li>Avoid using personal information</li>
                    <li>Use a mix of letters, numbers & symbols</li>
                    <li>Don't use the same password elsewhere</li>
                </ul>
            </div>
        </aside>

        <!-- Change Password Form -->
        <div class="change-password-container">
            <div class="page-header">
                <h1><i class="fas fa-key"></i> Change Password</h1>
                <p>Update your account password to keep your voting account secure</p>
            </div>

            <div class="change-password-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-lock"></i> Password Update</h2>
                    <p>Enter your current password and set a new secure password</p>
                </div>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>

                <form id="changePasswordForm" method="POST" action="v_change.php">
                    <div class="form-group">
                        <label for="old_password"><i class="fas fa-lock"></i> Current Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   id="old_password" 
                                   name="old_password" 
                                   class="form-control" 
                                   placeholder="Enter your current password" 
                                   required 
                                   autofocus>
                            <button type="button" class="password-toggle" onclick="togglePassword('old_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   placeholder="Enter new password" 
                                   required
                                   onkeyup="checkPasswordStrength()">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text">
                                <i class="fas fa-info-circle"></i>
                                <span id="strengthText">Password strength</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-check-circle"></i>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   placeholder="Confirm new password" 
                                   required
                                   onkeyup="checkPasswordMatch()">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="matchError"></div>
                    </div>

                    <div class="password-requirements">
                        <h4><i class="fas fa-list-check"></i> Password Requirements</h4>
                        <div class="requirement" id="reqLength">
                            <i class="fas fa-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement" id="reqUppercase">
                            <i class="fas fa-circle"></i>
                            <span>At least one uppercase letter</span>
                        </div>
                        <div class="requirement" id="reqLowercase">
                            <i class="fas fa-circle"></i>
                            <span>At least one lowercase letter</span>
                        </div>
                        <div class="requirement" id="reqNumber">
                            <i class="fas fa-circle"></i>
                            <span>At least one number</span>
                        </div>
                        <div class="requirement" id="reqSpecial">
                            <i class="fas fa-circle"></i>
                            <span>At least one special character</span>
                        </div>
                    </div>

                    <button type="submit" 
                            name="changepassword" 
                            class="submit-btn" 
                            id="submitBtn"
                            disabled>
                        <i class="fas fa-sync-alt"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date("Y"); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-shield-alt"></i> Secure Account Management - Keep Your Credentials Safe
            </p>
        </div>
    </footer>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const eyeIcon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                eyeIcon.className = 'fas fa-eye';
                button.setAttribute('aria-label', 'Show password');
            }
        }

        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');
            
            let strength = 0;
            const requirements = {
                length: false,
                uppercase: false,
                lowercase: false,
                number: false,
                special: false
            };

            // Check length
            if (password.length >= 8) {
                strength += 20;
                requirements.length = true;
                document.getElementById('reqLength').classList.add('met');
            } else {
                document.getElementById('reqLength').classList.remove('met');
            }

            // Check uppercase
            if (/[A-Z]/.test(password)) {
                strength += 20;
                requirements.uppercase = true;
                document.getElementById('reqUppercase').classList.add('met');
            } else {
                document.getElementById('reqUppercase').classList.remove('met');
            }

            // Check lowercase
            if (/[a-z]/.test(password)) {
                strength += 20;
                requirements.lowercase = true;
                document.getElementById('reqLowercase').classList.add('met');
            } else {
                document.getElementById('reqLowercase').classList.remove('met');
            }

            // Check numbers
            if (/[0-9]/.test(password)) {
                strength += 20;
                requirements.number = true;
                document.getElementById('reqNumber').classList.add('met');
            } else {
                document.getElementById('reqNumber').classList.remove('met');
            }

            // Check special characters
            if (/[\W_]/.test(password)) {
                strength += 20;
                requirements.special = true;
                document.getElementById('reqSpecial').classList.add('met');
            } else {
                document.getElementById('reqSpecial').classList.remove('met');
            }

            // Update strength meter
            strengthFill.style.width = strength + '%';
            
            // Update strength text and color
            if (strength < 40) {
                strengthFill.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Weak password';
            } else if (strength < 80) {
                strengthFill.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Moderate password';
            } else {
                strengthFill.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Strong password';
            }

            // Enable submit button if all requirements are met
            const allMet = Object.values(requirements).every(req => req === true);
            submitBtn.disabled = !allMet;
        }

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchError = document.getElementById('matchError');
            const submitBtn = document.getElementById('submitBtn');

            if (confirmPassword === '') {
                matchError.textContent = '';
                return;
            }

            if (password !== confirmPassword) {
                matchError.innerHTML = `<i class="fas fa-exclamation-circle"></i> Passwords do not match`;
                matchError.style.color = 'var(--danger)';
                submitBtn.disabled = true;
            } else {
                matchError.innerHTML = `<i class="fas fa-check-circle"></i> Passwords match`;
                matchError.style.color = 'var(--success)';
                
                // Only enable if all requirements are met
                const requirements = document.querySelectorAll('.requirement.met');
                if (requirements.length === 5) {
                    submitBtn.disabled = false;
                }
            }
        }

        // Form validation
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const oldPassword = document.getElementById('old_password').value.trim();
            const newPassword = document.getElementById('new_password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();

            if (!oldPassword) {
                e.preventDefault();
                showError('Please enter your current password.');
                return;
            }

            if (!newPassword) {
                e.preventDefault();
                showError('Please enter a new password.');
                return;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                showError('New password must be at least 8 characters long.');
                return;
            }

            if (!confirmPassword) {
                e.preventDefault();
                showError('Please confirm your new password.');
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showError('New password and confirm password do not match.');
                return;
            }

            // Check if new password is same as old password
            if (oldPassword === newPassword) {
                e.preventDefault();
                showError('New password must be different from current password.');
                return;
            }
        });

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: message,
                confirmButtonColor: '#1a2a6c'
            });
        }
    </script>

    <?php
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>