<?php
session_start();
include("connection.php"); // Ensure this uses MySQLi connection

// Set timezone to EAT
date_default_timezone_set('Africa/Nairobi');

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<script>alert("You are not logged in or not authorized! Please login as an admin."); window.location = "login.php";</script>';
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

// Handle form submission
if (isset($_POST['go'])) {
    $date = $_POST['date'] ?? '';
    if (!empty($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        // Validate future date (allow same day if needed)
        $selectedDate = strtotime($date);
        $today = strtotime(date('Y-m-d')); // Compare dates only, not time
        if ($selectedDate < $today) {
            echo '<script>alert("Please select a future date."); window.location = "setDate.php?date=' . urlencode($date) . '";</script>';
            exit();
        }

        // Delete existing election date for the same year
        $stmt = $conn->prepare("DELETE FROM election_date WHERE YEAR(date) = YEAR(?)");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $stmt->close();

        // Insert new election date with u_id
        $stmt = $conn->prepare("INSERT INTO election_date (date, u_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $date, $user_id);
        if ($stmt->execute()) {
            echo '<script>alert("Election date set successfully!"); window.location = "system_admin.php";</script>';
            exit();
        } else {
            error_log("Error setting election date: " . $stmt->error);
            echo '<script>alert("Error occurred while setting the date: ' . htmlspecialchars($stmt->error) . '"); window.location = "setDate.php?date=' . urlencode($date) . '";</script>';
            exit();
        }
        $stmt->close();
    } else {
        echo '<script>alert("Please select a valid date."); window.location = "setDate.php";</script>';
        exit();
    }
}

// Retain form input if redirected
$previousDate = isset($_GET['date']) ? htmlspecialchars(urldecode($_GET['date'])) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Set Election Date</title>
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
        
        /* 4. Date Section (Centered and functional) */
        .date-section {
            width: 100%;
            max-width: 450px; /* Centered, but slightly wider than original */
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid #1a2a6c;
        }
        .date-section h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #1a2a6c;
            font-size: 1.5em;
        }
        .date-section form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .date-section label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }
        .date-section input[type="date"] {
            padding: 12px;
            width: 250px; /* Wider input field */
            border: 2px solid #b21f1f; /* Highlight border with red/gold */
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
            transition: border-color 0.3s;
        }
        .date-section input[type="date"]:focus {
            border-color: #ffcc00;
            outline: none;
        }
        .date-section input[type="submit"] {
            padding: 12px 30px;
            background: #1a2a6c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1em;
            transition: background 0.3s, color 0.3s;
        }
        .date-section input[type="submit"]:hover {
            background: #ffcc00;
            color: #1a2a6c;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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
        }
        @media (max-width: 600px) {
            nav ul {
                flex-direction: column;
            }
            nav ul li a {
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .date-section {
                padding: 20px;
            }
            .date-section input[type="date"] {
                width: 90%;
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
                <li><a href="adminv_result.php">Result</a></li>
                <li><a class="active" href="setDate.php">Set Date</a></li>
                <li><a href="v_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <img src="deve/a.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="date-section">
                    <h2><i class="fas fa-calendar-alt"></i> <u>Specify Voting Date</u></h2>
                    <form name="myform" method="post">
                        <label for="SelectedDate">Select Election Date:</label>
                        <input type="date" id="SelectedDate" name="date" value="<?php echo $previousDate; ?>" required />
                        <input type="submit" name="go" value="Set"/>
                    </form>
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