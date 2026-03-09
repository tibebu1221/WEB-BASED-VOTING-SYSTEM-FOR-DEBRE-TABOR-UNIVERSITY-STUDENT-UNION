<?php
include("connection.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged In !! Please Login to access this page');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch user data using MySQLi
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM voter WHERE vid = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    $fullName = $FirstName . " " . $middleName;
} else {
    die("Error: User not found in the database.");
}
$stmt->close();

// Handle comment submission
if (isset($_POST['sent'])) {
    $fname = trim($_POST['fname']);
    $email = trim($_POST['email']);
    $comment = trim($_POST['com']);
    $date = date("d/m/Y");

    // Validate inputs
    $errors = [];
    
    if (empty($fname)) {
        $errors[] = "Full name is required!";
    } elseif (strlen($fname) < 5 || strlen($fname) > 25) {
        $errors[] = "Name must be 5-25 characters!";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }
    
    if (empty($comment)) {
        $errors[] = "Comment is required!";
    } elseif (strlen($comment) < 3 || strlen($comment) > 500) {
        $errors[] = "Comment must be 3-500 characters!";
    }

    if (empty($errors)) {
        // Fetch admin user ID
        $stmt = $conn->prepare("SELECT u_id FROM user WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin_row = $result->fetch_assoc();
            $admin_user_id = $admin_row['u_id'];
        } else {
            $errors[] = "Error: No admin user found!";
        }
        $stmt->close();

        if (empty($errors)) {
            // Insert comment into database
            $stmt = $conn->prepare("INSERT INTO comment (u_id, name, email, content, date, status) VALUES (?, ?, ?, ?, ?, 'unread')");
            $stmt->bind_param("sssss", $admin_user_id, $fname, $email, $comment, $date);
            if ($stmt->execute()) {
                $success = "Your message has been sent successfully!";
            } else {
                $errors[] = "Error: Unable to send comment. Please try again.";
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
    <title>Submit Comment - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2f4f4f;
            --secondary-color: #4a6fa5;
            --accent-color: #e63946;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --border-radius: 12px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f9ff 0%, #e8f4ff 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Full Screen Container */
        .fullscreen-container {
            width: 100vw;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .fullscreen-header {
            background: linear-gradient(to right, var(--primary-color), #3a5f5f);
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .logo {
            height: 60px;
            width: auto;
            border-radius: 10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .system-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .user-display {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 25px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .user-display i {
            font-size: 1.3rem;
            color: #ffd700;
        }

        .user-name {
            font-weight: 500;
            font-size: 1.1rem;
            color: white;
        }

        /* Navigation */
        .fullscreen-nav {
            background: white;
            padding: 0 50px;
            border-bottom: 1px solid #eaeaea;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .nav-list {
            display: flex;
            list-style: none;
            justify-content: center;
        }

        .nav-list li {
            border-right: 1px solid #f0f0f0;
        }

        .nav-list li:last-child {
            border-right: none;
        }

        .nav-list a {
            display: block;
            padding: 22px 35px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 1.1rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-list a:hover {
            background: linear-gradient(to right, rgba(74, 111, 165, 0.1), rgba(47, 79, 79, 0.05));
            color: var(--secondary-color);
        }

        .nav-list a.active {
            color: var(--secondary-color);
            background: linear-gradient(to right, rgba(74, 111, 165, 0.1), rgba(47, 79, 79, 0.05));
            border-bottom: 3px solid var(--secondary-color);
        }

        .nav-list a i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .fullscreen-main {
            flex: 1;
            display: flex;
            padding: 50px;
            gap: 50px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        /* Form Container */
        .form-container {
            flex: 2;
            background: white;
            border-radius: var(--border-radius);
            padding: 50px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .form-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .form-header h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }

        .form-header h1:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            border-radius: 2px;
        }

        .form-header p {
            font-size: 1.2rem;
            color: #666;
            max-width: 700px;
            margin: 25px auto 0;
            line-height: 1.8;
        }

        /* Messages */
        .message-box {
            margin-bottom: 35px;
            border-radius: 15px;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: slideIn 0.5s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 5px solid var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-left: 5px solid var(--accent-color);
            border: 1px solid #f5c6cb;
        }

        .message-icon {
            font-size: 2.5rem;
        }

        /* Form Styling */
        .comment-form {
            display: flex;
            flex-direction: column;
            gap: 35px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .form-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-label i {
            color: var(--secondary-color);
            font-size: 1.3rem;
            width: 24px;
        }

        .form-input {
            padding: 18px 25px;
            background: #f9fbfd;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 1.1rem;
            color: var(--dark-color);
            transition: var(--transition);
            outline: none;
        }

        .form-input:focus {
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 111, 165, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: #a0aec0;
        }

        .form-textarea {
            padding: 20px 25px;
            background: #f9fbfd;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 1.1rem;
            color: var(--dark-color);
            min-height: 220px;
            resize: vertical;
            transition: var(--transition);
            outline: none;
            font-family: inherit;
            line-height: 1.6;
        }

        .form-textarea:focus {
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 4px rgba(74, 111, 165, 0.1);
            transform: translateY(-2px);
        }

        .form-textarea::placeholder {
            color: #a0aec0;
        }

        .character-counter {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            color: #718096;
            padding: 0 5px;
        }

        /* Info Panel */
        .info-panel {
            flex: 1;
            background: linear-gradient(135deg, white, #f8fafc);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-content {
            text-align: center;
        }

        .info-icon {
            font-size: 5rem;
            color: var(--secondary-color);
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .info-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 15px;
        }

        .info-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            border-radius: 2px;
        }

        .info-text {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #4a5568;
            margin-bottom: 40px;
        }

        .feature-list {
            list-style: none;
            margin-top: 30px;
        }

        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border-left: 4px solid var(--secondary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
        }

        .feature-list li:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .feature-list i {
            color: var(--secondary-color);
            font-size: 1.5rem;
            margin-top: 3px;
        }

        .feature-list div {
            text-align: left;
        }

        .feature-list strong {
            display: block;
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .feature-list p {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f1f5f9;
        }

        .btn {
            padding: 18px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 180px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            box-shadow: 0 10px 20px rgba(74, 111, 165, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(74, 111, 165, 0.3);
            background: linear-gradient(135deg, #5a7fb5, #3f5f6f);
        }

        .btn-secondary {
            background: white;
            color: var(--dark-color);
            border: 2px solid #e2e8f0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: #f7fafc;
            transform: translateY(-3px);
            border-color: #cbd5e0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* Footer */
        .fullscreen-footer {
            background: var(--primary-color);
            padding: 30px 50px;
            text-align: center;
            margin-top: auto;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .copyright {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .system-info {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .fullscreen-main {
                flex-direction: column;
                padding: 30px;
            }
            
            .info-panel, .form-container {
                width: 100%;
            }
            
            .form-header h1 {
                font-size: 2.3rem;
            }
            
            .info-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .fullscreen-header, .fullscreen-nav, .fullscreen-footer {
                padding: 15px 20px;
            }
            
            .fullscreen-main {
                padding: 20px;
                gap: 30px;
            }
            
            .form-container, .info-panel {
                padding: 30px;
            }
            
            .nav-list {
                flex-direction: column;
            }
            
            .nav-list li {
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .nav-list a {
                padding: 18px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Floating Animation for Info Panel */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .info-panel {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Full Screen Container -->
    <div class="fullscreen-container">
        <!-- Header -->
        <header class="fullscreen-header animate-fade-in">
            <div class="header-left">
                <img src="img/logo.jpg" alt="Voting System Logo" class="logo">
                <div class="system-title">
                    <h1>DTUSU Voting System</h1>
                </div>
            </div>
            
            <div class="user-display">
                <i class="fas fa-user-circle"></i>
                <span class="user-name"><?php echo $fullName; ?></span>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="fullscreen-nav">
            <ul class="nav-list">
                <li><a href="voter.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="v_change.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="cast.php"><i class="fas fa-vote-yea"></i> Cast Vote</a></li>
                <li><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li class="active"><a href="voter_comment.php"><i class="fas fa-comment"></i> Comment</a></li>
                <li><a href="v_result.php"><i class="fas fa-chart-bar"></i> Result</a></li>
                <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="fullscreen-main">
            <!-- Info Panel -->
            <aside class="info-panel animate-fade-in">
                <div class="info-content">
                    <div class="info-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="info-title">Your Feedback Matters</h2>
                    <p class="info-text">
                        Help us improve the voting experience by sharing your valuable feedback. 
                        Your comments are directly reviewed by the election administration team.
                    </p>
                    
                    <ul class="feature-list">
                        <li>
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Secure Communication</strong>
                                <p>All feedback is encrypted and handled confidentially</p>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Quick Response Time</strong>
                                <p>We aim to respond to all feedback within 24 hours</p>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-star"></i>
                            <div>
                                <strong>Help Improve the System</strong>
                                <p>Your suggestions help us enhance the voting experience</p>
                            </div>
                        </li>
                        <li>
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>Direct to Administration</strong>
                                <p>Your feedback goes directly to the election administrators</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Form Container -->
            <div class="form-container animate-fade-in">
                <div class="form-header">
                    <h1><i class="fas fa-paper-plane"></i> Submit Your Feedback</h1>
                    <p>Please fill out the form below to send your comments, suggestions, or concerns to our election administration team.</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="message-box message-success">
                        <i class="fas fa-check-circle message-icon"></i>
                        <div>
                            <h3 style="color: #155724; margin-bottom: 5px;">Success!</h3>
                            <p style="color: #155724;"><?php echo $success; ?></p>
                        </div>
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'voter_comment.php';
                        }, 3000);
                    </script>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="message-box message-error">
                        <i class="fas fa-exclamation-circle message-icon"></i>
                        <div>
                            <h3 style="color: #721c24; margin-bottom: 5px;">Please fix the following errors:</h3>
                            <ul style="margin-left: 20px; margin-top: 5px; color: #721c24;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="commentForm" class="comment-form" method="POST" action="voter_comment.php" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label class="form-label" for="fname">
                            <i class="fas fa-user"></i> Your Full Name
                        </label>
                        <input type="text" id="fname" name="fname" class="form-input" 
                               placeholder="Enter your full name (5-25 characters)" 
                               value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : $fullName; ?>"
                               required>
                        <div class="character-counter">
                            <span id="nameCount">0 characters</span>
                            <span>Maximum: 25 characters</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email address"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="com">
                            <i class="fas fa-comment-dots"></i> Your Feedback
                        </label>
                        <textarea id="com" name="com" class="form-textarea" 
                                  placeholder="Please share your detailed feedback, suggestions, or concerns here (3-500 characters)..."
                                  required><?php echo isset($_POST['com']) ? htmlspecialchars($_POST['com']) : ''; ?></textarea>
                        <div class="character-counter">
                            <span id="commentCount">0 characters</span>
                            <span>Maximum: 500 characters</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Clear Form
                        </button>
                        <button type="submit" name="sent" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Feedback
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <footer class="fullscreen-footer">
            <div class="footer-content">
                <div class="copyright">
                    <p>Copyright &copy; <?php echo date("Y"); ?> Ethiopian Electoral Commission | Secure Online Voting Platform</p>
                </div>
                <div class="system-info">
                    <p>Version 3.0 | <?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Character counters
        const nameInput = document.getElementById('fname');
        const commentInput = document.getElementById('com');
        const nameCount = document.getElementById('nameCount');
        const commentCount = document.getElementById('commentCount');

        function updateCounters() {
            nameCount.textContent = nameInput.value.length + ' characters';
            commentCount.textContent = commentInput.value.length + ' characters';
            
            // Update colors based on limits
            if (nameInput.value.length > 25) {
                nameCount.style.color = '#e63946';
                nameCount.style.fontWeight = 'bold';
            } else if (nameInput.value.length >= 5) {
                nameCount.style.color = '#28a745';
                nameCount.style.fontWeight = '600';
            } else {
                nameCount.style.color = '#718096';
                nameCount.style.fontWeight = 'normal';
            }
            
            if (commentInput.value.length > 500) {
                commentCount.style.color = '#e63946';
                commentCount.style.fontWeight = 'bold';
            } else if (commentInput.value.length >= 3) {
                commentCount.style.color = '#28a745';
                commentCount.style.fontWeight = '600';
            } else {
                commentCount.style.color = '#718096';
                commentCount.style.fontWeight = 'normal';
            }
        }

        nameInput.addEventListener('input', updateCounters);
        commentInput.addEventListener('input', updateCounters);
        
        // Initialize counters
        updateCounters();

        // Form validation
        function validateForm() {
            const email = document.getElementById('email');
            const fname = document.getElementById('fname');
            const message = document.getElementById('com');
            const emailExp = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-Z0-9]{2,4}$/;

            if (!email.value.match(emailExp)) {
                alert("Please enter a valid email address");
                email.focus();
                return false;
            }
            
            if (fname.value.length < 5 || fname.value.length > 25) {
                alert("Please enter a name between 5 and 25 characters");
                fname.focus();
                return false;
            }
            
            if (message.value.length < 3 || message.value.length > 500) {
                alert("Please enter a comment between 3 and 500 characters");
                message.focus();
                return false;
            }
            
            // Show loading animation
            const submitBtn = document.querySelector('button[name="sent"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            return true;
        }

        // Form animation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.comment-form');
            form.style.opacity = '0';
            form.style.transform = 'translateY(30px)';
            form.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            
            setTimeout(() => {
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 300);
            
            // Input focus effects
            const inputs = document.querySelectorAll('.form-input, .form-textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.boxShadow = '0 0 0 4px rgba(74, 111, 165, 0.2)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>