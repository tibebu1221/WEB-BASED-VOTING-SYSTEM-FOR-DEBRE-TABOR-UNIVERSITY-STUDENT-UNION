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

$candidate_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT u_id, fname, mname FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $candidate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_id = $row['u_id'];
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
} else {
    die("Error: Candidate not found in the database.");
}
$stmt->close();

$stmt = $conn->prepare("SELECT u_id FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Error: User ID does not exist in the user table.");
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Comment | Candidate Portal</title>
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

        /* Comment Form Card */
        .comment-form-card {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .form-header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 20px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-input[readonly] {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
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

        .error {
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
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Info Card */
        .info-card {
            flex: 0 0 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .info-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card ul {
            list-style: none;
            padding: 0;
        }

        .info-card li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card li:last-child {
            border-bottom: none;
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
            
            .info-card {
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
            
            .comment-form-card {
                padding: 20px;
            }
            
            .form-header {
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
                    <a href="can_change.php" class="nav-link">
                        <i class="fas fa-key"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_candidate.php" class="nav-link">
                        <i class="fas fa-users"></i> Candidates
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_comment.php" class="nav-link active">
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
            <!-- Comment Form -->
            <div class="comment-form-card">
                <div class="form-header">
                    <h2><i class="fas fa-comment-dots"></i> Submit Your Comment</h2>
                    <p>Share your feedback, suggestions, or concerns with the election commission</p>
                </div>

                <?php
                if (isset($_POST['sent'])) {
                    $date = date("d/m/Y");
                    $fname = $_POST['fname'];
                    $email = $_POST['email'];
                    $content = $_POST['com'];

                    $stmt = $conn->prepare("INSERT INTO comment (u_id, name, email, content, date, status) VALUES (?, ?, ?, ?, ?, 'unread')");
                    $stmt->bind_param("sssss", $user_id, $fname, $email, $content, $date);
                    if ($stmt->execute()) {
                        echo '<div class="message success">';
                        echo '<i class="fas fa-check-circle"></i> Your comment has been sent successfully!';
                        echo '</div>';
                        echo '<script>setTimeout(function(){ window.location.href = "can_comment.php"; }, 3000);</script>';
                    } else {
                        echo '<div class="message error">';
                        echo '<i class="fas fa-exclamation-circle"></i> Error: Unable to send comment. Please try again.';
                        echo '</div>';
                    }
                    $stmt->close();
                }
                ?>

                <form id="form1" name="login" method="POST" action="can_comment.php" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label class="form-label" for="user_id">
                            <i class="fas fa-id-card"></i> User ID
                        </label>
                        <input type="text" id="user_id" name="user_id" class="form-input" 
                               readonly value="<?php echo htmlspecialchars($user_id); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fname">
                            <i class="fas fa-user"></i> Your Full Name
                        </label>
                        <input type="text" id="fname" name="fname" class="form-input" 
                               placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email address" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="com">
                            <i class="fas fa-comment"></i> Your Message
                        </label>
                        <textarea id="com" name="com" class="form-textarea" 
                                  placeholder="Write your comment here..." required></textarea>
                    </div>

                    <button type="submit" name="sent" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Comment
                    </button>
                </form>
            </div>

            <!-- Info Sidebar -->
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Comment Guidelines</h3>
                <ul>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Be respectful and constructive</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Provide clear and specific feedback</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Include relevant details</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Avoid personal attacks</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Stay on topic</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Your comments are confidential</span>
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

        // Form validation
        function validateForm() {
            const email = document.getElementById('email');
            const fname = document.getElementById('fname');
            const message = document.getElementById('com');

            // Validate email
            const emailExp = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-Z0-9]{2,4}$/;
            if (!email.value.match(emailExp)) {
                alert('Please enter a valid email address');
                email.focus();
                return false;
            }

            // Validate name length (5-25 characters)
            if (fname.value.length < 5 || fname.value.length > 25) {
                alert('Please enter between 5 and 25 characters for your full name');
                fname.focus();
                return false;
            }

            // Validate message length (3-500 characters)
            if (message.value.length < 3 || message.value.length > 500) {
                alert('Please enter between 3 and 500 characters for your comment');
                message.focus();
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