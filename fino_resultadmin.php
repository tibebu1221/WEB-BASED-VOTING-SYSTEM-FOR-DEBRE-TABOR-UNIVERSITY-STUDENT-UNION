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

// Fetch candidate data
$ctrl = $_GET['key'] ?? '';
if (!preg_match('/^[a-zA-Z0-9]+$/', $ctrl)) {
    echo '<script>alert("Invalid candidate ID."); window.location = "adminv_result.php";</script>';
    exit();
}

// CORRECTED: Removed 'education' from the SELECT query
$stmt = $conn->prepare("SELECT fname, mname, lname, age, sex, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo '<script>alert("Candidate not found."); window.location = "adminv_result.php";</script>';
    $stmt->close();
    exit();
}

$row = $result->fetch_assoc();
$r1 = htmlspecialchars($row['fname']);
$r2 = htmlspecialchars($row['mname']);
$r3 = htmlspecialchars($row['lname']);
$r4 = htmlspecialchars($row['age']);
$r5 = htmlspecialchars($row['sex']);
// $r7 is removed since education column doesn't exist
$r8 = htmlspecialchars($row['phone']);
$r9 = htmlspecialchars($row['email']);
$r10 = htmlspecialchars($row['experience']);
$r13 = htmlspecialchars($row['candidate_photo']);
$stmt->close();

// Fetch vote count
$stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM result WHERE choice = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc()['vote_count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Candidate Result Details</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
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
            height: 180px;
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
        nav ul li {
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
        
        /* 4. Result Details (Centered and responsive) */
        .result-details {
            width: 100%;
            max-width: 700px; /* Allows the content to expand more than the original 600px */
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border: 1px solid #1a2a6c;
        }
        .result-details h2 {
            background-color: #1a2a6c; /* Darker header color */
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            text-align: center;
            text-transform: uppercase;
            margin: -20px -20px 20px -20px;
            font-size: 1.5em;
        }
        .result-details .close-link {
            position: absolute; /* Changed to absolute position relative to .result-details */
            top: 10px;
            right: 10px;
            margin: 0;
            z-index: 10;
        }
        .result-details {
            position: relative; /* Needed for absolute positioning of close-link */
        }
        .result-details .close-link img {
            width: 30px;
            height: 30px;
            transition: transform 0.3s;
        }
        .result-details .close-link:hover img {
            transform: scale(1.1);
        }
        .result-details .details-grid {
            display: flex; /* Use flex for layout */
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        .details-grid > div {
            width: 100%;
            text-align: center;
        }
        .result-details .details-grid img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid #b21f1f; /* Highlight photo */
        }
        .result-details .details-grid p {
            margin: 8px 0;
            font-size: 16px;
            text-align: left;
            padding: 0 15px;
        }
        .result-details .details-grid p strong {
            color: #1a2a6c;
            display: inline-block;
            width: 120px; /* Align labels */
            font-weight: 600;
        }
        .vote-count {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            background-color: #f0f4f8;
            border-radius: 8px;
            border: 1px dashed #b21f1f;
        }
        .vote-count span {
            color: #b21f1f;
            font-weight: bold;
            font-size: 2em; /* Increase font size for emphasis */
            display: block;
            margin-top: 5px;
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
        @media (min-width: 768px) {
            .result-details .details-grid {
                /* Two columns layout for larger screens: photo on one side, details on the other */
                display: grid;
                grid-template-columns: 180px 1fr; 
                align-items: start;
                text-align: left;
            }
            .details-grid > div:first-child {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .details-grid > div:first-child p {
                text-align: center;
            }
            .result-details .details-grid p {
                text-align: left;
                padding: 0;
            }
        }

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
            .result-details .close-link {
                top: 25px;
                right: 25px;
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
            .result-details {
                padding: 15px;
            }
            .result-details h2 {
                margin: -15px -15px 15px -15px;
            }
            .result-details .details-grid img {
                width: 100px;
                height: 100px;
            }
            .result-details .details-grid p {
                font-size: 14px;
            }
            .result-details .details-grid p strong {
                width: 90px;
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
                <img src="deve/A.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="result-details">
                    <h2>
                        <i class="fas fa-user-tie"></i> Candidate Result Details
                        <a href="adminv_result.php" title="Close" class="close-link">
                            <img src="img/close_icon.gif" alt="Close">
                        </a>
                    </h2>
                    <div class="details-grid">
                        <div>
                            <img src="<?php echo file_exists($r13) ? $r13 : 'img/default_candidate.jpg'; ?>" alt="Candidate Photo">
                        </div>
                        <div>
                            <p><strong>Candidate Name:</strong> <?php echo "$r1 $r2 $r3"; ?></p>
                            <p><strong>Candidate ID:</strong> <?php echo $ctrl; ?></p>
                            <p><strong>Age:</strong> <?php echo $r4; ?></p>
                            <p><strong>Sex:</strong> <?php echo $r5; ?></p>
                            <!-- Education row removed as column doesn't exist -->
                            <!-- <p><strong>Education:</strong> <?php echo $r7; ?></p> -->
                            <!-- REMOVED JOB ROW SINCE WORK COLUMN DOESN'T EXIST -->
                            <p><strong>Experience:</strong> <?php echo $r10; ?></p>
                            <p><strong>Phone:</strong> <?php echo $r8; ?></p>
                            <p><strong>Email:</strong> <?php echo $r9; ?></p>
                        </div>
                    </div>
                    <p class="vote-count">Total Votes Received: <span><?php echo $counts; ?></span></p>
                </div>
            </div>
        </div>
        
        <footer>
            <p>Copyright &copy; <?php echo date("Y"); ?> EC. | Secure Online Voting System</p>
        </footer>
    </div>
</body>
</html>
<?php
$conn->close();
?>