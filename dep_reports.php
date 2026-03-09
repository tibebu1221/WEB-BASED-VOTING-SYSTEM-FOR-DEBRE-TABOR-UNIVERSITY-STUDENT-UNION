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

// Get date range parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';

// Validate date range
if (!empty($start_date) && !empty($end_date)) {
    if (strtotime($start_date) > strtotime($end_date)) {
        $temp = $start_date;
        $start_date = $end_date;
        $end_date = $temp;
    }
}

// First, let's check the structure of the request table
$check_request_table = $conn->query("SHOW COLUMNS FROM request");
$request_columns = [];
while ($row = $check_request_table->fetch_assoc()) {
    $request_columns[] = $row['Field'];
}

// Determine the correct status column name for request table
$status_column = null;
$possible_status_columns = ['status', 'Status', 'STATUS', 'request_status', 'approval_status'];

foreach ($possible_status_columns as $col) {
    if (in_array($col, $request_columns)) {
        $status_column = $col;
        break;
    }
}

// Get pending requests count for badge
$pending_count = 0;
if ($status_column) {
    $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request WHERE $status_column = 'pending'");
    if ($stmt_pending) {
        $stmt_pending->execute();
        $result_pending = $stmt_pending->get_result();
        if ($result_pending->num_rows > 0) {
            $row = $result_pending->fetch_assoc();
            $pending_count = $row['count'];
        }
        $stmt_pending->close();
    }
} else {
    // If no status column found, check what columns actually exist
    $stmt_pending = $conn->prepare("SELECT COUNT(*) as count FROM request");
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    if ($result_pending->num_rows > 0) {
        $row = $result_pending->fetch_assoc();
        $pending_count = $row['count'];
    }
    $stmt_pending->close();
}

// Get overall statistics
$total_candidates = 0;
$active_candidates = 0;
$eligible_candidates = 0;
$nominated_candidates = 0;
$approved_requests = 0;
$rejected_requests = 0;
$pending_requests = 0;

// Total candidates
$stmt_total = $conn->prepare("SELECT COUNT(*) as count FROM candidate");
$stmt_total->execute();
$result_total = $stmt_total->get_result();
if ($result_total->num_rows > 0) {
    $row = $result_total->fetch_assoc();
    $total_candidates = $row['count'];
}
$stmt_total->close();

// Active candidates - Using is_active column (assuming 1 means active)
$stmt_active = $conn->prepare("SELECT COUNT(*) as count FROM candidate WHERE is_active = 1");
$stmt_active->execute();
$result_active = $stmt_active->get_result();
if ($result_active->num_rows > 0) {
    $row = $result_active->fetch_assoc();
    $active_candidates = $row['count'];
}
$stmt_active->close();

// Eligible candidates (CGPA ≥ 2.75 and active)
$stmt_eligible = $conn->prepare("SELECT COUNT(*) as count FROM candidate WHERE cgpa >= 2.75 AND is_active = 1");
$stmt_eligible->execute(); 
$result_eligible = $stmt_eligible->get_result();
if ($result_eligible->num_rows > 0) {
    $row = $result_eligible->fetch_assoc();
    $eligible_candidates = $row['count'];
}
$stmt_eligible->close();

// Nominated candidates
$stmt_nominated = $conn->prepare("SELECT COUNT(DISTINCT candidateID) as count FROM request");
$stmt_nominated->execute();
$result_nominated = $stmt_nominated->get_result();
if ($result_nominated->num_rows > 0) {
    $row = $result_nominated->fetch_assoc();
    $nominated_candidates = $row['count'];
}
$stmt_nominated->close();

// Request statistics - FIXED with dynamic status column
if ($status_column) {
    $stmt_requests = $conn->prepare("SELECT $status_column as status, COUNT(*) as count FROM request GROUP BY $status_column");
    $stmt_requests->execute();
    $result_requests = $stmt_requests->get_result();
    while ($row = $result_requests->fetch_assoc()) {
        $status_value = strtolower($row['status']);
        switch ($status_value) {
            case 'approved':
            case 'approve':
                $approved_requests = $row['count'];
                break;
            case 'rejected':
            case 'reject':
                $rejected_requests = $row['count'];
                break;
            case 'pending':
                $pending_requests = $row['count'];
                break;
            default:
                // Handle other possible status values
                if (stripos($status_value, 'approve') !== false) {
                    $approved_requests += $row['count'];
                } elseif (stripos($status_value, 'reject') !== false) {
                    $rejected_requests += $row['count'];
                } elseif (stripos($status_value, 'pending') !== false || stripos($status_value, 'wait') !== false) {
                    $pending_requests += $row['count'];
                }
                break;
        }
    }
    $stmt_requests->close();
} else {
    // If no status column found, let's see what's in the request table
    $stmt_requests = $conn->prepare("SELECT COUNT(*) as count FROM request");
    $stmt_requests->execute();
    $result_requests = $stmt_requests->get_result();
    if ($result_requests->num_rows > 0) {
        $row = $result_requests->fetch_assoc();
        $pending_requests = $row['count']; // Assume all are pending if we can't determine status
    }
    $stmt_requests->close();
}

// Get office-wise nominations
$office_nominations = [];
$stmt_office = $conn->prepare("SELECT officeID, COUNT(*) as count FROM request GROUP BY officeID ORDER BY count DESC");
$stmt_office->execute();
$result_office = $stmt_office->get_result();
while ($row = $result_office->fetch_assoc()) {
    $office_nominations[] = $row;
}
$stmt_office->close();

// Get monthly trends - FIXED to handle NULL submitted_at and dynamic status column
$monthly_trends = [];
if ($status_column) {
    $stmt_monthly = $conn->prepare("
        SELECT 
            DATE_FORMAT(COALESCE(submitted_at, CURDATE()), '%Y-%m') as month,
            COUNT(*) as nominations,
            SUM(CASE WHEN $status_column IN ('approved', 'approve') THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN $status_column IN ('rejected', 'reject') THEN 1 ELSE 0 END) as rejected
        FROM request 
        WHERE COALESCE(submitted_at, CURDATE()) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(COALESCE(submitted_at, CURDATE()), '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
} else {
    $stmt_monthly = $conn->prepare("
        SELECT 
            DATE_FORMAT(COALESCE(submitted_at, CURDATE()), '%Y-%m') as month,
            COUNT(*) as nominations,
            0 as approved,
            0 as rejected
        FROM request 
        WHERE COALESCE(submitted_at, CURDATE()) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(COALESCE(submitted_at, CURDATE()), '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
}

$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();
while ($row = $result_monthly->fetch_assoc()) {
    $monthly_trends[] = $row;
}
$stmt_monthly->close();

// Get CGPA distribution
$cgpa_distribution = [
    'excellent' => 0, // 3.5 - 4.0
    'good' => 0,      // 3.0 - 3.49
    'fair' => 0,      // 2.75 - 2.99
    'poor' => 0       // Below 2.75
];

$stmt_cgpa = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN cgpa >= 3.5 THEN 1 END) as excellent,
        COUNT(CASE WHEN cgpa >= 3.0 AND cgpa < 3.5 THEN 1 END) as good,
        COUNT(CASE WHEN cgpa >= 2.75 AND cgpa < 3.0 THEN 1 END) as fair,
        COUNT(CASE WHEN cgpa < 2.75 THEN 1 END) as poor
    FROM candidate
");
$stmt_cgpa->execute();
$result_cgpa = $stmt_cgpa->get_result();
if ($row = $result_cgpa->fetch_assoc()) {
    $cgpa_distribution = $row;
}
$stmt_cgpa->close();

// Calculate statistics
$approval_rate = $total_candidates > 0 ? round(($approved_requests / $total_candidates) * 100) : 0;
$nomination_rate = $eligible_candidates > 0 ? round(($nominated_candidates / $eligible_candidates) * 100) : 0;
$active_rate = $total_candidates > 0 ? round(($active_candidates / $total_candidates) * 100) : 0;
$eligibility_rate = $total_candidates > 0 ? round(($eligible_candidates / $total_candidates) * 100) : 0;

// Get program-wise statistics - Using 'dept' column from candidate table
$program_stats = [];

$sql = "
    SELECT 
        c.dept as program,
        COUNT(*) as total_candidates,
        SUM(CASE WHEN c.is_active = 1 THEN 1 ELSE 0 END) as active_candidates,
        SUM(CASE WHEN c.cgpa >= 2.75 AND c.is_active = 1 THEN 1 ELSE 0 END) as eligible_candidates,
        SUM(CASE WHEN r.requestID IS NOT NULL THEN 1 ELSE 0 END) as nominated_candidates
    FROM candidate c
    LEFT JOIN request r ON c.c_id = r.candidateID
    WHERE c.dept IS NOT NULL AND c.dept != ''
    GROUP BY c.dept
    ORDER BY total_candidates DESC
";

$result_program = $conn->query($sql);
if ($result_program) {
    while ($row = $result_program->fetch_assoc()) {
        $program_stats[] = $row;
    }
}

// If no program column, use a default placeholder
if (empty($program_stats)) {
    $program_stats[] = [
        'program' => 'All Candidates',
        'total_candidates' => $total_candidates,
        'active_candidates' => $active_candidates,
        'eligible_candidates' => $eligible_candidates,
        'nominated_candidates' => $nominated_candidates
    ];
}

// Handle Export Requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $current_tab = $_GET['tab'] ?? 'overview';
    
    if ($export_type === 'pdf') {
        // Generate PDF Report
        generatePDFReport($current_tab, $total_candidates, $active_candidates, $eligible_candidates, 
                         $nominated_candidates, $approved_requests, $pending_requests, $rejected_requests,
                         $office_nominations, $monthly_trends, $cgpa_distribution, $program_stats,
                         $start_date, $end_date, $FirstName . ' ' . $middleName);
        exit();
    } elseif ($export_type === 'excel') {
        // Generate Excel Report
        generateExcelReport($current_tab, $total_candidates, $active_candidates, $eligible_candidates, 
                           $nominated_candidates, $approved_requests, $pending_requests, $rejected_requests,
                           $office_nominations, $monthly_trends, $cgpa_distribution, $program_stats,
                           $start_date, $end_date, $FirstName . ' ' . $middleName);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Department Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-dark: #0d1b4c;
            --secondary: #b21f1f;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
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
        
        /* Report Controls */
        .report-controls {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .controls-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .date-range {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .date-input {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }
        
        /* Stats Grid */
        .stats-grid {
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
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
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
        
        .icon-approved {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .icon-pending {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }
        
        .icon-rejected {
            background: linear-gradient(135deg, var(--danger), #dc2626);
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
        
        .stat-trend {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .trend-up {
            color: var(--success);
        }
        
        .trend-down {
            color: var(--danger);
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
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .chart-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            padding: 25px;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .data-table th {
            background: #f9fafb;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-excellent { background: linear-gradient(90deg, #10b981, #34d399); }
        .progress-good { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .progress-fair { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .progress-poor { background: linear-gradient(90deg, #ef4444, #f87171); }
        
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
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        /* Report Tabs */
        .report-tabs {
            display: flex;
            background: white;
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .report-tab {
            flex: 1;
            padding: 18px 20px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }
        
        .report-tab:hover {
            background: #f9fafb;
            color: var(--primary);
        }
        
        .report-tab.active {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
            background: linear-gradient(to bottom, rgba(26, 42, 108, 0.05), transparent);
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
            
            .charts-grid {
                grid-template-columns: 1fr;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .controls-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .date-range {
                width: 100%;
            }
            
            .date-input {
                width: 100%;
            }
            
            .report-tabs {
                flex-direction: column;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .data-table th,
            .data-table td {
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
                <a href="dep_view_candidates.php" class="nav-item">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text">View Candidates</span>
                </a>
                <a href="dep_reports.php" class="nav-item active">
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
                    <i class="fas fa-chart-pie" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Reports & Analytics</h1>
                </div>
                <p>Comprehensive analysis and insights into candidate nominations and system performance</p>
            </div>
            
            <!-- Report Tabs -->
            <div class="report-tabs">
                <div class="report-tab active" onclick="showReport('overview')">
                    <i class="fas fa-tachometer-alt"></i> Overview
                </div>
                <div class="report-tab" onclick="showReport('nominations')">
                    <i class="fas fa-paper-plane"></i> Nominations
                </div>
                <div class="report-tab" onclick="showReport('candidates')">
                    <i class="fas fa-users"></i> Candidates
                </div>
                <div class="report-tab" onclick="showReport('programs')">
                    <i class="fas fa-graduation-cap"></i> Departments
                </div>
            </div>
            
            <!-- Report Controls -->
            <div class="report-controls">
                <div class="controls-header">
                    <h2>
                        <i class="fas fa-calendar-alt"></i>
                        Date Range Selection
                    </h2>
                    <div class="export-buttons">
                        <button type="button" class="btn btn-primary" onclick="generatePDFReport()">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                
                <form method="get" action="" class="date-range">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: var(--gray); font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i> From Date
                        </label>
                        <input type="date" name="start_date" class="date-input" value="<?php echo $start_date; ?>" required>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: var(--gray); font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i> To Date
                        </label>
                        <input type="date" name="end_date" class="date-input" value="<?php echo $end_date; ?>" required>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Range
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetDateRange()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Overview Report -->
            <div id="overview-report" class="report-content">
                <!-- Key Statistics -->
                <div class="stats-grid">
                    <div class="stat-card animate-in">
                        <div class="stat-icon icon-total">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_candidates; ?></h3>
                            <p>Total Candidates</p>
                            <div class="stat-trend">
                                <span class="trend-up">100%</span> of database
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card animate-in delay-1">
                        <div class="stat-icon icon-active">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_candidates; ?></h3>
                            <p>Active Accounts</p>
                            <div class="stat-trend">
                                <span class="trend-up"><?php echo $active_rate; ?>%</span> activation rate
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card animate-in delay-2">
                        <div class="stat-icon icon-eligible">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $eligible_candidates; ?></h3>
                            <p>Eligible Candidates</p>
                            <div class="stat-trend">
                                <span class="trend-up"><?php echo $eligibility_rate; ?>%</span> eligibility rate
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card animate-in delay-3">
                        <div class="stat-icon icon-nominated">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $nominated_candidates; ?></h3>
                            <p>Nominated Candidates</p>
                            <div class="stat-trend">
                                <span class="trend-up"><?php echo $nomination_rate; ?>%</span> of eligible
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Grid -->
                <div class="charts-grid">
                    <!-- CGPA Distribution Chart -->
                    <div class="chart-card animate-in">
                        <div class="chart-header">
                            <h3>
                                <i class="fas fa-chart-bar"></i>
                                CGPA Distribution
                            </h3>
                            <span style="color: var(--gray); font-size: 0.9rem;">All Candidates</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="cgpaChart"></canvas>
                        </div>
                        <div style="margin-top: 15px;">
                            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <div style="width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></div>
                                    <span style="font-size: 0.85rem;">Excellent (≥ 3.5)</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <div style="width: 12px; height: 12px; background: #3b82f6; border-radius: 2px;"></div>
                                    <span style="font-size: 0.85rem;">Good (3.0-3.49)</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;"></div>
                                    <span style="font-size: 0.85rem;">Fair (2.75-2.99)</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></div>
                                    <span style="font-size: 0.85rem;">Poor (< 2.75)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Status Chart -->
                    <div class="chart-card animate-in delay-1">
                        <div class="chart-header">
                            <h3>
                                <i class="fas fa-chart-pie"></i>
                                Nomination Status
                            </h3>
                            <span style="color: var(--gray); font-size: 0.9rem;">All Requests</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Nominations Report -->
            <div id="nominations-report" class="report-content" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-nominated">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $nominated_candidates; ?></h3>
                            <p>Total Nominations</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-approved">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $approved_requests; ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_requests; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-rejected">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $rejected_requests; ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Trends Chart -->
                <div class="content-area">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-chart-line"></i>
                            Monthly Nomination Trends
                        </h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <!-- Office-wise Nominations -->
                <div class="content-area" style="margin-top: 30px;">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-briefcase"></i>
                            Office-wise Nominations
                        </h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Office</th>
                                <th>Nominations</th>
                                <th>Percentage</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($office_nominations)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--gray); padding: 30px;">
                                    No nominations recorded yet.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($office_nominations as $office): 
                                    $percentage = $nominated_candidates > 0 ? round(($office['count'] / $nominated_candidates) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($office['officeID']); ?></strong>
                                    </td>
                                    <td><?php echo $office['count']; ?></td>
                                    <td><?php echo $percentage; ?>%</td>
                                    <td style="width: 200px;">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: var(--primary);"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Candidates Report -->
            <div id="candidates-report" class="report-content" style="display: none;">
                <div class="charts-grid">
                    <!-- Eligibility Distribution -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>
                                <i class="fas fa-chart-pie"></i>
                                Eligibility Distribution
                            </h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="eligibilityChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Account Status -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>
                                <i class="fas fa-user-check"></i>
                                Account Status
                            </h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="accountChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- CGPA Distribution Table -->
                <div class="content-area" style="margin-top: 30px;">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-chart-bar"></i>
                            CGPA Distribution Details
                        </h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>CGPA Range</th>
                                <th>Category</th>
                                <th>Candidates</th>
                                <th>Percentage</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cgpa_categories = [
                                ['range' => '3.5 - 4.0', 'category' => 'Excellent', 'count' => $cgpa_distribution['excellent'], 'class' => 'progress-excellent'],
                                ['range' => '3.0 - 3.49', 'category' => 'Good', 'count' => $cgpa_distribution['good'], 'class' => 'progress-good'],
                                ['range' => '2.75 - 2.99', 'category' => 'Fair', 'count' => $cgpa_distribution['fair'], 'class' => 'progress-fair'],
                                ['range' => 'Below 2.75', 'category' => 'Poor', 'count' => $cgpa_distribution['poor'], 'class' => 'progress-poor']
                            ];
                            
                            foreach ($cgpa_categories as $category):
                                $percentage = $total_candidates > 0 ? round(($category['count'] / $total_candidates) * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo $category['range']; ?></td>
                                <td>
                                    <span style="font-weight: 600; color: <?php 
                                        echo $category['category'] === 'Excellent' ? 'var(--success)' : 
                                             ($category['category'] === 'Good' ? 'var(--info)' : 
                                             ($category['category'] === 'Fair' ? 'var(--warning)' : 'var(--danger)'));
                                    ?>">
                                        <?php echo $category['category']; ?>
                                    </span>
                                </td>
                                <td><?php echo $category['count']; ?></td>
                                <td><?php echo $percentage; ?>%</td>
                                <td style="width: 200px;">
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $category['class']; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Programs Report -->
            <div id="programs-report" class="report-content" style="display: none;">
                <div class="content-area">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-graduation-cap"></i>
                            Department-wise Statistics
                        </h2>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total</th>
                                <th>Active</th>
                                <th>Eligible</th>
                                <th>Nominated</th>
                                <th>Nomination Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($program_stats)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--gray); padding: 30px;">
                                    No department data available.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($program_stats as $program): 
                                    $nomination_rate = $program['eligible_candidates'] > 0 ? round(($program['nominated_candidates'] / $program['eligible_candidates']) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($program['program'] ?: 'Unknown Department'); ?></strong>
                                    </td>
                                    <td><?php echo $program['total_candidates']; ?></td>
                                    <td><?php echo $program['active_candidates']; ?></td>
                                    <td><?php echo $program['eligible_candidates']; ?></td>
                                    <td><?php echo $program['nominated_candidates']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span><?php echo $nomination_rate; ?>%</span>
                                            <div class="progress-bar" style="flex: 1; max-width: 150px;">
                                                <div class="progress-fill" style="width: <?php echo $nomination_rate; ?>%; background: var(--primary);"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
        
        // Show/hide report sections
        function showReport(reportType) {
            // Hide all reports
            document.querySelectorAll('.report-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.report-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected report
            document.getElementById(reportType + '-report').style.display = 'block';
            
            // Add active class to clicked tab
            const clickedTab = Array.from(document.querySelectorAll('.report-tab')).find(tab => 
                tab.textContent.toLowerCase().includes(reportType)
            );
            if (clickedTab) clickedTab.classList.add('active');
            
            // Update charts if needed
            if (reportType === 'overview' && !window.cgpaChart) {
                initializeCharts();
            }
        }
        
        // Reset date range
        function resetDateRange() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            const formatDate = (date) => date.toISOString().split('T')[0];
            
            document.querySelector('input[name="start_date"]').value = formatDate(firstDay);
            document.querySelector('input[name="end_date"]').value = formatDate(lastDay);
            
            document.querySelector('.date-range form').submit();
        }
        
        // Initialize Charts
        function initializeCharts() {
            // CGPA Distribution Chart
            const cgpaCtx = document.getElementById('cgpaChart').getContext('2d');
            window.cgpaChart = new Chart(cgpaCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent (≥ 3.5)', 'Good (3.0-3.49)', 'Fair (2.75-2.99)', 'Poor (< 2.75)'],
                    datasets: [{
                        data: [
                            <?php echo $cgpa_distribution['excellent']; ?>,
                            <?php echo $cgpa_distribution['good']; ?>,
                            <?php echo $cgpa_distribution['fair']; ?>,
                            <?php echo $cgpa_distribution['poor']; ?>
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#3b82f6',
                            '#f59e0b',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $approved_requests; ?>,
                            <?php echo $pending_requests; ?>,
                            <?php echo $rejected_requests; ?>
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });
            
            // Monthly Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                    datasets: [
                        {
                            label: 'Nominations',
                            data: <?php echo json_encode(array_column($monthly_trends, 'nominations')); ?>,
                            borderColor: '#1a2a6c',
                            backgroundColor: 'rgba(26, 42, 108, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Approved',
                            data: <?php echo json_encode(array_column($monthly_trends, 'approved')); ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
            
            // Eligibility Chart
            const eligibilityCtx = document.getElementById('eligibilityChart').getContext('2d');
            new Chart(eligibilityCtx, {
                type: 'bar',
                data: {
                    labels: ['Eligible', 'Not Eligible'],
                    datasets: [{
                        label: 'Candidates',
                        data: [
                            <?php echo $eligible_candidates; ?>,
                            <?php echo $total_candidates - $eligible_candidates; ?>
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#ef4444'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Account Chart
            const accountCtx = document.getElementById('accountChart').getContext('2d');
            new Chart(accountCtx, {
                type: 'polarArea',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [
                            <?php echo $active_candidates; ?>,
                            <?php echo $total_candidates - $active_candidates; ?>
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            ticks: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
        
        // Get current active tab
        function getCurrentTab() {
            const activeTab = document.querySelector('.report-tab.active');
            if (activeTab) {
                if (activeTab.textContent.includes('Overview')) return 'overview';
                if (activeTab.textContent.includes('Nominations')) return 'nominations';
                if (activeTab.textContent.includes('Candidates')) return 'candidates';
                if (activeTab.textContent.includes('Departments')) return 'programs';
            }
            return 'overview';
        }
        
        // Generate PDF Report
        function generatePDFReport() {
            const currentTab = getCurrentTab();
            const url = new URL(window.location.href);
            url.searchParams.set('export', 'pdf');
            url.searchParams.set('tab', currentTab);
            window.open(url.toString(), '_blank');
        }
        
        // Export to Excel
        function exportToExcel() {
            const currentTab = getCurrentTab();
            const url = new URL(window.location.href);
            url.searchParams.set('export', 'excel');
            url.searchParams.set('tab', currentTab);
            window.location.href = url.toString();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });
    </script>
</body>
</html>
<?php

// Function to generate PDF report
function generatePDFReport($tab, $total_candidates, $active_candidates, $eligible_candidates, 
                         $nominated_candidates, $approved_requests, $pending_requests, $rejected_requests,
                         $office_nominations, $monthly_trends, $cgpa_distribution, $program_stats,
                         $start_date, $end_date, $officer_name) {
    
    // Create HTML content for PDF
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Election Reports - ' . ucfirst($tab) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #1a2a6c; border-bottom: 2px solid #1a2a6c; padding-bottom: 10px; }
            h2 { color: #333; margin-top: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .info { margin: 15px 0; }
            .info span { font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background-color: #f2f2f2; color: #333; padding: 10px; text-align: left; border: 1px solid #ddd; }
            td { padding: 8px; border: 1px solid #ddd; }
            .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
            .stat-box { background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #1a2a6c; }
            .stat-value { font-size: 24px; font-weight: bold; color: #1a2a6c; }
            .stat-label { color: #666; }
            .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>University Election System - ' . ucfirst($tab) . ' Report</h1>
            <p>Generated on: ' . date('F d, Y H:i:s') . '</p>
            <p>Generated by: ' . htmlspecialchars($officer_name) . '</p>
            <p>Date Range: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>
        </div>';
    
    // Overview Report Content
    if ($tab === 'overview') {
        $html .= '
        <h2>Key Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">' . $total_candidates . '</div>
                <div class="stat-label">Total Candidates</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $active_candidates . '</div>
                <div class="stat-label">Active Accounts</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $eligible_candidates . '</div>
                <div class="stat-label">Eligible Candidates</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $nominated_candidates . '</div>
                <div class="stat-label">Nominated Candidates</div>
            </div>
        </div>
        
        <h2>CGPA Distribution</h2>
        <table>
            <tr>
                <th>Category</th>
                <th>Range</th>
                <th>Candidates</th>
                <th>Percentage</th>
            </tr>
            <tr>
                <td>Excellent</td>
                <td>≥ 3.5</td>
                <td>' . $cgpa_distribution['excellent'] . '</td>
                <td>' . ($total_candidates > 0 ? round(($cgpa_distribution['excellent'] / $total_candidates) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Good</td>
                <td>3.0 - 3.49</td>
                <td>' . $cgpa_distribution['good'] . '</td>
                <td>' . ($total_candidates > 0 ? round(($cgpa_distribution['good'] / $total_candidates) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Fair</td>
                <td>2.75 - 2.99</td>
                <td>' . $cgpa_distribution['fair'] . '</td>
                <td>' . ($total_candidates > 0 ? round(($cgpa_distribution['fair'] / $total_candidates) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Poor</td>
                <td>&lt; 2.75</td>
                <td>' . $cgpa_distribution['poor'] . '</td>
                <td>' . ($total_candidates > 0 ? round(($cgpa_distribution['poor'] / $total_candidates) * 100) : 0) . '%</td>
            </tr>
        </table>
        
        <h2>Nomination Status</h2>
        <table>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            <tr>
                <td>Approved</td>
                <td>' . $approved_requests . '</td>
                <td>' . (($approved_requests + $pending_requests + $rejected_requests) > 0 ? round(($approved_requests / ($approved_requests + $pending_requests + $rejected_requests)) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Pending</td>
                <td>' . $pending_requests . '</td>
                <td>' . (($approved_requests + $pending_requests + $rejected_requests) > 0 ? round(($pending_requests / ($approved_requests + $pending_requests + $rejected_requests)) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Rejected</td>
                <td>' . $rejected_requests . '</td>
                <td>' . (($approved_requests + $pending_requests + $rejected_requests) > 0 ? round(($rejected_requests / ($approved_requests + $pending_requests + $rejected_requests)) * 100) : 0) . '%</td>
            </tr>
        </table>';
    }
    
    // Nominations Report Content
    elseif ($tab === 'nominations') {
        $html .= '
        <h2>Nomination Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">' . $nominated_candidates . '</div>
                <div class="stat-label">Total Nominations</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $approved_requests . '</div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $pending_requests . '</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $rejected_requests . '</div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <h2>Office-wise Nominations</h2>';
        
        if (!empty($office_nominations)) {
            $html .= '<table>
                <tr>
                    <th>Office</th>
                    <th>Nominations</th>
                    <th>Percentage</th>
                </tr>';
            
            foreach ($office_nominations as $office) {
                $percentage = $nominated_candidates > 0 ? round(($office['count'] / $nominated_candidates) * 100) : 0;
                $html .= '<tr>
                    <td>' . htmlspecialchars($office['officeID']) . '</td>
                    <td>' . $office['count'] . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
            }
            
            $html .= '</table>';
        } else {
            $html .= '<p>No nominations recorded yet.</p>';
        }
        
        $html .= '
        <h2>Monthly Trends (Last 6 Months)</h2>';
        
        if (!empty($monthly_trends)) {
            $html .= '<table>
                <tr>
                    <th>Month</th>
                    <th>Nominations</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                </tr>';
            
            foreach ($monthly_trends as $month) {
                $html .= '<tr>
                    <td>' . $month['month'] . '</td>
                    <td>' . $month['nominations'] . '</td>
                    <td>' . $month['approved'] . '</td>
                    <td>' . $month['rejected'] . '</td>
                </tr>';
            }
            
            $html .= '</table>';
        } else {
            $html .= '<p>No monthly trend data available.</p>';
        }
    }
    
    // Candidates Report Content
    elseif ($tab === 'candidates') {
        $html .= '
        <h2>Candidate Statistics</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value">' . $total_candidates . '</div>
                <div class="stat-label">Total Candidates</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $active_candidates . '</div>
                <div class="stat-label">Active Accounts</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $eligible_candidates . '</div>
                <div class="stat-label">Eligible Candidates</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">' . $nominated_candidates . '</div>
                <div class="stat-label">Nominated Candidates</div>
            </div>
        </div>
        
        <h2>Eligibility Distribution</h2>
        <table>
            <tr>
                <th>Category</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            <tr>
                <td>Eligible (CGPA ≥ 2.75 & Active)</td>
                <td>' . $eligible_candidates . '</td>
                <td>' . ($total_candidates > 0 ? round(($eligible_candidates / $total_candidates) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Not Eligible</td>
                <td>' . ($total_candidates - $eligible_candidates) . '</td>
                <td>' . ($total_candidates > 0 ? round((($total_candidates - $eligible_candidates) / $total_candidates) * 100) : 0) . '%</td>
            </tr>
        </table>
        
        <h2>Account Status</h2>
        <table>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
            <tr>
                <td>Active</td>
                <td>' . $active_candidates . '</td>
                <td>' . ($total_candidates > 0 ? round(($active_candidates / $total_candidates) * 100) : 0) . '%</td>
            </tr>
            <tr>
                <td>Inactive</td>
                <td>' . ($total_candidates - $active_candidates) . '</td>
                <td>' . ($total_candidates > 0 ? round((($total_candidates - $active_candidates) / $total_candidates) * 100) : 0) . '%</td>
            </tr>
        </table>';
    }
    
    // Programs Report Content (now Departments)
    elseif ($tab === 'programs') {
        $html .= '
        <h2>Department-wise Statistics</h2>';
        
        if (!empty($program_stats)) {
            $html .= '<table>
                <tr>
                    <th>Department</th>
                    <th>Total</th>
                    <th>Active</th>
                    <th>Eligible</th>
                    <th>Nominated</th>
                    <th>Nomination Rate</th>
                </tr>';
            
            foreach ($program_stats as $program) {
                $nomination_rate = $program['eligible_candidates'] > 0 ? round(($program['nominated_candidates'] / $program['eligible_candidates']) * 100) : 0;
                $html .= '<tr>
                    <td>' . htmlspecialchars($program['program'] ?: 'Unknown Department') . '</td>
                    <td>' . $program['total_candidates'] . '</td>
                    <td>' . $program['active_candidates'] . '</td>
                    <td>' . $program['eligible_candidates'] . '</td>
                    <td>' . $program['nominated_candidates'] . '</td>
                    <td>' . $nomination_rate . '%</td>
                </tr>';
            }
            
            $html .= '</table>';
        } else {
            $html .= '<p>No department data available.</p>';
        }
    }
    
    $html .= '
        <div class="footer">
            <p>© ' . date("Y") . ' University Election System | Department Officer Portal</p>
            <p>This report was generated automatically by the system.</p>
        </div>
    </body>
    </html>';
    
    // Output PDF headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="election_report_' . $tab . '_' . date('Y-m-d') . '.pdf"');
    
    // For now, output HTML that can be printed as PDF
    // In production, you would use a PDF library like TCPDF, mPDF, or Dompdf
    echo '<script>
        window.onload = function() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 1000);
        }
    </script>';
    echo $html;
    exit();
}

// Function to generate Excel report
function generateExcelReport($tab, $total_candidates, $active_candidates, $eligible_candidates, 
                           $nominated_candidates, $approved_requests, $pending_requests, $rejected_requests,
                           $office_nominations, $monthly_trends, $cgpa_distribution, $program_stats,
                           $start_date, $end_date, $officer_name) {
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="election_report_' . $tab . '_' . date('Y-m-d') . '.xls"');
    
    // Start output
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #1a2a6c; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin: 20px 0; }
        .title { font-size: 18px; font-weight: bold; color: #1a2a6c; margin: 15px 0; }
    </style>';
    echo '</head>';
    echo '<body>';
    
    // Header information
    echo '<div class="header">';
    echo '<h1>University Election System - ' . ucfirst($tab) . ' Report</h1>';
    echo '<p><strong>Generated on:</strong> ' . date('F d, Y H:i:s') . '</p>';
    echo '<p><strong>Generated by:</strong> ' . htmlspecialchars($officer_name) . '</p>';
    echo '<p><strong>Date Range:</strong> ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)) . '</p>';
    echo '</div>';
    
    // Overview Report Content
    if ($tab === 'overview') {
        echo '<div class="section">';
        echo '<div class="title">Key Statistics</div>';
        echo '<table border="1">';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>Total Candidates</td><td>' . $total_candidates . '</td></tr>';
        echo '<tr><td>Active Accounts</td><td>' . $active_candidates . '</td></tr>';
        echo '<tr><td>Eligible Candidates</td><td>' . $eligible_candidates . '</td></tr>';
        echo '<tr><td>Nominated Candidates</td><td>' . $nominated_candidates . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<div class="title">CGPA Distribution</div>';
        echo '<table border="1">';
        echo '<tr><th>Category</th><th>Range</th><th>Candidates</th><th>Percentage</th></tr>';
        echo '<tr><td>Excellent</td><td>≥ 3.5</td><td>' . $cgpa_distribution['excellent'] . '</td><td>' . ($total_candidates > 0 ? round(($cgpa_distribution['excellent'] / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Good</td><td>3.0 - 3.49</td><td>' . $cgpa_distribution['good'] . '</td><td>' . ($total_candidates > 0 ? round(($cgpa_distribution['good'] / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Fair</td><td>2.75 - 2.99</td><td>' . $cgpa_distribution['fair'] . '</td><td>' . ($total_candidates > 0 ? round(($cgpa_distribution['fair'] / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Poor</td><td>&lt; 2.75</td><td>' . $cgpa_distribution['poor'] . '</td><td>' . ($total_candidates > 0 ? round(($cgpa_distribution['poor'] / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<div class="title">Nomination Status</div>';
        $total_requests = $approved_requests + $pending_requests + $rejected_requests;
        echo '<table border="1">';
        echo '<tr><th>Status</th><th>Count</th><th>Percentage</th></tr>';
        echo '<tr><td>Approved</td><td>' . $approved_requests . '</td><td>' . ($total_requests > 0 ? round(($approved_requests / $total_requests) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Pending</td><td>' . $pending_requests . '</td><td>' . ($total_requests > 0 ? round(($pending_requests / $total_requests) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Rejected</td><td>' . $rejected_requests . '</td><td>' . ($total_requests > 0 ? round(($rejected_requests / $total_requests) * 100) : 0) . '%</td></tr>';
        echo '</table>';
        echo '</div>';
    }
    
    // Nominations Report Content
    elseif ($tab === 'nominations') {
        echo '<div class="section">';
        echo '<div class="title">Nomination Statistics</div>';
        echo '<table border="1">';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>Total Nominations</td><td>' . $nominated_candidates . '</td></tr>';
        echo '<tr><td>Approved</td><td>' . $approved_requests . '</td></tr>';
        echo '<tr><td>Pending</td><td>' . $pending_requests . '</td></tr>';
        echo '<tr><td>Rejected</td><td>' . $rejected_requests . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        if (!empty($office_nominations)) {
            echo '<div class="section">';
            echo '<div class="title">Office-wise Nominations</div>';
            echo '<table border="1">';
            echo '<tr><th>Office</th><th>Nominations</th><th>Percentage</th></tr>';
            foreach ($office_nominations as $office) {
                $percentage = $nominated_candidates > 0 ? round(($office['count'] / $nominated_candidates) * 100) : 0;
                echo '<tr><td>' . htmlspecialchars($office['officeID']) . '</td><td>' . $office['count'] . '</td><td>' . $percentage . '%</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        }
        
        if (!empty($monthly_trends)) {
            echo '<div class="section">';
            echo '<div class="title">Monthly Trends (Last 6 Months)</div>';
            echo '<table border="1">';
            echo '<tr><th>Month</th><th>Nominations</th><th>Approved</th><th>Rejected</th></tr>';
            foreach ($monthly_trends as $month) {
                echo '<tr><td>' . $month['month'] . '</td><td>' . $month['nominations'] . '</td><td>' . $month['approved'] . '</td><td>' . $month['rejected'] . '</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        }
    }
    
    // Candidates Report Content
    elseif ($tab === 'candidates') {
        echo '<div class="section">';
        echo '<div class="title">Candidate Statistics</div>';
        echo '<table border="1">';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>Total Candidates</td><td>' . $total_candidates . '</td></tr>';
        echo '<tr><td>Active Accounts</td><td>' . $active_candidates . '</td></tr>';
        echo '<tr><td>Eligible Candidates</td><td>' . $eligible_candidates . '</td></tr>';
        echo '<tr><td>Nominated Candidates</td><td>' . $nominated_candidates . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<div class="title">Eligibility Distribution</div>';
        echo '<table border="1">';
        echo '<tr><th>Category</th><th>Count</th><th>Percentage</th></tr>';
        echo '<tr><td>Eligible (CGPA ≥ 2.75 & Active)</td><td>' . $eligible_candidates . '</td><td>' . ($total_candidates > 0 ? round(($eligible_candidates / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Not Eligible</td><td>' . ($total_candidates - $eligible_candidates) . '</td><td>' . ($total_candidates > 0 ? round((($total_candidates - $eligible_candidates) / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '</table>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<div class="title">Account Status</div>';
        echo '<table border="1">';
        echo '<tr><th>Status</th><th>Count</th><th>Percentage</th></tr>';
        echo '<tr><td>Active</td><td>' . $active_candidates . '</td><td>' . ($total_candidates > 0 ? round(($active_candidates / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '<tr><td>Inactive</td><td>' . ($total_candidates - $active_candidates) . '</td><td>' . ($total_candidates > 0 ? round((($total_candidates - $active_candidates) / $total_candidates) * 100) : 0) . '%</td></tr>';
        echo '</table>';
        echo '</div>';
    }
    
    // Programs Report Content (now Departments)
    elseif ($tab === 'programs') {
        if (!empty($program_stats)) {
            echo '<div class="section">';
            echo '<div class="title">Department-wise Statistics</div>';
            echo '<table border="1">';
            echo '<tr><th>Department</th><th>Total</th><th>Active</th><th>Eligible</th><th>Nominated</th><th>Nomination Rate</th></tr>';
            foreach ($program_stats as $program) {
                $nomination_rate = $program['eligible_candidates'] > 0 ? round(($program['nominated_candidates'] / $program['eligible_candidates']) * 100) : 0;
                echo '<tr><td>' . htmlspecialchars($program['program'] ?: 'Unknown Department') . '</td><td>' . $program['total_candidates'] . '</td><td>' . $program['active_candidates'] . '</td><td>' . $program['eligible_candidates'] . '</td><td>' . $program['nominated_candidates'] . '</td><td>' . $nomination_rate . '%</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        }
    }
    
    echo '<div class="section">';
    echo '<p style="text-align: center; color: #666; font-size: 12px; margin-top: 30px;">';
    echo '© ' . date("Y") . ' University Election System | Department Officer Portal<br>';
    echo 'This report was generated automatically by the system.';
    echo '</p>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit();
}

$conn->close();
?>