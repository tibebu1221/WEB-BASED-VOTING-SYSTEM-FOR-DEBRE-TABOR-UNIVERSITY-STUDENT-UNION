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

// Fetch statistics for dashboard
$candidate_count = $conn->query("SELECT COUNT(*) as count FROM candidate")->fetch_assoc()['count'];
$voter_count = $conn->query("SELECT COUNT(*) as count FROM voter")->fetch_assoc()['count'];
$voted_count = $conn->query("SELECT COUNT(*) as count FROM voter WHERE status = 1")->fetch_assoc()['count'];
$not_voted_count = $voter_count - $voted_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports | Officer Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 15px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3498db;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #3498db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .header-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .header-text p {
            font-size: 14px;
            color: #ecf0f1;
            opacity: 0.9;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 25px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info i {
            font-size: 20px;
            color: #3498db;
        }

        .user-info span {
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            flex: 1;
        }

        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 18px 10px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .nav-link i {
            font-size: 20px;
            margin-bottom: 8px;
            color: #7f8c8d;
        }

        .nav-link:hover {
            background: #f8f9fa;
            color: #3498db;
        }

        .nav-link:hover i {
            color: #3498db;
        }

        .nav-link.active {
            background: linear-gradient(to bottom, #f8f9fa, white);
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .nav-link.active i {
            color: #3498db;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .welcome-section {
            text-align: right;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .welcome-text {
            font-size: 16px;
            color: #555;
        }

        .welcome-text strong {
            color: #3498db;
            font-size: 18px;
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 5px solid;
        }

        .stat-card.candidates {
            border-left-color: #3498db;
        }

        .stat-card.voters {
            border-left-color: #2ecc71;
        }

        .stat-card.voted {
            border-left-color: #9b59b6;
        }

        .stat-card.not-voted {
            border-left-color: #e74c3c;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-card.candidates .stat-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card.voters .stat-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .stat-card.voted .stat-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .stat-card.not-voted .stat-icon {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-info h3 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }

        /* Report Generator Section */
        .report-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        .section-header h2 {
            font-size: 28px;
            color: #2c3e50;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-header h2 i {
            color: #3498db;
        }

        .report-selector {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 2px dashed #3498db;
        }

        .report-selector label {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-dropdown {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            background: white;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .report-dropdown:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .report-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .report-content.active {
            display: block;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .report-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 700;
        }

        .report-actions {
            display: flex;
            gap: 15px;
        }

        .report-action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .report-action-btn.print {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
        }

        .report-action-btn.download {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }

        .report-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Report Tables */
        .report-table-container {
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .report-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }

        .report-table th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
        }

        .report-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .report-table td {
            padding: 15px;
            color: #555;
            font-size: 15px;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .status-voted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-not-voted {
            background: #fee2e2;
            color: #991b1b;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
        }

        .summary-card h4 {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .summary-stat {
            text-align: center;
        }

        .summary-stat .number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-stat .label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 25px 30px;
            border-top: 3px solid #3498db;
            text-align: center;
        }

        .footer-text {
            color: #ecf0f1;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 15px;
        }

        .footer-link {
            color: #3498db;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-link:hover {
            color: #2ecc71;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .report-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .report-action-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 calc(33.333% - 4px);
            }
            
            .nav-link {
                padding: 15px 5px;
                font-size: 12px;
            }
            
            .nav-link i {
                font-size: 18px;
                margin-bottom: 5px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .report-table th,
            .report-table td {
                padding: 12px 8px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .nav-item {
                flex: 0 0 calc(50% - 2px);
            }
            
            .nav-link {
                font-size: 11px;
            }
            
            .nav-link i {
                display: block;
                margin: 0 0 5px 0;
                font-size: 16px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .report-section,
            .report-section * {
                visibility: visible;
            }
            
            .report-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                box-shadow: none;
                padding: 20px;
            }
            
            .report-selector,
            .report-actions,
            .navbar,
            .header,
            .footer,
            .stats-container,
            .welcome-section {
                display: none !important;
            }
            
            .report-table {
                border: 2px solid #000;
            }
            
            .report-table th,
            .report-table td {
                border: 1px solid #000;
                padding: 10px;
            }
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
                    <a href="o_generate.php" class="nav-link active">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_comment.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>V_Comment</span>
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

            <!-- Dashboard Stats -->
            <div class="stats-container">
                <div class="stat-card candidates">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Candidates</h3>
                        <div class="stat-number"><?php echo $candidate_count; ?></div>
                    </div>
                </div>
                
                <div class="stat-card voters">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Voters</h3>
                        <div class="stat-number"><?php echo $voter_count; ?></div>
                    </div>
                </div>
                
                <div class="stat-card voted">
                    <div class="stat-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Voted</h3>
                        <div class="stat-number"><?php echo $voted_count; ?></div>
                    </div>
                </div>
                
                <div class="stat-card not-voted">
                    <div class="stat-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Not Voted</h3>
                        <div class="stat-number"><?php echo $not_voted_count; ?></div>
                    </div>
                </div>
            </div>

            <!-- Report Generator Section -->
            <div class="report-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-chart-pie"></i>
                        Generate Reports
                    </h2>
                </div>

                <div class="report-selector">
                    <label for="reportType">
                        <i class="fas fa-chart-line"></i>
                        Select Report Type:
                    </label>
                    <select id="reportType" class="report-dropdown" onchange="showReport(this.value)">
                        <option value="0">-- Select Report Type --</option>
                        <option value="candidates">Candidate Report</option>
                        <option value="voters">Voter Report</option>
                        <option value="election">Election Summary</option>
                    </select>
                </div>

                <!-- Default Message -->
                <div id="reportDefault" class="report-content active">
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-file-alt" style="font-size: 64px; color: #bdc3c7; margin-bottom: 20px;"></i>
                        <h3 style="color: #7f8c8d; margin-bottom: 15px;">Select a Report Type</h3>
                        <p style="color: #95a5a6;">Choose a report type from the dropdown above to generate detailed reports.</p>
                    </div>
                </div>

                <!-- Candidates Report -->
                <div id="reportCandidates" class="report-content">
                    <div class="report-header">
                        <h3 class="report-title">
                            <i class="fas fa-users"></i>
                            Candidate Report
                        </h3>
                        <div class="report-actions">
                            <button class="report-action-btn print" onclick="printReport()">
                                <i class="fas fa-print"></i>
                                Print Report
                            </button>
                            <button class="report-action-btn download" onclick="downloadReport('candidates')">
                                <i class="fas fa-download"></i>
                                Download CSV
                            </button>
                        </div>
                    </div>
                    
                    <div class="report-table-container">
                        <?php
                        $sel = $conn->query("SELECT c_id, fname, mname, lname, sex, age, candidate_photo FROM candidate");
                        $intt = $sel->num_rows;
                        ?>
                        
                        <div class="summary-card">
                            <h4><i class="fas fa-info-circle"></i> Report Summary</h4>
                            <div class="summary-stats">
                                <div class="summary-stat">
                                    <div class="number"><?php echo $intt; ?></div>
                                    <div class="label">Total Candidates</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number"><?php echo date('Y-m-d'); ?></div>
                                    <div class="label">Report Date</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number">Officer</div>
                                    <div class="label">Generated By</div>
                                </div>
                            </div>
                        </div>
                        
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Candidate ID</th>
                                    <th>Photo</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $sel->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['c_id']); ?></strong></td>
                                    <td>
                                        <?php 
                                        $photo = htmlspecialchars($row['candidate_photo']);
                                        $photo_path = file_exists($photo) ? $photo : 'img/default_candidate.jpg';
                                        ?>
                                        <img src="<?php echo $photo_path; ?>" 
                                             alt="Candidate Photo" 
                                             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Voters Report -->
                <div id="reportVoters" class="report-content">
                    <div class="report-header">
                        <h3 class="report-title">
                            <i class="fas fa-user-check"></i>
                            Voter Report
                        </h3>
                        <div class="report-actions">
                            <button class="report-action-btn print" onclick="printReport()">
                                <i class="fas fa-print"></i>
                                Print Report
                            </button>
                            <button class="report-action-btn download" onclick="downloadReport('voters')">
                                <i class="fas fa-download"></i>
                                Download CSV
                            </button>
                        </div>
                    </div>
                    
                    <div class="report-table-container">
                        <?php
                        $sel = $conn->query("SELECT vid, fname, mname, lname, age, sex, year, department, status FROM voter");
                        $intt = $sel->num_rows;
                        ?>
                        
                        <div class="summary-card">
                            <h4><i class="fas fa-info-circle"></i> Report Summary</h4>
                            <div class="summary-stats">
                                <div class="summary-stat">
                                    <div class="number"><?php echo $intt; ?></div>
                                    <div class="label">Total Voters</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number"><?php echo $voted_count; ?></div>
                                    <div class="label">Voted</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number"><?php echo $not_voted_count; ?></div>
                                    <div class="label">Not Voted</div>
                                </div>
                            </div>
                        </div>
                        
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Voter ID</th>
                                    <th>Full Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Year</th>
                                    <th>Department</th>
                                    <th>Voting Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $sel->fetch_assoc()): 
                                    $status_text = ($row['status'] == 1) ? 'Voted' : 'Not Voted';
                                    $status_class = ($row['status'] == 1) ? 'status-voted' : 'status-not-voted';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['vid']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Election Summary Report -->
                <div id="reportElection" class="report-content">
                    <div class="report-header">
                        <h3 class="report-title">
                            <i class="fas fa-chart-bar"></i>
                            Election Summary Report
                        </h3>
                        <div class="report-actions">
                            <button class="report-action-btn print" onclick="printReport()">
                                <i class="fas fa-print"></i>
                                Print Report
                            </button>
                            <button class="report-action-btn download" onclick="downloadReport('election')">
                                <i class="fas fa-download"></i>
                                Download PDF
                            </button>
                        </div>
                    </div>
                    
                    <div class="report-table-container">
                        <div class="summary-card">
                            <h4><i class="fas fa-info-circle"></i> Election Overview</h4>
                            <div class="summary-stats">
                                <div class="summary-stat">
                                    <div class="number"><?php echo $candidate_count; ?></div>
                                    <div class="label">Candidates</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number"><?php echo $voter_count; ?></div>
                                    <div class="label">Registered Voters</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number"><?php echo $voted_count; ?></div>
                                    <div class="label">Votes Cast</div>
                                </div>
                                <div class="summary-stat">
                                    <div class="number">
                                        <?php echo $voter_count > 0 ? round(($voted_count / $voter_count) * 100, 1) : 0; ?>%
                                    </div>
                                    <div class="label">Voter Turnout</div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 25px; border-radius: 12px; margin-top: 30px;">
                            <h4 style="color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-calendar-alt"></i>
                                Report Details
                            </h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <p style="color: #7f8c8d; margin-bottom: 5px;">Report Generated:</p>
                                    <p style="font-weight: 600; color: #2c3e50;"><?php echo date('F j, Y, g:i a'); ?></p>
                                </div>
                                <div>
                                    <p style="color: #7f8c8d; margin-bottom: 5px;">Generated By:</p>
                                    <p style="font-weight: 600; color: #2c3e50;"><?php echo "$FirstName $middleName"; ?> (Officer)</p>
                                </div>
                                <div>
                                    <p style="color: #7f8c8d; margin-bottom: 5px;">System:</p>
                                    <p style="font-weight: 600; color: #2c3e50;">Ethiopian Election Commission</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        function showReport(reportType) {
            // Hide all report sections
            document.querySelectorAll('.report-content').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show the selected report section
            let reportSection;
            switch(reportType) {
                case 'candidates':
                    reportSection = document.getElementById('reportCandidates');
                    break;
                case 'voters':
                    reportSection = document.getElementById('reportVoters');
                    break;
                case 'election':
                    reportSection = document.getElementById('reportElection');
                    break;
                default:
                    reportSection = document.getElementById('reportDefault');
            }
            
            if (reportSection) {
                reportSection.classList.add('active');
            }
        }

        function printReport() {
            window.print();
        }

        function downloadReport(type) {
            alert(`Downloading ${type} report...\n\nNote: This is a demo function. In production, this would generate and download the actual report file.`);
            
            // For actual implementation, you would need server-side code
            // Example: window.location.href = `download_report.php?type=${type}`;
        }

        // Initialize with default report
        document.addEventListener('DOMContentLoaded', function() {
            showReport('0');
            
            // Add animation to stats cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>