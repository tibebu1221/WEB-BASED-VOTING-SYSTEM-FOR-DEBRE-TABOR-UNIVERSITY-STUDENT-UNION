<?php
include("connection.php");
session_start();

// Check connection
if (isset($conn) && !$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch election date
$election_date = '';
$date_result = mysqli_query($conn, "SELECT * FROM election_date LIMIT 1");
if ($row = mysqli_fetch_array($date_result)) {
    $election_date = htmlspecialchars($row['date']);
}

// Check if votes table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'votes'");
$votes_table_exists = mysqli_num_rows($table_check) > 0;

// Check if result table exists
$table_check2 = mysqli_query($conn, "SHOW TABLES LIKE 'result'");
$result_table_exists = mysqli_num_rows($table_check2) > 0;

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

$candidate_result = mysqli_query($conn, $query);
if (!$candidate_result) {
    die("Query failed: " . mysqli_error($conn));
}

$rank = 1;
$previous_votes = null;
$actual_rank = 1;
$total_votes = 0;
$max_votes = 0;

while ($row = mysqli_fetch_array($candidate_result)) {
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
    <title>Election Results | Online Voting System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --light-gray: #e5e7eb;
            --gold: #fbbf24;
            --silver: #94a3b8;
            --bronze: #d97706;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }
        
        .logo-text h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }
        
        /* Navigation */
        .nav-menu {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link.active {
            background: rgba(139, 92, 246, 0.2);
            color: white;
            border: 1px solid rgba(139, 92, 246, 0.4);
        }
        
        .nav-link i {
            font-size: 0.9rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 40px 0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        /* Left Sidebar */
        .sidebar {
            flex: 1;
            min-width: 300px;
        }
        
        .sidebar-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .developer-image {
            width: 100%;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border: 3px solid var(--light-gray);
        }
        
        .election-info {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-sm);
            padding: 25px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .election-info h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .election-date {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-sm);
        }
        
        .stat-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: var(--radius-sm);
            margin-top: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Right Content */
        .content {
            flex: 2;
            min-width: 300px;
        }
        
        .page-header {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        /* Warning Banner */
        .warning-banner {
            background: linear-gradient(135deg, var(--warning), #f59e0b);
            color: white;
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
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
            font-weight: 700;
            font-size: 1.2rem;
            margin-right: 15px;
            color: white;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .rank-1 { background: linear-gradient(135deg, var(--gold), #f59e0b); }
        .rank-2 { background: linear-gradient(135deg, var(--silver), #6b7280); }
        .rank-3 { background: linear-gradient(135deg, var(--bronze), #92400e); }
        .rank-other { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }
        
        /* Leaderboard */
        .leaderboard {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }
        
        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .leaderboard-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 15px;
            transition: var(--transition);
            background: var(--light);
            border-left: 4px solid transparent;
            position: relative;
        }
        
        .leaderboard-item:hover {
            transform: translateX(5px);
            box-shadow: var(--card-shadow);
            background: white;
        }
        
        .leaderboard-item.rank-1 { border-left-color: var(--gold); }
        .leaderboard-item.rank-2 { border-left-color: var(--silver); }
        .leaderboard-item.rank-3 { border-left-color: var(--bronze); }
        
        .candidate-photo-small {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 20px;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .candidate-details {
            flex: 1;
        }
        
        .candidate-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .candidate-id {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .vote-count {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            min-width: 80px;
            text-align: right;
        }
        
        .vote-label {
            font-size: 0.8rem;
            color: var(--gray);
            text-align: right;
        }
        
        /* Vote Progress Bar */
        .vote-progress {
            width: 100%;
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .vote-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius-sm);
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .stat-card.gold { border-top: 4px solid var(--gold); }
        .stat-card.silver { border-top: 4px solid var(--silver); }
        .stat-card.bronze { border-top: 4px solid var(--bronze); }
        .stat-card.total { border-top: 4px solid var(--primary); }
        
        .stat-number-large {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.gold .stat-number-large { color: var(--gold); }
        .stat-card.silver .stat-number-large { color: var(--silver); }
        .stat-card.bronze .stat-number-large { color: var(--bronze); }
        .stat-card.total .stat-number-large { color: var(--primary); }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
        }
        
        .empty-icon {
            width: 100px;
            height: 100px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--gray);
            font-size: 3rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        .footer-content {
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .copyright {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
            }
            
            .leaderboard-item {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .candidate-photo-small {
                margin: 0 auto 15px;
            }
            
            .vote-count {
                text-align: center;
                margin-top: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .logo-section {
                justify-content: center;
            }
            
            .nav-menu {
                justify-content: center;
            }
            
            .leaderboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
        
        /* Animations */
        .animate-in {
            animation: fadeIn 0.6s ease forwards;
            opacity: 0;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
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
                    <div class="logo">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Debre Tabor University
Student Union   Voting System</h1>
                        <p>Real-time Voting Rankings</p>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <a href="about.php" class="nav-link">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                    <a href="help.php" class="nav-link">
                        <i class="fas fa-question-circle"></i> Help
                    </a>
                    <a href="contacts.php" class="nav-link">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                    <a href="h_result.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i> Results
                    </a>
                    <a href="advert.php" class="nav-link">
                        <i class="fas fa-bullhorn"></i> Adverts
                    </a>
                    <a href="candidate.php" class="nav-link">
                        <i class="fas fa-users"></i> Candidates
                    </a>
                    <a href="vote.php" class="nav-link">
                        <i class="fas fa-check-circle"></i> Vote
                    </a>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container main-content">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card animate-in">
                <img src="deve/dt.PNG" alt="System Preview" class="developer-image">
                <div class="election-info">
                    <h3><i class="fas fa-calendar-alt"></i> Election Date</h3>
                    <?php if ($election_date): ?>
                        <div class="election-date"><?php echo date('F j, Y', strtotime($election_date)); ?></div>
                    <?php else: ?>
                        <div class="election-date">To Be Announced</div>
                    <?php endif; ?>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $candidate_count; ?></div>
                        <div class="stat-label">Total Candidates</div>
                    </div>
                    <div class="stat-box" style="margin-top: 10px;">
                        <div class="stat-number"><?php echo $total_votes; ?></div>
                        <div class="stat-label">Total Votes Cast</div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="dev.php" class="view-details-btn" style="background: var(--secondary); padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; color: white; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600;">
                        <i class="fas fa-code"></i> Developer Info
                    </a>
                </div>
            </div>
        </aside>

        <!-- Right Content -->
        <section class="content">
            <div class="page-header animate-in">
                <h1>Election Leaderboard</h1>
                <p>Candidates ranked by number of votes received</p>
                
                <?php if (!$votes_table_exists && !$result_table_exists): ?>
                    <div class="warning-banner animate-in delay-1">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>No votes recorded yet.</strong> Results will appear when voting begins.
                        </div>
                    </div>
                <?php elseif ($total_votes == 0): ?>
                    <div class="warning-banner animate-in delay-1" style="background: linear-gradient(135deg, var(--secondary), #3b82f6);">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>No votes cast yet.</strong> Be the first to vote!
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($candidate_count > 0): ?>
                <!-- Leaderboard -->
                <div class="leaderboard animate-in delay-1">
                    <div class="leaderboard-header">
                        <h2><i class="fas fa-trophy"></i> Current Standings</h2>
                        <div style="background: var(--light); padding: 8px 16px; border-radius: var(--radius-sm);">
                            <i class="fas fa-sync-alt" style="color: var(--primary); margin-right: 8px;"></i>
                            Updated: <?php echo date('g:i A'); ?>
                        </div>
                    </div>
                    
                    <?php 
                    // Find max votes for progress bars
                    $max_votes_display = 0;
                    foreach ($candidates as $candidate) {
                        if ($candidate['votes'] > $max_votes_display) {
                            $max_votes_display = $candidate['votes'];
                        }
                    }
                    
                    // Display leaderboard items
                    foreach ($candidates as $index => $candidate): 
                        $percentage = $max_votes_display > 0 ? ($candidate['votes'] / $max_votes_display) * 100 : 0;
                        $vote_percentage = $total_votes > 0 ? ($candidate['votes'] / $total_votes) * 100 : 0;
                    ?>
                        <div class="leaderboard-item rank-<?php echo min($candidate['rank'], 4); ?> animate-in delay-<?php echo min($index + 2, 4); ?>">
                            <div class="rank-badge rank-<?php echo min($candidate['rank'], 4); ?>">
                                <?php echo $candidate['rank']; ?>
                            </div>
                            
                            <img src="<?php echo file_exists($candidate['photo']) ? $candidate['photo'] : 'img/default_candidate.jpg'; ?>" 
                                 alt="<?php echo $candidate['fname'] . ' ' . $candidate['mname']; ?>"
                                 class="candidate-photo-small"
                                 onerror="this.src='img/default_candidate.jpg'">
                            
                            <div class="candidate-details">
                                <h3 class="candidate-name"><?php echo $candidate['fname'] . ' ' . $candidate['mname'] . ' ' . $candidate['lname']; ?></h3>
                                <div class="candidate-id">ID: <?php echo $candidate['id']; ?></div>
                                
                                <?php if ($max_votes_display > 0): ?>
                                    <div class="vote-progress">
                                        <div class="vote-progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <div class="vote-count"><?php echo $candidate['votes']; ?></div>
                                <div class="vote-label">VOTES</div>
                                <?php if ($total_votes > 0): ?>
                                    <div style="font-size: 0.9rem; color: var(--gray); margin-top: 5px;">
                                        <?php echo round($vote_percentage, 1); ?>%
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid animate-in delay-4">
                    <div class="stat-card gold">
                        <div class="stat-number-large">
                            <?php echo $candidates[0]['votes'] ?? 0; ?>
                        </div>
                        <div class="stat-label">1st Place Votes</div>
                        <?php if (isset($candidates[0])): ?>
                            <div style="margin-top: 10px; font-size: 0.9rem;">
                                <strong><?php echo $candidates[0]['fname'] . ' ' . $candidates[0]['mname']; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card silver">
                        <div class="stat-number-large">
                            <?php echo $candidates[1]['votes'] ?? 0; ?>
                        </div>
                        <div class="stat-label">2nd Place Votes</div>
                        <?php if (isset($candidates[1])): ?>
                            <div style="margin-top: 10px; font-size: 0.9rem;">
                                <strong><?php echo $candidates[1]['fname'] . ' ' . $candidates[1]['mname']; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card bronze">
                        <div class="stat-number-large">
                            <?php echo $candidates[2]['votes'] ?? 0; ?>
                        </div>
                        <div class="stat-label">3rd Place Votes</div>
                        <?php if (isset($candidates[2])): ?>
                            <div style="margin-top: 10px; font-size: 0.9rem;">
                                <strong><?php echo $candidates[2]['fname'] . ' ' . $candidates[2]['mname']; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-card total">
                        <div class="stat-number-large"><?php echo $total_votes; ?></div>
                        <div class="stat-label">Total Votes Cast</div>
                        <div style="margin-top: 10px; font-size: 0.9rem;">
                            <?php echo $candidate_count; ?> candidates
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="empty-state animate-in">
                    <div class="empty-icon">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h3>No Candidates Available</h3>
                    <p>There are no candidates registered for the election yet.</p>
                    <a href="candidate.php" class="view-details-btn" style="background: var(--secondary); margin-top: 20px; padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; color: white; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600;">
                        <i class="fas fa-eye"></i> View Candidates
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Section -->
            <div class="page-header" style="margin-top: 40px;">
                <h2>Election Statistics</h2>
                <p>Overview of the electoral process</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px;">
                    <div style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); padding: 25px; border-radius: var(--radius-sm); color: white;">
                        <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;"><?php echo $candidate_count; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Total Candidates</div>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--success), #059669); padding: 25px; border-radius: var(--radius-sm); color: white;">
                        <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;"><?php echo $total_votes; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Votes Cast</div>
                    </div>
                    <div style="background: linear-gradient(135deg, var(--accent), #d97706); padding: 25px; border-radius: var(--radius-sm); color: white;">
                        <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;"><?php echo $election_date ? 'Scheduled' : 'Pending'; ?></div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Election Status</div>
                    </div>
                    <div style="background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 25px; border-radius: var(--radius-sm); color: white;">
                        <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                            <?php 
                            if ($max_votes > 0 && $total_votes > 0) {
                                echo round(($max_votes / $total_votes) * 100) . '%';
                            } else {
                                echo '0%';
                            }
                            ?>
                        </div>
                        <div style="font-size: 0.9rem; opacity: 0.9;">Leading Share</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <p><i class="fas fa-shield-alt"></i> Secure & Transparent Voting Process</p>
            <p class="copyright">Copyright © <?php echo date("Y"); ?> Ethiopian Electoral Commission. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('.vote-progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Auto-refresh every 30 seconds
            setTimeout(() => {
                location.reload();
            }, 30000);
        });
    </script>

    <?php
    mysqli_close($conn);
    ?>
</body>
</html>