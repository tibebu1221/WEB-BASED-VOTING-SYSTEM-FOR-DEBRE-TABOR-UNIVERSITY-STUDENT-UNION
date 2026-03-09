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
    $middleName = htmlspecialchars($row['mname'] ?? '');
    $LastName = htmlspecialchars($row['lname']);
    $initials = strtoupper(substr($FirstName, 0, 1) . substr($LastName, 0, 1));
} else {
    echo '<script>alert("Error: User not found."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Get candidate count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM candidate");
$stmt->execute();
$result = $stmt->get_result();
$countav = $result->fetch_assoc()['count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Management | Admin Dashboard</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a2a6c;
            --secondary: #b21f1f;
            --accent: #ffcc00;
            --dark: #333;
            --light: #f8f9fa;
            --gray: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --border: #dee2e6;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
            border-radius: 8px;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
            border: 2px solid white;
        }

        .user-details h3 {
            font-size: 1rem;
            margin-bottom: 3px;
        }

        .user-details p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Navigation */
        .navbar {
            background: var(--dark);
            padding: 0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            font-size: 0.95rem;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-bottom-color: var(--accent);
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* Statistics */
        .stats-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
            margin-bottom: 30px;
        }

        .stats-card h2 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .stats-card .count {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }

        .stats-card p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .candidate-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s;
            border: 1px solid var(--border);
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .candidate-image {
            height: 200px;
            overflow: hidden;
            background: linear-gradient(45deg, #1a2a6c, #b21f1f);
        }

        .candidate-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .candidate-card:hover .candidate-image img {
            transform: scale(1.05);
        }

        .candidate-info {
            padding: 20px;
        }

        .candidate-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .candidate-id {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 15px;
            display: block;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 15px;
            border-radius: var(--radius);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            flex: 1;
            justify-content: center;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: #0e1c4d;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--gray);
            font-size: 0.9rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-top: 30px;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .candidates-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                flex-direction: column;
            }

            .nav-link {
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <img src="img/ethio_flag.JPG" alt="Logo">
            <h1>Candidate Management</h1>
        </div>
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <div class="user-details">
                <h3><?php echo $FirstName . ' ' . $middleName; ?></h3>
                <p>System Administrator</p>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="system_admin.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_account.php" class="nav-link">
                    <i class="fas fa-users-cog"></i> Accounts
                </a>
            </li>
            <li class="nav-item">
                <a href="a_candidate.php" class="nav-link active">
                    <i class="fas fa-user-tie"></i> Candidates
                </a>
            </li>
            <li class="nav-item">
                <a href="voters.php" class="nav-link">
                    <i class="fas fa-user-friends"></i> Voters
                </a>
            </li>
            <li class="nav-item">
                <a href="adminv_result.php" class="nav-link">
                    <i class="fas fa-poll-h"></i> Results
                </a>
            </li>
            <li class="nav-item">
                <a href="setDate.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i> Schedule
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link" style="color: #ff6b6b;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Statistics -->
        <div class="stats-card">
            <h2>General Information</h2>
            <div class="count"><?php echo $countav; ?></div>
            <p>candidates participating in the election</p>
        </div>

        <!-- Candidates Grid -->
        <div class="candidates-grid">
            <?php
            // Fixed SQL query - removed position and party columns
            $stmt = $conn->prepare("SELECT c_id, fname, mname, lname, candidate_photo FROM candidate ORDER BY fname");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $c_id = htmlspecialchars($row['c_id']);
                    $fname = htmlspecialchars($row['fname']);
                    $mname = htmlspecialchars($row['mname'] ?? '');
                    $lname = htmlspecialchars($row['lname']);
                    $photo = htmlspecialchars($row['candidate_photo']);
                    $fullName = trim($fname . ' ' . $mname . ' ' . $lname);
                    
                    // Create avatar URL as fallback
                    $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=1a2a6c&color=fff&size=200";
            ?>
            <div class="candidate-card">
                <div class="candidate-image">
                    <?php if($photo && file_exists($photo)): ?>
                        <img src="<?php echo $photo; ?>" alt="<?php echo $fullName; ?>" 
                             onerror="this.src='<?php echo $avatarUrl; ?>'">
                    <?php else: ?>
                        <img src="<?php echo $avatarUrl; ?>" alt="<?php echo $fullName; ?>">
                    <?php endif; ?>
                </div>
                <div class="candidate-info">
                    <h3 class="candidate-name"><?php echo $fullName; ?></h3>
                    <span class="candidate-id">Candidate ID: <?php echo $c_id; ?></span>
                    
                    <div class="action-buttons">
                        <a href="admin_can.php?key=<?php echo $c_id; ?>" class="btn btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3>No Candidates Found</h3>
                <p>No candidates have been registered in the system yet. Start by adding candidates for the election.</p>
                <a href="add_candidate.php" class="btn" style="background: var(--primary); color: white; max-width: 200px; margin: 0 auto;">
                    <i class="fas fa-user-plus"></i> Add Candidate
                </a>
            </div>
            <?php endif; 
            $stmt->close();
            ?>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?php echo date("Y"); ?> Election Commission | Candidate Management System</p>
            <p>Total Candidates: <?php echo $countav; ?> | Last Updated: <?php echo date("M j, Y g:i A"); ?></p>
        </footer>
    </main>

    <script>
        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.candidate-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>