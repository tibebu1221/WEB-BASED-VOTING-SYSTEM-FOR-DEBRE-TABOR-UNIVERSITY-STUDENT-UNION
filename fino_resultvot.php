<?php
include("connection.php"); // Ensure this file sets up a MySQLi connection
session_start();

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
$stmt = $conn->prepare("SELECT fname, mname FROM voter WHERE vid = ?");
$stmt->bind_param("s", $user_id); // 's' for string, assuming vid is a string
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
} else {
    die("Error: User not found in the database.");
}
$stmt->close();

// Fetch candidate details
$ctrl = isset($_GET['key']) ? $_GET['key'] : '';
if (empty($ctrl)) {
    echo '<p class="wrong">Error: No candidate selected!</p>';
    echo '<meta content="5;v_result.php" http-equiv="refresh"/>';
    exit();
}

// CHANGED: Removed 'work' from the SELECT query
$stmt = $conn->prepare("SELECT fname, mname, lname, age, sex, education, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $r1 = htmlspecialchars($row['fname']);
    $r2 = htmlspecialchars($row['mname']);
    $r3 = htmlspecialchars($row['lname']);
    $r4 = htmlspecialchars($row['age']);
    $r5 = htmlspecialchars($row['sex']);
    $r7 = htmlspecialchars($row['education']);
    $r8 = htmlspecialchars($row['phone']);
    $r9 = htmlspecialchars($row['email']);
    $r10 = htmlspecialchars($row['experience']);
    $r13 = htmlspecialchars($row['candidate_photo']);
} else {
    echo '<p class="wrong">Candidate not found!</p>';
    echo '<meta content="5;v_result.php" http-equiv="refresh"/>';
    $stmt->close();
    exit();
}
$stmt->close();

// Fetch vote count
$stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM result WHERE choice = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc()['vote_count'];
$stmt->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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
                <a href="voter.php"><img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px"></a>
                <img src="img/voter.png" width="400px" style="margin-left:30px;margin-top:40px" align="center">
            </th>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
                <ul>
                    <li><a href="voter.php">Home</a></li>
                    <li><a href="v_change.php">Change Password</a></li>
                    <li><a href="cast.php">Cast Vote</a></li>
                    <li><a href="voter_candidate.php">Candidates</a></li>
                    <li><a href="voter_comment.php">Comment</a></li>
                    <li class="active"><a href="v_result.php">Result</a></li>
                    <li><a href="vlogout.php">Logout</a></li>
                </ul>
            </td>
        </tr>
    </table>
    <table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="500px">
        <tr valign="top">
            <td>
                <div style="clear: both"></div>
                <div id="left">
                    <img src="deve/v.png" width="200px" height="400px" border="0">
                </div>
            </td>
            <td>
                <div id="right">
                    <div class="desk">
                        <h1 align="right">
                            <?php
                            echo '<img src="img/people.png" width="40px" height="30px">&nbsp;';
                            echo '<font style="text-transform:capitalize;" face="times new roman" color="black" size="3">Hi,&nbsp;' . htmlspecialchars($FirstName) . "&nbsp;" . htmlspecialchars($middleName) . '</font>';
                            ?>
                        </h1>
                        <br><br>
                        <br><br>
                        <table valign="top" align="center" style="border-radius:5px;border:1px solid #336699;width:400px">
                            <tr>
                                <th bgcolor="#2f4f4f">
                                    <font color="white" style="text-transform:uppercase;">Candidate Details</font>
                                    <a href="v_result.php" title="Close"><img src="img/close_icon.gif" align="right"></a>
                                </th>
                            </tr>
                            <tr>
                                <td>
                                    <table>
                                        <tr>
                                            <td align="center"><img src="<?php echo file_exists($r13) ? $r13 : 'img/default_candidate.jpg'; ?>" width="200px"></td>
                                        </tr>
                                        <tr>
                                            <td><b>Candidate:</b></td>
                                            <td><?php echo $r1 . "&nbsp;" . $r2 . "&nbsp;" . $r3; ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Age:</b></td>
                                            <td><?php echo $r4; ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Sex:</b></td>
                                            <td><?php echo $r5; ?></td>
                                        </tr>
                                        <!-- REMOVED WORK ROW SINCE WORK COLUMN DOESN'T EXIST -->
                                        <tr>
                                            <td><b>Education:</b></td>
                                            <td><?php echo $r7; ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Phone:</b></td>
                                            <td><?php echo $r8; ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Email:</b></td>
                                            <td><?php echo $r9; ?></td>
                                        </tr>
                                        <tr>
                                            <td><b>Experience:</b></td>
                                            <td><?php echo $r10; ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <p class="success" style="margin-left:-1px;">
                                                    This candidate has&nbsp;<font color="red"><?php echo $counts; ?></font>&nbsp;vote(s)
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <br><br>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#E6E6FA" align="center">
                <div id="bottom">
                    <p style="text-align:center;padding-right:20px;">Copyright &copy; <?php echo date("Y"); ?> EC.</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
<?php
$conn->close(); // Close the MySQLi connection
?>