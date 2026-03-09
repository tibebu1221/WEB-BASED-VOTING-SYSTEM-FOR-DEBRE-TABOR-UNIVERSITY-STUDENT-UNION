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

// Try different tables to find user
$tables_to_check = ['user', 'voter', 'admin', 'election_officer', 'users'];
$FirstName = "Officer";
$middleName = "";

foreach ($tables_to_check as $table) {
    $check_table = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check_table && $check_table->num_rows > 0) {
        // Table exists, check if it has fname and mname columns
        $columns_result = $conn->query("SHOW COLUMNS FROM $table");
        $columns = [];
        while ($col = $columns_result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        // Check if table has fname and an id column
        $has_fname = in_array('fname', $columns);
        $has_mname = in_array('mname', $columns);
        
        if ($has_fname) {
            // Find ID column
            $id_column = null;
            foreach ($columns as $col) {
                if (stripos($col, 'id') !== false || stripos($col, 'uid') !== false) {
                    $id_column = $col;
                    break;
                }
            }
            
            if ($id_column) {
                // Try to get fname and mname
                $stmt = $conn->prepare("SELECT fname, mname FROM $table WHERE $id_column = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $FirstName = htmlspecialchars($row['fname'] ?? 'Officer');
                        $middleName = htmlspecialchars($row['mname'] ?? '');
                        $stmt->close();
                        break;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Check if votes table exists
$table_check = $conn->query("SHOW TABLES LIKE 'votes'");
$votes_table_exists = $table_check && $table_check->num_rows > 0;

// Check if result table exists
$table_check2 = $conn->query("SHOW TABLES LIKE 'result'");
$result_table_exists = $table_check2 && $table_check2->num_rows > 0;

// Fetch candidates with their vote counts, ranked by votes
$candidates = [];
if ($votes_table_exists) {
    // Using votes table
    $query = "SELECT 
                c.c_id, 
                c.fname, 
                c.mname, 
                c.lname, 
                c.candidate_photo,
                COUNT(v.vote_id) as vote_count,
                COALESCE(COUNT(v.vote_id), 0) as total_votes
              FROM candidate c
              LEFT JOIN votes v ON c.c_id = v.candidate_id
              GROUP BY c.c_id
              ORDER BY total_votes DESC, c.fname ASC";
} elseif ($result_table_exists) {
    // Using result table
    $query = "SELECT 
                c.c_id, 
                c.fname, 
                c.mname, 
                c.lname, 
                c.candidate_photo,
                COUNT(r.choice) as vote_count,
                COALESCE(COUNT(r.choice), 0) as total_votes
              FROM candidate c
              LEFT JOIN result r ON c.c_id = r.choice
              GROUP BY c.c_id
              ORDER BY total_votes DESC, c.fname ASC";
} else {
    // Fallback query without votes/result table
    $query = "SELECT 
                c_id, 
                fname, 
                mname, 
                lname, 
                candidate_photo,
                0 as total_votes
              FROM candidate
              ORDER BY fname ASC";
}

$candidate_result = $conn->query($query);
if (!$candidate_result) {
    die("Query failed: " . $conn->error);
}

$rank = 1;
$previous_votes = null;
$actual_rank = 1;
$total_votes = 0;
$max_votes = 0;

while ($row = $candidate_result->fetch_assoc()) {
    $current_votes = $row['total_votes'];
    $total_votes += $current_votes;
    
    // Update max votes for the leader
    if ($current_votes > $max_votes) {
        $max_votes = $current_votes;
    }
    
    // Handle tied ranks
    if ($previous_votes !== null && $current_votes < $previous_votes) {
        $actual_rank = $rank;
    }
    
    $candidates[] = [
        'id' => htmlspecialchars($row['c_id']),
        'fname' => htmlspecialchars($row['fname']),
        'mname' => htmlspecialchars($row['mname']),
        'lname' => htmlspecialchars($row['lname']),
        'photo' => htmlspecialchars($row['candidate_photo']),
        'votes' => (int)$row['total_votes'],
        'rank' => $actual_rank
    ];
    
    $previous_votes = $current_votes;
    $rank++;
}

$candidate_count = count($candidates);

// Get winner information
$winner_info = null;
if (count($candidates) > 0 && $max_votes > 0) {
    foreach ($candidates as $candidate) {
        if ($candidate['votes'] == $max_votes) {
            $winner_info = $candidate;
            break;
        }
    }
}

$leading_candidate_name = $winner_info ? 
    $winner_info['fname'] . ' ' . $winner_info['mname'] . ' ' . $winner_info['lname'] : 
    "No votes yet";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results | Officer Portal</title>
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
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #3498db;
            display: flex;
            align-items: center;
            gap: 20px;
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

        .stat-icon.candidates {
            background: linear-gradient(135deg, #3498db, #2ecc71);
        }

        .stat-icon.votes {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-icon.leader {
            background: linear-gradient(135deg, #f39c12, #d35400);
        }

        .stat-icon.active {
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

        .stat-subtitle {
            font-size: 14px;
            color: #3498db;
            margin-top: 5px;
            font-weight: 600;
        }

        /* Rank Badges */
        .rank-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: white;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            border: 3px solid #ffd700;
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #e0e0e0);
            border: 3px solid #c0c0c0;
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #e67e22);
            border: 3px solid #cd7f32;
        }

        .rank-other {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: 3px solid #3498db;
        }

        /* Results Section */
        .results-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        .results-header h2 {
            font-size: 28px;
            color: #2c3e50;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .results-header h2 i {
            color: #3498db;
        }

        /* Leader Info Banner */
        .leader-banner {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 237, 78, 0.1));
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.01); }
            100% { transform: scale(1); }
        }

        .leader-banner i {
            font-size: 36px;
            color: #ffd700;
        }

        .leader-info h3 {
            color: #856404;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .leader-info p {
            color: #2c3e50;
            font-size: 16px;
        }

        .leader-info strong {
            color: #e74c3c;
        }

        /* Candidate Grid */
        .candidate-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .candidate-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .candidate-card.winner {
            border: 3px solid #ffd700;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .candidate-photo {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-bottom: 3px solid #3498db;
        }

        .candidate-details {
            padding: 25px;
        }

        .candidate-name {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }

        .candidate-id {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Vote Stats */
        .vote-stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .vote-count {
            font-size: 28px;
            font-weight: 800;
            color: #e74c3c;
            margin-bottom: 5px;
        }

        .vote-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .progress-container {
            margin-top: 10px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            color: #2c3e50;
        }

        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        /* Detail Button */
        .detail-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
        }

        .detail-btn:hover {
            background: linear-gradient(135deg, #2980b9, #2573a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        /* No Candidates */
        .no-candidates {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .no-candidates i {
            font-size: 64px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .no-candidates h3 {
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

        /* Warning Banner */
        .warning-banner {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse 2s infinite;
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
            
            .candidate-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
            
            .candidate-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .leader-banner {
                flex-direction: column;
                text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.jpg" alt="System Logo" class="logo-img">
                <div class="header-text">
                    <h1>DTUSU Voting System</h1>
                    <p>DTUSU Elections Officer Portal</p>
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
                    <a href="o_result.php" class="nav-link active">
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

            <?php if (!$votes_table_exists && !$result_table_exists): ?>
            <div class="warning-banner">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Voting data not found.</strong> No votes have been recorded yet. Results will appear when voting begins.
                </div>
            </div>
            <?php elseif ($total_votes == 0): ?>
            <div class="warning-banner" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>No votes cast yet.</strong> Results will appear here once voting starts.
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon candidates">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Candidates</h3>
                        <div class="stat-number"><?php echo $candidate_count; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon votes">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Votes</h3>
                        <div class="stat-number"><?php echo $total_votes; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon leader">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Leading Candidate</h3>
                        <div class="stat-number">
                            <?php echo $max_votes > 0 ? $max_votes . ' votes' : '--'; ?>
                        </div>
                        <?php if ($max_votes > 0): ?>
                        <div class="stat-subtitle"><?php echo $leading_candidate_name; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Your Role</h3>
                        <div class="stat-number">Officer</div>
                        <div class="stat-subtitle">Results Manager</div>
                    </div>
                </div>
            </div>

            <!-- Leader Banner -->
            <?php if ($max_votes > 0): ?>
            <div class="leader-banner">
                <i class="fas fa-trophy"></i>
                <div class="leader-info">
                    <h3><i class="fas fa-crown"></i> CURRENT ELECTION LEADER</h3>
                    <p>
                        <strong><?php echo $leading_candidate_name; ?></strong> is leading with 
                        <strong><?php echo $max_votes; ?> votes</strong>
                        <?php if ($total_votes > 0): ?>
                        (<?php echo round(($max_votes / $total_votes) * 100); ?>% of total votes)
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Results Section -->
            <div class="results-header">
                <h2>
                    <i class="fas fa-trophy"></i>
                    Election Results Leaderboard
                </h2>
            </div>

            <div class="candidate-grid">
                <?php if (count($candidates) > 0): ?>
                    <?php foreach ($candidates as $candidate): 
                        $vote_count = $candidate['votes'];
                        $vote_percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                        $fullName = $candidate['fname'] . ' ' . $candidate['mname'] . ' ' . $candidate['lname'];
                        $is_winner = ($vote_count == $max_votes && $vote_count > 0);
                        $photo_path = file_exists($candidate['photo']) ? $candidate['photo'] : 'img/default_candidate.jpg';
                    ?>
                        <div class="candidate-card <?php echo $is_winner ? 'winner' : ''; ?>">
                            <!-- Rank Badge -->
                            <div class="rank-badge rank-<?php echo min($candidate['rank'], 4); ?>">
                                <?php echo $candidate['rank']; ?>
                            </div>
                            
                            <img src="<?php echo $photo_path; ?>" 
                                 alt="<?php echo $candidate['fname'] . ' ' . $candidate['mname']; ?>" 
                                 class="candidate-photo">
                            
                            <div class="candidate-details">
                                <h3 class="candidate-name">
                                    <?php echo $candidate['fname'] . ' ' . $candidate['mname']; ?>
                                </h3>
                                
                                <p class="candidate-id">
                                    ID: <strong><?php echo $candidate['id']; ?></strong>
                                </p>
                                
                                <div class="vote-stats">
                                    <div class="vote-count"><?php echo $vote_count; ?></div>
                                    <div class="vote-label">Votes Received</div>
                                    
                                    <?php if ($total_votes > 0): ?>
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Vote Share</span>
                                            <span><?php echo round($vote_percentage, 1); ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $vote_percentage; ?>%;"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="margin-top: 15px; font-size: 14px; color: #7f8c8d;">
                                        <?php 
                                        if ($vote_count == 0) {
                                            echo 'No votes yet';
                                        } elseif ($candidate['rank'] == 1) {
                                            echo '🥇 1st Place';
                                        } elseif ($candidate['rank'] == 2) {
                                            echo '🥈 2nd Place';
                                        } elseif ($candidate['rank'] == 3) {
                                            echo '🥉 3rd Place';
                                        } else {
                                            echo $candidate['rank'] . 'th Place';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <a href="fino_result.php?key=<?php echo urlencode($candidate['id']); ?>" 
                                   class="detail-btn">
                                    <i class="fas fa-chart-line"></i> View Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-candidates">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Candidates Found</h3>
                        <p>There are no candidates registered for the current election.</p>
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
        // Add interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars on scroll
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

            // Add hover effect to candidate cards
            const candidateCards = document.querySelectorAll('.candidate-card');
            
            candidateCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
                
                // Make entire card clickable for detail view
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('.detail-btn')) {
                        const detailBtn = this.querySelector('.detail-btn');
                        if (detailBtn) {
                            detailBtn.click();
                        }
                    }
                });
            });

            // Update the active navigation state
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref === currentPage) {
                    link.classList.add('active');
                }
            });

            // Auto-refresh page every 30 seconds for real-time updates
            setTimeout(() => {
                location.reload();
            }, 30000);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>