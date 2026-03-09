<?php
session_start();
include("connection.php");

// BULLETPROOF AUTHORIZATION – Accepts any variation of department role
$valid_roles = ['department', 'Department', 'DEP', 'dep', 'Department Officer', 'registrar', 'Registrar'];

if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $valid_roles, true)) {
    echo "<script>alert('Unauthorized Access! Please login as Department Officer.'); window.location='login.php';</script>";
    exit();
}

// Normalize role for consistency
$_SESSION['role'] = 'department';

$user_id = $_SESSION['u_id'];
// Get user information
$stmt = $conn->prepare("SELECT fname, mname, lname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$FirstName = htmlspecialchars($user['fname'] ?? 'Officer');
$middleName = htmlspecialchars($user['mname'] ?? '');
$LastName = htmlspecialchars($user['lname'] ?? '');
$stmt->close();

// Get dashboard stats with error handling
$stats = [
    'total_students' => 0,
    'active_candidates' => 0,
    'total_voters' => 0,
    'pending_requests' => 0
];

try {
    // Count total students (users with role='student')
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE role = 'student' AND status = 1");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_students'] = $result['count'] ?? 0;
    $stmt->close();

    // Count active candidates (users with role='candidate' and status=1)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE role = 'candidate' AND status = 1");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['active_candidates'] = $result['count'] ?? 0;
    $stmt->close();

    // Count total voters (you might have a voters table or use user table with voting rights)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE status = 1");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_voters'] = $result['count'] ?? 0;
    $stmt->close();

    // For pending requests - adjust based on your actual table structure
    // If you have a requests table, use it. Otherwise, we'll use a default value.
    $stats['pending_requests'] = 0; // Default value
    
    // Try to get actual pending requests if you have a table for it
    // $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'");
    // if($stmt) {
    //     $stmt->execute();
    //     $result = $stmt->get_result()->fetch_assoc();
    //     $stats['pending_requests'] = $result['count'] ?? 0;
    //     $stmt->close();
    // }
    
} catch (Exception $e) {
    // If any query fails, use default values
    error_log("Database error in dep.php: " . $e->getMessage());
    // Continue with default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Representatives Dashboard | DTUSU Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-light: #2c3e8f;
            --secondary: #b21f1f;
            --accent: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #95a5a6;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .logo:hover {
            transform: rotate(-5deg) scale(1.05);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .header-title h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            background: linear-gradient(45deg, #fff, #b1c4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title p {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            transition: var(--transition);
            cursor: pointer;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 1.2rem;
        }

        .avatar-initials {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .user-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.125rem;
        }

        .user-info .role {
            font-size: 0.8rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .role i {
            font-size: 0.7rem;
        }

        /* Navigation Styles */
        .dashboard-nav {
            background: white;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 88px;
            z-index: 999;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 0;
        }

        .nav-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .nav-link {
            color: var(--dark);
            text-decoration: none;
            padding: 1.25rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 42, 108, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: linear-gradient(to bottom, rgba(26, 42, 108, 0.05), transparent);
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: linear-gradient(to bottom, rgba(26, 42, 108, 0.08), transparent);
        }

        .nav-icon {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .nav-text {
            font-size: 0.9rem;
        }

        /* Main Content */
        .dashboard-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, white, #f8f9ff);
            border-radius: var(--border-radius);
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26, 42, 108, 0.05), transparent);
            border-radius: 50%;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, transparent, rgba(178, 31, 31, 0.05));
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .welcome-content h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .welcome-content h2 .highlight {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .welcome-content h2 .highlight::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .welcome-content p {
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, var(--warning), #f1c40f); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, var(--success), #27ae60); }
        .stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, var(--accent), #2980b9); }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .stat-info p {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--box-shadow-lg);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 42, 108, 0.03), transparent);
            opacity: 0;
            transition: var(--transition);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 20px rgba(26, 42, 108, 0.2);
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: rotateY(180deg);
        }

        .feature-content h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .feature-content h3 a {
            color: inherit;
            text-decoration: none;
            transition: var(--transition);
        }

        .feature-content h3 a:hover {
            color: var(--secondary);
        }

        .feature-content p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .feature-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary);
            border-radius: 50px;
            transition: var(--transition);
            background: white;
        }

        .feature-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(5px);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--primary);
            color: white;
            padding: 2rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }

        .footer-container p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.7;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--secondary);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .nav-link {
                padding: 1rem 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .header-container {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .user-profile {
                width: 100%;
                justify-content: center;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 1 0 auto;
            }
            
            .welcome-section {
                padding: 2rem;
            }
            
            .welcome-content h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header, .nav-container, .dashboard-main {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .nav-link {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .nav-icon {
                font-size: 1.1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
            
            .welcome-content h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .user-profile {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .stat-card, .feature-card {
                padding: 1.5rem;
            }
            
            .stat-content {
                flex-direction: column;
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

        .welcome-section, .stat-card, .feature-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(1) { animation-delay: 0.5s; }
        .feature-card:nth-child(2) { animation-delay: 0.6s; }
        .feature-card:nth-child(3) { animation-delay: 0.7s; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate avatar initials
            function generateInitials(name) {
                return name.split(' ')
                    .map(word => word.charAt(0))
                    .join('')
                    .toUpperCase()
                    .substring(0, 2);
            }

            // Set avatar initials
            const userName = "<?php echo $FirstName . ' ' . $LastName; ?>";
            const initials = generateInitials(userName);
            const avatar = document.querySelector('.avatar-initials');
            if (avatar) {
                avatar.textContent = initials;
            }

            // Add active state to current nav item
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (currentPage === linkHref) {
                    link.classList.add('active');
                }
            });

            // Add click effect to cards
            const cards = document.querySelectorAll('.stat-card, .feature-card');
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('a')) {
                        this.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });

            // Update real-time data if needed
            function updateStats() {
                const stats = document.querySelectorAll('.stat-info h3');
                stats.forEach(stat => {
                    const value = parseInt(stat.textContent.replace(/,/g, ''));
                    if (!isNaN(value)) {
                        // Animate number increment
                        animateValue(stat, 0, value, 1000);
                    }
                });
            }

            function animateValue(element, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    element.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString();
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }

            // Initialize animations
            setTimeout(updateStats, 500);

            // Add hover effect to feature links
            const featureLinks = document.querySelectorAll('.feature-link');
            featureLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.querySelector('i').style.transform = 'translateX(5px)';
                });
                link.addEventListener('mouseleave', function() {
                    this.querySelector('i').style.transform = 'translateX(0)';
                });
            });

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === '1') window.location.href = 'dep.php';
                if (e.ctrlKey && e.key === '2') window.location.href = 'dep_manage_accounts.php';
                if (e.ctrlKey && e.key === 'l') window.location.href = 'logout.php';
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
            });
        });
    </script>
</head>
<body>
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="dep.php" class="logo">
                    <img src="img/logo.JPG" alt="DTUSU Logo">
                </a>
                <div class="header-title">
                    <h1>Department Officer Dashboard</h1>
                    <p>DTUSU Voting System - Student Representative Management</p>
                </div>
            </div>
            
            <div class="user-profile">
                <div class="avatar">
                    <div class="avatar-initials"></div>
                </div>
                <div class="user-info">
                    <h3><?php echo "$FirstName $middleName $LastName"; ?></h3>
                    <div class="role">
                        <i class="fas fa-shield-alt"></i>
                        <span>Department Officer</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="dashboard-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dep.php" class="nav-link active">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dep_manage_accounts.php" class="nav-link">
                        <i class="fas fa-users-graduate nav-icon"></i>
                        <span class="nav-text">Manage Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="dashboard-main">
        <section class="welcome-section">
            <div class="welcome-content">
                <h2>Welcome Back, <span class="highlight"><?php echo "$FirstName $middleName"; ?></span>!</h2>
                <p>You have full administrative control over student representatives, and candidate nominations. Monitor activities and manage the electoral process efficiently.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_students']); ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['active_candidates']); ?></h3>
                            <p>Active Candidates</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_voters']); ?></h3>
                            <p>Total Voters</p>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['pending_requests']); ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="feature-content">
                    <h3><a href="dep_manage_accounts.php">Manage Student Accounts</a></h3>
                    <p>Add, edit, and manage student profiles. View comprehensive student information and manage voter registration status.</p>
                    <a href="dep_manage_accounts.php" class="feature-link">
                        <span>Manage Students</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <div class="footer-container">
            <p>© <?php echo date("Y"); ?> DTUSU Online Voting System – Department Officer Panel</p>
            <div class="footer-links">
                <a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a>
                <a href="privacy.php"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                <a href="contact.php"><i class="fas fa-envelope"></i> Contact Support</a>
            </div>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>