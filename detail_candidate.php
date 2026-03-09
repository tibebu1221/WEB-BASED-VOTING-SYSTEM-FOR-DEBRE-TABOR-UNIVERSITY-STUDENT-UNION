<?php
ob_start(); // Start output buffering
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
    ob_end_flush();
    exit();
}

// Fetch user details
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$FirstName = $row['fname'];
$middleName = $row['mname'];
$stmt->close();

// Fetch candidate details (REMOVED 'work' from query)
$ctrl = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
$errors = [];
$candidate = null;
if (!empty($ctrl)) {
    $stmt = $conn->prepare("SELECT c_id, fname, mname, lname, age, sex, education, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
    $stmt->bind_param("s", $ctrl);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $candidate = $result->fetch_assoc();
    } else {
        $errors[] = "Candidate not found!";
    }
    $stmt->close();
} else {
    $errors[] = "Invalid candidate ID!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Voting</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
</head>
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
    <tr style="height:auto;border-radius:1px;background:white url(img/tbg.png) repeat-x left top;">
        <th colspan="2">
            <a href="e_officer.php"><img src="img/logo.JPG" width="200px" height="180px" align="left" style="margin-left:10px"></a>
            <img src="img/officer.png" width="450px" style="margin-left:30px;margin-top:40px" align="center">
        </th>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
            <ul>
                <li><a href="e_officer.php">Home</a></li>
                <li><a href="o_result.php">Result</a></li>
                <li><a href="o_generate.php">Generate Report</a></li>
                <li><a href="regdate.php">r_vote date</a></li>
                <li><a href="regcan_date.php">r_candidate date</a></li>
                <li><a href="ov_candidate">Candidate Registration</a></li>
                <li><a href="o_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </td>
    </tr>
</table>
<table align="center" bgcolor="D3D3D3" style="width:900px;border:1px solid gray;border-radius:1px;" height="500px">
    <tr valign="top">
        <td>
            <div style="clear: both"></div>
            <div id="left">
                <img src="deve/o.JPG" width="200px" height="400px" border="0">
            </div>
        </td>
        <td>
            <div id="right">
                <div class="desk">
                    <h1 align="right"></h1>
                    <?php
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
                        }
                    } elseif (!$candidate) {
                        echo '<p style="color:red;">No candidate data to display!</p>';
                    } else {
                    ?>
                    <table valign="top" align="center" style="border-radius:5px;border:1px solid #336699">
                        <tr>
                            <th colspan="2" bgcolor="#2f4f4f">
                                <font color="white" style="text-transform:uppercase;">Candidate Details</font>
                                <a href="ov_candidate.php" title="Close"><img src="img/close_icon.gif" align="right"></a>
                            </th>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">
                                <img src="<?php echo file_exists($candidate['candidate_photo']) ? htmlspecialchars($candidate['candidate_photo']) : 'img/default_photo.png'; ?>" width="100px" />
                            </td>
                        </tr>
                        <tr><td colspan="2">&nbsp;</td></tr>
                        <tr>
                            <td>First Name:</td>
                            <td><?php echo htmlspecialchars($candidate['fname']); ?></td>
                        </tr>
                        <tr>
                            <td>Middle Name:</td>
                            <td><?php echo htmlspecialchars($candidate['mname']); ?></td>
                        </tr>
                        <tr>
                            <td>Last Name:</td>
                            <td><?php echo htmlspecialchars($candidate['lname']); ?></td>
                        </tr>
                        <tr>
                            <td>Age:</td>
                            <td><?php echo htmlspecialchars($candidate['age']); ?></td>
                        </tr>
                        <tr>
                            <td>Sex:</td>
                            <td><?php echo htmlspecialchars($candidate['sex']); ?></td>
                        </tr>
                        <tr>
                            <td>Education:</td>
                            <td><?php echo htmlspecialchars($candidate['education']); ?></td>
                        </tr>
                        <!-- REMOVED JOB ROW SINCE WORK COLUMN DOESN'T EXIST -->
                        <tr>
                            <td>Phone:</td>
                            <td><?php echo htmlspecialchars($candidate['phone']); ?></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><?php echo htmlspecialchars($candidate['email']); ?></td>
                        </tr>
                        <tr>
                            <td>Experience:</td>
                            <td><?php echo htmlspecialchars($candidate['experience']); ?></td>
                        </tr>
                    </table>
                    <?php
                    }
                    ?>
                    <br><br><br><br>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#E6E6FA" align="center">
            <div id="bottom">
                <p style="text-align:center;padding-right:20px;">Copyright &copy; 2025 EC.</p>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
<?php
ob_end_flush(); // Flush output buffer
$conn->close(); // Close the database connection
?>