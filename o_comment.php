<?php
ob_start();
include("connection.php");
session_start();

// --- 1. Security Check and Login Redirect ---
if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged in! Please login to access this page');
        window.location.href = 'login.php';
    </script>
    <?php
    ob_end_flush();
    exit();
}

// --- 2. Fetch User Details ---
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user->num_rows > 0) {
    $row_user = $result_user->fetch_assoc();
    $FirstName = htmlspecialchars($row_user['fname']);
    $middleName = htmlspecialchars($row_user['mname']);
} else {
    $FirstName = "Election";
    $middleName = "Officer";
}
$stmt->close();

// --- 3. Fetch Comments Data ---
$result_unread = $conn->query("SELECT c_id FROM comment WHERE status = 'unread'");
$countav = $result_unread->num_rows;

$result_all = $conn->query("SELECT * FROM comment ORDER BY date DESC");
$count = $result_all->num_rows;

// Get comment statistics
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$today_comments = $conn->query("SELECT COUNT(*) as count FROM comment WHERE DATE(date) = '$today'")->fetch_assoc()['count'];
$yesterday_comments = $conn->query("SELECT COUNT(*) as count FROM comment WHERE DATE(date) = '$yesterday'")->fetch_assoc()['count'];
$total_comments = $conn->query("SELECT COUNT(*) as count FROM comment")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Comments | Officer Portal</title>
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

        /* Stats Cards */
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

        .stat-card.total {
            border-left-color: #3498db;
        }

        .stat-card.unread {
            border-left-color: #e74c3c;
        }

        .stat-card.today {
            border-left-color: #2ecc71;
        }

        .stat-card.yesterday {
            border-left-color: #9b59b6;
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

        .stat-card.total .stat-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .stat-card.unread .stat-icon {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-card.today .stat-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .stat-card.yesterday .stat-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
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

        /* Comments Section */
        .comments-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

        .section-actions {
            display: flex;
            gap: 15px;
        }

        .action-btn {
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
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Comments Table */
        .comments-table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .comments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .comments-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }

        .comments-table th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .comments-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
        }

        .comments-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .comments-table td {
            padding: 15px;
            color: #555;
            font-size: 15px;
            vertical-align: middle;
        }

        .comments-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .status-unread {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-read {
            background: #d1fae5;
            color: #065f46;
        }

        /* Action Buttons in Table */
        .table-actions {
            display: flex;
            gap: 10px;
        }

        .table-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .table-btn.view {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .table-btn.delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .table-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* User Avatar */
        .user-avatar {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        /* Email Column */
        .email-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* No Comments Message */
        .no-comments {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #bdc3c7;
        }

        .no-comments i {
            font-size: 64px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .no-comments h3 {
            color: #7f8c8d;
            font-size: 24px;
            margin-bottom: 10px;
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
            
            .section-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .comments-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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
            
            .comments-table th,
            .comments-table td {
                padding: 12px 8px;
                font-size: 14px;
            }
            
            /* Hide email column on mobile */
            .email-column {
                display: none;
            }
            
            .table-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .table-btn {
                width: 30px;
                height: 30px;
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
            
            .stats-container {
                grid-template-columns: 1fr;
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
                    <a href="o_generate.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Generate Report</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_comment.php" class="nav-link active">
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

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Comments</h3>
                        <div class="stat-number"><?php echo $total_comments; ?></div>
                    </div>
                </div>
                
                <div class="stat-card unread">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Unread Comments</h3>
                        <div class="stat-number"><?php echo $countav; ?></div>
                    </div>
                </div>
                
                <div class="stat-card today">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Today's Comments</h3>
                        <div class="stat-number"><?php echo $today_comments; ?></div>
                    </div>
                </div>
                
                <div class="stat-card yesterday">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Yesterday's Comments</h3>
                        <div class="stat-number"><?php echo $yesterday_comments; ?></div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="comments-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-inbox"></i>
                        Voter Comments Management
                    </h2>
                    <div class="section-actions">
                        <button class="action-btn" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i>
                            Mark All as Read
                        </button>
                    </div>
                </div>

                <?php if ($count > 0): ?>
                    <div class="comments-table-container">
                        <table class="comments-table">
                            <thead>
                                <tr>
                                    <th>Voter</th>
                                    <th class="email-column">Email</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_all->fetch_assoc()): 
                                    $ctrl = urlencode($row['c_id']);
                                    $name = htmlspecialchars($row['name']);
                                    $email = htmlspecialchars($row['email']);
                                    $date = htmlspecialchars($row['date']);
                                    $status = htmlspecialchars($row['status']);
                                    $status_class = $status == 'unread' ? 'status-unread' : 'status-read';
                                    $status_text = $status == 'unread' ? 'Unread' : 'Read';
                                    $initials = strtoupper(substr($name, 0, 2));
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-avatar">
                                            <div class="avatar"><?php echo $initials; ?></div>
                                            <div>
                                                <strong><?php echo $name; ?></strong>
                                                <div style="font-size: 12px; color: #7f8c8d;">ID: <?php echo htmlspecialchars($row['c_id']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="email-column email-cell" title="<?php echo $email; ?>">
                                        <i class="fas fa-envelope" style="color: #3498db; margin-right: 8px;"></i>
                                        <?php echo $email; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <span style="font-weight: 600;"><?php echo date('M j, Y', strtotime($date)); ?></span>
                                            <span style="font-size: 12px; color: #7f8c8d;"><?php echo date('g:i A', strtotime($date)); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="table-btn view" onclick="viewComment('<?php echo $ctrl; ?>')" title="View Comment">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="table-btn delete" onclick="deleteComment('<?php echo $ctrl; ?>')" title="Delete Comment">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-comments">
                        <i class="fas fa-comments-slash"></i>
                        <h3>No Comments Found</h3>
                        <p>There are no voter comments to display at the moment.</p>
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
        function viewComment(commentId) {
            window.location.href = `ope_com.php?key=${commentId}`;
        }

        function deleteComment(commentId) {
            if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                window.location.href = `delet_com.php?key=${commentId}`;
            }
        }

        function markAllAsRead() {
            if (confirm('Mark all comments as read?')) {
                // For demo purposes - in production, this would be an AJAX call
                fetch('mark_all_read.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error marking comments as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error marking comments as read');
                    });
            }
        }

        // Add animation to stats cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add row highlight on hover
            const tableRows = document.querySelectorAll('.comments-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
$conn->close();
?>