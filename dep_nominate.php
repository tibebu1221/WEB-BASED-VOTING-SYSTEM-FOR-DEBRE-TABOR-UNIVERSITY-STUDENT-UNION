<?php
session_start();
include("connection.php");

// BULLETPROOF AUTHORIZATION
$valid_roles = ['department', 'Department', 'DEP', 'dep', 'Department Officer', 'registrar', 'Registrar'];

if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $valid_roles, true)) {
    header("Location: login.php");
    exit();
}
$_SESSION['role'] = 'department'; // Normalize role

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle nomination
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'nominate' &&
    isset($_POST['token']) &&
    hash_equals($_SESSION['csrf_token'] ?? '', $_POST['token'] ?? '')
) {
    // Input validation and sanitization
    $u_id = htmlspecialchars($_POST['u_id']);
    $office = htmlspecialchars($_POST['office']);

    // Get the primary key (c_id) from the candidate table using u_id
    $stmt_c_id = $conn->prepare("SELECT c_id, fname, mname, lname FROM candidate WHERE u_id = ?");
    $stmt_c_id->bind_param("s", $u_id);
    $stmt_c_id->execute();
    $result_c_id = $stmt_c_id->get_result();
    $candidate_data = $result_c_id->fetch_assoc();
    $candID = $candidate_data['c_id'] ?? null;
    $candidate_name = $candidate_data['fname'] . ' ' . $candidate_data['mname'] . ' ' . $candidate_data['lname'];
    $stmt_c_id->close();

    if ($candID) {
        // Check if already nominated for any office
        $stmt_check = $conn->prepare("SELECT officeID FROM request WHERE candidateID = ?");
        $stmt_check->bind_param("i", $candID);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $existing_office = $result_check->fetch_assoc()['officeID'];
            $_SESSION['msg'] = "<div class='alert alert-warning'>Candidate <strong>{$candidate_name}</strong> is already nominated for <strong>{$existing_office}</strong> position.</div>";
        } else {
            // Insert new nomination
            // Check if discipline_status column exists
            $check_column = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
            if ($check_column && $check_column->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO request (candidateID, officeID, discipline_status, submitted_at) VALUES (?, ?, 'pending', NOW())");
                $stmt->bind_param("is", $candID, $office);
            } else {
                $stmt = $conn->prepare("INSERT INTO request (candidateID, officeID, submitted_at) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $candID, $office);
            }
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = "<div class='alert alert-success'>Candidate <strong>{$candidate_name}</strong> successfully nominated for <strong>{$office}</strong> position!</div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Error nominating candidate. Please try again.</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Error: Candidate not found or not registered as a candidate.</div>";
    }

    header("Location: dep_nominate.php");
    exit();
}

// Fetch user details for sidebar
$user_id = $_SESSION['u_id'];
$stmt_user = $conn->prepare("SELECT fname, mname, lname FROM user WHERE u_id = ?");
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();
$FirstName = htmlspecialchars($user_data['fname']);
$middleName = htmlspecialchars($user_data['mname'] ?? '');
$lastName = htmlspecialchars($user_data['lname'] ?? '');
$initials = strtoupper(substr($FirstName, 0, 1) . (!empty($middleName) ? substr($middleName, 0, 1) : ''));
$stmt_user->close();

// Get pending requests count for badge - FIXED: removed WHERE status = 'pending'
$pending_count = 0;
// Check if status column exists in request table
$check_status_column = $conn->query("SHOW COLUMNS FROM request LIKE 'status'");
if ($check_status_column && $check_status_column->num_rows > 0) {
    $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request WHERE status = 'pending'");
} else {
    // If status column doesn't exist, count all requests
    $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request");
}
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
if ($result_pending->num_rows > 0) {
    $row = $result_pending->fetch_assoc();
    $pending_count = $row['count'];
}
$stmt_pending->close();

// Get total eligible candidates count
$eligible_count = 0;
$stmt_eligible = $conn->prepare("SELECT COUNT(*) as count FROM candidate WHERE cgpa >= 2.75 AND status = 1");
$stmt_eligible->execute();
$result_eligible = $stmt_eligible->get_result();
if ($result_eligible->num_rows > 0) {
    $row = $result_eligible->fetch_assoc();
    $eligible_count = $row['count'];
}
$stmt_eligible->close();

// Get nominated candidates count
$nominated_count = 0;
$stmt_nominated = $conn->prepare("SELECT COUNT(DISTINCT candidateID) as count FROM request");
$stmt_nominated->execute();
$result_nominated = $stmt_nominated->get_result();
if ($result_nominated->num_rows > 0) {
    $row = $result_nominated->fetch_assoc();
    $nominated_count = $row['count'];
}
$stmt_nominated->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate Candidates | Department Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ... (CSS styles remain exactly the same) ... */
        :root {
            --primary: #1a2a6c;
            --primary-dark: #0d1b4c;
            --secondary: #b21f1f;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: linear-gradient(135deg, #0d1b4c, #1a2a6c);
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
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
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
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.4);
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
            background: rgba(26, 42, 108, 0.3);
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
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
        
        .icon-total {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }
        
        .icon-eligible {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        
        .icon-nominated {
            background: linear-gradient(135deg, var(--accent), #d97706);
        }
        
        .icon-pending {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
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
        
        /* Content Area */
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
        
        .action-buttons {
            display: flex;
            gap: 15px;
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
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-icon {
            font-size: 1.2rem;
        }
        
        /* Candidates Table */
        .candidates-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .candidates-table th {
            background: #f9fafb;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .candidates-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .candidates-table tr:hover {
            background: #f9fafb;
        }
        
        .student-id {
            font-family: monospace;
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .cgpa-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .cgpa-excellent {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .cgpa-good {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }
        
        .cgpa-fair {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .nomination-status {
            font-weight: 600;
        }
        
        .nomination-pending {
            color: var(--accent);
        }
        
        .nomination-approved {
            color: var(--success);
        }
        
        .nomination-rejected {
            color: var(--danger);
        }
        
        .office-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Form Styles */
        .nomination-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .office-select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.9rem;
            background: white;
            min-width: 180px;
            transition: var(--transition);
        }
        
        .office-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 42, 108, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--gray);
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f3f4f6;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #e5e7eb;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
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
            
            .candidates-table {
                display: block;
                overflow-x: auto;
            }
            
            .candidates-table th,
            .candidates-table td {
                white-space: nowrap;
            }
            
            .nomination-form {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .office-select {
                width: 100%;
            }
            
            .content-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .action-buttons {
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
            box-shadow: 0 8px 25px rgba(26, 42, 108, 0.3);
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
                <a href="dep.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Department</h2>
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
                    <p>Department Officer</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dep.php" class="nav-item">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="dep_nominate.php" class="nav-item active">
                    <i class="fas fa-vote-yea nav-icon"></i>
                    <span class="nav-text">Nominate</span>
                    <?php if ($eligible_count > 0): ?>
                    <span class="badge"><?php echo $eligible_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dep_view_candidates.php" class="nav-item">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text">View Candidates</span>
                </a>
                <a href="dep_reports.php" class="nav-item">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </nav>
            
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
                    <i class="fas fa-vote-yea" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Nominate Eligible Candidates</h1>
                </div>
                <p>Nominate candidates with CGPA ≥ 2.75 and activated accounts for various offices</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $eligible_count; ?></h3>
                        <p>Eligible Candidates</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-nominated">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $nominated_count; ?></h3>
                        <p>Nominated</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_count; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-eligible">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $eligible_count > 0 ? round(($nominated_count / $eligible_count) * 100) : 0; ?>%</h3>
                        <p>Nomination Rate</p>
                    </div>
                </div>
            </div>
            
            <!-- Message Display -->
            <?php if (isset($_SESSION['msg'])): ?>
                <?php echo $_SESSION['msg']; ?>
                <?php unset($_SESSION['msg']); ?>
            <?php endif; ?>
            
            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Eligible Candidates List
                    </h2>
                    <div class="action-buttons">
                        <button class="btn btn-secondary" onclick="refreshPage()">
                            <i class="fas fa-redo"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <?php
                // Fetch eligible candidates: CGPA >= 2.75 AND account status = 1
                $res = $conn->query("
                    SELECT c.*, r.requestID, r.officeID as nominated_office, 
                           r.discipline_status as request_status
                    FROM candidate c
                    LEFT JOIN request r ON c.c_id = r.candidateID
                    WHERE c.cgpa >= 2.75 AND c.status = 1
                    ORDER BY c.cgpa DESC, c.fname
                ");

                if ($res->num_rows === 0):
                ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>No Eligible Candidates Found</h3>
                    <p>No candidates meet the eligibility criteria (CGPA ≥ 2.75 and activated account).</p>
                    <a href="dep.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="candidates-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>CGPA</th>
                                <th>Account Status</th>
                                <th>Nomination Status</th>
                                <th>Office</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            while ($r = $res->fetch_assoc()):
                                // Determine CGPA badge class
                                if ($r['cgpa'] >= 3.5) {
                                    $cgpa_class = 'cgpa-excellent';
                                } elseif ($r['cgpa'] >= 3.0) {
                                    $cgpa_class = 'cgpa-good';
                                } else {
                                    $cgpa_class = 'cgpa-fair';
                                }
                                
                                // Determine nomination status based on discipline_status or request existence
                                $nomination_status = '';
                                $nomination_class = '';
                                if ($r['requestID']) {
                                    if ($r['request_status'] === 'pending') {
                                        $nomination_status = 'Pending Review';
                                        $nomination_class = 'nomination-pending';
                                    } elseif ($r['request_status'] === 'clear') {
                                        $nomination_status = 'Cleared';
                                        $nomination_class = 'nomination-approved';
                                    } elseif ($r['request_status'] === 'disciplinary_action') {
                                        $nomination_status = 'Disciplinary Issue';
                                        $nomination_class = 'nomination-rejected';
                                    } else {
                                        $nomination_status = 'Nominated';
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['fname'] . ' ' . $r['mname'] . ' ' . $r['lname']); ?></strong>
                                </td>
                                <td>
                                    <span class="student-id"><?php echo htmlspecialchars($r['u_id']); ?></span>
                                </td>
                                <td>
                                    <span class="cgpa-badge <?php echo $cgpa_class; ?>">
                                        <i class="fas fa-star"></i> <?php echo number_format($r['cgpa'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i> Activated
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['requestID']): ?>
                                        <span class="nomination-status <?php echo $nomination_class; ?>">
                                            <?php if ($r['request_status'] === 'pending'): ?>
                                                <i class="fas fa-clock"></i> 
                                            <?php elseif ($r['request_status'] === 'clear'): ?>
                                                <i class="fas fa-check"></i> 
                                            <?php elseif ($r['request_status'] === 'disciplinary_action'): ?>
                                                <i class="fas fa-times"></i> 
                                            <?php else: ?>
                                                <i class="fas fa-paper-plane"></i> 
                                            <?php endif; ?>
                                            <?php echo $nomination_status; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">
                                            <i class="fas fa-times"></i> Not Nominated
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['nominated_office']): ?>
                                        <span class="office-badge">
                                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($r['nominated_office']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$r['requestID']): ?>
                                    <form method="post" class="nomination-form">
                                        <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="nominate">
                                        <input type="hidden" name="u_id" value="<?php echo $r['u_id']; ?>">
                                        
                                        <select name="office" class="office-select" required>
                                            <option value="" disabled selected>Select Office</option>
                                            <option value="President">President</option>
                                            <option value="Vice President">Vice President</option>
                                            <option value="Secretary">Secretary</option>
                                            <option value="Academic Officer">Academic Officer</option>
                                            <option value="Gender Officer">Gender Officer</option>
                                        </select>
                                        
                                        <button type="submit" class="btn btn-primary btn-small">
                                            <i class="fas fa-paper-plane"></i> Nominate
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color: var(--success); font-weight: 500;">
                                            <i class="fas fa-check"></i> Already Nominated
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $counter++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <p>© <?php echo date("Y"); ?> University Election System | Department Officer Portal</p>
                <p><i class="fas fa-graduation-cap"></i> Promoting Academic Excellence and Student Leadership</p>
            </footer>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
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
        
        // Form validation and confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.nomination-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const officeSelect = this.querySelector('select[name="office"]');
                    const candidateName = this.closest('tr').querySelector('td:nth-child(2) strong').textContent;
                    const studentId = this.querySelector('input[name="u_id"]').value;
                    
                    if (!officeSelect.value) {
                        e.preventDefault();
                        alert('Please select an office before nominating.');
                        officeSelect.focus();
                        return false;
                    }
                    
                    const confirmation = confirm(`Are you sure you want to nominate ${candidateName} (${studentId}) for ${officeSelect.value} position?`);
                    if (!confirmation) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // Auto-hide success messages after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
        
        // Refresh page function
        function refreshPage() {
            window.location.reload();
        }
        
        // Highlight newly nominated rows
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('nominated')) {
            const nominatedId = urlParams.get('nominated');
            const rows = document.querySelectorAll('tr');
            rows.forEach(row => {
                if (row.textContent.includes(nominatedId)) {
                    row.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    row.style.transition = 'background-color 0.5s ease';
                    
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 3000);
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>