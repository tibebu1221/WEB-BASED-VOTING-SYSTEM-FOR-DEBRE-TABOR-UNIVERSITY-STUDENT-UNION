<?php
include("connection.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged In !! Please Login to access this page');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch candidate user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname, lname, candidate_photo FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    $lastName = htmlspecialchars($row['lname']);
    $candidatePhoto = htmlspecialchars($row['candidate_photo']);
    $fullName = $FirstName . ' ' . $middleName . ' ' . $lastName;
} else {
    die("Error: Candidate not found in the database.");
}
$stmt->close();

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
    <title>Election Results - Candidate Dashboard</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a2a6c;
            --secondary-blue: #2F4F4F;
            --accent-green: #28a745;
            --light-gray: #f5f7fa;
            --medium-gray: #e0e6ef;
            --dark-gray: #4a5568;
            --text-dark: #2d3748;
            --text-light: #718096;
            --white: #ffffff;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
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

        .candidate-logo {
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

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid var(--accent-green);
            object-fit: cover;
        }

        .user-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 0.9rem;
            opacity: 0.9;
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
            padding: 30px 0;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border-left: 5px solid var(--accent-green);
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

        /* Stats Cards */
        .stats-container {
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

        .stat-icon.votes {
            background: linear-gradient(135deg, #e6f7e6 0%, #d4f0d4 100%);
            color: var(--accent-green);
        }

        .stat-icon.leader {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
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

        .stat-subtitle {
            font-size: 0.9rem;
            color: var(--accent-green);
            margin-top: 5px;
            font-weight: 600;
        }

        /* Rank Badges */
        .rank-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: white;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .rank-1 {
            background: linear-gradient(135deg, var(--gold), #e6b800);
            border: 3px solid #ffd700;
        }

        .rank-2 {
            background: linear-gradient(135deg, var(--silver), #a8a8a8);
            border: 3px solid #c0c0c0;
        }

        .rank-3 {
            background: linear-gradient(135deg, var(--bronze), #b5651d);
            border: 3px solid #cd7f32;
        }

        .rank-other {
            background: linear-gradient(135deg, var(--primary-blue), #2c3e8c);
            border: 3px solid var(--primary-blue);
        }

        /* Badge for current user */
        .current-user-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, var(--accent-green), #218838);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            z-index: 2;
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        .candidate-card.winner {
            border: 3px solid var(--gold);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .candidate-header {
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

        .candidate-body {
            padding: 25px;
        }

        /* Vote Stats */
        .vote-stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .vote-count {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-green);
            margin-bottom: 5px;
        }

        .vote-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }

        .progress-container {
            margin-top: 10px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .progress-bar {
            height: 8px;
            background: var(--medium-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-green), #218838);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .rank-info {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-top: 10px;
            font-weight: 600;
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
            font-size: 0.9rem;
        }

        .info-item i {
            color: var(--primary-blue);
            width: 20px;
        }

        .view-detail-btn {
            display: block;
            width: 100%;
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

        /* Warning Banner */
        .warning-banner {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid #ffc107;
        }

        /* Leader Banner */
        .leader-banner {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 237, 78, 0.05));
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .leader-banner i {
            font-size: 36px;
            color: var(--gold);
        }

        .leader-info h3 {
            color: #856404;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .leader-info p {
            color: var(--text-dark);
            font-size: 1rem;
        }

        .leader-info strong {
            color: var(--accent-green);
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
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            
            .leader-banner {
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
                    <a href="system_admin.php">
                        <img src="img/logo.JPG" alt="University Logo" class="logo">
                    </a>
                    <img src="img/can.png" alt="Candidate Dashboard" class="candidate-logo">
                </div>
                <div class="user-welcome">
                    <img src="<?php echo ($candidatePhoto && file_exists($candidatePhoto)) ? $candidatePhoto : 'img/default-avatar.jpg'; ?>" 
                         alt="<?php echo $fullName; ?>" 
                         class="user-avatar"
                         onerror="this.src='img/default-avatar.jpg'">
                    <div class="user-info">
                        <h3><?php echo $FirstName . ' ' . $middleName; ?></h3>
                        <p>Candidate Dashboard</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="candidate_view.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="post.php"><i class="fas fa-newspaper"></i> Post News</a></li>
                <li><a href="can_change.php"><i class="fas fa-key"></i> Change Password</a></li>
                <li><a href="can_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="can_comment.php"><i class="fas fa-comment"></i> Comments</a></li>
                <li class="active"><a href="can_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
                <li><a href="clogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-trophy"></i> Election Results Leaderboard</h1>
            <p>Candidates ranked by number of votes received. View real-time election statistics.</p>
        </div>

        <?php if (!$votes_table_exists && !$result_table_exists): ?>
        <div class="warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Voting data not found.</strong> No votes have been recorded yet. Results will appear when voting begins.
            </div>
        </div>
        <?php elseif ($total_votes == 0): ?>
        <div class="warning-banner">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>No votes cast yet.</strong> Results will appear here once voting starts.
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-container">
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
                <div class="stat-icon leader">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-content">
                    <h3>Leading Candidate</h3>
                    <div class="stat-number"><?php echo $max_votes > 0 ? $max_votes . ' votes' : '--'; ?></div>
                    <?php if ($max_votes > 0): ?>
                    <div class="stat-subtitle"><?php echo $leading_candidate_name; ?></div>
                    <?php endif; ?>
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

        <!-- Candidates List -->
        <div style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-blue); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-list-ol"></i> Candidate Rankings
            </h2>
            <p style="color: var(--text-light); margin-top: 5px;">
                Candidates are displayed in order of votes received (highest to lowest)
            </p>
        </div>

        <?php if (count($candidates) > 0): ?>
        <div class="candidates-grid">
            <?php foreach ($candidates as $candidate): 
                $vote_count = $candidate['votes'];
                $vote_percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                $displayName = $candidate['fname'] . ' ' . $candidate['mname'];
                $fullName = $candidate['fname'] . ' ' . $candidate['mname'] . ' ' . $candidate['lname'];
                $isCurrentUser = ($candidate['id'] == $user_id);
                $is_winner = ($vote_count == $max_votes && $vote_count > 0);
                $photo_path = file_exists($candidate['photo']) ? $candidate['photo'] : 'img/default_candidate.jpg';
            ?>
            <div class="candidate-card <?php echo $is_winner ? 'winner' : ''; ?>">
                <!-- Rank Badge -->
                <div class="rank-badge rank-<?php echo min($candidate['rank'], 4); ?>">
                    <?php echo $candidate['rank']; ?>
                </div>
                
                <?php if ($isCurrentUser): ?>
                <div class="current-user-badge">
                    <i class="fas fa-user"></i> You
                </div>
                <?php endif; ?>
                
                <div class="candidate-header">
                    <img src="<?php echo $photo_path; ?>" 
                         alt="<?php echo $displayName; ?>" 
                         class="candidate-photo"
                         onerror="this.src='img/default_candidate.jpg'">
                    <h3 class="candidate-name"><?php echo $displayName; ?></h3>
                    <div class="candidate-id">ID: <?php echo $candidate['id']; ?></div>
                </div>
                
                <div class="candidate-body">
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
                        
                        <div class="rank-info">
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
                    
                    <div class="candidate-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <span><strong>Full Name:</strong> <?php echo $fullName; ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-card"></i>
                            <span><strong>Candidate ID:</strong> <?php echo $candidate['id']; ?></span>
                        </div>
                        <?php if ($isCurrentUser): ?>
                        <div class="info-item">
                            <i class="fas fa-star" style="color: var(--accent-green);"></i>
                            <span style="color: var(--accent-green); font-weight: 600;">This is your profile</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <a href="fincan_result.php?key=<?php echo urlencode($candidate['id']); ?>" class="view-detail-btn">
                        <i class="fas fa-chart-bar"></i> View Detailed Results
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-friends"></i>
            <h3>No Candidates Found</h3>
            <p>There are currently no registered candidates in the system.</p>
        </div>
        <?php endif; ?>

        <!-- Important Notice -->
        <div style="background: linear-gradient(135deg, #e6f0ff, #d4e4ff); 
                    border-radius: var(--border-radius); 
                    padding: 25px; 
                    margin-top: 40px;
                    border-left: 5px solid var(--primary-blue);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <i class="fas fa-info-circle" style="font-size: 1.5rem; color: var(--primary-blue);"></i>
                <h3 style="color: var(--primary-blue); margin: 0;">Election Information</h3>
            </div>
            <ul style="color: var(--dark-gray); margin-left: 20px;">
                <li>Candidates are ranked by number of votes received (highest to lowest)</li>
                <li>Gold/Silver/Bronze badges indicate top 3 positions</li>
                <li>Vote share shows percentage of total votes each candidate received</li>
                <li>Your profile is highlighted with a "You" badge</li>
                <li>Click "View Detailed Results" for comprehensive voting statistics</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="far fa-copyright"></i> <?php echo date("Y"); ?> Election Commission. All rights reserved.</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-chart-line"></i> Candidate Results Dashboard - Real-time Rankings
            </p>
        </div>
    </footer>

    <script>
        // Add animation to cards on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.candidate-card, .stat-card');
            
            const observer = new IntersectionObserver((entries) => {
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
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });

            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-fill');
            
            const barObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const progressFill = entry.target;
                        const width = progressFill.style.width;
                        progressFill.style.width = '0%';
                        
                        setTimeout(() => {
                            progressFill.style.transition = 'width 1.5s ease-in-out';
                            progressFill.style.width = width;
                        }, 300);
                        
                        barObserver.unobserve(progressFill);
                    }
                });
            }, {
                threshold: 0.5
            });
            
            progressBars.forEach(bar => {
                barObserver.observe(bar);
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