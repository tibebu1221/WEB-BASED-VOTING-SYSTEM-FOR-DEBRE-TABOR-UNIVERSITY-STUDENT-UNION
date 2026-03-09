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

$stmt = $conn->prepare("SELECT fname, mname FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
} else {
    $stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $FirstName = $row['fname'];
        $middleName = $row['mname'];
        
        $stmt2 = $conn->prepare("SELECT fname, mname FROM candidate WHERE username = (SELECT username FROM user WHERE u_id = ?)");
        $stmt2->bind_param("s", $user_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        if ($result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $FirstName = $row2['fname'];
            $middleName = $row2['mname'];
        }
        $stmt2->close();
    } else {
        echo '<script>alert("Error: User not found in the database. Please contact administrator."); window.location = "login.php";</script>';
        exit();
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post News | Candidate Portal</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(to right, #2c3e50, #34495e);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            height: 80px;
            width: auto;
            border-radius: 10px;
        }

        .system-title h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(to right, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .system-title p {
            font-size: 14px;
            opacity: 0.8;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 25px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info i {
            font-size: 20px;
            color: #3498db;
        }

        .user-info span {
            font-weight: 600;
            font-size: 16px;
        }

        .navbar {
            background: white;
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            border-radius: 15px;
            overflow: hidden;
        }

        .nav-item {
            flex: 1;
            text-align: center;
        }

        .nav-link {
            display: block;
            padding: 20px 15px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border-bottom: 4px solid transparent;
        }

        .nav-link i {
            margin-right: 8px;
            font-size: 18px;
        }

        .nav-link:hover {
            background: linear-gradient(to right, #3498db15, #2ecc7115);
            color: #3498db;
            border-bottom: 4px solid #3498db;
        }

        .nav-link.active {
            background: linear-gradient(to right, #3498db, #2ecc71);
            color: white;
            border-bottom: 4px solid #2980b9;
        }

        .main-content {
            margin-bottom: 30px;
        }

        .post-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .post-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #3498db, #2ecc71);
        }

        .page-title {
            font-size: 36px;
            color: #2c3e50;
            margin-bottom: 40px;
            font-weight: 800;
            text-align: center;
            background: linear-gradient(to right, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-greeting {
            text-align: center;
            margin-bottom: 40px;
            font-size: 20px;
            color: #2c3e50;
        }

        .user-greeting span {
            color: #3498db;
            font-weight: 600;
            text-transform: capitalize;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            background: white;
        }

        .form-textarea {
            width: 100%;
            padding: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            min-height: 200px;
            resize: vertical;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: inherit;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            background: white;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(to right, #3498db, #2ecc71);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e1e5e9;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }

        .message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            animation: fadeIn 0.5s ease;
        }

        .success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #155724;
        }

        .wrong {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #721c24;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 15px;
        }

        .footer {
            background: linear-gradient(to right, #2c3e50, #34495e);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .footer p {
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        .footer-links a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #2ecc71;
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .post-card {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                <span><?php echo htmlspecialchars($FirstName) . ' ' . htmlspecialchars($middleName); ?></span>
                <i class="fas fa-chevron-down"></i>
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
                    <a href="post.php" class="nav-link active">
                        <i class="fas fa-bullhorn"></i> Post News
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
                    <a href="can_change.php" class="nav-link">
                        <i class="fas fa-key"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="clogout.php" class="nav-link" style="color: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="post-container">
                <div class="post-card">
                    <h1 class="page-title">Post News</h1>
                    
                    <div class="user-greeting">
                        Welcome, <span><?php echo htmlspecialchars($FirstName); ?></span>! Share your campaign updates with voters.
                    </div>

                    <?php
                    if (isset($_POST['postn'])) {
                        $title = $_POST['titles'];
                        $content = $_POST['content'];
                        $date = date("d/m/Y");

                        $stmt = $conn->prepare("INSERT INTO event (title, content, posted_by, date) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $title, $content, $FirstName, $date);

                        if ($stmt->execute()) {
                            echo '<div class="message success">';
                            echo '<i class="fas fa-check-circle"></i> News posted successfully! Redirecting...';
                            echo '</div>';
                            echo '<script>setTimeout(function(){ window.location.href = "post.php"; }, 3000);</script>';
                        } else {
                            echo '<div class="message wrong">';
                            echo '<i class="fas fa-exclamation-circle"></i> Error: Unable to post. Please try again.';
                            echo '</div>';
                        }
                        $stmt->close();
                    }
                    ?>

                    <div class="form-header">
                        <h2><i class="fas fa-newspaper"></i> Create New Post</h2>
                        <p>Share important updates, campaign announcements, or news with your voters</p>
                    </div>

                    <form action="post.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label" for="title">
                                <i class="fas fa-heading"></i> Title
                            </label>
                            <input type="text" id="title" name="titles" class="form-input" 
                                   placeholder="Enter a compelling title for your post" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="content">
                                <i class="fas fa-align-left"></i> Content
                            </label>
                            <textarea id="content" name="content" class="form-textarea" 
                                      placeholder="Write your news content here. Be clear and engaging..." required></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" name="postn" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Publish Post
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Clear Form
                            </button>
                        </div>
                    </form>

                    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                        <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 18px;">
                            <i class="fas fa-lightbulb"></i> Tips for effective posts:
                        </h3>
                        <ul style="color: #7f8c8d; padding-left: 20px; line-height: 1.6;">
                            <li>Keep titles clear and attention-grabbing</li>
                            <li>Be concise but informative</li>
                            <li>Use proper formatting with paragraphs</li>
                            <li>Include relevant information for voters</li>
                            <li>Proofread before publishing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2023 Ethiopian National Election Commission. All rights reserved.</p>
            <p>Online Voting System v2.0 | Secure & Transparent Democratic Process</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                <a href="#"><i class="fas fa-file-contract"></i> Terms of Service</a>
                <a href="#"><i class="fas fa-phone-alt"></i> Contact Support</a>
                <a href="#"><i class="fas fa-question-circle"></i> Help Center</a>
            </div>
        </footer>
    </div>

    <script>
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Auto-resize textarea
        const textarea = document.getElementById('content');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>