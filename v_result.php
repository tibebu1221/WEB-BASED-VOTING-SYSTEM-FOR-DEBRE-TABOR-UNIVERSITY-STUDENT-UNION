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

// Get user details
$stmt = $conn->prepare("SELECT fname, mname FROM voter WHERE vid = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}

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

// Count candidates
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM candidate");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$candidate_count = $result->fetch_assoc()['count'];
$stmt->close();

// Count total votes
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM result");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$total_votes = $result->fetch_assoc()['count'];
$stmt->close();

// Get winner information with candidate name
$stmt = $conn->prepare("SELECT r.choice, COUNT(*) as votes, c.fname, c.mname, c.lname 
                       FROM result r 
                       LEFT JOIN candidate c ON r.choice = c.c_id 
                       GROUP BY r.choice 
                       ORDER BY votes DESC 
                       LIMIT 1");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $winner_info = $result->fetch_assoc();
    $winner_id = $winner_info['choice'];
    $max_votes = $winner_info['votes'];
    $leading_candidate_name = htmlspecialchars($winner_info['fname'] . ' ' . $winner_info['mname'] . ' ' . $winner_info['lname']);
} else {
    $winner_id = null;
    $max_votes = 0;
    $leading_candidate_name = "No votes yet";
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Online Voting System</title>
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
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
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
            text-align: center;
            margin-bottom: 40px;
            padding: 20px 0;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-header h1 {
            color: var(--primary-blue);
            font-size: 2.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* Results Stats */
        .results-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
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
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-icon.candidates {
            background: linear-gradient(135deg, #e6f0ff 0%, #d4e4ff 100%);
            color: var(--primary-blue);
        }

        .stat-icon.results {
            background: linear-gradient(135deg, #e6f7ff 0%, #d4f0ff 100%);
            color: #0077cc;
        }

        .stat-icon.votes {
            background: linear-gradient(135deg, #e6f7e6 0%, #d4f0d4 100%);
            color: var(--accent-green);
        }

        .stat-content h3 {
            font-size: 0.95rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        /* Results Grid */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .result-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
        }

        .result-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .result-header {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .candidate-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid var(--white);
            object-fit: cover;
            margin: 0 auto 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .candidate-name {
            color: var(--white);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .candidate-id {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }

        .result-body {
            padding: 25px;
        }

        .vote-stats {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .vote-count {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }

        .vote-label {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .progress-container {
            margin-top: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .progress-bar {
            height: 10px;
            background: var(--medium-gray);
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-green), #3d8b3d);
            border-radius: 5px;
            transition: width 1s ease-in-out;
        }

        .result-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .view-detail-btn {
            flex: 1;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: var(--transition);
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

        /* Winner Badge */
        .winner-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #856404;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            z-index: 2;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Results Summary */
        .results-summary {
            background: linear-gradient(135deg, #f8faff 0%, #f0f5ff 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            margin: 40px 0;
            border-left: 5px solid var(--accent-green);
        }

        .summary-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-header h3 {
            color: var(--primary-blue);
            font-size: 1.5rem;
            margin: 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            background: var(--white);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Leader Info */
        .leader-info {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 237, 78, 0.1));
            border-radius: 10px;
            border: 2px solid rgba(255, 215, 0, 0.3);
            text-align: center;
        }

        .leader-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .leader-header h4 {
            color: #856404;
            font-size: 1.3rem;
            margin: 0;
        }

        .leader-details {
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .leader-details strong {
            color: var(--primary-blue);
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
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            
            .result-actions {
                flex-direction: column;
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
            
            .page-header h1 {
                font-size: 1.6rem;
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
                <li><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="voter_comment.php"><i class="fas fa-comment"></i> Feedback</a></li>
                <li class="active"><a href="v_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-line"></i> Election Results</h1>
            <p>View the latest election results and candidate performance statistics</p>
        </div>

        <!-- Results Stats -->
        <div class="results-stats">
            <div class="stat-card">
                <div class="stat-icon candidates">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Candidates</h3>
                    <div class="stat-number"><?php echo $candidate_count; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon votes">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Votes Cast</h3>
                    <div class="stat-number"><?php echo $total_votes; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon results">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3>Leading Candidate</h3>
                    <div class="stat-number">
                        <?php 
                        if ($max_votes > 0) {
                            echo $max_votes . ' votes';
                        } else {
                            echo '--';
                        }
                        ?>
                    </div>
                    <?php if ($max_votes > 0): ?>
                    <div style="font-size: 0.9rem; color: var(--accent-green); margin-top: 5px; font-weight: 600;">
                        <?php echo $leading_candidate_name; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="summary-header">
                <i class="fas fa-chart-pie" style="font-size: 1.8rem; color: var(--accent-green);"></i>
                <h3>Election Summary</h3>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value"><?php echo $candidate_count; ?></div>
                    <div class="summary-label">Candidates</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $total_votes; ?></div>
                    <div class="summary-label">Total Votes</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">
                        <?php echo $total_votes > 0 ? '100%' : '0%'; ?>
                    </div>
                    <div class="summary-label">Participation Rate</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">
                        <?php 
                        if ($max_votes > 0 && $total_votes > 0) {
                            echo round(($max_votes / $total_votes) * 100) . '%';
                        } else {
                            echo '--';
                        }
                        ?>
                    </div>
                    <div class="summary-label">Leading Share</div>
                </div>
            </div>
            
            <!-- Leader Info -->
            <?php if ($max_votes > 0): ?>
            <div class="leader-info">
                <div class="leader-header">
                    <i class="fas fa-crown" style="color: #ffd700; font-size: 1.5rem;"></i>
                    <h4>Current Leader</h4>
                    <i class="fas fa-crown" style="color: #ffd700; font-size: 1.5rem;"></i>
                </div>
                <div class="leader-details">
                    <strong><?php echo $leading_candidate_name; ?></strong> is leading with 
                    <strong><?php echo $max_votes; ?> votes</strong>
                    <?php if ($total_votes > 0): ?>
                    (<?php echo round(($max_votes / $total_votes) * 100); ?>% of total votes)
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Results Grid -->
        <?php
        // Fetch candidate list with their vote counts
        $stmt = $conn->prepare("SELECT c.c_id, c.fname, c.mname, c.lname, c.candidate_photo, 
                               COUNT(r.choice) as vote_count
                               FROM candidate c 
                               LEFT JOIN result r ON c.c_id = r.choice 
                               GROUP BY c.c_id 
                               ORDER BY vote_count DESC");
        
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0):
        ?>
        <div class="results-grid">
            <?php while ($row = $result->fetch_assoc()): 
                $ctrl = htmlspecialchars($row['c_id']);
                $fname = htmlspecialchars($row['fname']);
                $mname = htmlspecialchars($row['mname']);
                $lname = htmlspecialchars($row['lname']);
                $photo = filter_var($row['candidate_photo'], FILTER_SANITIZE_URL);
                $vote_count = $row['vote_count'];
                $fullName = $fname . ' ' . $mname . ' ' . $lname;
                $vote_percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                $is_winner = ($ctrl == $winner_id && $vote_count > 0);
            ?>
            <div class="result-card">
                <?php if ($is_winner): ?>
                <div class="winner-badge">
                    <i class="fas fa-crown"></i> Leader
                </div>
                <?php endif; ?>
                
                <div class="result-header">
                    <img src="<?php echo $photo ?: 'img/default-avatar.jpg'; ?>" 
                         alt="<?php echo $fullName; ?>" 
                         class="candidate-photo"
                         onerror="this.src='img/default-avatar.jpg'">
                    <h3 class="candidate-name"><?php echo $fname . ' ' . $mname; ?></h3>
                    <div class="candidate-id">ID: <?php echo $ctrl; ?></div>
                </div>
                
                <div class="result-body">
                    <div class="vote-stats">
                        <div class="vote-count"><?php echo $vote_count; ?></div>
                        <div class="vote-label">Votes Received</div>
                        
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Vote Share</span>
                                <span><?php echo round($vote_percentage, 1); ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $vote_percentage; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="candidate-info" style="margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: var(--text-dark);">
                            <i class="fas fa-user" style="color: var(--accent-green);"></i>
                            <span><strong>Full Name:</strong> <?php echo $fullName; ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: var(--text-dark);">
                            <i class="fas fa-trophy" style="color: #ffd700;"></i>
                            <span><strong>Rank:</strong> 
                                <?php 
                                if ($vote_count > 0) {
                                    echo $vote_count == $max_votes ? '1st Place' : 'Contender';
                                } else {
                                    echo 'No Votes';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="result-actions">
                        <a href="fino_resultvot.php?key=<?php echo urlencode($ctrl); ?>" class="view-detail-btn">
                            <i class="fas fa-chart-bar"></i> Detailed Results
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <h3>No Election Results Available</h3>
            <p>Results will be displayed here once the election has concluded and votes have been counted.</p>
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
                <i class="fas fa-chart-line"></i> Real-time Election Results - Stay Informed
            </p>
        </div>
    </footer>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Animate progress bars on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progressFill = entry.target;
                        const width = progressFill.style.width;
                        progressFill.style.width = '0%';
                        
                        setTimeout(() => {
                            progressFill.style.transition = 'width 1.5s ease-in-out';
                            progressFill.style.width = width;
                        }, 300);
                        
                        observer.unobserve(progressFill);
                    }
                });
            }, {
                threshold: 0.5
            });
            
            progressBars.forEach(bar => {
                observer.observe(bar);
            });

            // Add animation to result cards
            const resultCards = document.querySelectorAll('.result-card');
            
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            resultCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                cardObserver.observe(card);
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>