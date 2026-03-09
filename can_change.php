<?php
include("connection.php");
session_start();

if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged In !! Please Login to access this page');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname, password FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
    $oldpassword = $row['password'];
} else {
    die("Error: User not found in the database.");
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Candidate Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            height: 45px;
            width: auto;
            border-radius: 6px;
        }

        .system-title h1 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 2px;
        }

        .system-title p {
            font-size: 12px;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: white;
        }

        .user-info i {
            font-size: 16px;
        }

        /* Navigation */
        .navbar {
            background: #f1f5f9;
            padding: 5px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 2px;
        }

        .nav-item {
            flex: 1;
        }

        .nav-link {
            display: block;
            padding: 12px 8px;
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s ease;
            border-radius: 6px;
            text-align: center;
        }

        .nav-link i {
            margin-right: 6px;
            font-size: 14px;
        }

        .nav-link:hover {
            background: #e2e8f0;
            color: #3498db;
        }

        .nav-link.active {
            background: #3498db;
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 25px;
            display: flex;
            gap: 30px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .user-greeting {
            text-align: right;
            margin-bottom: 25px;
            font-size: 14px;
            color: #64748b;
        }

        .user-greeting span {
            color: #3498db;
            font-weight: 600;
        }

        /* Password Form Card */
        .password-form-card {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .security-header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }

        .security-header h2 {
            font-size: 20px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .security-header p {
            opacity: 0.9;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
            font-size: 14px;
        }

        .form-label i {
            margin-right: 8px;
            color: #3498db;
        }

        .password-input-container {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
        }

        .toggle-password:hover {
            color: #3498db;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 2px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background: #ef4444;
        }

        .strength-medium {
            background: #f59e0b;
        }

        .strength-strong {
            background: #10b981;
        }

        .strength-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .wrong {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }

        .submit-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Security Tips Card */
        .security-tips-card {
            flex: 0 0 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .security-tips-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-tips-card ul {
            list-style: none;
            padding: 0;
        }

        .security-tips-card li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-tips-card li:last-child {
            border-bottom: none;
        }

        /* Password Requirements */
        .requirements-list {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #3498db;
        }

        .requirements-list h4 {
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .requirements-list ul {
            list-style: none;
            padding: 0;
        }

        .requirements-list li {
            padding: 5px 0;
            color: #64748b;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirements-list li.valid {
            color: #10b981;
        }

        .requirements-list li.invalid {
            color: #ef4444;
        }

        /* Footer */
        .footer {
            background: #f1f5f9;
            padding: 15px 25px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }

        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: #2ecc71;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                border-radius: 8px;
            }
            
            .header {
                flex-direction: column;
                gap: 12px;
                padding: 15px;
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 calc(33.333% - 4px);
            }
            
            .nav-link {
                padding: 10px 5px;
                font-size: 12px;
            }
            
            .main-content {
                flex-direction: column;
                padding: 20px;
            }
            
            .security-tips-card {
                flex: 0 0 auto;
            }
        }

        @media (max-width: 480px) {
            .nav-item {
                flex: 0 0 calc(50% - 2px);
            }
            
            .nav-link {
                font-size: 11px;
            }
            
            .nav-link i {
                display: block;
                margin: 0 0 4px 0;
                font-size: 12px;
            }
            
            .password-form-card {
                padding: 20px;
            }
            
            .security-header {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.JPG" alt="Logo" class="logo">
                <div class="system-title">
                 <h1> DTUSU Voting System</h1>
                     <p>Candidate Portal</p>
                </div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($FirstName); ?></span>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="candidate_view.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="post.php" class="nav-link">
                        <i class="fas fa-bullhorn"></i> Post
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_change.php" class="nav-link active">
                        <i class="fas fa-key"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_candidate.php" class="nav-link">
                        <i class="fas fa-users"></i> Candidates
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_comment.php" class="nav-link">
                        <i class="fas fa-comments"></i> Comments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_result.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Results
                    </a>
                </li>
                <li class="nav-item">
                    <a href="clogout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Password Form -->
            <div class="password-form-card">
                <div class="security-header">
                    <h2><i class="fas fa-shield-alt"></i> Change Your Password</h2>
                    <p>Update your password to keep your account secure</p>
                </div>

                <?php
                if (isset($_POST['changepassword'])) {
                    $oldpass = md5($_POST['old_password']);
                    $newpass = md5($_POST['new_password']);
                    $confirmpass = md5($_POST['confirm_password']);

                    if ($oldpassword !== $oldpass) {
                        echo '<div class="message wrong">';
                        echo '<i class="fas fa-exclamation-triangle"></i> Incorrect old password!';
                        echo '</div>';
                        echo '<script>setTimeout(function(){ window.location.href = "can_change.php"; }, 3000);</script>';
                    } elseif ($newpass !== $confirmpass) {
                        echo '<div class="message wrong">';
                        echo '<i class="fas fa-exclamation-triangle"></i> New passwords do not match!';
                        echo '</div>';
                        echo '<script>setTimeout(function(){ window.location.href = "can_change.php"; }, 3000);</script>';
                    } else {
                        $stmt = $conn->prepare("UPDATE candidate SET password = ? WHERE c_id = ?");
                        $stmt->bind_param("ss", $newpass, $user_id);
                        if ($stmt->execute()) {
                            echo '<div class="message success">';
                            echo '<i class="fas fa-check-circle"></i> Password changed successfully!';
                            echo '</div>';
                            echo '<script>setTimeout(function(){ window.location.href = "can_change.php"; }, 3000);</script>';
                        } else {
                            echo '<div class="message wrong">';
                            echo '<i class="fas fa-exclamation-triangle"></i> Error: Unable to change password.';
                            echo '</div>';
                        }
                        $stmt->close();
                    }
                }
                ?>

                <form id="form1" name="login" method="POST" action="can_change.php" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label class="form-label" for="old_password">
                            <i class="fas fa-lock"></i> Current Password
                        </label>
                        <div class="password-input-container">
                            <input type="password" id="old_password" name="old_password" class="form-input" 
                                   placeholder="Enter your current password" required>
                            <button type="button" class="toggle-password" data-target="old_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">
                            <i class="fas fa-key"></i> New Password
                        </label>
                        <div class="password-input-container">
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                   placeholder="Enter your new password" required>
                            <button type="button" class="toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter" id="strength-meter"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Password strength</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="fas fa-key"></i> Confirm New Password
                        </label>
                        <div class="password-input-container">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your new password" required>
                            <button type="button" class="toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-match" class="strength-text"></div>
                    </div>

                    <div class="requirements-list">
                        <h4><i class="fas fa-list-check"></i> Password Requirements:</h4>
                        <ul>
                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                            <li id="req-uppercase"><i class="fas fa-circle"></i> At least one uppercase letter</li>
                            <li id="req-lowercase"><i class="fas fa-circle"></i> At least one lowercase letter</li>
                            <li id="req-number"><i class="fas fa-circle"></i> At least one number</li>
                            <li id="req-special"><i class="fas fa-circle"></i> At least one special character</li>
                        </ul>
                    </div>

                    <button type="submit" name="changepassword" class="submit-btn" id="submit-btn">
                        <i class="fas fa-sync-alt"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Security Tips -->
            <div class="security-tips-card">
                <h3><i class="fas fa-shield-alt"></i> Security Tips</h3>
                <ul>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Use a strong, unique password</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Don't reuse passwords</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Change passwords regularly</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Avoid personal information</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Use a password manager</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Enable two-factor authentication</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Ethiopian Election Commission</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
                <a href="#"><i class="fas fa-file-contract"></i> Terms</a>
                <a href="#"><i class="fas fa-phone-alt"></i> Support</a>
                <a href="#"><i class="fas fa-question-circle"></i> Help</a>
            </div>
        </footer>
    </div>

    <script>
        // Navigation active state
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthMeter = document.getElementById('strength-meter');
        const strengthText = document.getElementById('strength-text');
        const submitBtn = document.getElementById('submit-btn');
        const passwordMatch = document.getElementById('password-match');

        newPasswordInput.addEventListener('input', checkPasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);

        function checkPasswordStrength() {
            const password = newPasswordInput.value;
            let strength = 0;
            
            // Check password requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement indicators
            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-special', hasSpecial);
            
            // Calculate strength
            if (hasLength) strength += 20;
            if (hasUppercase) strength += 20;
            if (hasLowercase) strength += 20;
            if (hasNumber) strength += 20;
            if (hasSpecial) strength += 20;
            
            // Update strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update strength text and color
            if (strength <= 40) {
                strengthMeter.className = 'strength-meter strength-weak';
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#ef4444';
                submitBtn.disabled = true;
            } else if (strength <= 80) {
                strengthMeter.className = 'strength-meter strength-medium';
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#f59e0b';
                submitBtn.disabled = false;
            } else {
                strengthMeter.className = 'strength-meter strength-strong';
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#10b981';
                submitBtn.disabled = false;
            }
            
            // Check password match if confirm password is filled
            if (confirmPasswordInput.value) {
                checkPasswordMatch();
            }
        }

        function updateRequirement(elementId, isValid) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            
            if (isValid) {
                element.className = 'valid';
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#10b981';
            } else {
                element.className = 'invalid';
                icon.className = 'fas fa-circle';
                icon.style.color = '#ef4444';
            }
        }

        function checkPasswordMatch() {
            const password = newPasswordInput.value;
            const confirm = confirmPasswordInput.value;
            
            if (!confirm) {
                passwordMatch.textContent = '';
                return;
            }
            
            if (password === confirm) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.style.color = '#10b981';
                submitBtn.disabled = false;
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.style.color = '#ef4444';
                submitBtn.disabled = true;
            }
        }

        // Form validation
        function validateForm() {
            const oldPass = document.getElementById('old_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (!oldPass || !newPass || !confirmPass) {
                alert('Please fill in all password fields!');
                return false;
            }
            
            if (newPass !== confirmPass) {
                alert('New Password and Confirm Password do not match!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>