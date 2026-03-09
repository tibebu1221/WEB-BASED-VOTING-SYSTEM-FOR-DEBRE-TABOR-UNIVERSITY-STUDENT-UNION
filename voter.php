<?php
include("connection.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data using MySQLi
$user_id = $_SESSION['u_id'];

// First, check what columns exist in the voter table
$checkColumns = $conn->query("SHOW COLUMNS FROM voter LIKE 'profile_image'");
$hasProfileImage = ($checkColumns->num_rows > 0);

// Prepare query based on available columns
if ($hasProfileImage) {
    $stmt = $conn->prepare("SELECT fname, mname, profile_image FROM voter WHERE vid = ?");
} else {
    $stmt = $conn->prepare("SELECT fname, mname FROM voter WHERE vid = ?");
}

$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $firstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    
    // Set profile image with fallback
    if ($hasProfileImage && !empty($row['profile_image'])) {
        $profileImage = $row['profile_image'];
    } else {
        $profileImage = 'img/default-avatar.png';
        // You can also use a default avatar from online service:
        // $profileImage = 'https://ui-avatars.com/api/?name=' . urlencode($firstName . '+' . $middleName) . '&background=4a6fa5&color=fff&size=150';
    }
} else {
    // If user not found, redirect to login
    header('Location: login.php');
    exit();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2f4f4f;
            --secondary-color: #4a6fa5;
            --accent-color: #e63946;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
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
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
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
            height: 60px;
            width: auto;
        }

        .system-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background-color: #4a6fa5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .welcome-text {
            font-size: 1rem;
        }

        .welcome-text h3 {
            font-weight: 500;
            margin-bottom: 3px;
        }

        /* Navigation */
        .nav-container {
            background-color: white;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            margin-top: 20px;
            overflow: hidden;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .nav-menu li {
            flex: 1;
            text-align: center;
            border-right: 1px solid #eee;
        }

        .nav-menu li:last-child {
            border-right: none;
        }

        .nav-menu a {
            display: block;
            padding: 18px 15px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-menu a:hover {
            background-color: var(--light-color);
            color: var(--secondary-color);
        }

        .nav-menu a.active {
            color: var(--secondary-color);
            background-color: rgba(74, 111, 165, 0.1);
            border-bottom: 3px solid var(--secondary-color);
        }

        .nav-menu a i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        .welcome-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-card h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 2.2rem;
        }

        .welcome-card p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .user-info-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .user-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid var(--light-color);
            object-fit: cover;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #4a6fa5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 36px;
        }

        .user-avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .user-role {
            color: var(--secondary-color);
            font-weight: 500;
            background-color: rgba(74, 111, 165, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
        }

        /* Quick Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 0.95rem;
        }

        .icon-1 {
            background-color: var(--secondary-color);
        }
        
        .icon-2 {
            background-color: var(--success-color);
        }
        
        .icon-3 {
            background-color: var(--warning-color);
        }
        
        .icon-4 {
            background-color: var(--accent-color);
        }

        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 25px 0;
            margin-top: 40px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        .footer-logo {
            height: 40px;
            width: auto;
        }

        .copyright {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .system-version {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Notifications */
        .notification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .nav-menu {
                flex-direction: column;
                display: none;
            }
            
            .nav-menu.active {
                display: flex;
            }
            
            .nav-menu li {
                border-right: none;
                border-bottom: 1px solid #eee;
            }
        }

        /* Dashboard Alert */
        .dashboard-alert {
            background: linear-gradient(to right, #4a6fa5, #2f4f4f);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
        }

        .dashboard-alert i {
            font-size: 1.5rem;
        }

        .dashboard-alert p {
            margin: 0;
            flex: 1;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .action-btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .action-btn-secondary {
            background-color: var(--light-color);
            color: var(--dark-color);
            border: 1px solid #ddd;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container header-content">
            <div class="logo-section">
                <img src="img/logo.JPG" alt="Voting System Logo" class="logo">
                <div class="system-name">DTUSU Voting System</div>
            </div>
            
            <div class="user-profile">
                <?php if (strpos($profileImage, 'http') === 0 || file_exists($profileImage)): ?>
                    <img src="<?php echo $profileImage; ?>" alt="User Avatar" class="avatar-img">
                <?php else: ?>
                    <div class="avatar">
                        <?php echo strtoupper(substr($firstName, 0, 1) . substr($middleName, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="welcome-text">
                    <h3><?php echo $firstName . ' ' . $middleName; ?></h3>
                    <p>Voter Dashboard</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Dashboard Alert -->
        <div class="dashboard-alert">
            <i class="fas fa-info-circle"></i>
            <p>Welcome to your voter dashboard! You can cast your vote, view candidates, and check election results from here.</p>
        </div>

        <!-- Navigation -->
        <nav class="nav-container">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="voter.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="v_change.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="cast.php"><i class="fas fa-vote-yea"></i> Cast Vote</a></li>
                <li><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="voter_comment.php"><i class="fas fa-comment"></i> Comment</a></li>
                <li><a href="v_resultv.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="welcome-card">
                <h1>Welcome to Your Voter Dashboard</h1>
                <p>This is your central hub for participating in the online voting process. From here, you can view candidates, cast your vote, see election results, and provide feedback.</p>
                <p>Your vote is your voice. Make sure to exercise your democratic right responsibly.</p>
                <div style="margin-top: 20px; padding: 15px; background-color: rgba(74, 111, 165, 0.1); border-radius: var(--border-radius);">
                    <h3 style="color: var(--secondary-color); margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Important Notice</h3>
                    <p>Voting period: <?php echo date('F j, Y'); ?> - <?php echo date('F j, Y', strtotime('+7 days')); ?></p>
                </div>
            </div>
            
            <div class="user-info-card">
                <?php if (strpos($profileImage, 'http') === 0 || file_exists($profileImage)): ?>
                    <img src="<?php echo $profileImage; ?>" alt="User Avatar" class="user-avatar-img">
                <?php else: ?>
                    <div class="user-avatar-large">
                        <?php echo strtoupper(substr($firstName, 0, 1) . substr($middleName, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h2 class="user-name"><?php echo $firstName . ' ' . $middleName; ?></h2>
                <p>Registered Voter</p>
                <div class="user-role">Voter ID: <?php echo substr($user_id, 0, 8) . '...'; ?></div>
                
                <div style="margin-top: 25px; width: 100%;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px; text-align: center;">Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="cast.php" class="action-btn action-btn-primary">
                            <i class="fas fa-vote-yea"></i> Vote Now
                        </a>
                        <a href="voter_candidate.php" class="action-btn action-btn-secondary">
                            <i class="fas fa-users"></i> View Candidates
                        </a>
                        <a href="v_resultv.php" class="action-btn action-btn-secondary">
                            <i class="fas fa-chart-bar"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon icon-1">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Verified</h3>
                    <p>Account Status</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-2">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Active</h3>
                    <p>Voting Status</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-3">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>7 days</h3>
                    <p>Voting Period</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon icon-4">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Secure</h3>
                    <p>Voting System</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div>
                <img src="img/ethio_flag.JPG" alt="Ethiopia Flag" class="footer-logo">
            </div>
            <div class="copyright">
                <p>Copyright &copy; <?php echo date("Y"); ?> National Election Board of Ethiopia. All rights reserved.</p>
                <p style="margin-top: 5px; font-size: 0.9rem;">This is a secure online voting platform.</p>
            </div>
            <div class="system-version">
                <p>v2.1.0 | Last updated: <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
            
            // Change icon
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('navMenu').classList.remove('active');
                    document.querySelector('#mobileMenuToggle i').classList.remove('fa-times');
                    document.querySelector('#mobileMenuToggle i').classList.add('fa-bars');
                }
            });
        });

        // Welcome message animation
        document.addEventListener('DOMContentLoaded', function() {
            const welcomeCard = document.querySelector('.welcome-card');
            welcomeCard.style.opacity = '0';
            welcomeCard.style.transform = 'translateY(20px)';
            welcomeCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                welcomeCard.style.opacity = '1';
                welcomeCard.style.transform = 'translateY(0)';
            }, 300);
            
            // Animate stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 500 + (index * 100));
            });
        });
    </script>
</body>
</html>

<?php
$conn->close(); // Close the MySQLi connection
?>