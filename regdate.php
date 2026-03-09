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

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
} else {
    $FirstName = "Election";
    $middleName = "Officer";
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Set Voter Registration Date</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>

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
        
        /* --- Main Content Area (Sidebar + Form) --- */
        .content-area {
            flex-grow: 1; 
            display: flex;
            background-color: #D3D3D3; /* Light Gray */
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
        
        /* Main Form Area */
        .main-content {
            flex-grow: 1; 
            padding: 40px; /* Increased padding for better spacing */
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .desk {
            width: 100%;
            max-width: 500px; /* Limits the size of the content block */
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .desk h2 {
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-top: 0;
            color: #333;
        }

        /* Form Styling */
        .date-form {
            margin-top: 20px;
        }
        .date-form table {
            width: 100%;
            border-collapse: collapse;
        }
        .date-form td {
            padding: 10px 0;
        }
        .date-form td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .date-form input[type="date"],
        .date-form input[type="submit"] {
            padding: 10px;
            border: 1px solid #aaa;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        .date-form input[type="submit"] {
            background-color: #2E8B57; /* Sea Green */
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-top: 10px; /* space between fields and button */
            width: auto; /* reset width for button */
            float: right;
        }
        .date-form input[type="submit"]:hover {
            background-color: #228B22; /* Forest Green */
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
            .main-content {
                padding: 15px;
                align-items: center;
            }
        }
    </style>

    <script type="text/javascript">
        // Keeping the original isdelete function, though it seems unused on this page
        function isdelete() {
            var d = confirm('Are you sure you want to Delete?');
            if (!d) {
                window.location.href = 'e_candidate.php';
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header-banner">
        <div class="header-logo">
            <a href="system_admin.php"><img src="img/logo.JPG" alt="Logo"></a>
        </div>
        <div class="header-title">
            <img src="img/officer.png" alt="Officer Title">
        </div>
    </div>

    <div class="navbar">
        <ul>
            <li><a href="e_officer.php">Home</a></li>
            <li><a href="o_result.php">Result</a></li>
            <li><a href="o_generate.php">Generate Report</a></li>
            <li class="active"><a href="regdate.php">r_vote date</a></li>
            <li><a href="regcan_date.php">r_candidate date</a></li>
            <?php
            // Fetch voter registration date
            $resultDate = $conn->query("SELECT * FROM voter_reg_date LIMIT 1");
            $dateRes = $resultDate->fetch_assoc();
            $startDate = $dateRes['start'] ?? null;
            $endDate = $dateRes['end'] ?? null;
            $current = date("Y-m-d");

            if ($startDate && $endDate && $current >= $startDate && $current <= $endDate) {
                echo '<li><a href="reg_voter.php">Voter</a></li>';
            }
            ?>
           <!-- <li><a href="stations.php">Stations</a></li> -->
            <?php
            // Fetch candidate registration date
            $resultDate = $conn->query("SELECT * FROM candidate_reg_date LIMIT 1");
            $dateRes = $resultDate->fetch_assoc();
            $startDate_can = $dateRes['start'] ?? null;
            $endDate_can = $dateRes['end'] ?? null;

            if ($startDate_can && $endDate_can && $current >= $startDate_can && $current <= $endDate_can) {
                echo '<li><a href="ov_candidate.php">Candidates</a></li>';
            }
            ?>
            <li><a href="o_comment.php">V_Comment</a></li>
            <li><a href="e_officer_send_request.php">Send Request</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="content-area">
        <div class="sidebar">
            <img src="deve/o.JPG" alt="Officer Sidebar Image">
        </div>

        <div class="main-content">
            <div class="desk">
                <h1 align="right">Welcome, <?php echo "$FirstName $middleName"; ?>!</h1>
                
                <h2><u>Specify Voter Registration Dates:</u></h2>
                
                <form name="myform" method="post" class="date-form">
                    <table>
                        <tr>
                            <td>Start Date</td>
                            <td><input type="date" name="sDate" required></td>
                        </tr>
                        <tr>
                            <td>End Date</td>
                            <td><input type="date" name="eDate" required></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: right;"><input type="submit" name="setDate" value="Set"></td>
                        </tr>
                    </table>
                </form>
                
                <?php
                if (isset($_POST['setDate'])) {
                    $stdate = $_POST['sDate'];
                    $endate = $_POST['eDate'];

                    // Validate dates
                    if (empty($stdate) || empty($endate)) {
                        echo "<p style='color:red;'>**Please provide both start and end dates.**</p>";
                    } elseif (strtotime($endate) <= strtotime($stdate)) {
                        echo "<p style='color:red;'>**End date must be after start date.**</p>";
                    } else {
                        // Delete existing dates
                        // Note: This assumes only one range is active at a time, which seems to be the intent.
                        if (!$conn->query("DELETE FROM voter_reg_date")) {
                            echo "<p style='color:red;'>Error clearing old date: " . $conn->error . "</p>";
                        } else {
                            // Insert new dates using prepared statement
                            $stmt = $conn->prepare("INSERT INTO voter_reg_date (start, end) VALUES (?, ?)");
                            $stmt->bind_param("ss", $stdate, $endate);
                            if ($stmt->execute()) {
                                echo "<p style='color:green;'>**Voter registration dates set successfully!**</p>";
                                // Use JavaScript for a cleaner redirect after success message
                                echo "<script>setTimeout(function(){ window.location.href = 'e_officer.php'; }, 2000);</script>";
                                
                            } else {
                                echo "<p style='color:red;'>Error occurred while specifying: " . $conn->error . "</p>";
                            }
                            $stmt->close();
                        }
                    }
                }
                ?>
                <br>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Copyright &copy; 2009 EC. | Secure Online Voting System</p>
    </div>
</div>
</body>
</html>
<?php
$conn->close(); // Close the database connection
?>