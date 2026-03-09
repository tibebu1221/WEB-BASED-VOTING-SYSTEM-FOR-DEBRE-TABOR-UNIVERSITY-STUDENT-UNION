<?php
session_start();
include("connection.php");

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<script>alert("You are not authorized! Please login as an admin."); window.location = "login.php";</script>';
    exit();
}

// Fetch admin user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname, lname, email FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname'] ?? '');
    $LastName = htmlspecialchars($row['lname']);
    $email = htmlspecialchars($row['email']);
    $fullName = trim($FirstName . ' ' . $middleName . ' ' . $LastName);
    $initials = strtoupper(substr($FirstName, 0, 1) . substr($LastName, 0, 1));
} else {
    echo '<script>alert("Error: User not found."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Get user statistics
$totalUsers = getTotalUsers($conn);
$activeUsers = getActiveUsers($conn);
$inactiveUsers = getInactiveUsers($conn);
$adminUsers = getAdminUsers($conn);
$voterUsers = getVoterUsers($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management | Admin Dashboard</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
            --dark: #1f2937;
            --gray: #6b7280;
            --light: #f9fafb;
            --border: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 16px;
            --radius-sm: 10px;
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
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: var(--shadow);
        }

        .logo-text h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .logo-text p {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Navigation */
        .nav-menu {
            padding: 1.5rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.75rem;
            padding-left: 0.5rem;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-icon {
            width: 20px;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: var(--transition);
        }

        /* Top Header with Profile */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .page-title h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        .page-title p {
            color: var(--gray);
            font-size: 1rem;
        }

        /* Profile in Top Right */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-info {
            text-align: right;
        }

        .profile-info h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .profile-info p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .profile-badge {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gm-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.3), var(--shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .gm-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.4), var(--shadow-lg);
        }

        .gm-avatar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 70%
            );
            transform: rotate(45deg);
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .gm-text {
            font-size: 1.1rem;
            letter-spacing: 1px;
            font-weight: 900;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .profile-status {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 12px;
            height: 12px;
            background: var(--secondary);
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: var(--shadow-sm);
        }

        /* Buttons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--secondary), #0da271);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
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
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-info p {
            font-size: 0.95rem;
            color: var(--gray);
            font-weight: 500;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
        }

        .stat-icon.users { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.active { background: linear-gradient(135deg, var(--secondary), #0da271); }
        .stat-icon.inactive { background: linear-gradient(135deg, var(--danger), #dc2626); }
        .stat-icon.admins { background: linear-gradient(135deg, var(--purple), #7c3aed); }

        /* Create Account Card (Minimized) */
        .create-account-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 2px dashed var(--primary);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 2rem;
        }

        .create-account-card:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary);
            margin-right: 1.25rem;
            flex-shrink: 0;
        }

        .card-content {
            flex: 1;
        }

        .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .card-content p {
            color: var(--gray);
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .create-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .create-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Users Table Section */
        .users-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid var(--border);
        }

        .section-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            background: white;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Table Styling */
        .table-container {
            padding: 1.5rem 2rem;
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1000px;
        }

        .users-table thead th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--border);
            background: var(--light);
        }

        .users-table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid var(--border);
        }

        .users-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02) 0%, rgba(139, 92, 246, 0.02) 100%);
            transform: translateY(-2px);
        }

        .users-table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            color: var(--dark);
        }

        /* User Info Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-details h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .user-details p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Badges */
        .badge {
            padding: 0.4rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .badge-primary {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Action Buttons */
        .action-cell {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            font-size: 0.9rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--info), #2563eb);
        }

        .btn-status {
            background: linear-gradient(135deg, var(--secondary), #0da271);
        }

        .btn-deactivate {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--gray);
            font-size: 0.9rem;
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
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* Mobile Toggle */
        .menu-toggle {
            display: none;
            background: var(--primary);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 1.1rem;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .top-header {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
            
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info {
                text-align: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .create-account-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 1.5rem;
            }
            
            .card-icon {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
            
            .create-btn {
                width: 100%;
                justify-content: center;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.25rem;
            }
            
            .search-box {
                width: 100%;
            }
            
            .users-table td {
                padding: 1rem;
            }
            
            .gm-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-cell {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .top-header h1 {
                font-size: 1.5rem;
            }
            
            .gm-avatar {
                width: 45px;
                height: 45px;
                font-size: 1rem;
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-fade {
            animation: fadeIn 0.5s ease forwards;
        }

        .animate-slide {
            animation: slideIn 0.5s ease forwards;
        }

        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Account Manager</h2>
                        <p>Group Three System</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="nav-menu">
                <div class="nav-section">
                    <h4 class="nav-title">Main Navigation</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="system_admin.php" class="nav-link">
                                <i class="fas fa-home nav-icon"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_account.php" class="nav-link active">
                                <i class="fas fa-users-cog nav-icon"></i>
                                Account Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="a_candidate.php" class="nav-link">
                                <i class="fas fa-user-tie nav-icon"></i>
                                Candidates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="voters.php" class="nav-link">
                                <i class="fas fa-user-friends nav-icon"></i>
                                Voters
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <h4 class="nav-title">Election Tools</h4>
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
                                Results
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
                    <h4 class="nav-title">Analytics</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="a_generate.php" class="nav-link">
                                <i class="fas fa-chart-line nav-icon"></i>
                                Reports & Analytics
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-section">
                    <a href="logout.php" class="btn btn-outline" style="color: white; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); width: 100%;">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header with GM Avatar -->
            <header class="top-header animate-fade">
                <div class="page-title">
                    <h1>Account Management</h1>
                    <p>Manage all user accounts, roles, and permissions</p>
                </div>
                
                <div class="profile-section">
                    <div class="profile-info">
                        <h3>Group Three</h3>
                        <p>System Administrator</p>
                        <small><?php echo $email; ?></small>
                    </div>
                    <div class="profile-badge">
                        <div class="gm-avatar">
                            <span class="gm-text">GM</span>
                        </div>
                        <div class="profile-status"></div>
                    </div>
                </div>
            </header>

            <!-- Header Actions -->
            <div class="header-actions animate-fade delay-100" style="margin-bottom: 2rem;">
                <a href="a_generate.php?export=users" class="btn btn-primary">
                    <i class="fas fa-file-export"></i>
                    Export Report
                </a>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card animate-fade delay-100">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $totalUsers; ?></h3>
                            <p>Total Users</p>
                        </div>
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fade delay-200">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $activeUsers; ?></h3>
                            <p>Active Users</p>
                        </div>
                        <div class="stat-icon active">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fade delay-300">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $inactiveUsers; ?></h3>
                            <p>Inactive Users</p>
                        </div>
                        <div class="stat-icon inactive">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fade delay-400">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3><?php echo $adminUsers; ?></h3>
                            <p>Administrators</p>
                        </div>
                        <div class="stat-icon admins">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Account Card (Minimized) -->
            <div class="create-account-card animate-fade" onclick="window.location.href='create.php'">
                <div class="card-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="card-content">
                    <h3>Create New Account</h3>
                    <p>Add new users to the system with custom roles and permissions</p>
                </div>
                <button class="create-btn">
                    <i class="fas fa-plus"></i>
                    Create Account
                </button>
            </div>

            <!-- Users Table -->
            <section class="users-section animate-fade">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> All System Users</h2>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search users..." id="searchInput">
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User Profile</th>
                                <th>User ID</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php
                            // Fixed SQL query - removed created_at column
                            $stmt = $conn->prepare("SELECT u_id, fname, mname, lname, role, status FROM user ORDER BY fname");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()):
                                $userId = htmlspecialchars($row['u_id']);
                                $fname = htmlspecialchars($row['fname']);
                                $mname = htmlspecialchars($row['mname'] ?? '');
                                $lname = htmlspecialchars($row['lname']);
                                $role = htmlspecialchars($row['role']);
                                $status = $row['status'];
                                $fullName = trim($fname . ' ' . $mname . ' ' . $lname);
                                $userInitials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
                                
                                // Determine badge color based on role
                                $roleBadgeClass = 'badge-primary';
                                if ($role == 'admin') $roleBadgeClass = 'badge-danger';
                                if ($role == 'voter') $roleBadgeClass = 'badge-success';
                                if ($role == 'staff') $roleBadgeClass = 'badge-warning';
                            ?>
                            <tr class="user-row animate-slide">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar" style="background: linear-gradient(135deg, <?php echo getRandomColor(); ?>, <?php echo getRandomColor(); ?>);">
                                            <?php echo $userInitials; ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo $fullName; ?></h4>
                                            <p><?php echo $fname . '@election.com'; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: var(--light); padding: 0.4rem 0.6rem; border-radius: 5px; font-family: monospace; font-size: 0.85rem;">
                                        <?php echo $userId; ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge <?php echo $roleBadgeClass; ?>">
                                        <?php if($role == 'admin'): ?>
                                            <i class="fas fa-user-shield"></i>
                                        <?php elseif($role == 'voter'): ?>
                                            <i class="fas fa-user-check"></i>
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                        <?php echo ucfirst($role); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status == 1): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times-circle"></i>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-cell">
                                        <a href="edituser.php?key=<?php echo $userId; ?>" 
                                           class="action-btn btn-edit" 
                                           title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="status.php?status=<?php echo $userId; ?>" 
                                           class="action-btn <?php echo $status == 1 ? 'btn-deactivate' : 'btn-status'; ?>" 
                                           title="<?php echo $status == 1 ? 'Deactivate User' : 'Activate User'; ?>"
                                           onclick="return confirmAction('<?php echo $fname; ?>', '<?php echo $status == 1 ? 'deactivate' : 'activate'; ?>')">
                                            <i class="<?php echo $status == 1 ? 'fas fa-user-slash' : 'fas fa-user-check'; ?>"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; 
                            $stmt->close();
                            
                            if($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">
                                    <div style="font-size: 1rem; color: var(--gray); margin-bottom: 1rem;">
                                        <i class="fas fa-users-slash" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                                        <p>No users found in the system</p>
                                    </div>
                                    <a href="create.php" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.9rem;">
                                        <i class="fas fa-user-plus"></i>
                                        Create First User
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Footer -->
            <footer class="dashboard-footer">
                <div class="footer-links">
                    <a href="system_admin.php">Dashboard</a>
                    <a href="manage_account.php">Account Management</a>
                    <a href="a_candidate.php">Candidates</a>
                    <a href="voters.php">Voters</a>
                    <a href="adminv_result.php">Results</a>
                    <a href="a_generate.php">Analytics</a>
                </div>
                <p>© <?php echo date("Y"); ?> Group Three Election System | Account Management Portal</p>
                <p>Total Users: <?php echo $totalUsers; ?> | Active Users: <?php echo $activeUsers; ?> | Last Updated: <span id="currentTime"><?php echo date("M j, Y g:i A"); ?></span></p>
            </footer>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const userRows = document.querySelectorAll('.user-row');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            userRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Confirm action
        function confirmAction(userName, action) {
            const actionText = action === 'activate' ? 'activate' : 'deactivate';
            return confirm(`Are you sure you want to ${actionText} ${userName}'s account?`);
        }

        // Update current time
        function updateTime() {
            const now = new Date();
            const options = { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            };
            document.getElementById('currentTime').textContent = now.toLocaleString('en-US', options);
        }

        // Update time every minute
        updateTime();
        setInterval(updateTime, 60000);

        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .create-account-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Add animation to table rows
            userRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
            });

            // Create account card click
            const createCard = document.querySelector('.create-account-card');
            createCard.addEventListener('click', () => {
                window.location.href = 'create.php';
            });

            // GM Avatar hover effect
            const gmAvatar = document.querySelector('.gm-avatar');
            gmAvatar.addEventListener('mouseenter', () => {
                gmAvatar.style.transform = 'scale(1.1) rotate(5deg)';
            });
            
            gmAvatar.addEventListener('mouseleave', () => {
                gmAvatar.style.transform = 'scale(1) rotate(0deg)';
            });
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1200 && 
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
// Helper functions
function getTotalUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM user");
    return $result->fetch_assoc()['total'] ?? 0;
}

function getActiveUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as active FROM user WHERE status = 1");
    return $result->fetch_assoc()['active'] ?? 0;
}

function getInactiveUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as inactive FROM user WHERE status = 0");
    return $result->fetch_assoc()['inactive'] ?? 0;
}

function getAdminUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as admins FROM user WHERE role = 'admin'");
    return $result->fetch_assoc()['admins'] ?? 0;
}

function getVoterUsers($conn) {
    $result = $conn->query("SELECT COUNT(*) as voters FROM user WHERE role = 'voter'");
    return $result->fetch_assoc()['voters'] ?? 0;
}

function getRandomColor() {
    $colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#3b82f6'];
    return $colors[array_rand($colors)];
}

$conn->close();
?>