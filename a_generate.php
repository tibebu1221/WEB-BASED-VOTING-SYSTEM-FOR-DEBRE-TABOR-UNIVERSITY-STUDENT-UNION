<?php
session_start();
include("connection.php");

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as an admin.');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch admin user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname, lname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    $LastName = htmlspecialchars($row['lname']);
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Get report data
$candidate_count = 0;
$voter_count = 0;

// Get candidate count
$stmt_cand = $conn->prepare("SELECT COUNT(*) as count FROM candidate");
$stmt_cand->execute();
$result_cand = $stmt_cand->get_result();
if ($row_cand = $result_cand->fetch_assoc()) {
    $candidate_count = $row_cand['count'];
}
$stmt_cand->close();

// Get voter count
$stmt_voter = $conn->prepare("SELECT COUNT(*) as count FROM voter");
$stmt_voter->execute();
$result_voter = $stmt_voter->get_result();
if ($row_voter = $result_voter->fetch_assoc()) {
    $voter_count = $row_voter['count'];
}
$stmt_voter->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report | Election Commission Dashboard</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-light: #2c3e8f;
            --secondary: #b21f1f;
            --accent: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #95a5a6;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .logo:hover {
            transform: rotate(-5deg) scale(1.05);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .header-title h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            background: linear-gradient(45deg, #fff, #b1c4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title p {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            transition: var(--transition);
            cursor: pointer;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 1.2rem;
        }

        .avatar-initials {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .user-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.125rem;
        }

        .user-info .role {
            font-size: 0.8rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .role i {
            font-size: 0.7rem;
        }

        /* Navigation Styles */
        .dashboard-nav {
            background: white;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 88px;
            z-index: 999;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 0;
        }

        .nav-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .nav-link {
            color: var(--dark);
            text-decoration: none;
            padding: 1.25rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 42, 108, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: linear-gradient(to bottom, rgba(26, 42, 108, 0.05), transparent);
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: linear-gradient(to bottom, rgba(26, 42, 108, 0.08), transparent);
        }

        .nav-icon {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }

        .nav-text {
            font-size: 0.9rem;
        }

        /* Main Content */
        .dashboard-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Report Dashboard Styles */
        .report-dashboard {
            display: grid;
            gap: 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, white, #f8f9ff);
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(26, 42, 108, 0.05), transparent);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .welcome-content h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .welcome-content h2 .highlight {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .welcome-content p {
            font-size: 1.1rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, var(--warning), #f1c40f); }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .stat-info p {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }

        /* Report Section */
        .report-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--box-shadow);
            margin-top: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header h3 i {
            color: var(--accent);
        }

        .report-selector {
            position: relative;
            margin-bottom: 2rem;
        }

        .report-select {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid #e0e6ed;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            background: white;
            color: var(--dark);
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%231a2a6c' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.5rem center;
            background-size: 16px;
        }

        .report-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }

        /* Report Tables */
        .report-table-container {
            overflow-x: auto;
            border-radius: var(--border-radius-sm);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .report-table th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-table th:first-child {
            border-top-left-radius: var(--border-radius-sm);
        }

        .report-table th:last-child {
            border-top-right-radius: var(--border-radius-sm);
        }

        .report-table tbody tr {
            border-bottom: 1px solid #f0f4f8;
            transition: var(--transition);
        }

        .report-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .report-table td {
            padding: 1.25rem 1.5rem;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-cast {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .status-not-cast {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.875rem 2rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(26, 42, 108, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-icon {
            font-size: 0.9rem;
        }

        /* Report Summary */
        .report-summary {
            background: linear-gradient(135deg, #f8fafc, #f0f4f8);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            margin-top: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .report-summary p {
            color: var(--dark);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-summary .count {
            font-weight: 700;
            color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        /* Footer */
        .dashboard-footer {
            background: var(--primary);
            color: white;
            padding: 2rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }

        .footer-container p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.7;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
        }

        /* Animations */
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

        .welcome-section, .stat-card, .report-section {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .report-section { animation-delay: 0.3s; }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .nav-link {
                padding: 1rem 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .header-container {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .user-profile {
                width: 100%;
                justify-content: center;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 1 0 auto;
            }
            
            .welcome-section {
                padding: 2rem;
            }
            
            .welcome-content h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header, .nav-container, .dashboard-main {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .nav-link {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .nav-icon {
                font-size: 1.1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .report-table th,
            .report-table td {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .user-profile {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .stat-card, .report-section {
                padding: 1.5rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        /* Print Styles */
        @media print {
            .dashboard-header,
            .dashboard-nav,
            .stat-card,
            .report-selector,
            .action-buttons,
            .dashboard-footer {
                display: none !important;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .dashboard-main {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            
            .report-section {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            
            .report-table {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .report-table th,
            .report-table td {
                border: 1px solid #ddd;
                padding: 10px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate avatar initials
            function generateInitials(name) {
                return name.split(' ')
                    .map(word => word.charAt(0))
                    .join('')
                    .toUpperCase()
                    .substring(0, 2);
            }

            // Set avatar initials
            const userName = "<?php echo $FirstName . ' ' . $LastName; ?>";
            const initials = generateInitials(userName);
            const avatar = document.querySelector('.avatar-initials');
            if (avatar) {
                avatar.textContent = initials;
            }

            // Handle report type selection
            const reportSelect = document.getElementById('reportType');
            const candidateReport = document.getElementById('candidateReport');
            const voterReport = document.getElementById('voterReport');

            function showReport() {
                const selectedValue = reportSelect.value;
                
                // Hide all reports
                candidateReport.style.display = 'none';
                voterReport.style.display = 'none';
                
                // Show selected report
                if (selectedValue === 'candidates') {
                    candidateReport.style.display = 'block';
                } else if (selectedValue === 'voters') {
                    voterReport.style.display = 'block';
                }
            }

            // Initialize report display
            showReport();

            // Add event listener
            if (reportSelect) {
                reportSelect.addEventListener('change', showReport);
            }

            // Print function
            window.printReport = function() {
                window.print();
            }

            // Export to CSV function (basic implementation)
            window.exportToCSV = function() {
                const reportType = reportSelect.value;
                if (!reportType) {
                    alert('Please select a report type first.');
                    return;
                }

                alert('CSV export feature would be implemented here.\nIn a full implementation, this would generate and download a CSV file.');
                // In a real implementation, you would:
                // 1. Make an AJAX request to get the data
                // 2. Convert to CSV format
                // 3. Create download link
            }

            // Add active state to current nav item
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (currentPage === linkHref) {
                    link.classList.add('active');
                }
            });

            // Add smooth animations to elements
            const animatedElements = document.querySelectorAll('.stat-card, .report-section');
            animatedElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });

            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.report-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</head>
<body>
    <!-- Header Section -->
    <header class="dashboard-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="system_admin.php" class="logo">
                    <img src="img/logo.JPG" alt="Election Commission Logo">
                </a>
                <div class="header-title">
                    <h1>Election Commission Dashboard</h1>
                    <p>Generate Comprehensive Election Reports</p>
                </div>
            </div>
            
            <div class="user-profile">
                <div class="avatar">
                    <div class="avatar-initials"></div>
                </div>
                <div class="user-info">
                    <h3><?php echo "$FirstName $middleName $LastName"; ?></h3>
                    <div class="role">
                        <i class="fas fa-user-shield"></i>
                        <span>System Administrator</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="dashboard-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="system_admin.php" class="nav-link">
                        <i class="fas fa-home nav-icon"></i>
                        <span class="nav-text">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_account.php" class="nav-link">
                        <i class="fas fa-users-cog nav-icon"></i>
                        <span class="nav-text">Manage Account</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="a_generate.php" class="nav-link active">
                        <i class="fas fa-file-alt nav-icon"></i>
                        <span class="nav-text">Generate Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="a_candidate.php" class="nav-link">
                        <i class="fas fa-user-tie nav-icon"></i>
                        <span class="nav-text">Candidates</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="voters.php" class="nav-link">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">Voters</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="adminv_result.php" class="nav-link">
                        <i class="fas fa-chart-bar nav-icon"></i>
                        <span class="nav-text">Result</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="setDate.php" class="nav-link">
                        <i class="fas fa-calendar-alt nav-icon"></i>
                        <span class="nav-text">Set Date</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="v_comment.php" class="nav-link">
                        <i class="fas fa-comments nav-icon"></i>
                        <span class="nav-text">V_Comment</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt nav-icon"></i>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="report-dashboard">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <h2>Welcome, <span class="highlight"><?php echo $FirstName . ' ' . $middleName; ?></span></h2>
                    <p>Generate comprehensive election reports, analyze data, and export information for official use.</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($candidate_count); ?></h3>
                                <p>Total Candidates</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($voter_count); ?></h3>
                                <p>Registered Voters</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Report Generation Section -->
            <section class="report-section">
                <div class="section-header">
                    <h3><i class="fas fa-file-export"></i> Generate Election Report</h3>
                </div>

                <!-- Report Type Selector -->
                <div class="report-selector">
                    <select id="reportType" class="report-select">
                        <option value="">Select Report Type...</option>
                        <option value="candidates">Candidates Report</option>
                        <option value="voters">Voters Report</option>
                    </select>
                </div>

                <!-- Candidates Report -->
                <div id="candidateReport" style="display: none;">
                    <div class="report-summary">
                        <p><i class="fas fa-info-circle"></i> Displaying <span class="count"><?php echo $candidate_count; ?></span> candidates in the system.</p>
                    </div>
                    
                    <?php
                    // Fetch candidate data
                    $stmt_cand = $conn->prepare("SELECT c_id, fname, mname, lname, sex, age FROM candidate ORDER BY lname, fname");
                    $stmt_cand->execute();
                    $result_cand = $stmt_cand->get_result();
                    ?>
                    
                    <div class="report-table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Candidate ID</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_cand->num_rows > 0): ?>
                                    <?php while ($row = $result_cand->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['c_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']); ?></td>
                                            <td><?php echo htmlspecialchars($row['sex']); ?></td>
                                            <td><?php echo htmlspecialchars($row['age']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 3rem;">
                                            <div class="empty-state">
                                                <i class="fas fa-user-slash"></i>
                                                <h4>No Candidates Found</h4>
                                                <p>There are no candidates registered in the system.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php $stmt_cand->close(); ?>
                </div>

                <!-- Voters Report -->
                <div id="voterReport" style="display: none;">
                    <div class="report-summary">
                        <p><i class="fas fa-info-circle"></i> Displaying <span class="count"><?php echo $voter_count; ?></span> voters in the system.</p>
                    </div>
                    
                    <?php
                    // First, let's check what columns exist in the voter table
                    $check_columns = $conn->query("DESCRIBE voter");
                    $columns = [];
                    if ($check_columns) {
                        while ($col = $check_columns->fetch_assoc()) {
                            $columns[] = $col['Field'];
                        }
                    }
                    
                    // Build query based on available columns
                    $select_fields = "Vid, fname, mname, lname";
                    $has_age = in_array('age', $columns);
                    $has_sex = in_array('sex', $columns);
                    $has_status = in_array('status', $columns);
                    
                    if ($has_age) $select_fields .= ", age";
                    if ($has_sex) $select_fields .= ", sex";
                    if ($has_status) $select_fields .= ", status";
                    
                    // Also check for other possible columns
                    $possible_columns = ['email', 'phone', 'address', 'station', 'kebele', 'wereda', 'zone'];
                    foreach ($possible_columns as $col) {
                        if (in_array($col, $columns)) {
                            $select_fields .= ", $col";
                        }
                    }
                    
                    // Fetch voter data with dynamic columns
                    $query = "SELECT $select_fields FROM voter ORDER BY lname, fname";
                    $stmt_voter = $conn->prepare($query);
                    if (!$stmt_voter) {
                        // If prepared statement fails, use basic columns
                        $query = "SELECT Vid, fname, mname, lname FROM voter ORDER BY lname, fname";
                        $stmt_voter = $conn->prepare($query);
                    }
                    
                    if ($stmt_voter) {
                        $stmt_voter->execute();
                        $result_voter = $stmt_voter->get_result();
                    } else {
                        // Fallback to simple query
                        $result_voter = $conn->query("SELECT Vid, fname, mname, lname FROM voter ORDER BY lname, fname");
                    }
                    ?>
                    
                    <div class="report-table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Voter ID</th>
                                    <th>Full Name</th>
                                    <?php if ($has_age): ?>
                                    <th>Age</th>
                                    <?php endif; ?>
                                    <?php if ($has_sex): ?>
                                    <th>Gender</th>
                                    <?php endif; ?>
                                    <!-- Check for station or other location columns -->
                                    <?php 
                                    $has_location = false;
                                    $location_columns = ['station', 'kebele', 'wereda', 'zone', 'address'];
                                    foreach ($location_columns as $loc_col) {
                                        if (in_array($loc_col, $columns)) {
                                            $has_location = true;
                                            $location_label = ucfirst($loc_col);
                                            break;
                                        }
                                    }
                                    if ($has_location): ?>
                                    <th><?php echo $location_label; ?></th>
                                    <?php endif; ?>
                                    <?php if ($has_status): ?>
                                    <th>Voting Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_voter && $result_voter->num_rows > 0): ?>
                                    <?php while ($row = $result_voter->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['Vid']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']); ?></td>
                                            <?php if ($has_age): ?>
                                            <td><?php echo isset($row['age']) ? htmlspecialchars($row['age']) : 'N/A'; ?></td>
                                            <?php endif; ?>
                                            <?php if ($has_sex): ?>
                                            <td><?php echo isset($row['sex']) ? htmlspecialchars($row['sex']) : 'N/A'; ?></td>
                                            <?php endif; ?>
                                            <?php if ($has_location): 
                                                $location_value = '';
                                                foreach ($location_columns as $loc_col) {
                                                    if (isset($row[$loc_col]) && !empty($row[$loc_col])) {
                                                        $location_value = $row[$loc_col];
                                                        break;
                                                    }
                                                }
                                            ?>
                                            <td><?php echo htmlspecialchars($location_value ?: 'N/A'); ?></td>
                                            <?php endif; ?>
                                            <?php if ($has_status): ?>
                                            <td>
                                                <?php if (isset($row['status'])): ?>
                                                <span class="status-badge <?php echo $row['status'] == 0 ? 'status-not-cast' : 'status-cast'; ?>">
                                                    <?php echo $row['status'] == 0 ? 'Not Cast' : 'Cast'; ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="status-badge status-not-cast">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo 2 + ($has_age ? 1 : 0) + ($has_sex ? 1 : 0) + ($has_location ? 1 : 0) + ($has_status ? 1 : 0); ?>" style="text-align: center; padding: 3rem;">
                                            <div class="empty-state">
                                                <i class="fas fa-users-slash"></i>
                                                <h4>No Voters Found</h4>
                                                <p>There are no voters registered in the system.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php 
                    if (isset($stmt_voter) && is_object($stmt_voter) && method_exists($stmt_voter, 'close')) {
                        $stmt_voter->close();
                    }
                    ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="exportToCSV()">
                        <i class="fas fa-file-csv btn-icon"></i>
                        Export to CSV
                    </button>
                    <button class="btn btn-primary" onclick="printReport()">
                        <i class="fas fa-print btn-icon"></i>
                        Print Report
                    </button>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="footer-container">
            <p>© <?php echo date("Y"); ?> Election Commission | Secure Online Voting System</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-shield-alt"></i> Security</a>
                <a href="#"><i class="fas fa-question-circle"></i> Help</a>
                <a href="#"><i class="fas fa-envelope"></i> Contact</a>
            </div>
        </div>
    </footer>
</body>
</html>
<?php
$conn->close();
?>