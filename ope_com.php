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

// --- 3. Fetch and Update Comment Status ---
$ctrl = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
$errors = [];
$comment = null;

if (!empty($ctrl)) {
    // Update comment status to 'read'
    $stmt_update = $conn->prepare("UPDATE comment SET status = 'read' WHERE c_id = ?");
    $stmt_update->bind_param("s", $ctrl);
    if (!$stmt_update->execute()) {
        $errors[] = "Error updating comment status: " . htmlspecialchars($conn->error);
    }
    $stmt_update->close();

    // Fetch comment details
    $stmt_fetch = $conn->prepare("SELECT * FROM comment WHERE c_id = ?");
    $stmt_fetch->bind_param("s", $ctrl);
    $stmt_fetch->execute();
    $result_comment = $stmt_fetch->get_result();
    if ($result_comment->num_rows == 1) {
        $comment = $result_comment->fetch_assoc();
    } else {
        $errors[] = "Comment not found!";
    }
    $stmt_fetch->close();
} else {
    $errors[] = "Invalid comment ID!";
}

// Get comment statistics
$total_comments = $conn->query("SELECT COUNT(*) as count FROM comment")->fetch_assoc()['count'];
$unread_comments = $conn->query("SELECT COUNT(*) as count FROM comment WHERE status = 'unread'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Comment | Officer Portal</title>
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

        .stat-card.viewing {
            border-left-color: #2ecc71;
        }

        .stat-card.status {
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

        .stat-card.viewing .stat-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .stat-card.status .stat-icon {
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

        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* Comment View Section */
        .comment-section {
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
        }

        .action-btn.back {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
        }

        .action-btn.delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* Comment Card */
        .comment-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .comment-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-bottom: 2px solid #3498db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .comment-sender {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sender-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }

        .sender-info h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .sender-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .sender-info p i {
            color: #3498db;
            margin-right: 8px;
            width: 16px;
        }

        .comment-meta {
            text-align: right;
        }

        .comment-date {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .comment-time {
            font-size: 14px;
            color: #7f8c8d;
        }

        .comment-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-read {
            background: #d1fae5;
            color: #065f46;
        }

        .comment-body {
            padding: 30px;
            background: #f8f9fa;
            min-height: 200px;
        }

        .comment-content {
            background: white;
            border-radius: 8px;
            padding: 25px;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            min-height: 150px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .comment-actions {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Error Message */
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }

        .error-message i {
            font-size: 48px;
            color: #dc2626;
            margin-bottom: 20px;
        }

        .error-message h3 {
            color: #dc2626;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .error-message p {
            color: #7f1d1d;
            margin-bottom: 20px;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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
            
            .comment-header {
                flex-direction: column;
                text-align: center;
            }
            
            .comment-sender {
                flex-direction: column;
                text-align: center;
            }
            
            .comment-meta {
                text-align: center;
            }
            
            .comment-actions {
                flex-direction: column;
                text-align: center;
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
            
            .comment-header, .comment-body, .comment-actions {
                padding: 20px;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
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
            
            .error-actions {
                flex-direction: column;
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
                    <h1>Online Voting System</h1>
                    <p>Ethiopian Election Commission - Officer Portal</p>
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
                        <div class="stat-label">All Time</div>
                    </div>
                </div>
                
                <div class="stat-card unread">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Unread Comments</h3>
                        <div class="stat-number"><?php echo $unread_comments; ?></div>
                        <div class="stat-label">Require Attention</div>
                    </div>
                </div>
                
                <div class="stat-card viewing">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Viewing</h3>
                        <div class="stat-number">
                            <?php echo $comment ? htmlspecialchars(substr($comment['name'], 0, 15)) . '...' : 'N/A'; ?>
                        </div>
                        <div class="stat-label">Current Comment</div>
                    </div>
                </div>
                
                <div class="stat-card status">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Status</h3>
                        <div class="stat-number">
                            <?php echo $comment ? 'Read' : 'Error'; ?>
                        </div>
                        <div class="stat-label">
                            <?php echo $comment ? 'Marked as read' : 'Not available'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment View Section -->
            <?php if (!empty($errors)): ?>
                <!-- Error Display -->
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Comment</h3>
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                    <div class="error-actions">
                        <button class="action-btn back" onclick="window.location.href='o_comment.php'">
                            <i class="fas fa-arrow-left"></i>
                            Back to Comments
                        </button>
                    </div>
                </div>
            <?php elseif ($comment): ?>
                <div class="comment-section">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-envelope-open-text"></i>
                            Voter Comment Details
                        </h2>
                        <div class="section-actions">
                            <button class="action-btn back" onclick="window.location.href='o_comment.php'">
                                <i class="fas fa-arrow-left"></i>
                                Back to Comments
                            </button>
                            <button class="action-btn delete" onclick="deleteComment('<?php echo urlencode($ctrl); ?>')">
                                <i class="fas fa-trash"></i>
                                Delete Comment
                            </button>
                        </div>
                    </div>

                    <!-- Comment Card -->
                    <div class="comment-card">
                        <div class="comment-header">
                            <div class="comment-sender">
                                <div class="sender-avatar">
                                    <?php 
                                    $initials = strtoupper(substr($comment['name'], 0, 2));
                                    echo $initials; 
                                    ?>
                                </div>
                                <div class="sender-info">
                                    <h3><?php echo htmlspecialchars($comment['name']); ?></h3>
                                    <p>
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($comment['email']); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-id-card"></i>
                                        Comment ID: <?php echo htmlspecialchars($comment['c_id']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="comment-meta">
                                <div class="comment-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('F j, Y', strtotime($comment['date'])); ?>
                                </div>
                                <div class="comment-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($comment['date'])); ?>
                                </div>
                                <div class="comment-status status-read">
                                    <i class="fas fa-check-circle"></i>
                                    Status: Read
                                </div>
                            </div>
                        </div>

                        <div class="comment-body">
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>

                        <div class="comment-actions">
                            <div style="font-size: 14px; color: #7f8c8d;">
                                <i class="fas fa-info-circle"></i>
                                This comment was automatically marked as read when you opened it.
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="action-btn back" onclick="replyToComment()" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                                    <i class="fas fa-reply"></i>
                                    Reply
                                </button>
                                <button class="action-btn delete" onclick="deleteComment('<?php echo urlencode($ctrl); ?>')">
                                    <i class="fas fa-trash"></i>
                                    Delete Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Comment Data -->
                <div class="error-message">
                    <i class="fas fa-comment-slash"></i>
                    <h3>No Comment Data Available</h3>
                    <p>Unable to display the requested comment. It may have been deleted or doesn't exist.</p>
                    <div class="error-actions">
                        <button class="action-btn back" onclick="window.location.href='o_comment.php'">
                            <i class="fas fa-arrow-left"></i>
                            Back to Comments
                        </button>
                    </div>
                </div>
            <?php endif; ?>
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
        function deleteComment(commentId) {
            if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                window.location.href = `delet_com.php?key=${commentId}`;
            }
        }

        function replyToComment() {
            alert('Reply functionality would be implemented here.\n\nIn a production system, this would open an email composition window or a reply form.');
            
            // For actual implementation:
            // window.location.href = `reply_comment.php?key=<?php echo urlencode($ctrl); ?>`;
            // OR
            // window.open(`mailto:${commentEmail}?subject=Re: Your Comment`, '_blank');
        }

        // Add animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add subtle animation to comment card
            const commentCard = document.querySelector('.comment-card');
            if (commentCard) {
                setTimeout(() => {
                    commentCard.style.transform = 'translateY(0)';
                    commentCard.style.opacity = '1';
                }, 400);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key goes back
            if (e.key === 'Escape') {
                window.location.href = 'o_comment.php';
            }
            // Ctrl+D to delete
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                deleteComment('<?php echo urlencode($ctrl); ?>');
            }
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
$conn->close();
?>