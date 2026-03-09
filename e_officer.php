<?php
session_start();
include("connection.php");

// Check if the user is logged in and has officer role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

// Fetch officer user data
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

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

// Get current date
$current_date = date("Y-m-d");

// Initialize variables
$voter_reg_active = $candidate_reg_active = false;
$voter_dates = $candidate_dates = $election_date = null;

// Check voter registration dates
if (tableExists($conn, 'voter_reg_date')) {
    $query = "SELECT start, end FROM voter_reg_date LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $voter_dates = $result->fetch_assoc();
        $voter_reg_active = ($current_date >= $voter_dates['start'] && $current_date <= $voter_dates['end']);
    }
}

// Check candidate registration dates
if (tableExists($conn, 'candidate_reg_date')) {
    $query = "SELECT start, end FROM candidate_reg_date LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $candidate_dates = $result->fetch_assoc();
        $candidate_reg_active = ($current_date >= $candidate_dates['start'] && $current_date <= $candidate_dates['end']);
    }
}

// Check election date
if (tableExists($conn, 'election_date')) {
    $query = "SELECT date FROM election_date LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $election_date = $result->fetch_assoc()['date'];
    }
}

// Get statistics
$stats = [
    'voters' => 0,
    'candidates' => 0,
    'votes' => 0,
    'pending_requests' => 0
];

if (tableExists($conn, 'voter')) {
    $result = $conn->query("SELECT COUNT(*) as count FROM voter");
    if ($result) $stats['voters'] = $result->fetch_assoc()['count'];
}

if (tableExists($conn, 'candidate')) {
    $result = $conn->query("SELECT COUNT(*) as count FROM candidate");
    if ($result) $stats['candidates'] = $result->fetch_assoc()['count'];
}

if (tableExists($conn, 'vote')) {
    $result = $conn->query("SELECT COUNT(*) as count FROM vote");
    if ($result) $stats['votes'] = $result->fetch_assoc()['count'];
}

// Fixed: Removed WHERE clause for status column which doesn't exist
if (tableExists($conn, 'request')) {
    $result = $conn->query("SELECT COUNT(*) as count FROM request");
    if ($result) $stats['pending_requests'] = $result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard | DTUSU Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #006400;
            --secondary: #FFD700;
            --accent: #FF0000;
            --dark: #1a2a6c;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
            --radius: 16px;
            --radius-sm: 8px;
            --sidebar-bg: linear-gradient(135deg, #1e1b4b, #312e81);
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
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 0, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 100, 0, 0.05) 0%, transparent 50%);
            z-index: -1;
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
            overflow: hidden;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            justify-content: center;
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
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .logo-text p {
            font-size: 0.85rem;
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
            flex-shrink: 0;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--warning), var(--primary));
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            min-height: 0;
        }

        .nav-menu {
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: rgba(0, 100, 0, 0.2);
            color: white;
            border-left: 4px solid var(--secondary);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent);
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
            background: var(--accent);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.1);
        }

        .logout-btn {
            width: 100%;
            padding: 14px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            border: none;
            font-family: inherit;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }

        .nav-container::-webkit-scrollbar {
            width: 6px;
        }

        .nav-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .nav-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .nav-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        /* Header */
        .header {
            margin-bottom: 40px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-voters {
            background: linear-gradient(135deg, var(--primary), var(--dark));
        }

        .icon-candidates {
            background: linear-gradient(135deg, var(--secondary), #d97706);
        }

        .icon-votes {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        .icon-pending {
            background: linear-gradient(135deg, var(--accent), #dc2626);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark) 100%);
            border-radius: var(--radius);
            padding: 40px;
            margin-bottom: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 25s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-150px, -150px) rotate(360deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: capitalize;
            font-family: 'Poppins', sans-serif;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: var(--primary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-outline-light {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        /* Date Cards */
        .dates-section {
            margin-bottom: 40px;
        }

        .content-area {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 40px;
            margin-bottom: 40px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .content-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .date-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .date-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .date-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .date-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }

        .date-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        .date-content p {
            color: var(--gray);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        /* Quick Actions */
        .quick-actions-section {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: var(--light);
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 25px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 15px;
        }

        .action-card:hover {
            background: white;
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 40px;
            border-top: 1px solid #e5e7eb;
        }

        /* Current Date Widget */
        .current-date {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 12px 25px;
            border-radius: var(--radius-sm);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(0, 100, 0, 0.3);
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header,
            .user-card,
            .nav-text,
            .logo-text,
            .logout-btn span {
                display: none;
            }
            
            .logo {
                justify-content: center;
            }
            
            .user-card {
                justify-content: center;
                padding: 15px;
                margin: 10px;
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
            
            .nav-container {
                padding: 10px;
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
            .logo-text,
            .logout-btn span {
                display: block;
            }
            
            .logo {
                justify-content: flex-start;
            }
            
            .nav-item {
                justify-content: flex-start;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .welcome-card {
                padding: 30px 20px;
            }
            
            .welcome-content h2 {
                font-size: 2rem;
            }
            
            .welcome-actions {
                flex-direction: column;
            }
            
            .dates-grid,
            .actions-grid {
                grid-template-columns: 1fr;
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
            box-shadow: 0 8px 25px rgba(0, 100, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* Animation classes */
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
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }

        /* Floating particles */
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            animation: float-particle 20s linear infinite;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Floating particles background -->
    <div class="floating-particles" id="particles"></div>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="e_officer.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-gavel"></i>
                    </div> 
                    
                    <div class="logo-text">
                        <h2>DTUSU Elections</h2>

                        <p>Officer Portal</p>
                    </div>
                </a>
            </div>
            
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $FirstName . ' ' . $middleName; ?></h3>
                    <p>
                        <i class="fas fa-user-shield"></i>
                        Election Committee
                    </p>
                </div>
            </div>
            
            <!-- Scrollable navigation container -->
            <div class="nav-container">
                <nav class="nav-menu">
                    <a href="e_officer.php" class="nav-item active">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    
                    <a href="o_result.php" class="nav-item">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span class="nav-text">Results</span>
                    </a>
                    
                    <a href="o_generate.php" class="nav-item">
                        <i class="fas fa-file-alt nav-icon"></i>
                        <span class="nav-text">Generate Report</span>
                    </a>
                    
                    <a href="regdate.php" class="nav-item">
                        <i class="fas fa-calendar-alt nav-icon"></i>
                        <span class="nav-text">Voter Dates</span>
                    </a>
                    
                    <a href="regcan_date.php" class="nav-item">
                        <i class="fas fa-calendar-check nav-icon"></i>
                        <span class="nav-text">Candidate Dates</span>
                    </a>
                    
                    <?php if ($voter_reg_active): ?>
                    <a href="reg_voter.php" class="nav-item">
                        <i class="fas fa-user-plus nav-icon"></i>
                        <span class="nav-text">Voter Registration</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($candidate_reg_active): ?>
                    <a href="ov_candidate.php" class="nav-item">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">Candidate Registration</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="o_comment.php" class="nav-item">
                        <i class="fas fa-comment nav-icon"></i>
                        <span class="nav-text">Comments</span>
                    </a>
                    
                    <a href="e_officer_send_request.php" class="nav-item">
                        <i class="fas fa-paper-plane nav-icon"></i>
                        <span class="nav-text">Send Request</span>
                    </a>
                    
                    <?php if (tableExists($conn, 'request')): ?>
                    <a href="dc_requests.php" class="nav-item">
                        <i class="fas fa-tasks nav-icon"></i>
                        <span class="nav-text">Manage Requests</span>
                        <?php if ($stats['pending_requests'] > 0): ?>
                        <span class="badge"><?php echo $stats['pending_requests']; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="logout-section">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Current Date Widget -->
            <div class="current-date animate-fade-in">
                <i class="fas fa-calendar-day"></i>
                <span id="currentDateTime"><?php echo date('F j, Y, g:i A'); ?></span>
            </div>
            
            <!-- Header -->
            <div class="header animate-fade-in">
                <div class="page-title">
                    <i class="fas fa-gavel" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Election Committee Dashboard</h1>
                </div>
                <p>Welcome to the election committee control panel</p>
            </div>
            
            <!-- Welcome Card -->
            <div class="welcome-card animate-fade-in delay-100">
                <div class="welcome-content">
                    <h2>Welcome  <?php echo $FirstName; ?>!!!</h2>
                    <p>You're managing the election process. Monitor voter registrations, candidate activities, and voting progress from this comprehensive dashboard.</p>
                    <div class="welcome-actions">
                        <a href="o_generate.php" class="btn">
                            <i class="fas fa-file-export"></i> Generate Report
                        </a>
                        <a href="regdate.php" class="btn btn-outline-light">
                            <i class="fas fa-calendar"></i> Manage Dates
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card animate-fade-in delay-100">
                    <div class="stat-icon icon-voters">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['voters']; ?></h3>
                        <p>Registered Voters</p>
                    </div>
                </div>
                
                <div class="stat-card animate-fade-in delay-200">
                    <div class="stat-icon icon-candidates">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['candidates']; ?></h3>
                        <p>Candidates</p>
                    </div>
                </div>
                
                <div class="stat-card animate-fade-in delay-300">
                    <div class="stat-icon icon-votes">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['votes']; ?></h3>
                        <p>Votes Cast</p>
                    </div>
                </div>
                
                <div class="stat-card animate-fade-in delay-400">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_requests']; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
            </div>
            
            <!-- Date Information -->
            <div class="content-area animate-fade-in delay-200">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-calendar"></i>
                        Election Timeline
                    </h2>
                </div>
                
                <div class="dates-grid">
                    <div class="date-card">
                        <div class="date-header">
                            <div class="date-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="date-title">Voter Registration</div>
                        </div>
                        <div class="date-content">
                            <?php if ($voter_dates): ?>
                                <p><i class="fas fa-calendar"></i> Start: <?php echo date('M j, Y', strtotime($voter_dates['start'])); ?></p>
                                <p><i class="fas fa-calendar"></i> End: <?php echo date('M j, Y', strtotime($voter_dates['end'])); ?></p>
                                <span class="status-badge <?php echo $voter_reg_active ? 'status-active' : 'status-inactive'; ?>">
                                    <i class="fas fa-<?php echo $voter_reg_active ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $voter_reg_active ? 'Active Now' : 'Inactive'; ?>
                                </span>
                            <?php else: ?>
                                <p><i class="fas fa-exclamation-circle"></i> No dates configured</p>
                                <span class="status-badge status-inactive">Not Configured</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="date-card">
                        <div class="date-header">
                            <div class="date-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="date-title">Candidate Registration</div>
                        </div>
                        <div class="date-content">
                            <?php if ($candidate_dates): ?>
                                <p><i class="fas fa-calendar"></i> Start: <?php echo date('M j, Y', strtotime($candidate_dates['start'])); ?></p>
                                <p><i class="fas fa-calendar"></i> End: <?php echo date('M j, Y', strtotime($candidate_dates['end'])); ?></p>
                                <span class="status-badge <?php echo $candidate_reg_active ? 'status-active' : 'status-inactive'; ?>">
                                    <i class="fas fa-<?php echo $candidate_reg_active ? 'check-circle' : 'times-circle'; ?>"></i>
                                    <?php echo $candidate_reg_active ? 'Active Now' : 'Inactive'; ?>
                                </span>
                            <?php else: ?>
                                <p><i class="fas fa-exclamation-circle"></i> No dates configured</p>
                                <span class="status-badge status-inactive">Not Configured</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="date-card">
                        <div class="date-header">
                            <div class="date-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                            <div class="date-title">Election Day</div>
                        </div>
                        <div class="date-content">
                            <?php if ($election_date): ?>
                                <p><i class="fas fa-calendar-day"></i> Date: <?php echo date('M j, Y', strtotime($election_date)); ?></p>
                                <?php
                                $today = date('Y-m-d');
                                $days_left = floor((strtotime($election_date) - strtotime($today)) / (60 * 60 * 24));
                                
                                if ($days_left > 0) {
                                    echo '<span class="status-badge status-active">' . $days_left . ' days left</span>';
                                } elseif ($days_left == 0) {
                                    echo '<span class="status-badge status-active">Election Day!</span>';
                                } else {
                                    echo '<span class="status-badge status-inactive">Completed</span>';
                                }
                                ?>
                            <?php else: ?>
                                <p><i class="fas fa-exclamation-circle"></i> No date scheduled</p>
                                <span class="status-badge status-inactive">Not Scheduled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions-section animate-fade-in delay-300">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h2>
                </div>
                
                <div class="actions-grid">
                    <a href="o_result.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="action-title">View Results</div>
                        <p>Monitor election results in real-time</p>
                    </a>
                    
                    <a href="o_generate.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="action-title">Generate Report</div>
                        <p>Create and export detailed reports</p>
                    </a>
                    
                    <a href="e_officer_send_request.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="action-title">Send Request</div>
                        <p>Submit requests to administration</p>
                    </a>
                    
                    <?php if (tableExists($conn, 'request')): ?>
                    <a href="dc_requests.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="action-title">Manage Requests</div>
                        <p>Handle district committee requests</p>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer animate-fade-in delay-400">
                <p>© <?php echo date("Y"); ?> DTUSU Electoral Commission | Secure Online Voting System</p>
                <p><i class="fas fa-shield-alt"></i> Officer Portal v2.0 | Last login: <?php echo date("g:i A"); ?></p>
            </footer>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            menuToggle.innerHTML = sidebar.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768) {
                if (sidebar && !sidebar.contains(event.target) && menuToggle && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
        
        // Update real-time clock
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            };
            const dateTimeString = now.toLocaleDateString('en-US', options);
            document.getElementById('currentDateTime').textContent = dateTimeString;
        }
        
        // Update every second
        setInterval(updateDateTime, 1000);
        updateDateTime();
        
        // Add hover animations to cards
        const cards = document.querySelectorAll('.stat-card, .date-card, .action-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
        
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if (!particlesContainer) return;
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 10 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}vw`;
                
                // Random animation delay
                particle.style.animationDelay = `${Math.random() * 20}s`;
                
                // Random animation duration
                const duration = 15 + Math.random() * 10;
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize particles
        document.addEventListener('DOMContentLoaded', createParticles);
        
        // Add animation classes to elements
        document.addEventListener('DOMContentLoaded', () => {
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            animatedElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
                el.style.opacity = '0';
                setTimeout(() => {
                    el.style.animationPlayState = 'running';
                }, 100);
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>