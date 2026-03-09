<?php
session_start();
include("connection.php"); // Ensure this uses MySQLi connection

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
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
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
    <title>Online Voting - Election Results</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- FULL SCREEN CSS ADJUSTMENTS --- */
        
        /* 1. Body and Container Setup */
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Stack content vertically */
            margin: 0; /* Remove default body margin */
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            width: 100%;
            max-width: none; /* KEY CHANGE: Removes max-width constraint for full screen */
            background: rgba(255, 255, 255, 0.95);
            border-radius: 0; /* Removing rounded corners for full edge-to-edge look */
            box-shadow: none;
            overflow: hidden;
            flex-grow: 1; /* Makes the container fill the vertical space */
            display: flex;
            flex-direction: column;
        }
        
        /* 2. Header and Navigation (full width) */
        .header {
            background: linear-gradient(to right, #2c3e50, #1a2a6c);
            padding: 20px;
            text-align: center;
        }
        .header img {
            height: 160px;
            border-radius: 8px;
        }
        nav {
            background: #1a2a6c;
            padding: 0;
        }
        nav ul {
            display: flex;
            list-style: none;
            justify-content: center;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        nav ul li a:hover,
        nav ul li a.active {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 3px solid #ffcc00;
        }
        
        /* 3. Content Wrapper (Sidebar + Main Content) */
        .content-wrapper {
            display: flex;
            flex-grow: 1; /* Allows content area to fill vertical space */
            min-height: 500px;
        }
        .sidebar {
            width: 250px; /* Adjusted sidebar width */
            flex-shrink: 0;
            background: #f0f4f8;
            padding: 25px;
            border-right: 1px solid #e0e6ed;
        }
        .sidebar img {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: contain;
        }
        .main-content {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f8f8f8;
        }
        
        /* 4. Result Section (Centered and responsive) */
        .result-section {
            width: 100%;
            max-width: 1400px; /* Allows the content to spread out nicely on large screens */
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        /* Stats Section */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid;
        }
        
        .stat-card.total {
            border-top-color: #1a2a6c;
        }
        
        .stat-card.votes {
            border-top-color: #b21f1f;
        }
        
        .stat-card.leader {
            border-top-color: #ffcc00;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.total .stat-number {
            color: #1a2a6c;
        }
        
        .stat-card.votes .stat-number {
            color: #b21f1f;
        }
        
        .stat-card.leader .stat-number {
            color: #ffcc00;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        .leader-name {
            font-size: 0.9rem;
            color: #1a2a6c;
            font-weight: 600;
            margin-top: 5px;
        }
        
        /* Leader Banner */
        .leader-banner {
            background: linear-gradient(135deg, rgba(255, 204, 0, 0.1), rgba(255, 204, 0, 0.05));
            border: 2px solid rgba(255, 204, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .leader-banner i {
            font-size: 36px;
            color: #ffcc00;
        }
        
        .leader-info h3 {
            color: #856404;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .leader-info p {
            color: #333;
            font-size: 1rem;
        }
        
        .leader-info strong {
            color: #b21f1f;
        }
        
        /* Rank Badges */
        .rank-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: white;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #ffcc00, #e6b800);
            border: 2px solid #ffcc00;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #a8a8a8);
            border: 2px solid #c0c0c0;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #b5651d);
            border: 2px solid #cd7f32;
        }
        
        .rank-other {
            background: linear-gradient(135deg, #1a2a6c, #0d1e5a);
            border: 2px solid #1a2a6c;
        }
        
        /* Result Grid */
        .result-grid {
            display: grid;
            /* Allow up to 4 columns on large screens for full effect */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
        }
        .result-card {
            border: 2px solid #1a2a6c; /* Dark blue border */
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            padding: 20px;
            text-align: center;
            background: #ffffff;
            transition: transform 0.3s;
            position: relative;
        }
        .result-card.winner {
            border-color: #ffcc00;
            box-shadow: 0 8px 25px rgba(255, 204, 0, 0.3);
        }
        .result-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }
        .result-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #b21f1f; /* Red border */
        }
        .result-card h3 {
            color: #1a2a6c;
            margin: 10px 0;
            font-size: 1.2rem;
        }
        .result-card p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        /* Vote Stats */
        .vote-stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .vote-count {
            font-size: 1.8rem;
            font-weight: 800;
            color: #b21f1f;
            margin-bottom: 5px;
        }
        
        .vote-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .vote-percentage {
            font-size: 1rem;
            font-weight: 600;
            color: #1a2a6c;
            margin-top: 10px;
        }
        
        .result-card a {
            display: inline-block;
            margin-top: 15px;
            color: white;
            background-color: #1a2a6c;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            box-sizing: border-box;
        }
        .result-card a:hover {
            background-color: #ffcc00;
            color: #1a2a6c;
            transform: translateY(-2px);
        }
        
        .info-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .info-header h2 {
            color: #1a2a6c;
            font-size: 2.2rem;
            margin-bottom: 15px;
        }
        .info-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .info-header p span {
            color: #b21f1f; /* Highlight count in red */
            font-weight: bold;
            font-size: 1.3em;
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
        
        /* 5. Footer (full width) */
        footer {
            background: #1a2a6c;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        /* Responsive Tweaks */
        @media (max-width: 900px) {
            .content-wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #e0e6ed;
                padding: 15px 0;
                display: flex;
                justify-content: center;
            }
            .sidebar img {
                max-width: 150px;
                height: auto;
            }
            .main-content {
                padding: 20px;
            }
            .result-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }
        @media (max-width: 600px) {
            nav ul {
                flex-direction: column;
            }
            nav ul li a {
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .result-grid {
                grid-template-columns: 1fr;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
            .leader-banner {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="system_admin.php"><img src="img/logo.jpg" alt="Election Logo"></a>
        </div>
        
        <nav>
            <ul>
                <li><a href="system_admin.php">Home</a></li>
                <li><a href="manage_account.php">Manage Account</a></li>
                <li><a href="a_generate.php">Generate Report</a></li>
                <li><a href="a_candidate.php">Candidates</a></li>
                <li><a href="voters.php">Voters</a></li>
                <li><a class="active" href="adminv_result.php">Result</a></li>
                <li><a href="setDate.php">Set Date</a></li>
                <li><a href="v_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <img src="deve/a.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="result-section">
                    <div class="info-header">
                        <h2><i class="fas fa-trophy"></i> <u>Election Results Leaderboard</u></h2>
                        <p>Candidates ranked by number of votes received. There are <span><?php echo $candidate_count; ?></span> candidates participating.</p>
                        <p>Click "View Detail" to see comprehensive voting statistics for each candidate.</p>
                    </div>
                    
                    <?php if (!$votes_table_exists && !$result_table_exists): ?>
                    <div class="warning-banner">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>No voting data found.</strong> Results will appear when voting begins.
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
                        <div class="stat-card total">
                            <div class="stat-number"><?php echo $candidate_count; ?></div>
                            <div class="stat-label">Total Candidates</div>
                        </div>
                        
                        <div class="stat-card votes">
                            <div class="stat-number"><?php echo $total_votes; ?></div>
                            <div class="stat-label">Total Votes Cast</div>
                        </div>
                        
                        <div class="stat-card leader">
                            <div class="stat-number"><?php echo $max_votes > 0 ? $max_votes : '--'; ?></div>
                            <div class="stat-label">Leading Candidate Votes</div>
                            <?php if ($max_votes > 0): ?>
                            <div class="leader-name"><?php echo $leading_candidate_name; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Leader Banner -->
                    <?php if ($max_votes > 0): ?>
                    <div class="leader-banner">
                        <i class="fas fa-crown"></i>
                        <div class="leader-info">
                            <h3><i class="fas fa-trophy"></i> CURRENT ELECTION LEADER</h3>
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
                    
                    <div class="result-grid">
                        <?php
                        if (count($candidates) > 0) {
                            foreach ($candidates as $candidate): 
                                $vote_count = $candidate['votes'];
                                $vote_percentage = $total_votes > 0 ? ($vote_count / $total_votes) * 100 : 0;
                                $displayName = $candidate['fname'] . ' ' . $candidate['mname'];
                                $fullName = $candidate['fname'] . ' ' . $candidate['mname'] . ' ' . $candidate['lname'];
                                $is_winner = ($vote_count == $max_votes && $vote_count > 0);
                                $photo_path = file_exists($candidate['photo']) ? $candidate['photo'] : 'img/default_candidate.jpg';
                                ?>
                                <div class="result-card <?php echo $is_winner ? 'winner' : ''; ?>">
                                    <!-- Rank Badge -->
                                    <div class="rank-badge rank-<?php echo min($candidate['rank'], 4); ?>">
                                        <?php echo $candidate['rank']; ?>
                                    </div>
                                    
                                    <img src="<?php echo $photo_path; ?>" alt="Candidate Photo" onerror="this.src='img/default_candidate.jpg'">
                                    <h3><?php echo $displayName; ?></h3>
                                    <p><strong>ID:</strong> <?php echo $candidate['id']; ?></p>
                                    <p><strong>Full Name:</strong> <?php echo $fullName; ?></p>
                                    
                                    <div class="vote-stats">
                                        <div class="vote-count"><?php echo $vote_count; ?></div>
                                        <div class="vote-label">Votes Received</div>
                                        <?php if ($total_votes > 0): ?>
                                        <div class="vote-percentage">
                                            Vote Share: <?php echo round($vote_percentage, 1); ?>%
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="margin: 10px 0; font-size: 0.9rem; color: #666;">
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
                                    
                                    <a href="fino_resultadmin.php?key=<?php echo $candidate['id']; ?>">
                                        <i class="fas fa-chart-line"></i> View Detail
                                    </a>
                                </div>
                                <?php
                            endforeach;
                        } else {
                            echo '<p style="text-align: center; color: #b21f1f; font-weight: bold; padding: 20px; grid-column: 1 / -1;">No candidates found to display results.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <footer>
            <p>Copyright &copy; <?php echo date("Y"); ?> Ethiopian Electoral Commission | Secure Online Voting System</p>
            <p style="margin-top: 10px; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-chart-line"></i> Admin Results Dashboard - Real-time Rankings
            </p>
        </footer>
    </div>
    
    <script>
        // Add animation to cards on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.result-card');
            
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