<?php
include("connection.php"); // Ensure this file uses mysqli_ for database connection
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
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$FirstName = htmlspecialchars($row['fname']);
$middleName = htmlspecialchars($row['mname']);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Candidate Detail</title>
    <link rel="icon" type="image/jpg" href="img/flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- MODERN FULL-SCREEN LAYOUT CSS --- */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f0f0; 
        }

        .container {
            width: 100%;
            min-height: 100vh;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        /* --- Header/Banner Styles --- */
        .header-banner {
            background: white url(img/tbg.png) repeat-x left top;
            padding: 10px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-logo img {
            width: 200px;
            height: 160px;
            margin-left: 10px;
        }
        .header-title img {
            width: 450px;
            margin-right: 30px; 
            margin-top: 40px;
        }
        
        /* --- Navigation Styles --- */
        .navbar {
            background-color: #2f4f4f; /* Dark Slate Gray */
        }
        .navbar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        .navbar ul li a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .navbar ul li a:hover,
        .navbar ul li.active a {
            background-color: #007bff;
        }
        
        /* --- Main Content Area (Sidebar + Detail) --- */
        .content-area {
            flex-grow: 1; 
            display: flex;
            background-color: #D3D3D3; 
        }
        
        /* Sidebar */
        .sidebar {
            width: 220px;
            flex-shrink: 0; 
        }
        .sidebar img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        
        /* Main Detail Area */
        .main-detail {
            flex-grow: 1; 
            padding: 20px;
            /* Center the candidate detail card vertically and horizontally */
            display: flex; 
            justify-content: center;
            align-items: flex-start; /* Aligns content to the top */
        }

        .desk {
            width: 100%;
            max-width: 600px; /* Limits the width of the content inside */
        }

        /* --- Candidate Detail Card Styles (Replacement for Inner Table) --- */
        .candidate-card-details {
            border-radius: 5px;
            border: 1px solid #336699;
            width: 100%;
            background-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 20px;
        }
        
        .card-header {
            background-color: #2f4f4f;
            color: white;
            padding: 10px 15px;
            text-transform: uppercase;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header a {
            line-height: 0; /* Align the close icon */
        }
        .card-header img {
            height: 16px; 
            width: 16px;
        }

        .card-body {
            padding: 15px;
        }
        
        .candidate-photo-section {
            text-align: center;
            margin-bottom: 15px;
        }
        .candidate-photo-section img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 3px solid #ccc;
            border-radius: 5px;
        }

        .candidate-data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .candidate-data-table td {
            padding: 8px 0;
            border-bottom: 1px dotted #e0e0e0;
        }
        .candidate-data-table tr:last-child td {
            border-bottom: none;
        }
        .candidate-data-table td:first-child {
            font-weight: bold;
            width: 35%;
        }

        .vote-count-message {
            background-color: #fdd;
            border: 1px solid red;
            color: #333;
            padding: 8px;
            margin-top: 10px;
            border-radius: 3px;
            text-align: center;
        }
        .vote-count-message font {
            color: red;
            font-weight: bold;
        }

        /* --- Footer Styles --- */
        .footer {
            background-color: #E6E6FA; 
            text-align: center;
            padding: 10px 0;
            flex-shrink: 0; 
        }
        .footer p {
            margin: 0;
        }

        /* --- Responsive Adjustments --- */
        @media (max-width: 900px) {
            .header-banner {
                flex-direction: column;
                text-align: center;
            }
            .header-logo, .header-title {
                margin: 10px 0;
            }
            .header-title img {
                margin: 0;
                width: 100%;
                max-width: 300px;
            }
            .content-area {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                text-align: center;
                padding: 10px 0;
            }
            .sidebar img {
                height: auto;
                max-width: 200px;
            }
            .main-detail {
                padding: 15px;
                align-items: flex-start;
            }
        }
        @media (max-width: 600px) {
            .candidate-card-details {
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-banner">
        <div class="header-logo">
            <a href="system_admin.php"><img src="img/logo.jpg" alt="Logo"></a>
        </div>
        <div class="header-title">
            <img src="img/officer.png" alt="Officer Title">
        </div>
    </div>

    <div class="navbar">
        <ul>
            <li><a href="e_officer.php">Home</a></li>
            <li class="active"><a href="o_result.php">Result</a></li>
            <li><a href="o_generate.php">Generate Report</a></li>
            <li><a href="regdate.php">r_vote date</a></li>
            <li><a href="regcan_date.php">r_candidate date</a></li>
            <?php
            // Fetch voter registration date
            $resultDate = $conn->query("SELECT * FROM voter_reg_date LIMIT 1");
            $dateRes = $resultDate->fetch_assoc();
            $startDate = $dateRes['start'];
            $endDate = $dateRes['end'];
            $current = date("Y-m-d");
            if ($current >= $startDate && $current <= $endDate) {
                echo '<li><a href="reg_voter.php">Voter</a></li>';
            }
            ?>
            
            <?php
            // Fetch candidate registration date
            $resultDate = $conn->query("SELECT * FROM candidate_reg_date LIMIT 1");
            $dateRes = $resultDate->fetch_assoc();
            $startDate = $dateRes['start'];
            $endDate = $dateRes['end'];
            $current = date("Y-m-d");
            if ($current >= $startDate && $current <= $endDate) {
                echo '<li><a href="ov_candidate.php">Candidates</a></li>';
            }
            ?>
            <li><a href="o_comment.php">V_Comment</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="content-area">
        <div class="sidebar">
            <img src="deve/o.png" alt="Officer Sidebar Image">
        </div>

        <div class="main-detail">
            <div class="desk">
                <h1 align="right">Welcome, <?php echo "$FirstName $middleName"; ?>!</h1>
                
                <?php
                // Fetch candidate details using prepared statement
                // REMOVED 'education' from query as it doesn't exist in the table
                $ctrl = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
                $stmt1 = $conn->prepare("SELECT fname, mname, lname, age, sex, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
                $stmt1->bind_param("s", $ctrl);
                $stmt1->execute();
                $result = $stmt1->get_result();
                $count = $result->num_rows;

                if ($count == 0) {
                    echo "<p style='color:red;'>Candidate is not registered!</p>";
                } else {
                    $row = $result->fetch_assoc();
                    $r1 = htmlspecialchars($row['fname']);
                    $r2 = htmlspecialchars($row['mname']);
                    $r3 = htmlspecialchars($row['lname']);
                    $r4 = htmlspecialchars($row['age']);
                    $r5 = htmlspecialchars($row['sex']);
                    $r8 = htmlspecialchars($row['phone']);
                    $r9 = htmlspecialchars($row['email']);
                    $r10 = htmlspecialchars($row['experience']);
                    $r13 = htmlspecialchars($row['candidate_photo']);
                    $stmt1->close(); 
                    ?>
                    <div class="candidate-card-details">
                        <div class="card-header">
                            <span>Candidate Details</span>
                            <a href="o_result.php" title="Close"><img src="img/close_icon.gif" alt="Close"></a>
                        </div>
                        <div class="card-body">
                            <div class="candidate-photo-section">
                                <img src='<?php echo file_exists($r13) ? $r13 : 'img/default_candidate.jpg'; ?>' alt="Candidate Photo">
                            </div>
                            
                            <table class="candidate-data-table">
                                <tr>
                                    <td>Candidate Name:</td>
                                    <td><strong><?php echo "$r1 $r2 $r3"; ?></strong></td>
                                </tr>
                                
                                <?php
                                // Fetch vote count using a new prepared statement
                                $stmt2 = $conn->prepare("SELECT COUNT(*) as vote_count FROM result WHERE choice = ?");
                                $stmt2->bind_param("s", $ctrl);
                                $stmt2->execute();
                                $results = $stmt2->get_result();
                                $counts = $results->fetch_assoc()['vote_count'];
                                
                                echo "<tr><td colspan='2' class='vote-count-message'>This candidate has <font color='red'>$counts</font> vote(s)</td></tr>";
                                $stmt2->close(); 
                                ?>
                                
                                <tr><td>Age:</td><td><?php echo $r4; ?></td></tr>
                                <tr><td>Sex:</td><td><?php echo $r5; ?></td></tr>
                                <!-- Education row removed as column doesn't exist in database -->
                                <tr><td>Experience:</td><td><?php echo $r10; ?></td></tr>
                                <tr><td>Phone:</td><td><?php echo $r8; ?></td></tr>
                                <tr><td>Email:</td><td><?php echo $r9; ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <br><br>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Copyright &copy; 2025 EC. | Secure Online Voting System</p>
    </div>
</div>
</body>
</html>
<?php
$conn->close(); // Close the database connection
?>