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
    } else {
        echo '<script>alert("Error: User not found in the database."); window.location = "login.php";</script>';
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
    <title>Candidate Portal | Online Voting System</title>
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

        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #3498db, #2ecc71);
        }

        .welcome-title {
            font-size: 42px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 800;
            background: linear-gradient(to right, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-text {
            font-size: 20px;
            color: #34495e;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .user-greeting {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 40px;
            font-weight: 600;
        }

        .user-greeting span {
            color: #3498db;
            text-transform: capitalize;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-top: 50px;
        }

        .feature-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .feature-item:hover {
            transform: translateY(-10px);
            border-color: #3498db;
            box-shadow: 0 15px 30px rgba(52, 152, 219, 0.15);
        }

        .feature-icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 20px;
            height: 80px;
            width: 80px;
            line-height: 80px;
            background: white;
            border-radius: 50%;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .feature-desc {
            font-size: 15px;
            color: #7f8c8d;
            line-height: 1.6;
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

        @media (max-width: 992px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .welcome-card {
                padding: 40px 30px;
            }
            
            .welcome-title {
                font-size: 36px;
            }
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
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-title {
                font-size: 30px;
            }
            
            .user-greeting {
                font-size: 24px;
            }
            
            .welcome-text {
                font-size: 18px;
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
                    <a href="candidate_view.php" class="nav-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="post.php" class="nav-link">
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
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h1 class="welcome-title">Welcome to Candidate Portal</h1>
                
                <div class="user-greeting">
                    Hello, <span><?php echo htmlspecialchars($FirstName); ?></span>! 
                </div>
                
                <p class="welcome-text">
                    Welcome to your dedicated dashboard for the Ethiopian National Election Commission's Online Voting System. 
                    Here you can manage your campaign, interact with voters, post updates, and monitor election progress 
                    in real-time through our secure and transparent platform.
                </p>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3 class="feature-title">Campaign Updates</h3>
                        <p class="feature-desc">Share your latest news and campaign activities with voters</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Live Analytics</h3>
                        <p class="feature-desc">Track election results and voter engagement in real-time</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="feature-title">Voter Feedback</h3>
                        <p class="feature-desc">Respond to comments and engage with your constituents</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Secure Platform</h3>
                        <p class="feature-desc">Military-grade encryption ensures election integrity</p>
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
    </script>
</body>
</html>