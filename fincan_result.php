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
$stmt = $conn->prepare("SELECT fname, mname FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $user_id); // 's' for string, assuming c_id is a string
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = $row['fname'];
    $middleName = $row['mname'];
} else {
    die("Error: User not found in the database.");
}
$stmt->close(); // Close the statement for user query
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Online Voting</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <script type="text/javascript">
        function formValidation() {
            // Add validation logic if needed; currently, form has no visible input fields
            return true;
        }
    </script>
</head>
<body>
    <table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
        <tr style="height:auto;border-radius:1px;background:white url(img/tbg.png) repeat-x left top;">
            <th colspan="2">
                <a href="system_admin.php"><img src="img/logo.JPG" width="200px" height="180px" align="left" style="margin-left:10px"></a>
                <img src="img/can.png" width="400px" style="margin-left:30px;margin-top:30px" align="center">
            </th>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
                <ul>
                    <li><a href="candidate_view.php">Home</a></li>
                    <li><a href="post.php">Post News</a></li>
                    <li><a href="can_change.php">Change Password</a></li>
                    <li><a href="can_candidate.php">Candidates</a></li>
                    <li><a href="can_comment.php">Comment</a></li>
                    <li class="active"><a href="can_result.php">Result</a></li>
                    <li><a href="clogout.php">Logout</a></li>
                </ul>
            </td>
        </tr>
    </table>
    <table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="40px">
        <tr valign="top">
            <td>
                <div style="clear: both"></div>
                <div id="left">
                    <img src="deve/c.JPG" width="200px" height="400px" border="0">
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
                        <br><br><br><br>
                        <?php
                        // Fetch candidate details using prepared statement
                        $ctrl = $_GET['key'] ?? ''; // Use GET since the key is passed in the URL
                        if (empty($ctrl)) {
                            echo '<p class="error">Error: Invalid candidate ID.</p>';
                            exit();
                        }

                        // CORRECTED: Removed 'education' from the SELECT query
                        $stmt = $conn->prepare("SELECT fname, mname, lname, age, sex, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
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
                            // $r7 is removed since education column doesn't exist
                            $r8 = htmlspecialchars($row['phone']);
                            $r9 = htmlspecialchars($row['email']);
                            $r10 = htmlspecialchars($row['experience']);
                            $r13 = htmlspecialchars($row['candidate_photo']);
                            $stmt->close(); // Close the candidate query statement

                            // Fetch vote count using a new prepared statement
                            $stmt_vote = $conn->prepare("SELECT COUNT(*) as vote_count FROM result WHERE choice = ?");
                            $stmt_vote->bind_param("s", $ctrl);
                            $stmt_vote->execute();
                            $results = $stmt_vote->get_result();
                            $counts = $results->fetch_assoc()['vote_count'];
                            ?>
                            <form id="form1" method="POST" action="fincan_result.php" onsubmit="return formValidation()">
                                <table valign="top" align="center" style="border-radius:5px;border:1px solid #336699;width:400px">
                                    <tr>
                                        <th colspan="2" bgcolor="#2f4f4f">
                                            <font color="white" style="text-transform:uppercase;">
                                                Candidate Result
                                            </font>
                                            <a href="can_result.php" title="Close"><img src="img/close_icon.gif" align="right"></a>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table>
                                                <tr><td colspan="2" align="center"><img src="<?php echo file_exists($r13) ? $r13 : 'img/default_candidate.jpg'; ?>" width="200px"></td></tr>
                                                <tr><td><b>Candidate Name:</b></td><td><b><?php echo $r1 . " " . $r2 . " " . $r3; ?></b></td></tr>
                                                <tr><td><b>Age:</b></td><td><?php echo $r4; ?></td></tr>
                                                <tr><td><b>Sex:</b></td><td><?php echo $r5; ?></td></tr>
                                                <!-- Education row removed as column doesn't exist -->
                                                <!-- <tr><td><b>Education:</b></td><td><?php echo $r7; ?></td></tr> -->
                                                <tr><td><b>Experience:</b></td><td><?php echo $r10; ?></td></tr>
                                                <tr><td><b>Phone:</b></td><td><?php echo $r8; ?></td></tr>
                                                <tr><td><b>Email:</b></td><td><?php echo $r9; ?></td></tr>
                                                <tr><td colspan="2"><input type="hidden" name="results" value="<?php echo htmlspecialchars($ctrl); ?>"></td></tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <p class='success' style='margin-left:1px;'>This candidate has&nbsp;<font color='red'><?php echo $counts; ?></font>&nbsp;vote(s)</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                            <?php
                            $stmt_vote->close(); // Close the vote count query statement
                        } else {
                            echo '<p class="error">Candidate is not registered!</p>';
                        }
                        ?>
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