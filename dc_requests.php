<?php
include("connection.php");
session_start();

// ---------- 1. AUTHENTICATION ----------
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

// ---------- 2. USER INFO ----------
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$FirstName = htmlspecialchars($user['fname'] ?? '');
$MiddleName = htmlspecialchars($user['mname'] ?? '');
$initials = strtoupper(substr($FirstName, 0, 1) . (!empty($MiddleName) ? substr($MiddleName, 0, 1) : ''));
$stmt->close();

// ---------- 3. CSRF TOKEN ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ---------- 4. DETECT CANDIDATE ID COLUMN NAME ----------
$candidate_id_column = 'id'; // Default assumption
$result = $conn->query("SHOW COLUMNS FROM candidate");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $field = strtolower($row['Field']);
        // Look for columns that might contain candidate ID
        if (strpos($field, 'cand') !== false || strpos($field, 'id') !== false) {
            $candidate_id_column = $row['Field'];
            break;
        }
    }
}

// ---------- 5. FETCH PENDING REQUESTS ----------
$requests = [];
$pending_count = 0;
$cleared_count = 0;
$issues_found_count = 0;

// First, let's check what columns exist in the candidate table
$candidate_columns = [];
$result = $conn->query("SHOW COLUMNS FROM candidate");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $candidate_columns[] = $row['Field'];
    }
}

// Check what columns exist in the request table
$request_columns = [];
$result = $conn->query("SHOW COLUMNS FROM request");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $request_columns[] = $row['Field'];
    }
}

// Build query based on available columns
$sql = "SELECT r.requestID, r.candidateID, r.officeID, r.submitted_at";

// Check if discipline_status column exists
if (in_array('discipline_status', $request_columns)) {
    $sql .= ", r.discipline_status";
}

// Add candidate name columns if they exist
if (in_array('fname', $candidate_columns)) {
    $sql .= ", c.fname AS cand_fname";
}
if (in_array('mname', $candidate_columns)) {
    $sql .= ", c.mname AS cand_mname";
}
if (in_array('lname', $candidate_columns)) {
    $sql .= ", c.lname AS cand_lname";
}

$sql .= " FROM request r";
$sql .= " LEFT JOIN candidate c ON r.candidateID = c.`$candidate_id_column`";
$sql .= " ORDER BY r.submitted_at DESC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        // Count based on discipline_status
        if (isset($row['discipline_status'])) {
            if ($row['discipline_status'] === 'pending') {
                $pending_count++;
            } elseif ($row['discipline_status'] === 'clear') {
                $cleared_count++;
            } elseif ($row['discipline_status'] === 'disciplinary_action') {
                $issues_found_count++;
            } else {
                $pending_count++; // Default to pending for other statuses
            }
        } else {
            $pending_count++; // No status means pending
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | Discipline Committee</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            overflow: hidden; /* Prevent double scrollbars */
        }
        
        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            flex-shrink: 0; /* Prevent header from shrinking */
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
            flex-shrink: 0; /* Prevent user card from shrinking */
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
        }
        
        .nav-container {
            flex: 1;
            overflow-y: auto; /* Enable vertical scrolling for nav only */
            padding: 20px;
            min-height: 0; /* Important for flex child scrolling */
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
        
        .logout-section {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0; /* Prevent logout section from shrinking */
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
            transition: var(--transition);
            font-weight: 500;
            border: none;
            font-family: inherit;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }
        
        /* Custom scrollbar for sidebar */
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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
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
        
        .icon-pending {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }
        
        .icon-cleared {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        
        .icon-issues {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }
        
        .icon-total {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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
        
        /* Main Content Area */
        .content-area {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
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
        
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-icon {
            font-size: 1.2rem;
        }
        
        /* Requests Table */
        .requests-container {
            overflow-x: auto;
        }
        
        .requests-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .requests-table th {
            background: #f9fafb;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .requests-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .requests-table tr:hover {
            background: #f9fafb;
        }
        
        .request-id {
            font-weight: 600;
            color: var(--primary);
        }
        
        .candidate-id {
            font-family: monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .office-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .date-time {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .status-cleared {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        
        .status-issues {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 40px;
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
            
            .requests-table {
                display: block;
                overflow-x: auto;
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #9ca3af;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
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
                        <h2>Election Committee Panel</h2>
                        <p>Committee Portal</p>
                    </div>
                </a>
            </div>
            
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $FirstName . ' ' . $MiddleName; ?></h3>
                    <p>Discipline Committee</p>
                </div>
            </div>
            
            <!-- Scrollable navigation container -->
            <div class="nav-container">
                <nav class="nav-menu">
                    <a href="e_officer.php" class="nav-item">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    
                    <a href="o_comment.php" class="nav-item">
                        <i class="fas fa-comment nav-icon"></i>
                        <span class="nav-text">Comments</span>
                    </a>
                    <a href="e_officer_send_request.php" class="nav-item">
                        <i class="fas fa-paper-plane nav-icon"></i>
                        <span class="nav-text">Send Request</span>
                    </a>
                    <a href="dc_requests.php" class="nav-item active">
                        <i class="fas fa-tasks nav-icon"></i>
                        <span class="nav-text">Manage Requests</span>
                        <?php if ($pending_count > 0): ?>
                        <span class="badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
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
            <!-- Header -->
            <div class="header">
                <div class="page-title">
                    <i class="fas fa-tasks" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Manage Candidate Requests</h1>
                </div>
                <p>View candidate discipline status for verification requests</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_count; ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-cleared">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $cleared_count; ?></h3>
                        <p>Cleared</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-issues">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $issues_found_count; ?></h3>
                        <p>Issues Found</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($requests); ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-users"></i>
                        Candidate Requests
                    </h2>
                </div>
                
                <?php if (isset($_SESSION['msg'])): $msg = $_SESSION['msg']; ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <i class="fas fa-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> alert-icon"></i>
                    <div><?php echo htmlspecialchars($msg['text']); ?></div>
                </div>
                <?php unset($_SESSION['msg']); endif; ?>
                
                <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Requests Found</h3>
                    <p>There are no candidate verification requests at this time.</p>
                    <a href="e_officer.php" class="btn">
                        <i class="fas fa-home"></i> Return to Dashboard
                    </a>
                </div>
                <?php else: ?>
                <div class="requests-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Candidate</th>
                                <th>Office ID</th>
                                <th>Submitted</th>
                                <th>Discipline Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <?php
                            // Build candidate name from available columns
                            $candidate_name_parts = [];
                            if (isset($request['cand_fname'])) $candidate_name_parts[] = $request['cand_fname'];
                            if (isset($request['cand_mname'])) $candidate_name_parts[] = $request['cand_mname'];
                            if (isset($request['cand_lname'])) $candidate_name_parts[] = $request['cand_lname'];
                            
                            $candidate_name = !empty($candidate_name_parts) ? implode(' ', $candidate_name_parts) : 'Candidate ' . htmlspecialchars($request['candidateID']);
                            
                            // Get discipline status (use discipline_status if available, otherwise show pending)
                            $status = isset($request['discipline_status']) ? $request['discipline_status'] : 'pending';
                            ?>
                            <tr>
                                <td>
                                    <span class="request-id">#<?php echo htmlspecialchars($request['requestID']); ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <strong><?php echo htmlspecialchars($candidate_name); ?></strong>
                                        <small class="candidate-id">ID: <?php echo htmlspecialchars($request['candidateID']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="office-badge"><?php echo htmlspecialchars($request['officeID'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo date("M d, Y", strtotime($request['submitted_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock"></i> Pending Review
                                    </span>
                                    <?php elseif ($status === 'clear'): ?>
                                    <span class="status-badge status-cleared">
                                        <i class="fas fa-check"></i> Cleared
                                    </span>
                                    <?php elseif ($status === 'disciplinary_action'): ?>
                                    <span class="status-badge status-issues">
                                        <i class="fas fa-times"></i> Issues Found
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock"></i> Pending Review
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <p>© <?php echo date("Y"); ?> Electoral Commission | Election Committee Portal</p>
                <p><i class="fas fa-shield-alt"></i> Maintaining Electoral Integrity</p>
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
        
        // Prevent body scroll when sidebar is open on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('active') && window.innerWidth <= 768) {
                            body.style.overflow = 'hidden';
                        } else {
                            body.style.overflow = 'auto';
                        }
                    }
                });
            });
            
            observer.observe(sidebar, { attributes: true });
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>