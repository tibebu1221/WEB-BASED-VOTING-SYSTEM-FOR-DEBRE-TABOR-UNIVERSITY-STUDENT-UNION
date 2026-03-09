<?php
session_start();
include("connection.php");

// Only allow department roles
$valid_roles = ['department', 'Department', 'dep', 'DEP', 'registrar', 'Registrar', 'Department Officer', 'dept'];
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $valid_roles, true)) {
    header("Location: login.php"); exit();
}
$_SESSION['role'] = 'department';

// Get user info
$user_id = $_SESSION['u_id'];
$user_stmt = $conn->prepare("SELECT fname, mname, lname FROM user WHERE u_id = ?");
$user_stmt->bind_param("s", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_name = htmlspecialchars(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));
$initials = strtoupper(substr($user['fname'] ?? 'D', 0, 1) . substr($user['lname'] ?? 'P', 0, 1));
$user_stmt->close();

// Fetch all requests sent by department
$res = $conn->query("
    SELECT r.*, c.fname, c.mname, c.lname, c.username, c.cgpa
    FROM request r
    JOIN candidate c ON r.candidateID = c.c_id
    ORDER BY r.requestID DESC
");

$total_requests = 0;
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

// Count statuses
if ($res) {
    $requests = [];
    while ($row = $res->fetch_assoc()) {
        $requests[] = $row;
        $total_requests++;
        
        if ($row['status'] === 'pending') {
            $pending_count++;
        } elseif ($row['status'] === 'approved') {
            $approved_count++;
        } elseif ($row['status'] === 'rejected') {
            $rejected_count++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nomination Requests | Department Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-light: #2a3a9c;
            --secondary: #0066CC;
            --secondary-light: #0077EE;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: linear-gradient(135deg, #1a2a6c, #2a3a9c);
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
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
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
            background: linear-gradient(135deg, #0066CC, #1a2a6c);
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
            background: linear-gradient(135deg, var(--warning), var(--secondary));
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
            border-left: 4px solid var(--secondary);
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
            background: var(--secondary);
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
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
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }
        
        .icon-pending {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }
        
        .icon-approved {
            background: linear-gradient(135deg, var(--success), #059669);
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
        
        .candidate-name {
            font-weight: 600;
            color: var(--dark);
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
        
        .cgpa-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            color: #7c3aed;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
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
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 204, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--gray);
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f3f4f6;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .view-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--secondary), var(--secondary-light));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 0.9rem;
        }
        
        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 204, 0.3);
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
            
            .requests-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
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
        
        /* Date Badge */
        .date-badge {
            font-size: 0.85rem;
            color: var(--gray);
            background: #f9fafb;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
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
                        <h2>Department Portal</h2>
                        <p>Nomination System</p>
                    </div>
                </a>
            </div>
            
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $user_name; ?></h3>
                    <p>Department Officer</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="dep.php" class="nav-item">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="dep_view_requests.php" class="nav-item active">
                    <i class="fas fa-list-alt nav-icon"></i>
                    <span class="nav-text">Nomination Requests</span>
                    <?php if ($pending_count > 0): ?>
                    <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dep_nominate.php" class="nav-item">
                    <i class="fas fa-user-plus nav-icon"></i>
                    <span class="nav-text">Nominate Candidate</span>
                </a>
                <a href="dep_results.php" class="nav-item">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text">Results</span>
                </a>
                <a href="dep_settings.php" class="nav-item">
                    <i class="fas fa-cog nav-icon"></i>
                    <span class="nav-text">Settings</span>
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
                    <i class="fas fa-list-alt" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Nomination Requests</h1>
                </div>
                <p>Track all candidate nominations submitted by the department</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_requests; ?></h3>
                        <p>Total Nominations</p>
                    </div>
                </div>
                
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
                    <div class="stat-icon icon-approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $approved_count; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $rejected_count; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-users"></i>
                        All Nominations
                    </h2>
                    <div style="color: var(--gray); font-size: 0.9rem;">
                        Showing <?php echo $total_requests; ?> nomination requests
                    </div>
                </div>
                
                <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Nominations Yet</h3>
                    <p>You haven't nominated any candidates yet. Start nominating candidates to see them here.</p>
                    <a href="dep_nominate.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Nominate Candidate
                    </a>
                </div>
                <?php else: ?>
                <div class="requests-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Username</th>
                                <th>CGPA</th>
                                <th>Office</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <?php
                            $candidate_name = htmlspecialchars($request['fname'] . ' ' . $request['mname'] . ' ' . $request['lname']);
                            $status_class = 'status-' . $request['status'];
                            $status_text = ucfirst($request['status']);
                            
                            // Format submitted date
                            $submitted_date = isset($request['submitted_at']) 
                                ? date("M d, Y", strtotime($request['submitted_at']))
                                : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span class="candidate-name"><?php echo $candidate_name; ?></span>
                                        <small class="candidate-id">ID: <?php echo htmlspecialchars($request['candidateID']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="candidate-id"><?php echo htmlspecialchars($request['username']); ?></span>
                                </td>
                                <td>
                                    <span class="cgpa-badge"><?php echo number_format($request['cgpa'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="office-badge"><?php echo htmlspecialchars($request['officeID']); ?></span>
                                </td>
                                <td>
                                    <span class="date-badge"><?php echo $submitted_date; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="view-btn" onclick="viewRequestDetails(<?php echo $request['requestID']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
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
                <p>© <?php echo date("Y"); ?> University Electoral System | Department Portal</p>
                <p><i class="fas fa-university"></i> Managing Student Nominations</p>
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
        
        // View request details
        function viewRequestDetails(requestID) {
            // This could open a modal or redirect to a details page
            alert(`Viewing details for request #${requestID}\nThis feature can be extended to show more details.`);
            // Example: window.location.href = `dep_request_details.php?id=${requestID}`;
        }
        
        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.requests-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>