<?php
include("connection.php");
session_start();

if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged In !! Please Login to access this page');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
} else {
    die("Error: User not found in the database.");
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates | Candidate Portal</title>
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
            background: #f8fafc;
            min-height: 100vh;
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            height: 45px;
            width: auto;
            border-radius: 6px;
        }

        .system-title h1 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 2px;
        }

        .system-title p {
            font-size: 12px;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: white;
        }

        .user-info i {
            font-size: 16px;
        }

        /* Navigation */
        .navbar {
            background: #f1f5f9;
            padding: 5px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 2px;
        }

        .nav-item {
            flex: 1;
        }

        .nav-link {
            display: block;
            padding: 12px 8px;
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s ease;
            border-radius: 6px;
            text-align: center;
        }

        .nav-link i {
            margin-right: 6px;
            font-size: 14px;
        }

        .nav-link:hover {
            background: #e2e8f0;
            color: #3498db;
        }

        .nav-link.active {
            background: #3498db;
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 25px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .user-greeting {
            text-align: right;
            margin-bottom: 25px;
            font-size: 14px;
            color: #64748b;
        }

        .user-greeting span {
            color: #3498db;
            font-weight: 600;
        }

        /* Stats Card */
        .stats-card {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .stats-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-card .count {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-card .label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .candidate-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .candidate-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 3px solid #3498db;
        }

        .candidate-info {
            padding: 20px;
        }

        .candidate-name {
            font-size: 18px;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .candidate-id {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 15px;
            font-family: monospace;
        }

        .view-detail-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .view-detail-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #475569;
        }

        /* Footer */
        .footer {
            background: #f1f5f9;
            padding: 15px 25px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }

        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: #2ecc71;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                border-radius: 8px;
            }
            
            .header {
                flex-direction: column;
                gap: 12px;
                padding: 15px;
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 calc(33.333% - 4px);
            }
            
            .nav-link {
                padding: 10px 5px;
                font-size: 12px;
            }
            
            .candidates-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
                margin: 0 0 4px 0;
                font-size: 12px;
            }
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .candidate-photo {
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.JPG" alt="Logo" class="logo">
                <div class="system-title">
                    <h1> DTUSU Voting System</h1>
                     <p>Candidate Portal</p>
                </div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($FirstName); ?></span>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="candidate_view.php" class="nav-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="post.php" class="nav-link">
                        <i class="fas fa-bullhorn"></i> Post
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_change.php" class="nav-link">
                        <i class="fas fa-key"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_candidate.php" class="nav-link active">
                        <i class="fas fa-users"></i> Candidates
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_comment.php" class="nav-link">
                        <i class="fas fa-comments"></i> Comments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="can_result.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Results
                    </a>
                </li>
                <li class="nav-item">
                    <a href="clogout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Candidates</h1>
                <p class="page-subtitle">View all participating candidates in the election</p>
            </div>

            <div class="user-greeting">
                Welcome, <span><?php echo htmlspecialchars($FirstName) . ' ' . htmlspecialchars($middleName); ?></span>
            </div>

            <?php
            // Count candidates
            $result = $conn->query("SELECT * FROM candidate");
            $countav = $result->num_rows;
            ?>

            <div class="stats-card">
                <h3><i class="fas fa-user-friends"></i> Total Candidates</h3>
                <div class="count"><?php echo $countav; ?></div>
                <div class="label">participating in the election</div>
            </div>

            <div class="candidates-grid">
                <?php
                // Fetch and display candidate details
                $result = $conn->query("SELECT c_id, fname, mname, lname, candidate_photo FROM candidate");
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $ctrl = htmlspecialchars($row['c_id']);
                        $fname = htmlspecialchars($row['fname']);
                        $mname = htmlspecialchars($row['mname']);
                        $lname = htmlspecialchars($row['lname']);
                        $photo = !empty($row['candidate_photo']) ? htmlspecialchars($row['candidate_photo']) : 'img/default-avatar.jpg';
                        ?>
                        <div class="candidate-card">
                            <img src="<?php echo $photo; ?>" alt="<?php echo $fname; ?>" class="candidate-photo">
                            <div class="candidate-info">
                                <div class="candidate-name"><?php echo $fname . ' ' . $mname . ' ' . $lname; ?></div>
                                <div class="candidate-id">ID: <?php echo $ctrl; ?></div>
                                <a href="c_can.php?key=<?php echo $ctrl; ?>" class="view-detail-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="empty-state" style="grid-column: 1 / -1;">
                            <i class="fas fa-user-friends"></i>
                            <h3>No Candidates Found</h3>
                            <p>There are currently no candidates registered for the election.</p>
                          </div>';
                }
                ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Ethiopian Election Commission</p>
            <div class="footer-links">
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
                <a href="#"><i class="fas fa-file-contract"></i> Terms</a>
                <a href="#"><i class="fas fa-phone-alt"></i> Support</a>
                <a href="#"><i class="fas fa-question-circle"></i> Help</a>
            </div>
        </footer>
    </div>

    <script>
        // Navigation active state
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.nav-link').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>