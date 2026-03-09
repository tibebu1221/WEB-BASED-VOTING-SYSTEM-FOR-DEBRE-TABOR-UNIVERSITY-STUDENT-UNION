<?php
include("connection.php");
session_start();

// Check if user is logged in
if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged in! Please login to access this page');
        window.location.href = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch user details
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$FirstName = htmlspecialchars($row['fname']);
$middleName = htmlspecialchars($row['mname']);
$stmt->close();

// Get voter statistics
$total_voters = $conn->query("SELECT COUNT(*) as count FROM voter")->fetch_assoc()['count'];
$active_voters = $conn->query("SELECT COUNT(*) as count FROM voter WHERE is_active = 1")->fetch_assoc()['count'];
$inactive_voters = $conn->query("SELECT COUNT(*) as count FROM voter WHERE is_active = 0")->fetch_assoc()['count'];
$voted_voters = $conn->query("SELECT COUNT(*) as count FROM voter WHERE status = 1")->fetch_assoc()['count'];
$not_voted_voters = $conn->query("SELECT COUNT(*) as count FROM voter WHERE status = 0")->fetch_assoc()['count'];

// Get voter list
$result = $conn->query("SELECT vid, fname, mname, lname, sex, age, status, is_active, phone, email FROM voter ORDER BY is_active DESC, status ASC");
$count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Management | Officer Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e1e8ed;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3498db;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #9b59b6);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-img {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: rotate(5deg) scale(1.05);
        }

        .header-text h1 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .header-text p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 14px 28px;
            border-radius: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .user-info i {
            font-size: 22px;
            color: #3498db;
            background: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info span {
            color: white;
            font-weight: 700;
            font-size: 17px;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 5px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            flex: 1;
            position: relative;
        }

        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 18px 10px;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .nav-link i {
            font-size: 20px;
            margin-bottom: 8px;
            color: #94a3b8;
        }

        .nav-link:hover {
            background: #f1f5f9;
            color: #3498db;
        }

        .nav-link:hover i {
            color: #3498db;
        }

        .nav-link.active {
            background: linear-gradient(to bottom, #f1f5f9, white);
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .nav-link.active i {
            color: #3498db;
        }

        /* Main Content */
        .main-content {
            padding: 35px;
            background: #f8fafc;
            min-height: 600px;
        }

        .welcome-section {
            text-align: right;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 150px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3498db);
        }

        .welcome-text {
            font-size: 18px;
            color: #64748b;
            font-weight: 500;
        }

        .welcome-text strong {
            color: #3498db;
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 25px;
            border-left: 5px solid;
            cursor: pointer;
        }

        .stat-card.total { border-left-color: #3498db; }
        .stat-card.active { border-left-color: #2ecc71; }
        .stat-card.inactive { border-left-color: #e74c3c; }
        .stat-card.voted { border-left-color: #9b59b6; }
        .stat-card.not-voted { border-left-color: #f39c12; }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
        }

        .stat-card.total .stat-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.active .stat-icon { background: linear-gradient(135deg, #2ecc71, #27ae60); }
        .stat-card.inactive .stat-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.voted .stat-icon { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-card.not-voted .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }

        .stat-info h3 {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #2c3e50;
            line-height: 1;
        }

        /* Management Section */
        .management-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 30px;
            color: #2c3e50;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .section-header h2 i {
            color: #3498db;
            font-size: 34px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .add-btn {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(52, 152, 219, 0.4);
        }

        /* Voters Table */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .voters-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1000px;
        }

        .voters-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            position: sticky;
            top: 0;
        }

        .voters-table th {
            padding: 22px 20px;
            text-align: left;
            color: white;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 3px solid #3498db;
        }

        .voters-table th:first-child {
            border-top-left-radius: 15px;
        }

        .voters-table th:last-child {
            border-top-right-radius: 15px;
        }

        .voters-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            background: white;
        }

        .voters-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .voters-table td {
            padding: 20px;
            color: #475569;
            font-size: 15px;
            vertical-align: middle;
            font-weight: 500;
        }

        .voters-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .voters-table tbody tr:nth-child(even):hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            display: inline-block;
            min-width: 85px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #a7f3d0;
        }

        .status-inactive {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 2px solid #fecaca;
        }

        .status-cast {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            border: 2px solid #bfdbfe;
        }

        .status-not-cast {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 2px solid #fde68a;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            text-decoration: none;
        }

        .action-btn.edit {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .action-btn.toggle {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .action-btn:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* Voter Info */
        .voter-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .voter-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .voter-details h4 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .voter-details p {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .voter-details p i {
            width: 14px;
            color: #94a3b8;
        }

        /* No Voters Message */
        .no-voters {
            text-align: center;
            padding: 60px 20px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
            border: 2px dashed #cbd5e1;
            margin: 20px 0;
        }

        .no-voters i {
            font-size: 70px;
            color: #94a3b8;
            margin-bottom: 25px;
        }

        .no-voters h3 {
            color: #64748b;
            font-size: 26px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .no-voters p {
            color: #94a3b8;
            font-size: 17px;
            margin-bottom: 30px;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #2c3e50, #1a202c);
            padding: 30px 40px;
            border-top: 3px solid #3498db;
            text-align: center;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #9b59b6);
        }

        .footer-text {
            color: #cbd5e1;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .footer-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .footer-link:hover {
            color: #3498db;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                margin: 10px;
            }
            
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 30px 25px;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .section-header {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .header-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 calc(50% - 2px);
            }
            
            .nav-link {
                padding: 16px 8px;
                font-size: 13px;
            }
            
            .nav-link i {
                font-size: 18px;
                margin-bottom: 8px;
            }
            
            .main-content {
                padding: 25px;
            }
            
            .management-section {
                padding: 25px;
            }
            
            .voters-table th,
            .voters-table td {
                padding: 16px 12px;
                font-size: 14px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .header-text h1 {
                font-size: 22px;
            }
            
            .section-header h2 {
                font-size: 26px;
            }
        }

        @media (max-width: 480px) {
            .nav-item {
                flex: 0 0 100%;
            }
            
            .nav-link {
                padding: 14px;
                font-size: 14px;
                flex-direction: row;
                justify-content: center;
                gap: 15px;
            }
            
            .nav-link i {
                margin-bottom: 0;
                font-size: 18px;
            }
            
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 20px;
            }
            
            .header, .main-content, .footer {
                padding: 20px;
            }
            
            .management-section {
                padding: 20px;
            }
            
            .voter-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            animation: fadeIn 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9, #27ae60);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.jpg" alt="System Logo" class="logo-img">
                <div class="header-text">
                     <h1> DTUSU Voting System</h1>

                    <p>  DTUSU Elections Officer  </p>
                </div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo "$FirstName $middleName"; ?></span>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="e_officer.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_result.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_generate.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reg_voter.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <p class="welcome-text">
                    Welcome back, <strong><?php echo "$FirstName $middleName"; ?></strong>
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Voters</h3>
                        <div class="stat-number"><?php echo $total_voters; ?></div>
                    </div>
                </div>
                
                <div class="stat-card active">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Voters</h3>
                        <div class="stat-number"><?php echo $active_voters; ?></div>
                    </div>
                </div>
                
                <div class="stat-card inactive">
                    <div class="stat-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inactive Voters</h3>
                        <div class="stat-number"><?php echo $inactive_voters; ?></div>
                    </div>
                </div>
                
                <div class="stat-card voted">
                    <div class="stat-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Voted</h3>
                        <div class="stat-number"><?php echo $voted_voters; ?></div>
                    </div>
                </div>
                
                <div class="stat-card not-voted">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Not Voted</h3>
                        <div class="stat-number"><?php echo $not_voted_voters; ?></div>
                    </div>
                </div>
            </div>

            <!-- Management Section -->
            <div class="management-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-user-cog"></i>
                        Voter Management
                    </h2>
                    <div class="header-actions">
                        <a href="add_voter.php" class="header-btn add-btn">
                            <i class="fas fa-user-plus"></i>
                            Add New Voter
                        </a>
                    </div>
                </div>

                <?php if ($count > 0): ?>
                    <div class="table-container">
                        <table class="voters-table">
                            <thead>
                                <tr>
                                    <th>Voter Information</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Voting Status</th>
                                    <th>Account Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $ctrl = urlencode($row['vid']);
                                    $fname = htmlspecialchars($row['fname']);
                                    $mname = htmlspecialchars($row['mname']);
                                    $lname = htmlspecialchars($row['lname']);
                                    $sex = htmlspecialchars($row['sex']);
                                    $age = htmlspecialchars($row['age']);
                                    $status = $row['status'];
                                    $isActive = $row['is_active'];
                                    $phone = htmlspecialchars($row['phone']);
                                    $email = htmlspecialchars($row['email']);
                                    
                                    $initials = strtoupper(substr($fname, 0, 1) . substr($mname, 0, 1));
                                    $votingStatusClass = $status == 1 ? 'status-cast' : 'status-not-cast';
                                    $votingStatusText = $status == 1 ? 'Cast' : 'Not Cast';
                                    $accountStatusClass = $isActive == 1 ? 'status-active' : 'status-inactive';
                                    $accountStatusText = $isActive == 1 ? 'Active' : 'Inactive';
                                    $toggleAction = $isActive == 1 ? 'deactivate' : 'activate';
                                    $toggleIcon = $isActive == 1 ? 'fa-toggle-off' : 'fa-toggle-on';
                                    $toggleTooltip = $isActive == 1 ? 'Deactivate Account' : 'Activate Account';
                                ?>
                                <tr>
                                    <td>
                                        <div class="voter-info">
                                            <div class="voter-avatar"><?php echo $initials; ?></div>
                                            <div class="voter-details">
                                                <h4><?php echo "$fname $mname $lname"; ?></h4>
                                                <p>
                                                    <i class="fas fa-id-card"></i>
                                                    <span>ID: <?php echo htmlspecialchars($row['vid']); ?></span>
                                                </p>
                                                <p>
                                                    <i class="fas fa-phone"></i>
                                                    <span><?php echo $phone; ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $age; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $sex == 'male' ? 'status-active' : 'status-inactive'; ?>">
                                            <i class="fas fa-<?php echo $sex == 'male' ? 'male' : 'female'; ?>"></i>
                                            <?php echo ucfirst($sex); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $votingStatusClass; ?>">
                                            <i class="fas fa-<?php echo $status == 1 ? 'check-circle' : 'clock'; ?>"></i>
                                            <?php echo $votingStatusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $accountStatusClass; ?>">
                                            <i class="fas fa-<?php echo $isActive == 1 ? 'check-circle' : 'times-circle'; ?>"></i>
                                            <?php echo $accountStatusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_voter.php?key=<?php echo $ctrl; ?>" 
                                               class="action-btn edit" 
                                               title="Edit Voter">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="toggle_voter_status.php?key=<?php echo $ctrl; ?>&action=<?php echo $toggleAction; ?>" 
                                               class="action-btn toggle" 
                                               onclick="return confirmAction('<?php echo ucfirst($toggleAction); ?>', '<?php echo "$fname $mname"; ?>')"
                                               title="<?php echo $toggleTooltip; ?>">
                                                <i class="fas <?php echo $toggleIcon; ?>"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-voters">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Voters Found</h3>
                        <p>There are no voters registered in the system yet.</p>
                        <a href="add_voter.php" class="header-btn add-btn" style="width: auto; padding: 15px 40px;">
                            <i class="fas fa-user-plus"></i>
                            Add Your First Voter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p class="footer-text">
                &copy; <?php echo date("Y"); ?> Ethiopian Election Commission | Secure Online Voting System
            </p>
            
            <div class="footer-links">
                <a href="#" class="footer-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy Policy</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-file-contract"></i>
                    <span>Terms of Service</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Help Center</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Support</span>
                </a>
            </div>
        </footer>
    </div>

    <script>
        function confirmAction(action, voterName) {
            const message = `Are you sure you want to ${action.toLowerCase()} the account of ${voterName}?`;
            return confirm(message);
        }

        // Add animations to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('.voters-table tbody tr');
            tableRows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });

            // Make stat cards clickable
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    const statType = this.classList[1];
                    alert(`Showing details for: ${statType.replace('-', ' ')} voters`);
                    // In production, you could filter the table based on the stat clicked
                });
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>