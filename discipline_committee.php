<?php
session_start();
include("connection.php");

// Check if the user is logged in and has discipline_committee role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'discipline_committee') {
    header("Location: login.php");
    exit();
}

// Handle logout if requested
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Fetch user data using MySQLi
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname'] ?? '');
    $initials = strtoupper(substr($FirstName, 0, 1) . (!empty($middleName) ? substr($middleName, 0, 1) : ''));
} else {
    header("Location: logout.php");
    exit();
}
$stmt->close();

// Get pending requests count based on discipline_status
$pending_requests = 0;
$cleared_requests = 0;
$disciplinary_requests = 0;
$total_requests = 0;

// Check if discipline_status column exists
$check_column = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
if ($check_column && $check_column->num_rows === 0) {
    // Add discipline columns if they don't exist
    $conn->query("ALTER TABLE request ADD COLUMN discipline_status VARCHAR(50) DEFAULT 'pending'");
    $conn->query("ALTER TABLE request ADD COLUMN review_notes TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE request ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL");
}

// Get counts for different discipline statuses
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN discipline_status IS NULL OR discipline_status = '' OR discipline_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN discipline_status = 'clear' THEN 1 ELSE 0 END) as cleared,
    SUM(CASE WHEN discipline_status = 'disciplinary_action' THEN 1 ELSE 0 END) as disciplinary
    FROM request";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_requests = $row['total'] ?? 0;
    $pending_requests = $row['pending'] ?? 0;
    $cleared_requests = $row['cleared'] ?? 0;
    $disciplinary_requests = $row['disciplinary'] ?? 0;
}

// Get today's counts
$today = date('Y-m-d');
$today_sql = "SELECT 
    COUNT(*) as today_total,
    SUM(CASE WHEN DATE(submitted_at) = ? THEN 1 ELSE 0 END) as today_submitted,
    SUM(CASE WHEN DATE(reviewed_at) = ? AND discipline_status = 'clear' THEN 1 ELSE 0 END) as today_cleared,
    SUM(CASE WHEN DATE(reviewed_at) = ? AND discipline_status = 'disciplinary_action' THEN 1 ELSE 0 END) as today_rejected
    FROM request";

$stmt = $conn->prepare($today_sql);
$stmt->bind_param("sss", $today, $today, $today);
$stmt->execute();
$today_result = $stmt->get_result();

$today_cleared = 0;
$today_rejected = 0;
$today_submitted = 0;

if ($today_result->num_rows > 0) {
    $today_row = $today_result->fetch_assoc();
    $today_submitted = $today_row['today_submitted'] ?? 0;
    $today_cleared = $today_row['today_cleared'] ?? 0;
    $today_rejected = $today_row['today_rejected'] ?? 0;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discipline Committee | Election System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: linear-gradient(135deg, #1e1b4b, #312e81);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        
        /* Logout Button at Top */
        .top-logout-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            background: rgba(239, 68, 68, 0.15);
            color: #ff6b6b;
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
        }
        
        .top-logout-btn:hover {
            background: rgba(239, 68, 68, 0.25);
            color: #ff5252;
            border-color: rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }
        
        .logo-text h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .logo-text p {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 300;
        }
        
        .user-card {
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.05);
            margin: 20px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: capitalize;
        }
        
        .user-info p {
            font-size: 0.85rem;
            opacity: 0.8;
            color: #e0e0e0;
        }
        
        .nav-menu {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-item.active {
            background: rgba(139, 92, 246, 0.2);
            color: white;
            border-left: 4px solid var(--primary);
        }
        
        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nav-text {
            font-size: 0.95rem;
        }
        
        .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Bottom logout section - keeping as backup */
        .logout-section {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: none; /* Hidden now since we have top logout */
        }
        
        /* Logout Modal */
        .logout-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        .modal-content h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .modal-content p {
            color: var(--gray);
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
        }
        
        .modal-btn {
            flex: 1;
            padding: 12px;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }
        
        .modal-btn.cancel {
            background: var(--light);
            color: var(--gray);
        }
        
        .modal-btn.cancel:hover {
            background: #e5e7eb;
        }
        
        .modal-btn.confirm {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .modal-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .page-title h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .page-title p {
            color: var(--gray);
            font-size: 1rem;
        }
        
        .date-display {
            background: white;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            box-shadow: var(--card-shadow);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius);
            padding: 40px;
            margin-bottom: 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-100px, -100px) rotate(360deg); }
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
        }
        
        .welcome-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: capitalize;
        }
        
        .welcome-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .welcome-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-light {
            background: white;
            color: var(--primary);
        }
        
        .btn-light:hover {
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn-outline-light {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            transform: translateY(-2px);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }
        
        .stat-icon.pending { background: linear-gradient(135deg, var(--warning), #d97706); }
        .stat-icon.cleared { background: linear-gradient(135deg, var(--success), #059669); }
        .stat-icon.disciplinary { background: linear-gradient(135deg, var(--danger), #dc2626); }
        .stat-icon.total { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        /* Quick Tasks */
        .quick-tasks {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .task-card {
            background: var(--light);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            padding: 25px;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }
        
        .task-card:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--card-shadow);
        }
        
        .task-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .task-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .task-card p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-top: 40px;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: var(--radius-sm);
            background: var(--light);
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: #f3f4f6;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        
        .icon-review { background: var(--warning); }
        .icon-cleared { background: var(--success); }
        .icon-disciplinary { background: var(--danger); }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content h5 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .activity-content p {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 50px;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header,
            .user-card,
            .nav-text,
            .logo-text {
                display: none;
            }
            
            .top-logout-btn {
                position: relative;
                top: 0;
                right: 0;
                margin: 10px auto;
                display: flex;
            }
            
            .logo {
                justify-content: center;
            }
            
            .user-card {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-item {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-icon {
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
                width: 280px;
            }
            
            .sidebar-header,
            .user-card,
            .nav-text,
            .logo-text {
                display: block;
            }
            
            .top-logout-btn {
                position: absolute;
                top: 25px;
                right: 25px;
                display: flex;
            }
            
            .logo {
                justify-content: flex-start;
            }
            
            .nav-item {
                justify-content: flex-start;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-grid,
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-banner {
                padding: 30px 20px;
            }
            
            .welcome-content h2 {
                font-size: 2rem;
            }
            
            .welcome-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Logout Confirmation Modal -->
    <div class="logout-modal" id="logoutModal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout from the Discipline Committee Portal?</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="hideLogoutModal()">Cancel</button>
                <button class="modal-btn confirm" onclick="performLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="discipline_committee.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Discipline Panel</h2>
                        <p>Committee Portal</p>
                    </div>
                </a>
                <!-- Logout Button at Top Right -->
                <button class="top-logout-btn" onclick="showLogoutModal()" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
            
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $FirstName . ' ' . $middleName; ?></h3>
                    <p>Discipline Committee</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="discipline_committee.php" class="nav-item active">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="dc_manage_requests.php" class="nav-item">
                    <i class="fas fa-tasks nav-icon"></i>
                    <span class="nav-text">Manage Requests</span>
                    <?php if ($pending_requests > 0): ?>
                    <span class="badge"><?php echo $pending_requests; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dc_check_request.php" class="nav-item">
                    <i class="fas fa-check-circle nav-icon"></i>
                    <span class="nav-text">Check Validity</span>
                </a>
                <a href="dc_generate_report.php" class="nav-item">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Generate Report</span>
                </a>
                
            </nav>
            
            <!-- Old logout section - hidden -->
            <div class="logout-section">
                <button class="logout-btn" onclick="showLogoutModal()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>Discipline Committee Dashboard</h1>
                    <p>Verify candidate eligibility based on discipline status</p>
                </div>
                <div class="date-display">
                    <i class="fas fa-calendar-day"></i>
                    <span id="currentDate"><?php echo date('F j, Y'); ?></span>
                </div>
            </div>
            
            <!-- Welcome Banner -->
            <div class="welcome-banner animate-in">
                <div class="welcome-content">
                    <h2>Welcome, <?php echo $FirstName; ?>!</h2>
                    <p>You're responsible for reviewing candidate discipline status. Your role ensures only qualified candidates proceed in the electoral process.</p>
                    <div class="welcome-actions">
                        <a href="dc_manage_requests.php" class="btn btn-light">
                            <i class="fas fa-tasks"></i> Review Pending Requests
                            <?php if ($pending_requests > 0): ?>
                            <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 5px;">
                                <?php echo $pending_requests; ?> pending
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="dc_check_request.php" class="btn btn-outline-light">
                            <i class="fas fa-check-circle"></i> Check Candidate Validity
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card animate-in delay-1">
                    <div class="stat-header">
                        <div class="stat-icon total">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $total_requests; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                
                <div class="stat-card animate-in delay-2">
                    <div class="stat-header">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $pending_requests; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                
                <div class="stat-card animate-in delay-3">
                    <div class="stat-header">
                        <div class="stat-icon cleared">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $cleared_requests; ?></div>
                    <div class="stat-label">Cleared (Approved)</div>
                </div>
                
                <div class="stat-card animate-in delay-4">
                    <div class="stat-header">
                        <div class="stat-icon disciplinary">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?php echo $disciplinary_requests; ?></div>
                    <div class="stat-label">Issues Found (Rejected)</div>
                </div>
            </div>
            
            <!-- Today's Activity -->
            <div class="quick-tasks animate-in delay-2">
                <h3 class="section-title">
                    <i class="fas fa-calendar-day"></i>
                    Today's Activity (<?php echo date('M d, Y'); ?>)
                </h3>
                
                <div class="tasks-grid">
                    <div class="task-card" style="cursor: default;">
                        <div class="task-icon" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <h4>Submitted Today</h4>
                        <p><strong style="font-size: 1.5rem; color: var(--primary);"><?php echo $today_submitted; ?></strong> new requests</p>
                    </div>
                    
                    <div class="task-card" style="cursor: default;">
                        <div class="task-icon" style="background: linear-gradient(135deg, var(--success), #059669);">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4>Cleared Today</h4>
                        <p><strong style="font-size: 1.5rem; color: var(--success);"><?php echo $today_cleared; ?></strong> candidates cleared</p>
                    </div>
                    
                    <div class="task-card" style="cursor: default;">
                        <div class="task-icon" style="background: linear-gradient(135deg, var(--danger), #dc2626);">
                            <i class="fas fa-times"></i>
                        </div>
                        <h4>Rejected Today</h4>
                        <p><strong style="font-size: 1.5rem; color: var(--danger);"><?php echo $today_rejected; ?></strong> issues found</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Tasks -->
            <div class="quick-tasks animate-in delay-2" style="margin-top: 40px;">
                <h3 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
                
                <div class="tasks-grid">
                    <a href="dc_manage_requests.php" class="task-card">
                        <div class="task-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4>Review Requests</h4>
                        <p>Examine and process pending candidate discipline reviews</p>
                    </a>
                    
                    <a href="dc_check_request.php" class="task-card">
                        <div class="task-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h4>Check Eligibility</h4>
                        <p>Verify candidate discipline records and background</p>
                    </a>
                    
                    <a href="dc_generate_report.php" class="task-card">
                        <div class="task-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h4>Generate Reports</h4>
                        <p>Create detailed discipline status reports</p>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity animate-in delay-3">
                <h3 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h3>
                
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon icon-review">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Discipline Review Started</h5>
                            <p>Started discipline review for candidate #CR2024001</p>
                        </div>
                        <div class="activity-time">Just now</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon icon-cleared">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Discipline Cleared</h5>
                            <p>Candidate #CR2024000 cleared for discipline compliance</p>
                        </div>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon icon-disciplinary">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="activity-content">
                            <h5>Discipline Issues Found</h5>
                            <p>Request #RQ2024123 rejected due to discipline issues</p>
                        </div>
                        <div class="activity-time">Yesterday</div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <p>© <?php echo date("Y"); ?> Electoral Commission | Discipline Committee Portal</p>
                <p><i class="fas fa-shield-alt"></i> Ensuring Candidate Discipline Compliance Since 2024</p>
            </footer>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const logoutModal = document.getElementById('logoutModal');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            menuToggle.innerHTML = sidebar.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
        
        // Show logout confirmation modal
        function showLogoutModal() {
            logoutModal.style.display = 'flex';
        }
        
        // Hide logout confirmation modal
        function hideLogoutModal() {
            logoutModal.style.display = 'none';
        }
        
        // Perform logout
        function performLogout() {
            // Hide the modal first
            hideLogoutModal();
            
            // Add a small delay for smooth transition
            setTimeout(() => {
                // Redirect with logout parameter
                window.location.href = 'discipline_committee.php?logout=true';
            }, 300);
        }
        
        // Close modal when clicking outside
        logoutModal.addEventListener('click', (event) => {
            if (event.target === logoutModal) {
                hideLogoutModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && logoutModal.style.display === 'flex') {
                hideLogoutModal();
            }
        });
        
        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            const dateString = now.toLocaleDateString('en-US', options);
            document.getElementById('currentDate').textContent = dateString;
        }
        
        // Update every minute
        setInterval(updateDateTime, 60000);
        updateDateTime();
    </script>
</body>
</html>

<?php
$conn->close();
?>