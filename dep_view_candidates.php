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

// Initialize variables
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$cgpa_filter = $_GET['cgpa'] ?? '';
$sort_by = $_GET['sort'] ?? 'name_asc';

// Check if discipline_status column exists
$check_column = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
$has_discipline_status = $check_column && $check_column->num_rows > 0;

// Build query
$sql = "SELECT c.*, 
               r.requestID, 
               r.officeID as nominated_office, ";
if ($has_discipline_status) {
    $sql .= "r.discipline_status as request_status, ";
} else {
    $sql .= "'nominated' as request_status, ";
}
$sql .= "       r.submitted_at as nomination_date
        FROM candidate c
        LEFT JOIN request r ON c.c_id = r.candidateID
        WHERE 1=1";
        
$params = [];
$types = '';

// Apply filters
if (!empty($search_term)) {
    $sql .= " AND (c.fname LIKE ? OR c.mname LIKE ? OR c.lname LIKE ? OR c.u_id LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if ($status_filter !== 'all') {
    if ($status_filter === 'active') {
        $sql .= " AND c.status = 1";
    } elseif ($status_filter === 'inactive') {
        $sql .= " AND c.status = 0";
    } elseif ($status_filter === 'nominated') {
        $sql .= " AND r.requestID IS NOT NULL";
    } elseif ($status_filter === 'not_nominated') {
        $sql .= " AND r.requestID IS NULL";
    }
}

if (!empty($cgpa_filter)) {
    if ($cgpa_filter === 'excellent') {
        $sql .= " AND c.cgpa >= 3.5";
    } elseif ($cgpa_filter === 'good') {
        $sql .= " AND c.cgpa >= 3.0 AND c.cgpa < 3.5";
    } elseif ($cgpa_filter === 'fair') {
        $sql .= " AND c.cgpa >= 2.75 AND c.cgpa < 3.0";
    } elseif ($cgpa_filter === 'poor') {
        $sql .= " AND c.cgpa < 2.75";
    }
}

// Apply sorting
switch ($sort_by) {
    case 'name_asc':
        $sql .= " ORDER BY c.fname ASC, c.mname ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY c.fname DESC, c.mname DESC";
        break;
    case 'cgpa_asc':
        $sql .= " ORDER BY c.cgpa ASC";
        break;
    case 'cgpa_desc':
        $sql .= " ORDER BY c.cgpa DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY COALESCE(r.submitted_at, '9999-12-31') ASC";
        break;
    case 'date_desc':
        $sql .= " ORDER BY COALESCE(r.submitted_at, '9999-12-31') DESC";
        break;
    default:
        $sql .= " ORDER BY c.fname ASC";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}
$stmt->close();

// Get statistics
$total_candidates = count($candidates);
$active_candidates = 0;
$eligible_candidates = 0;
$nominated_candidates = 0;

foreach ($candidates as $candidate) {
    if ($candidate['status'] == 1) {
        $active_candidates++;
    }
    if ($candidate['cgpa'] >= 2.75 && $candidate['status'] == 1) {
        $eligible_candidates++;
    }
    if ($candidate['requestID']) {
        $nominated_candidates++;
    }
}

// Get pending requests count for badge - FIXED: check if status column exists
$pending_count = 0;
$check_status_column = $conn->query("SHOW COLUMNS FROM request LIKE 'status'");
if ($check_status_column && $check_status_column->num_rows > 0) {
    $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request WHERE status = 'pending'");
} else {
    // If no status column, check for discipline_status or count all
    $check_discipline_column = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
    if ($check_discipline_column && $check_discipline_column->num_rows > 0) {
        $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request WHERE discipline_status = 'pending'");
    } else {
        $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request");
    }
}

$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
if ($result_pending->num_rows > 0) {
    $row = $result_pending->fetch_assoc();
    $pending_count = $row['count'];
}
$stmt_pending->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates | Department Portal</title>
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
            cursor: pointer;
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
        
        .icon-active {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        
        .icon-eligible {
            background: linear-gradient(135deg, var(--accent), #d97706);
        }
        
        .icon-nominated {
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
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .filter-box {
            min-width: 180px;
        }
        
        .sort-box {
            min-width: 200px;
        }
        
        .filter-select {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            background: white;
            transition: var(--transition);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
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
        
        .candidate-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
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
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .cgpa-good {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .cgpa-fair {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .cgpa-poor {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
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
        
        .nomination-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .nomination-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .nomination-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .nomination-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .nomination-cleared {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .nomination-disciplined {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .nomination-none {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray);
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
        
        .date-time {
            color: var(--gray);
            font-size: 0.9rem;
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
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
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
            
            .search-filter {
                flex-direction: column;
            }
            
            .search-box,
            .filter-box,
            .sort-box {
                width: 100%;
            }
            
            .candidates-table {
                display: block;
                overflow-x: auto;
            }
            
            .candidates-table th,
            .candidates-table td {
                white-space: nowrap;
            }
            
            .content-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .export-buttons {
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
                <a href="dep_nominate.php" class="nav-item">
                    <i class="fas fa-vote-yea nav-icon"></i>
                    <span class="nav-text">Nominate</span>
                    <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dep_view_candidates.php" class="nav-item active">
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
                    <i class="fas fa-users" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Candidate Directory</h1>
                </div>
                <p>Browse, search, and filter all registered candidates in the system</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card" onclick="applyFilter('status', 'all')">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_candidates; ?></h3>
                        <p>Total Candidates</p>
                    </div>
                </div>
                
                <div class="stat-card" onclick="applyFilter('status', 'active')">
                    <div class="stat-icon icon-active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $active_candidates; ?></h3>
                        <p>Active Accounts</p>
                    </div>
                </div>
                
                <div class="stat-card" onclick="applyFilter('cgpa', 'fair')">
                    <div class="stat-icon icon-eligible">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $eligible_candidates; ?></h3>
                        <p>Eligible (CGPA ≥ 2.75)</p>
                    </div>
                </div>
                
                <div class="stat-card" onclick="applyFilter('status', 'nominated')">
                    <div class="stat-icon icon-nominated">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $nominated_candidates; ?></h3>
                        <p>Nominated</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        All Candidates
                    </h2>
                    <div class="export-buttons">
                        <button class="btn btn-secondary" onclick="printTable()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-file-export"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <form method="get" action="" class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" placeholder="Search by name or student ID..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    
                    <div class="filter-box">
                        <select name="status" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            <option value="nominated" <?php echo $status_filter === 'nominated' ? 'selected' : ''; ?>>Nominated</option>
                            <option value="not_nominated" <?php echo $status_filter === 'not_nominated' ? 'selected' : ''; ?>>Not Nominated</option>
                        </select>
                    </div>
                    
                    <div class="filter-box">
                        <select name="cgpa" class="filter-select" onchange="this.form.submit()">
                            <option value="" <?php echo empty($cgpa_filter) ? 'selected' : ''; ?>>All CGPA</option>
                            <option value="excellent" <?php echo $cgpa_filter === 'excellent' ? 'selected' : ''; ?>>Excellent (≥ 3.5)</option>
                            <option value="good" <?php echo $cgpa_filter === 'good' ? 'selected' : ''; ?>>Good (3.0 - 3.49)</option>
                            <option value="fair" <?php echo $cgpa_filter === 'fair' ? 'selected' : ''; ?>>Fair (2.75 - 2.99)</option>
                            <option value="poor" <?php echo $cgpa_filter === 'poor' ? 'selected' : ''; ?>>Poor (< 2.75)</option>
                        </select>
                    </div>
                    
                    <div class="sort-box">
                        <select name="sort" class="filter-select" onchange="this.form.submit()">
                            <option value="name_asc" <?php echo $sort_by === 'name_asc' ? 'selected' : ''; ?>>Sort: Name A-Z</option>
                            <option value="name_desc" <?php echo $sort_by === 'name_desc' ? 'selected' : ''; ?>>Sort: Name Z-A</option>
                            <option value="cgpa_desc" <?php echo $sort_by === 'cgpa_desc' ? 'selected' : ''; ?>>Sort: CGPA High-Low</option>
                            <option value="cgpa_asc" <?php echo $sort_by === 'cgpa_asc' ? 'selected' : ''; ?>>Sort: CGPA Low-High</option>
                            <option value="date_desc" <?php echo $sort_by === 'date_desc' ? 'selected' : ''; ?>>Sort: Newest First</option>
                            <option value="date_asc" <?php echo $sort_by === 'date_asc' ? 'selected' : ''; ?>>Sort: Oldest First</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <a href="dep_view_candidates.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
                
                <?php if (empty($candidates)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>No Candidates Found</h3>
                    <p><?php echo !empty($search_term) ? 'No candidates match your search criteria. Try a different search term.' : 'No candidates have been registered yet.'; ?></p>
                    <a href="dep_nominate.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register Candidates
                    </a>
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="candidates-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Candidate</th>
                                <th>Student ID</th>
                                <th>CGPA</th>
                                <th>Account Status</th>
                                <th>Nomination Status</th>
                                <th>Nominated For</th>
                                <th>Nomination Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($candidates as $candidate):
                                // Determine CGPA badge class
                                if ($candidate['cgpa'] >= 3.5) {
                                    $cgpa_class = 'cgpa-excellent';
                                } elseif ($candidate['cgpa'] >= 3.0) {
                                    $cgpa_class = 'cgpa-good';
                                } elseif ($candidate['cgpa'] >= 2.75) {
                                    $cgpa_class = 'cgpa-fair';
                                } else {
                                    $cgpa_class = 'cgpa-poor';
                                }
                                
                                // Determine nomination badge
                                $request_status = $candidate['request_status'] ?? '';
                                $nomination_class = '';
                                $nomination_text = '';
                                $nomination_icon = '';
                                
                                if (!$candidate['requestID']) {
                                    $nomination_class = 'nomination-none';
                                    $nomination_text = 'Not Nominated';
                                    $nomination_icon = 'fa-times';
                                } elseif ($request_status === 'pending' || $request_status === 'nominated') {
                                    $nomination_class = 'nomination-pending';
                                    $nomination_text = 'Pending Review';
                                    $nomination_icon = 'fa-clock';
                                } elseif ($request_status === 'approved' || $request_status === 'clear') {
                                    $nomination_class = 'nomination-cleared';
                                    $nomination_text = 'Cleared';
                                    $nomination_icon = 'fa-check';
                                } elseif ($request_status === 'rejected' || $request_status === 'disciplinary_action') {
                                    $nomination_class = 'nomination-disciplined';
                                    $nomination_text = 'Disciplinary Issue';
                                    $nomination_icon = 'fa-times';
                                } else {
                                    $nomination_class = 'nomination-pending';
                                    $nomination_text = 'Nominated';
                                    $nomination_icon = 'fa-paper-plane';
                                }
                                
                                // Create avatar initials
                                $avatar_initials = strtoupper(substr($candidate['fname'], 0, 1) . substr($candidate['lname'], 0, 1));
                            ?>
                            <tr>
                                <td><?php echo $counter; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="candidate-avatar">
                                            <?php echo $avatar_initials; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($candidate['fname'] . ' ' . $candidate['mname'] . ' ' . $candidate['lname']); ?></strong><br>
                                            <small style="color: var(--gray);"><?php echo htmlspecialchars($candidate['department'] ?? 'N/A'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="student-id"><?php echo htmlspecialchars($candidate['u_id']); ?></span>
                                </td>
                                <td>
                                    <span class="cgpa-badge <?php echo $cgpa_class; ?>">
                                        <i class="fas fa-star"></i> <?php echo number_format($candidate['cgpa'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($candidate['status'] == 1): ?>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-times-circle"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="nomination-badge <?php echo $nomination_class; ?>">
                                        <i class="fas <?php echo $nomination_icon; ?>"></i> <?php echo $nomination_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($candidate['nominated_office']): ?>
                                        <span class="office-badge">
                                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($candidate['nominated_office']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($candidate['nomination_date']): ?>
                                        <span class="date-time">
                                            <?php echo date('M d, Y', strtotime($candidate['nomination_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$candidate['requestID'] && $candidate['status'] == 1 && $candidate['cgpa'] >= 2.75): ?>
                                        <a href="dep_nominate.php?student=<?php echo urlencode($candidate['u_id']); ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-paper-plane"></i> Nominate
                                        </a>
                                    <?php elseif ($candidate['requestID']): ?>
                                        <button class="btn btn-secondary btn-small" onclick="viewNominationDetails(<?php echo $candidate['requestID']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-small" disabled>
                                            <i class="fas fa-ban"></i> Not Eligible
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $counter++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary -->
                <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: var(--radius-sm);">
                    <h3 style="margin-bottom: 15px; color: var(--dark); font-size: 1.1rem;">
                        <i class="fas fa-chart-pie"></i> Summary
                    </h3>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <div>
                            <span style="color: var(--gray);">Showing:</span>
                            <strong><?php echo $total_candidates; ?></strong> candidates
                        </div>
                        <div>
                            <span style="color: var(--gray);">Active:</span>
                            <strong><?php echo $active_candidates; ?></strong> (<?php echo $total_candidates > 0 ? round(($active_candidates / $total_candidates) * 100) : 0; ?>%)
                        </div>
                        <div>
                            <span style="color: var(--gray);">Eligible:</span>
                            <strong><?php echo $eligible_candidates; ?></strong> (<?php echo $total_candidates > 0 ? round(($eligible_candidates / $total_candidates) * 100) : 0; ?>%)
                        </div>
                        <div>
                            <span style="color: var(--gray);">Nominated:</span>
                            <strong><?php echo $nominated_candidates; ?></strong> (<?php echo $eligible_candidates > 0 ? round(($nominated_candidates / $eligible_candidates) * 100) : 0; ?>% of eligible)
                        </div>
                    </div>
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
        
        // Apply filter from stats cards
        function applyFilter(filterType, filterValue) {
            const url = new URL(window.location.href);
            url.searchParams.set(filterType, filterValue);
            window.location.href = url.toString();
        }
        
        // Print table function
        function printTable() {
            const printContent = `
                <html>
                <head>
                    <title>Candidate List - <?php echo date('Y-m-d'); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #1a2a6c; margin-bottom: 10px; }
                        .summary { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                        th { background: #1a2a6c; color: white; }
                        .badge { padding: 4px 8px; border-radius: 10px; font-size: 0.85rem; }
                        .excellent { background: #10b981; color: white; }
                        .good { background: #3b82f6; color: white; }
                        .fair { background: #f59e0b; color: white; }
                        .poor { background: #ef4444; color: white; }
                    </style>
                </head>
                <body>
                    <h1>Candidate Directory</h1>
                    <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                    <div class="summary">
                        Total Candidates: <?php echo $total_candidates; ?> | 
                        Active: <?php echo $active_candidates; ?> | 
                        Eligible: <?php echo $eligible_candidates; ?> | 
                        Nominated: <?php echo $nominated_candidates; ?>
                    </div>
                    ${document.querySelector('.candidates-table').outerHTML}
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
        
        // Export to CSV function
        function exportToCSV() {
            const rows = document.querySelectorAll('.candidates-table tr');
            let csv = [];
            
            // Add headers
            const headers = [];
            document.querySelectorAll('.candidates-table th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            csv.push(headers.join(','));
            
            // Add data rows
            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('td').forEach(td => {
                    // Clean up the text (remove badges and icons)
                    let text = td.textContent.trim();
                    text = text.replace(/\n/g, ' ').replace(/\s+/g, ' ');
                    rowData.push(`"${text}"`);
                });
                if (rowData.length > 0) {
                    csv.push(rowData.join(','));
                }
            });
            
            // Create and download CSV file
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `candidates_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // View nomination details
        function viewNominationDetails(requestId) {
            // In a real implementation, this would open a modal or redirect to details page
            alert(`Nomination Details for Request ID: ${requestId}\n\nThis feature would show detailed nomination information in a future update.`);
        }
        
        // Auto-focus on search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
        });
        
        // Highlight search term in table
        <?php if (!empty($search_term)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const searchTerm = '<?php echo addslashes($search_term); ?>';
            const cells = document.querySelectorAll('.candidates-table td');
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            
            cells.forEach(cell => {
                const html = cell.innerHTML;
                const highlighted = html.replace(regex, '<mark style="background: yellow; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                if (html !== highlighted) {
                    cell.innerHTML = highlighted;
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
$conn->close();
?>