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

// Fetch unread comment count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM comment WHERE status = 'unread'");
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
    <title>Online Voting - View Comments</title>
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
        
        /* 4. Comment Section (Centered and functional) */
        .comment-section {
            width: 100%;
            max-width: 900px; /* Increased max-width for better use of space */
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid #1a2a6c;
        }
        .comment-section h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #1a2a6c;
            font-size: 1.8em;
            border-bottom: 2px solid #ffcc00;
            padding-bottom: 10px;
        }
        .comment-section .message-count {
            text-align: center;
            margin-bottom: 25px;
            font-size: 18px;
            color: #2c3e50;
        }
        .comment-section .message-count span {
            color: #b21f1f; /* Use the red color from the background gradient */
            font-weight: 700;
            font-size: 1.2em;
        }
        
        /* 5. Comment Table Styling */
        .comment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden; /* Ensures border-radius applies to contents */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .comment-table th,
        .comment-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        .comment-table th {
            background-color: #1a2a6c;
            color: white;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .comment-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .comment-table td {
            font-size: 14px;
        }
        .comment-table td.status-unread {
            color: #b21f1f; /* Red */
            font-weight: 600;
        }
        .comment-table td.status-read {
            color: #2c3e50; /* Dark Blue/Gray */
        }
        .comment-table td a {
            color: #1a2a6c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .comment-table td a:hover {
            color: #ffcc00;
        }
        .comment-table td img {
            vertical-align: middle;
        }

        /* 6. Footer (full width) */
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
            .comment-section {
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
            .comment-table th,
            .comment-table td {
                font-size: 10px;
                padding: 6px;
            }
            /* Hide columns on small screens */
            .comment-table th:nth-child(2),
            .comment-table td:nth-child(2) {
                display: none; /* Hide Email column */
            }
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this comment?');
        }
    </script>
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
                <img src="deve/a.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="comment-section">
                    <h2><i class="fas fa-comment-dots"></i> <u>Voter Comments</u></h2>
                    <p class="message-count">You have <span><?php echo $countav; ?></span> new message(s).</p>
                    <?php
                    $stmt = $conn->prepare("SELECT c_id, name, email, date, status FROM comment ORDER BY date DESC");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $count = $result->num_rows;
                    ?>
                    <table class="comment-table">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Open</th>
                            <th>Delete</th>
                        </tr>
                        <?php if ($count == 0) { ?>
                            <tr><td colspan="6" style="color: red; text-align: center; padding: 20px;">No comments found!</td></tr>
                        <?php } else {
                            while ($row = $result->fetch_assoc()) {
                                $ctrl = htmlspecialchars($row['c_id']);
                                $name = htmlspecialchars($row['name']);
                                $email = htmlspecialchars($row['email']);
                                $date = htmlspecialchars($row['date']);
                                $status = htmlspecialchars($row['status']);
                                ?>
                                <tr>
                                    <td><img src="img/bul.jpg" width="10px" alt="Bullet">&nbsp;<?php echo $name; ?></td>
                                    <td><?php echo $email; ?></td>
                                    <td><?php echo $date; ?></td>
                                    <td class="<?php echo $status == 'unread' ? 'status-unread' : 'status-read'; ?>">
                                        <?php echo $status; ?>
                                    </td>
                                    <td><a href="open_com.php?key=<?php echo $ctrl; ?>"><i class="fas fa-eye" title="Open Comment"></i> Open</a></td>
                                    <td><a href="delete_com.php?key=<?php echo $ctrl; ?>" onclick="return confirmDelete();">
                                        <img width="15px" height="15px" src="img/actions-delete.png" alt="Delete" title="Delete Comment">
                                    </a></td>
                                </tr>
                                <?php
                            }
                        }
                        $stmt->close();
                        ?>
                    </table>
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