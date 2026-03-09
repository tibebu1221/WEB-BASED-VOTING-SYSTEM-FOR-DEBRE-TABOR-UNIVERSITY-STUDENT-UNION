<?php
session_start();
include("connection.php");

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname, lname, email FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname'] ?? '');
    $lastName = htmlspecialchars($row['lname'] ?? '');
    $email = htmlspecialchars($row['email']);
    $fullName = trim($FirstName . ' ' . $middleName . ' ' . $lastName);
    $initials = strtoupper(substr($FirstName, 0, 1) . substr($lastName, 0, 1));
} else {
    echo '<script>alert("Error: User data not found."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Get comprehensive stats
$stats = [
    'total_voters' => 0,
    'verified_voters' => 0,
    'pending_voters' => 0,
    'total_candidates' => 0,
    'active_candidates' => 0,
    'total_votes' => 0,
    'today_votes' => 0,
    'total_users' => 0,
    'active_elections' => 0,
    'total_posts' => 0
];

try {
    // Get voter statistics
    $voterResult = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM voter");
    if ($voterResult && $row = $voterResult->fetch_assoc()) {
        $stats['total_voters'] = $row['total'];
        $stats['verified_voters'] = $row['verified'];
        $stats['pending_voters'] = $row['pending'];
    }
    
    // Get candidate statistics
    $candidateResult = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as active
        FROM candidate");
    if ($candidateResult && $row = $candidateResult->fetch_assoc()) {
        $stats['total_candidates'] = $row['total'];
        $stats['active_candidates'] = $row['active'];
    }
    
    // Get vote statistics
    $voteResult = $conn->query("SELECT 
        COUNT(DISTINCT voter_id) as total,
        SUM(CASE WHEN DATE(voted_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM votes");
    if ($voteResult && $row = $voteResult->fetch_assoc()) {
        $stats['total_votes'] = $row['total'];
        $stats['today_votes'] = $row['today'];
    }
    
    // Get user statistics
    $userResult = $conn->query("SELECT COUNT(*) as total FROM user WHERE role != 'admin'");
    if ($userResult && $row = $userResult->fetch_assoc()) {
        $stats['total_users'] = $row['total'];
    }
    
    // Get election statistics
    $electionResult = $conn->query("SELECT COUNT(*) as active FROM elections WHERE status = 'active'");
    if ($electionResult && $row = $electionResult->fetch_assoc()) {
        $stats['active_elections'] = $row['active'];
    }
    
    // Get total posts/positions
    $postResult = $conn->query("SELECT COUNT(*) as total FROM posts");
    if ($postResult && $row = $postResult->fetch_assoc()) {
        $stats['total_posts'] = $row['total'];
    }
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard |System Adminstrator Dashboard</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary: #4cc9f0;
            --success: #4ade80;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --light: #f9fafb;
            --border: #e5e7eb;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Main Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            box-shadow: var(--shadow);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .logo-text h2 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .logo-text p {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* User Profile */
        .user-profile {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            box-shadow: var(--shadow);
        }

        .user-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .user-info p {
            font-size: 0.8rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Navigation */
        .sidebar-nav {
            padding: 1rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray);
            margin-bottom: 0.75rem;
            padding: 0 0.5rem;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-icon {
            width: 20px;
            font-size: 1.1rem;
        }

        .badge {
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 1.5rem;
            transition: var(--transition);
        }

        /* Header */
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .header-left h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .header-left p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .header-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .date-time {
            background: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .menu-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.25rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
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
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-info p {
            font-size: 0.875rem;
            color: var(--gray);
            font-weight: 500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        /* Different colors for each stat card */
        .stat-card:nth-child(1) .stat-icon { background: var(--primary); }
        .stat-card:nth-child(2) .stat-icon { background: var(--success); }
        .stat-card:nth-child(3) .stat-icon { background: var(--info); }
        .stat-card:nth-child(4) .stat-icon { background: var(--warning); }

        /* Dashboard Layout */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        .section-subtitle {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .action-btn {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(76, 201, 240, 0.1) 100%);
            border: 1px solid rgba(67, 97, 238, 0.2);
            border-radius: 10px;
            padding: 1.25rem;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .action-btn:hover .action-icon {
            background: white;
            color: var(--primary);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            transition: var(--transition);
        }

        .action-text h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .action-text p {
            font-size: 0.8rem;
            color: var(--gray);
            opacity: 0.9;
        }

        .action-btn:hover .action-text p {
            color: rgba(255,255,255,0.9);
        }

        /* Election Status */
        .election-status {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .status-db .status-icon { background: var(--primary); }
        .status-voting .status-icon { background: var(--success); }
        .status-security .status-icon { background: var(--warning); }
        .status-election .status-icon { background: var(--info); }

        .status-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
        }

        .status-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: auto;
        }

        .status-online {
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3);
        }

        .status-offline {
            background: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--gray);
            font-size: 0.875rem;
            border-top: 1px solid var(--border);
            margin-top: 2rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .header-right {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-time {
                width: 100%;
                justify-content: center;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide {
            animation: slideUp 0.5s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">SAD</div>
                    <div class="logo-text">
                        <h2>System Admin Dashboard</h2>
                        <p>Admin Dashboard</p>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="user-profile">
                <div class="user-avatar"><?php echo $initials; ?></div>
                <div class="user-info">
                    <h3><?php echo $FirstName . ' ' . $lastName; ?></h3>
                    <p><i class="fas fa-user-shield"></i> System Administrator</p>
                    <small><?php echo $email; ?></small>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h4 class="nav-title">Main Navigation</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="system_admin.php" class="nav-link active">
                                <i class="fas fa-home nav-icon"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_account.php" class="nav-link">
                                <i class="fas fa-users-cog nav-icon"></i>
                                User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="a_candidate.php" class="nav-link">
                                <i class="fas fa-user-tie nav-icon"></i>
                                Candidates
                                <?php if($stats['pending_voters'] > 0): ?>
                                <span class="badge"><?php echo $stats['pending_voters']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="voters.php" class="nav-link">
                                <i class="fas fa-user-friends nav-icon"></i>
                                Voters
                                <?php if($stats['pending_voters'] > 0): ?>
                                <span class="badge"><?php echo $stats['pending_voters']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h4 class="nav-title">Election Control</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="setDate.php" class="nav-link">
                                <i class="fas fa-calendar-alt nav-icon"></i>
                                Election Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="adminv_result.php" class="nav-link">
                                <i class="fas fa-poll-h nav-icon"></i>
                                Election Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="a_advert.php" class="nav-link">
                                <i class="fas fa-bullhorn nav-icon"></i>
                                Announcements
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h4 class="nav-title">Reports</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="a_generate.php" class="nav-link">
                                <i class="fas fa-chart-line nav-icon"></i>
                                Analytics Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link" style="color: var(--danger);">
                                <i class="fas fa-sign-out-alt nav-icon"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1>Welcome back, <?php echo $FirstName; ?>! 👋</h1>
                    <p>Manage your election system with powerful tools and real-time insights</p>
                </div>
                <div class="header-right">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="date-time">
                        <i class="fas fa-clock"></i>
                        <span id="currentDateTime"><?php echo date('M d, Y h:i A'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card animate-slide">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $stats['total_voters']; ?></h3>
                            <p>Total Voters</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-slide delay-1">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $stats['total_candidates']; ?></h3>
                            <p>Total Candidates</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-slide delay-2">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $stats['active_candidates']; ?></h3>
                            <p>Active Candidates</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-slide delay-3">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $stats['total_posts']; ?></h3>
                            <p>Total Positions</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Layout -->
            <div class="dashboard-layout">
                <!-- Quick Actions -->
                <section class="quick-actions">
                    <div class="section-header">
                        <div>
                            <h2 class="section-title">Quick Actions</h2>
                            <p class="section-subtitle">Access frequently used features</p>
                        </div>
                    </div>
                    
                    <div class="actions-grid">
                        <a href="manage_account.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div class="action-text">
                                <h4>Manage Users</h4>
                                <p>Add, edit, or remove users</p>
                            </div>
                        </a>
                        
                        <a href="a_candidate.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="action-text">
                                <h4>Candidates</h4>
                                <p>Manage election candidates</p>
                            </div>
                        </a>
                        
                        <a href="voters.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="action-text">
                                <h4>Voter List</h4>
                                <p>View and manage voters</p>
                            </div>
                        </a>
                        
                        <a href="a_advert.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="action-text">
                                <h4>Announcements</h4>
                                <p>Send notifications</p>
                            </div>
                        </a>
                        
                        <a href="setDate.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="action-text">
                                <h4>Schedule</h4>
                                <p>Set election dates</p>
                            </div>
                        </a>
                        
                        <a href="a_generate.php" class="action-btn">
                            <div class="action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="action-text">
                                <h4>Analytics</h4>
                                <p>View reports & insights</p>
                            </div>
                        </a>
                    </div>
                </section>

                

            <!-- Election Management Status -->
            <section class="election-status">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Election Management</h2>
                        <p class="section-subtitle">Monitor and control election activities</p>
                    </div>
                </div>
                
                <div class="status-grid">
                    <div class="status-item status-db">
                        <div class="status-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <div class="status-info">
                            <h4>Voting System</h4>
                            <p><?php echo $stats['total_candidates'] > 0 ? 'Ready for voting' : 'Setup required'; ?></p>
                        </div>
                        <div class="status-indicator <?php echo $stats['total_candidates'] > 0 ? 'status-online' : 'status-offline'; ?>"></div>
                    </div>
                    
                    <div class="status-item status-voting">
                        <div class="status-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="status-info">
                            <h4>Voter Registration</h4>
                            <p><?php echo $stats['total_voters']; ?> voters registered</p>
                        </div>
                    </div>
                    
                    <div class="status-item status-security">
                        <div class="status-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="status-info">
                            <h4>Candidates</h4>
                            <p><?php echo $stats['active_candidates']; ?> active candidates</p>
                        </div>
                    </div>
                    
                    <div class="status-item status-election">
                        <div class="status-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="status-info">
                            <h4>Election Status</h4>
                            <p><?php echo $stats['active_elections'] > 0 ? 'Active election running' : 'No active election'; ?></p>
                        </div>
                        <div class="status-indicator <?php echo $stats['active_elections'] > 10 ? 'status-online' : 'status-offline'; ?>"></div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="dashboard-footer">
                <div class="footer-links">
                    <a href="system_admin.php">Dashboard</a>
                    <a href="manage_account.php">User Management</a>
                    <a href="a_candidate.php">Candidates</a>
                    <a href="voters.php">Voters</a>
                    <a href="adminv_result.php">Results</a>
                    <a href="a_generate.php">Analytics</a>
                    <a href="setDate.php">Schedule</a>
                    <a href="a_advert.php">Announcements</a>
                </div>
                <p>© <?php echo date("Y"); ?> Election Management System v4.0 | Secure Digital Voting Platform</p>
                <p id="footerTime">Last updated: <?php echo date("F j, Y, g:i a"); ?></p>
            </footer>
        </main>
    </div>

    <script>
        // Toggle mobile menu
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            
            const dateTimeStr = now.toLocaleDateString('en-US', options);
            document.getElementById('currentDateTime').textContent = dateTimeStr;
            document.getElementById('footerTime').textContent = 'Last updated: ' + now.toLocaleString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        // Update time every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Add hover animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Add click animations to action buttons
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                e.target !== menuToggle && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) $conn->close();
?>