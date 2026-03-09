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

// Fetch comment data
$ctrl = $_GET['key'] ?? '';
if (!preg_match('/^[a-zA-Z0-9]+$/', $ctrl)) {
    // If key is missing or invalid, check if it was posted (redirect from previous page)
    $ctrl = $_POST['c_id'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9]+$/', $ctrl)) {
        echo '<script>alert("Invalid comment ID."); window.location = "v_comment.php";</script>';
        exit();
    }
}

$stmt = $conn->prepare("SELECT c_id, name, email, content, date, status FROM comment WHERE c_id = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo '<script>alert("Comment not found."); window.location = "v_comment.php";</script>';
    $stmt->close();
    exit();
}

$row = $result->fetch_assoc();
$r1 = htmlspecialchars($row['c_id']);
$r2 = htmlspecialchars($row['name']);
$r3 = htmlspecialchars($row['email']);
$r4 = htmlspecialchars($row['content']);
$r5 = htmlspecialchars($row['date']);
$status = htmlspecialchars($row['status']);
$stmt->close();

// Update comment status to 'read' if not already
if ($status !== 'read') {
    $stmt = $conn->prepare("UPDATE comment SET status = 'read' WHERE c_id = ?");
    $stmt->bind_param("s", $ctrl);
    $stmt->execute();
    $stmt->close();
}

// Handle form submission (Just redirects back to comments list)
if (isset($_POST['update'])) {
    echo '<script>window.location = "v_comment.php";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - View Comment</title>
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
        
        /* 4. Comment Section (Centered and functional) */
        .comment-section {
            width: 100%;
            max-width: 600px; /* Keeps the comment view readable/centered */
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid #1a2a6c;
        }
        .comment-section h2 {
            background-color: #1a2a6c; /* Use the main blue color */
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            text-align: center;
            margin: -30px -30px 30px -30px; /* Adjusted margin to match new padding */
            font-size: 1.5em;
        }
        .comment-section .comment-details {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ccc;
        }
        .comment-section .comment-details p {
            margin: 8px 0;
            font-size: 16px;
        }
        .comment-section .comment-details p strong {
            color: #b21f1f; /* Use a highlight color for labels */
            display: inline-block;
            width: 80px;
        }
        .comment-section textarea {
            width: 100%;
            min-height: 200px; /* Increased height for readability */
            padding: 15px;
            border: 2px solid #2c3e50;
            border-radius: 8px;
            font-size: 16px;
            resize: vertical;
            background: #f0f4f8;
            line-height: 1.5;
        }
        .comment-section input[type="submit"] {
            display: block;
            width: 150px;
            padding: 12px 20px;
            background: #1a2a6c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            margin: 20px auto 0 auto; /* Center the button */
            font-size: 1.1em;
            transition: background 0.3s, color 0.3s;
        }
        .comment-section input[type="submit"]:hover {
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
            .comment-section {
                padding: 15px;
            }
            .comment-section h2 {
                margin: -15px -15px 20px -15px;
            }
            .comment-section textarea {
                min-height: 150px;
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
                <li><a href="setDate.php">Set Date</a></li>
                <li><a class="active" href="v_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <img src="deve/A.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="comment-section">
                    <h2><i class="fas fa-envelope-open-text"></i> Comment Details</h2>
                    <div class="comment-details">
                        <p><strong>From:</strong> <?php echo $r2; ?></p>
                        <p><strong>Email:</strong> <?php echo $r3; ?></p>
                        <p><strong>Date:</strong> <?php echo $r5; ?></p>
                    </div>
                    <form id="form1" method="POST" action="open_com.php">
                        <input type="hidden" name="c_id" value="<?php echo $r1; ?>">
                        <textarea readonly placeholder="Comment Content"><?php echo $r4; ?></textarea>
                        <input type="submit" name="update" value="Ok"/>
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