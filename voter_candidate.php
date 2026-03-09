<?php
include("connection.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    echo '<script>
        Swal.fire({
            icon: "warning",
            title: "Login Required",
            text: "You are not logged in! Please login to access this page.",
            confirmButtonColor: "#1a2a6c"
        }).then(() => {
            window.location = "login.php";
        });
    </script>';
    exit();
}

// Fetch user data
$user_id = $_SESSION['u_id'];

// Ensure $conn is your MySQLi connection from connection.php
$stmt = $conn->prepare("SELECT fname, mname FROM voter WHERE vid = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
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
    <title>View Candidates - Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-blue: #1a2a6c;
            --secondary-blue: #2F4F4F;
            --accent-green: #51a351;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e6ef;
            --dark-gray: #4a5568;
            --text-dark: #2d3748;
            --text-light: #718096;
            --white: #ffffff;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            height: 60px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .voter-logo {
            height: 80px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        /* User Welcome */
        .user-welcome {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
        }

        .user-welcome i {
            font-size: 1.5rem;
            color: var(--accent-green);
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Navigation */
        .navbar {
            background: var(--secondary-blue);
            padding: 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            color: var(--white);
            text-decoration: none;
            padding: 16px 22px;
            display: block;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .nav-menu .active a {
            background: var(--accent-green);
            color: var(--white);
            border-radius: 6px;
            margin: 4px 2px;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-title h1 {
            color: var(--primary-blue);
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title h1 i {
            color: var(--accent-green);
        }

        .candidate-count {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--card-shadow);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.candidates {
            background: linear-gradient(135deg, #e6f0ff 0%, #d4e4ff 100%);
            color: var(--primary-blue);
        }

        .stat-content h3 {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .candidate-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .candidate-header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid var(--white);
            object-fit: cover;
            margin: 0 auto 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .candidate-name {
            color: var(--white);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .candidate-id {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .candidate-body {
            padding: 25px;
        }

        .candidate-info {
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .info-item i {
            color: var(--accent-green);
            width: 20px;
        }

        .view-detail-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .view-detail-btn:hover {
            background: linear-gradient(135deg, #0d1e5a, #1a2a6c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 42, 108, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin: 40px 0;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--medium-gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto;
        }

        /* Footer */
        .footer {
            background: var(--secondary-blue);
            color: var(--white);
            padding: 25px 0;
            margin-top: 50px;
        }

        .footer-content {
            text-align: center;
        }

        .footer p {
            margin: 0;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .user-welcome {
                margin-top: 10px;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .page-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .nav-menu {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-menu li {
                width: 100%;
            }
            
            .nav-menu a {
                text-align: center;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 15px;
            }
            
            .candidate-photo {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <a href="voter.php">
                        <img src="img/logo.JPG" alt="Debre Tabor University Logo" class="logo">
                    </a>
                    <img src="img/voter.png" alt="Voter Dashboard" class="voter-logo">
                </div>
                <div class="user-welcome">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Welcome</div>
                        <div class="user-name"><?php echo $FirstName . ' ' . $middleName; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="voter.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="v_change.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="cast.php"><i class="fas fa-vote-yea"></i> Cast Vote</a></li>
                <li class="active"><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="voter_comment.php"><i class="fas fa-comment"></i> Comment</a></li>
                <li><a href="v_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-users"></i> View All Candidates</h1>
                <p style="color: var(--text-light); margin-top: 5px;">Review candidate profiles before casting your vote</p>
            </div>
            
            <?php
            // Count candidates
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM candidate");
            $stmt->execute();
            $result = $stmt->get_result();
            $countav = $result->fetch_assoc()['count'];
            $stmt->close();
            ?>
            <div class="candidate-count">
                <i class="fas fa-user-tie"></i>
                <span><?php echo $countav; ?> Candidates</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon candidates">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Candidates</h3>
                    <div class="stat-number"><?php echo $countav; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e6f7ff 0%, #d4f0ff 100%); color: #0077cc;">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-content">
                    <h3>Your Vote Status</h3>
                    <?php
                    // Check if user has voted (you'll need to implement this logic)
                    $hasVoted = false; // Replace with actual check
                    ?>
                    <div class="stat-number" style="color: <?php echo $hasVoted ? 'var(--accent-green)' : 'var(--danger)'; ?>;">
                        <?php echo $hasVoted ? 'Voted' : 'Not Voted'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidates Grid -->
        <?php
        // Fetch candidate list
        $stmt = $conn->prepare("SELECT c_id, fname, mname, lname, candidate_photo FROM candidate");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0):
        ?>
        <div class="candidates-grid">
            <?php while ($row = $result->fetch_assoc()): 
                $ctrl = htmlspecialchars($row['c_id']);
                $fname = htmlspecialchars($row['fname']);
                $mname = htmlspecialchars($row['mname']);
                $lname = htmlspecialchars($row['lname']);
                $photo = htmlspecialchars($row['candidate_photo']);
                $fullName = $fname . ' ' . $mname . ' ' . $lname;
            ?>
            <div class="candidate-card">
                <div class="candidate-header">
                    <img src="<?php echo $photo ?: 'img/default-avatar.jpg'; ?>" 
                         alt="<?php echo $fullName; ?>" 
                         class="candidate-photo"
                         onerror="this.src='img/default-avatar.jpg'">
                    <h3 class="candidate-name"><?php echo $fname . ' ' . $mname; ?></h3>
                    <div class="candidate-id">ID: <?php echo $ctrl; ?></div>
                </div>
                
                <div class="candidate-body">
                    <div class="candidate-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span><strong>Full Name:</strong> <?php echo $fullName; ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-card"></i>
                            <span><strong>Candidate ID:</strong> <?php echo $ctrl; ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-list-ol"></i>
                            <span>View full profile for more details</span>
                        </div>
                    </div>
                    
                    <a href="voter_can.php?key=<?php echo $ctrl; ?>" class="view-detail-btn">
                        <i class="fas fa-eye"></i> View Full Profile
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-times"></i>
            <h3>No Candidates Available</h3>
            <p>There are currently no candidates registered for the election. Please check back later.</p>
        </div>
        <?php 
        endif;
        $stmt->close();
        ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date("Y"); ?> Debre Tabor University Student Union Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-shield-alt"></i> Secure Voting Portal - Review Candidates Before Voting
            </p>
        </div>
    </footer>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Animation for cards on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.candidate-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>

    <?php
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>
</body>
</html>