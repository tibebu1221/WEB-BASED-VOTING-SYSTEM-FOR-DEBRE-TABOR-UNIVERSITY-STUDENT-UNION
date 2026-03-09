<?php
    include("connection.php");
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Password Recovery - Ethiopian Online Voting</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2F4F4F;
            --secondary: #006400;
            --accent: #DAA520;
            --light: #F8F9FA;
            --dark: #343A40;
            --gray: #6C757D;
            --danger: #DC3545;
            --success: #28A745;
            --info: #17A2B8;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e9f5fb 0%, #f0f9ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
        }

        /* Header */
        .header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 20px 40px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section img {
            height: 70px;
            border-radius: 8px;
            border: 3px solid var(--accent);
        }

        .system-title h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .system-title p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Navigation */
        .nav-bar {
            background: white;
            padding: 0 40px;
            border-bottom: 1px solid #eaeaea;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            overflow-x: auto;
            white-space: nowrap;
        }

        .nav-menu li {
            flex-shrink: 0;
        }

        .nav-menu a {
            display: block;
            padding: 18px 20px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }

        .nav-menu a:hover {
            color: var(--secondary);
            background: var(--light);
        }

        .nav-menu .active a {
            color: var(--secondary);
            border-bottom: 3px solid var(--accent);
        }

        /* Main Content */
        .main-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        @media (max-width: 992px) {
            .main-wrapper {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }

        .profile-card {
            text-align: center;
            margin-bottom: 25px;
        }

        .profile-img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent);
            margin-bottom: 15px;
        }

        .date-card {
            background: linear-gradient(135deg, #2F4F4F, #006400);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
        }

        .date-card h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .date-card .date-value {
            font-size: 1.4rem;
            font-weight: bold;
            margin: 10px 0;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .form-header p {
            color: var(--gray);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Form Styling */
        .recovery-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }

        .form-label i {
            color: var(--accent);
            margin-right: 8px;
            width: 20px;
        }

        .form-input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 100, 0, 0.1);
        }

        .form-input::placeholder {
            color: #a0a0a0;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
            padding-left: 25px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            color: white;
            flex: 2;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 100, 0, 0.2);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: var(--dark);
            border: 2px solid #e0e0e0;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        /* Message Styling */
        .message-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-info {
            background: linear-gradient(to right, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .message-container i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Footer */
        .footer {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 25px 40px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            margin-top: 30px;
            text-align: center;
            box-shadow: var(--box-shadow);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--accent);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }

        .copyright {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 20px;
            }

            .nav-bar {
                padding: 0 20px;
            }

            .nav-menu a {
                padding: 15px;
                font-size: 0.9rem;
            }

            .form-container {
                padding: 25px;
            }

            .form-header h2 {
                font-size: 1.6rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Close Button */
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .close-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Security Note */
        .security-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #856404;
        }

        .security-note i {
            color: #ffc107;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.jpg" alt="Ethiopian Flag Logo">
                <div class="system-title">
                   <h1>Debre Tabor University
Student Union   Voting System</h1>
                     
                </div>
            </div>
            <a href="index.php" class="close-btn" title="Return to Home">
                <i class="fas fa-times"></i>
            </a>
        </header>

        <!-- Navigation -->
        <nav class="nav-bar">
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help</a></li>
                <li><a href="contacts.php"><i class="fas fa-envelope"></i> Contact</a></li>
               <!-- <li><a href="h_result.php"><i class="fas fa-chart-bar"></i> Results</a></li> -->
                <li><a href="candidate.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
                <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote</a></li>
                <li class="active"><a href="#"><i class="fas fa-key"></i> Password Recovery</a></li>
            </ul>
        </nav>

        <div class="main-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="profile-card">
                    <img src="deve/dt.PNG" alt="Candidate Portal" class="profile-img">
                    <h3>Candidate Portal</h3>
                    <p>Password Recovery Center</p>
                </div>

                <?php
                $result = mysqli_query($conn, "SELECT * FROM election_date");
                while($row = mysqli_fetch_array($result)) {
                    $date = htmlspecialchars($row['date']);
                ?>
                <div class="date-card">
                    <h3><i class="fas fa-calendar-alt"></i> Next Election Date</h3>
                    <div class="date-value"><?php echo $date; ?></div>
                    <p>Mark your calendar for this important day</p>
                </div>
                <?php } ?>
            </aside>

            <!-- Main Form Area -->
            <main class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-unlock-alt"></i> Candidate Password Recovery</h2>
                    <p>Enter your account details below to reset your password. All fields are required for security verification.</p>
                </div>

                <?php if(isset($_POST['forg'])): ?>
                    <div class="message-container <?php 
                        if(mysqli_stmt_affected_rows($stmt_update ?? null) > 0) echo 'message-success';
                        else echo 'message-error';
                    ?>">
                        <?php
                        $email = $_POST['email'];
                        $phone = $_POST['phone'];
                        $username = $_POST['username'];
                        $newpass = md5($_POST['new_password']);

                        $stmt = mysqli_prepare($conn, "SELECT c_id FROM candidate WHERE email = ? AND phone = ? AND username = ?");
                        mysqli_stmt_bind_param($stmt, "sss", $email, $phone, $username);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if(mysqli_num_rows($result) == 1) {
                            $stmt_update = mysqli_prepare($conn, "UPDATE candidate SET password = ? WHERE email = ? AND phone = ? AND username = ?");
                            mysqli_stmt_bind_param($stmt_update, "ssss", $newpass, $email, $phone, $username);
                            mysqli_stmt_execute($stmt_update);

                            if(mysqli_stmt_affected_rows($stmt_update) > 0) {
                                echo '<i class="fas fa-check-circle"></i>';
                                echo '<h3>Password Changed Successfully!</h3>';
                                echo '<p>You will be redirected to the candidate login page in 3 seconds.</p>';
                                echo '<meta content="3;candidate.php" http-equiv="refresh" />';
                            } else {
                                echo '<i class="fas fa-exclamation-triangle"></i>';
                                echo '<h3>Update Failed</h3>';
                                echo '<p>Could not change password. Please try again.</p>';
                            }
                            if(isset($stmt_update)) mysqli_stmt_close($stmt_update);
                        } else {
                            echo '<i class="fas fa-exclamation-circle"></i>';
                            echo '<h3>Verification Failed</h3>';
                            echo '<p>The Email, Phone, or Username provided is incorrect!</p>';
                            echo '<p>You will be redirected to try again in 3 seconds.</p>';
                            echo '<meta content="3;forgetc.php" http-equiv="refresh" />';
                        }
                        mysqli_stmt_close($stmt);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="forgetc.php" method="POST" class="recovery-form" name="frm">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" class="form-input" placeholder="Enter your registered email" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone" class="form-input" placeholder="Enter your phone number" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" class="form-input" placeholder="Enter your username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="new_password" id="pw" class="form-input" placeholder="Enter new password (min. 6 chars with a number)" required pattern='(?=.*\d).{6,}'>
                        <div class="password-requirements">
                            <i class="fas fa-info-circle"></i> Must be at least 6 characters with at least one number
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
                </form>

                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Security Notice:</strong> For your protection, we verify multiple account details before allowing password changes. 
                    If you continue to experience issues, please contact the Election Office directly.
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a>
                <a href="contacts.php"><i class="fas fa-headset"></i> Contact Support</a>
                <a href="dev.php"><i class="fas fa-code"></i> Developer Team</a>
            </div>
            <p class="copyright">Copyright © 2009 EC. Ethiopian Online Voting System - Hossana City Administration</p>
        </footer>
    </div>

    <?php mysqli_close($conn); ?>
</body>
</html>