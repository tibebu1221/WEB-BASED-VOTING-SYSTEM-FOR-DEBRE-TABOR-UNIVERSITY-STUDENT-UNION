<?php
include("connection.php"); // Ensure this file sets up a MySQLi connection
session_start();

// Set timezone to EAT
date_default_timezone_set('Africa/Nairobi');

// Check if the user is logged in
if (!isset($_SESSION['u_id'])) {
    ?>
    <script>
        alert('You are not logged In !! Please Login to access this page');
        window.location = 'login.php';
    </script>
    <?php
    exit(); // Stop further execution
}

// Fetch user data using MySQLi
$user_id = $_SESSION['u_id'];

// Ensure $conn is your MySQLi connection from connection.php
$stmt = $conn->prepare("SELECT fname, mname, vid FROM voter WHERE vid = ?");
$stmt->bind_param("s", $user_id); // 's' for string, assuming vid is a string
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
    $vid = htmlspecialchars($row['vid']);
    $station = ""; // Set to empty or fetch from another table if needed
} else {
    die("Error: User not found in the database.");
}
$stmt->close();

// Handle vote submission
if (isset($_POST['ok'])) {
    $candidate = $_POST['candidate'];

    // Validate candidate input
    if (empty($candidate)) {
        echo '<div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); 
                border-left: 5px solid #ffc107; color: #856404; padding: 15px 20px; 
                border-radius: 8px; box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3); 
                max-width: 400px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 24px; color: #ffc107;"></i>
                    <div>
                        <h4 style="margin: 0 0 5px 0; color: #856404;">Selection Required</h4>
                        <p style="margin: 0; color: #856404;">Please select a candidate!</p>
                    </div>
                </div>
              </div>';
        echo '<meta content="6;cast.php" http-equiv="refresh"/>';
    } else {
        // Check if user has already voted
        $stmt = $conn->prepare("SELECT * FROM result WHERE vid = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo '<div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
                    border-left: 5px solid #dc3545; color: #721c24; padding: 15px 20px; 
                    border-radius: 8px; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); 
                    max-width: 400px; animation: blink 1s infinite;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-ban" style="font-size: 24px; color: #dc3545;"></i>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #721c24; font-weight: bold;">Already Voted!</h4>
                            <p style="margin: 0; color: #721c24; font-weight: bold;">You have already cast your vote!</p>
                            <small style="color: #721c24;">You cannot vote more than once.</small>
                        </div>
                    </div>
                  </div>';
            echo '<meta content="15;cast.php" http-equiv="refresh"/>';
        } else {
            // Insert vote into result table (removed station from query)
            $stmt = $conn->prepare("INSERT INTO result (vid, u_id, fname, mname, choice) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $user_id, $vid, $FirstName, $middleName, $candidate);
            if ($stmt->execute()) {
                // Update voter status
                $stmt = $conn->prepare("UPDATE voter SET status = 1 WHERE vid = ?");
                $stmt->bind_param("s", $user_id);
                if ($stmt->execute()) {
                    echo '<div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
                            border-left: 5px solid #28a745; color: #155724; padding: 15px 20px; 
                            border-radius: 8px; box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); 
                            max-width: 400px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check-circle" style="font-size: 24px; color: #28a745;"></i>
                                <div>
                                    <h4 style="margin: 0 0 5px 0; color: #155724;">Vote Cast Successfully!</h4>
                                    <p style="margin: 0; color: #155724;">Thank you for casting your vote!</p>
                                </div>
                            </div>
                          </div>';
                    echo '<meta content="6;cast.php" http-equiv="refresh"/>';
                } else {
                    echo '<div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
                            border-left: 5px solid #dc3545; color: #721c24; padding: 15px 20px; 
                            border-radius: 8px; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); 
                            max-width: 400px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #dc3545;"></i>
                                <div>
                                    <h4 style="margin: 0 0 5px 0; color: #721c24;">Update Error</h4>
                                    <p style="margin: 0; color: #721c24;">Error updating voter status. Please try again.</p>
                                </div>
                            </div>
                          </div>';
                    echo '<meta content="6;cast.php" http-equiv="refresh"/>';
                }
            } else {
                echo '<div style="position: fixed; top: 20px; right: 20px; z-index: 9999; 
                        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
                        border-left: 5px solid #dc3545; color: #721c24; padding: 15px 20px; 
                        border-radius: 8px; box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); 
                        max-width: 400px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #dc3545;"></i>
                            <div>
                                <h4 style="margin: 0 0 5px 0; color: #721c24;">Vote Failed</h4>
                                <p style="margin: 0; color: #721c24;">Error casting vote. Please try again.</p>
                            </div>
                        </div>
                      </div>';
                echo '<meta content="6;cast.php" http-equiv="refresh"/>';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Vote - Online Voting System</title>
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
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(to right, #2c3e50, #4a6491);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section img {
            height: 60px;
            border-radius: 8px;
        }

        .system-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 30px;
            backdrop-filter: blur(10px);
        }

        .user-info i {
            font-size: 1.5rem;
            color: #64b5f6;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .user-id {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 0.8rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        .nav-menu li {
            position: relative;
        }

        .nav-menu a {
            color: #2c3e50;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }

        .nav-menu a.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .nav-menu i {
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-container {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .voting-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 2rem auto;
            max-width: 900px;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .card-header h2 i {
            font-size: 2.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .card-body {
            padding: 3rem;
        }

        /* Election Status */
        .status-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            border-left: 5px solid #667eea;
        }

        .status-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }

        .status-title i {
            font-size: 2rem;
            color: #667eea;
        }

        .status-message {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #2c3e50;
        }

        .date-info {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .date-item {
            text-align: center;
            flex: 1;
        }

        .date-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .date-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Voting Form */
        .voting-form {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group label i {
            color: #667eea;
        }

        .candidate-select {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1.5rem center;
            background-size: 1em;
        }

        .candidate-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .candidate-select option {
            padding: 1rem;
            font-size: 1rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 2rem auto 0;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Alert Messages */
        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 5px solid #28a745;
            color: #155724;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 5px solid #ffc107;
            color: #856404;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 5px solid #dc3545;
            color: #721c24;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border-left: 5px solid #17a2b8;
            color: #0c5460;
        }

        .blink {
            animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
            50% { opacity: 0.5; }
        }

        /* Footer */
        .footer {
            background: linear-gradient(to right, #2c3e50, #4a6491);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-logo img {
            height: 40px;
        }

        .copyright {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .footer-links {
            display: flex;
            gap: 20px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .footer-links a:hover {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }

            .nav-menu a {
                justify-content: center;
            }

            .card-body {
                padding: 1.5rem;
            }

            .date-info {
                flex-direction: column;
                gap: 1rem;
            }

            .footer-content {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInRight 0.5s ease, slideOutRight 0.5s ease 4.5s forwards;
            border-left: 5px solid;
        }

        .toast-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }

        .toast-success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .toast-warning {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }

        .toast-info {
            border-left-color: #17a2b8;
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        }

        .toast-icon {
            font-size: 1.8rem;
        }

        .toast-content h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .toast-content p {
            margin: 0;
            color: #495057;
            font-size: 0.95rem;
        }

        .toast-danger .toast-content h4 {
            color: #721c24;
        }

        .toast-danger .toast-content p {
            color: #721c24;
        }

        .toast-success .toast-content h4 {
            color: #155724;
        }

        .toast-success .toast-content p {
            color: #155724;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Red alert specific styles */
        .red-alert {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
            border-left: 5px solid #dc3545 !important;
            color: #721c24 !important;
        }

        .red-alert i {
            color: #dc3545 !important;
        }

        .red-alert h4 {
            color: #721c24 !important;
            font-weight: bold !important;
        }

        .red-alert p {
            color: #721c24 !important;
            font-weight: bold !important;
        }

        .red-alert small {
            color: #721c24 !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo-section">
            <img src="img/ethio_flag.JPG" alt="Ethiopia Flag">
            <div class="system-title">
                <h1>Cast Vote Dashboard  </h1>
            </div>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($FirstName) . ' ' . htmlspecialchars($middleName); ?></span>
                <span class="user-id">Voter ID: <?php echo htmlspecialchars($vid); ?></span>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <ul class="nav-menu">
            <li><a href="voter.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="v_change.php"><i class="fas fa-key"></i> Change Password</a></li>
            <li class="active"><a href="cast.php"><i class="fas fa-vote-yea"></i> Cast Vote</a></li>
            <li><a href="voter_candidate.php"><i class="fas fa-users"></i> Candidates</a></li>
            <li><a href="voter_comment.php"><i class="fas fa-comment"></i> Comment</a></li>
            <li><a href="v_result.php"><i class="fas fa-chart-bar"></i> Result</a></li>
            <li><a href="vlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-container">
        <div class="voting-card">
            <div class="card-header">
                <h2><i class="fas fa-ballot-check"></i> Cast Your Vote</h2>
                <p>Your voice matters. Make your choice count!</p>
            </div>

            <div class="card-body">
                <?php
                // Fetch election date
                $date1 = date('Y-m-d');
                $yr = date('Y');
                $stmt = $conn->prepare("SELECT date FROM election_date WHERE YEAR(date) = ?");
                $stmt->bind_param("s", $yr);
                $stmt->execute();
                $result = $stmt->get_result();
                $date2 = $result->num_rows > 0 ? $result->fetch_assoc()['date'] : null;
                $stmt->close();

                if (!$date2) {
                    echo '<div class="alert alert-danger blink">
                            <i class="fas fa-calendar-times"></i>
                            <div>
                                <h4>No Election Scheduled</h4>
                                <p>There are no elections scheduled for this year.</p>
                            </div>
                          </div>';
                    $voting_allowed = false;
                } else {
                    $date1_obj = new DateTime($date1);
                    $date2_obj = new DateTime($date2);
                    $interval = $date1_obj->diff($date2_obj);
                    $days_diff = $interval->days * ($interval->invert ? -1 : 1);
                    $voting_allowed = ($date1 === $date2);
                    
                    $status_class = '';
                    $status_icon = '';
                    $status_title = '';
                    $status_message = '';
                    
                    if ($date1 > $date2) {
                        $status_class = 'alert-danger';
                        $status_icon = 'fas fa-times-circle';
                        $status_title = 'Voting Period Expired';
                        $status_message = 'The voting period for this election has ended.';
                    } elseif ($date1 < $date2) {
                        if ($days_diff >= 3) {
                            $status_class = 'alert-info';
                            $status_icon = 'fas fa-info-circle';
                        } elseif ($days_diff == 2 || $days_diff == 1) {
                            $status_class = 'alert-warning blink';
                            $status_icon = 'fas fa-exclamation-triangle';
                        } else {
                            $status_class = 'alert-danger';
                            $status_icon = 'fas fa-times-circle';
                        }
                        $status_title = 'Election Scheduled';
                        $status_message = "Election will be held on " . htmlspecialchars($date2);
                    } else {
                        $status_class = 'alert-success blink';
                        $status_icon = 'fas fa-check-circle';
                        $status_title = 'Election Day!';
                        $status_message = 'Today is the election day. Cast your vote now!';
                    }
                    
                    echo '<div class="alert ' . $status_class . '">
                            <i class="' . $status_icon . ' fa-2x"></i>
                            <div>
                                <h4>' . $status_title . '</h4>
                                <p>' . $status_message . '</p>
                            </div>
                          </div>';
                    
                    if ($date1 === $date2) {
                        echo '<div class="status-container">
                                <div class="status-title">
                                    <i class="fas fa-calendar-check"></i>
                                    <h3>Today\'s Election Information</h3>
                                </div>
                                <p class="status-message">
                                    Welcome to the national election voting platform. 
                                    You are authorized to cast your vote today. 
                                    Please select your preferred candidate below and submit your vote.
                                </p>
                                <div class="date-info">
                                    <div class="date-item">
                                        <div class="date-label">Current Date</div>
                                        <div class="date-value">' . htmlspecialchars($date1) . '</div>
                                    </div>
                                    <div class="date-item">
                                        <div class="date-label">Election Date</div>
                                        <div class="date-value">' . htmlspecialchars($date2) . '</div>
                                    </div>
                                    <div class="date-item">
                                        <div class="date-label">Status</div>
                                        <div class="date-value">' . ($voting_allowed ? 'Active' : 'Inactive') . '</div>
                                    </div>
                                </div>
                              </div>';
                    }
                }
                ?>

                <!-- Voting Form -->
                <div class="voting-form">
                    <form role="form" action="cast.php" method="post" enctype="multipart/form-data" autocomplete="off">
                        <div class="form-group">
                            <label for="candidate">
                                <i class="fas fa-user-tie"></i>
                                Select Your Preferred Candidate
                            </label>
                            <select id="candidate" name="candidate" class="candidate-select" 
                                    required x-moz-errormessage="Select candidate" 
                                    <?php echo $voting_allowed ? '' : 'disabled'; ?>>
                                <option value="">-- Please choose a candidate --</option>
                                <?php
                                $stmt = $conn->prepare("SELECT c_id, fname, mname, lname FROM candidate");
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    while ($CYS_row = $result->fetch_assoc()) {
                                        $candidate_id = htmlspecialchars($CYS_row['c_id']);
                                        $candidate_name = htmlspecialchars(trim($CYS_row['fname'] . ' ' . $CYS_row['mname'] . ' ' . $CYS_row['lname']));
                                        ?>
                                        <option value="<?php echo $candidate_id; ?>">
                                            <?php echo $candidate_name; ?>
                                        </option>
                                        <?php
                                    }
                                } else {
                                    echo '<option value="" disabled>No candidates available</option>';
                                }
                                $stmt->close();
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="submit-btn" 
                                name="<?php echo $voting_allowed ? 'ok' : 'project'; ?>" 
                                <?php echo $voting_allowed ? '' : 'disabled'; ?>>
                            <i class="fas fa-paper-plane"></i>
                            Submit Your Vote
                        </button>
                    </form>
                </div>

                <!-- Voting Instructions -->
                <div class="status-container" style="margin-top: 2rem; background: #e8f4fc; border-left-color: #17a2b8;">
                    <div class="status-title">
                        <i class="fas fa-info-circle" style="color: #17a2b8;"></i>
                        <h3>Voting Instructions</h3>
                    </div>
                    <ul style="list-style-type: none; padding-left: 0;">
                        <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                            <i class="fas fa-check-circle" style="color: #28a745; position: absolute; left: 0;"></i>
                            Select your preferred candidate from the dropdown list
                        </li>
                        <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                            <i class="fas fa-check-circle" style="color: #28a745; position: absolute; left: 0;"></i>
                            Review your selection before submitting
                        </li>
                        <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                            <i class="fas fa-check-circle" style="color: #28a745; position: absolute; left: 0;"></i>
                            Click "Submit Your Vote" to cast your vote
                        </li>
                        <li style="padding-left: 1.5rem; position: relative;">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107; position: absolute; left: 0;"></i>
                            <strong>Note:</strong> You can only vote once. Your vote is final and cannot be changed.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="img/ethio_flag.JPG" alt="Ethiopia Flag">
            </div>
            <div class="copyright">
                &copy; <?php echo date("Y"); ?> National Election Commission. All rights reserved.
            </div>
            <div class="footer-links">
                <a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a>
                <a href="#"><i class="fas fa-file-contract"></i> Terms of Service</a>
                <a href="#"><i class="fas fa-headset"></i> Contact Support</a>
            </div>
        </div>
    </footer>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission confirmation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const candidateSelect = document.getElementById('candidate');
                    if (candidateSelect && candidateSelect.value) {
                        const candidateName = candidateSelect.options[candidateSelect.selectedIndex].text;
                        if (!confirm(`Are you sure you want to vote for "${candidateName}"?\n\nThis action cannot be undone.`)) {
                            e.preventDefault();
                        }
                    }
                });
            }

            // Animate status messages
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach((alert, index) => {
                alert.style.animationDelay = `${index * 0.1}s`;
            });

            // Update time dynamically
            function updateTime() {
                const timeElement = document.getElementById('current-time');
                if (timeElement) {
                    const now = new Date();
                    timeElement.textContent = now.toLocaleTimeString('en-US', {
                        hour12: true,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
            }
            setInterval(updateTime, 1000);
            updateTime();
        });
    </script>
</body>
</html>
<?php
$conn->close(); // Close the MySQLi connection
?>