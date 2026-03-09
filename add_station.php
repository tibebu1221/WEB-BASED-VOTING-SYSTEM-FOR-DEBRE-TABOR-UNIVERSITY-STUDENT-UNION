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
$stmt = $conn->prepare("SELECT * FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$FirstName = $row['fname'];
$middleName = $row['mname'];
$stmt->close();

// Handle form submission
$errors = [];
$success = "";
if (isset($_POST['ok'])) {
    $psname = trim($_POST['psname']);
    $kebele = trim($_POST['kebele']);
    $city = trim($_POST['city']);

    // Server-side validation
    if (empty($psname) || empty($kebele) || empty($city)) {
        $errors[] = "All fields are required!";
    } elseif (!preg_match('/^[A-Za-z0-9\s]{1,10}$/', $psname)) {
        $errors[] = "Station name must be alphanumeric and up to 10 characters!";
    } elseif (!preg_match('/^[A-Za-z0-9\s]{1,10}$/', $kebele)) {
        $errors[] = "Kebele must be alphanumeric and up to 10 characters!";
    } else {
        // Check for duplicate station name
        $stmt = $conn->prepare("SELECT * FROM station WHERE psname = ?");
        $stmt->bind_param("s", $psname);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Station name is already used!";
            $stmt->close();
        } else {
            $stmt->close();
            // Insert station data
            $stmt = $conn->prepare("INSERT INTO station (u_id, psname, kebele, city) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $psname, $kebele, $city);
            if ($stmt->execute()) {
                $success = "Station registered successfully!";
                $stmt->close();
                header("Location: stations.php");
                ob_end_flush();
                exit();
            } else {
                $errors[] = "Error registering station: " . htmlspecialchars($conn->error);
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Voting</title>
    <link rel="icon" type="image/jpg" href="img/flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <script type="text/javascript">
        function validateForm() {
            var psname = document.getElementById("psname").value;
            var kebele = document.getElementById("kebele").value;

            if (!psname || !kebele) {
                alert("All fields are required!");
                return false;
            }
            if (!/^[A-Za-z0-9\s]{1,10}$/.test(psname)) {
                alert("Station name must be alphanumeric and up to 10 characters!");
                return false;
            }
            if (!/^[A-Za-z0-9\s]{1,10}$/.test(kebele)) {
                alert("Kebele must be alphanumeric and up to 10 characters!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
    <tr style="height:auto;border-radius:1px;background:white url(img/tbg.png) repeat-x left top;">
        <th colspan="2">
            <a href="e_officer.php"><img src="img/logo.jpg" width="200px" height="180px" align="left" style="margin-left:10px"></a>
            <img src="img/officer.png" width="450px" style="margin-left:30px;margin-top:40px" align="center">
        </th>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#2F4F4F" id="Menus" style="height:auto;border-radius:1px;">
            <ul>
                <li><a href="e_officer.php">Home</a></li>
                <li><a href="o_result.php">Result</a></li>
                <li><a href="o_generate.php">Generate Report</a></li>
                <li><a href="regdate.php">r_vote date</a></li>
                <li><a href="regcan_date.php">r_candidate date</a></li>
                <?php
                $resultDate = $conn->query("SELECT * FROM voter_reg_date");
                $dateRes = $resultDate->fetch_assoc();
                $startDate = $dateRes['start'];
                $endDate = $dateRes['end'];
                $current = date("Y-m-d");
                if ($current >= $startDate && $current <= $endDate) {
                    echo '<li><a href="reg_voter.php">Voter</a></li>';
                }
                ?>
                <li class="active"><a href="stations.php">Stations</a></li>
                <?php
                $resultDate = $conn->query("SELECT * FROM candidate_reg_date");
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
        </td>
    </tr>
</table>
<table align="center" bgcolor="D3D3D3" style="width:900px;border:1px solid gray;border-radius:1px;" height="400px">
    <tr valign="top">
        <td>
            <div style="clear: both"></div>
            <div id="left">
                <img src="deve/o.png" width="200px" height="400px" border="0">
            </div>
        </td>
        <td>
            <div id="right">
                <div class="desk">
                    <?php
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
                        }
                    }
                    if ($success) {
                        echo '<p style="color:green;">' . htmlspecialchars($success) . '</p>';
                    }
                    ?>
                    <table class="log_table" align="center">
                        <form action="add_station.php" method="post" onsubmit="return validateForm()">
                            <tr bgcolor="#2F4F4F">
                                <th colspan="4">
                                    <font color="#ffffff" size="5">Add new Polling station</font>
                                    <a href="stations.php"><img align="right" src="img/close_icon.gif" title="close"></a>
                                </th>
                            </tr>
                            <tr><td><br></td></tr>
                            <tr>
                                <td><label>Station Name</label></td>
                                <td><input type="text" name="psname" id="psname" maxlength="10" required/></td>
                            </tr>
                            <tr>
                                <td><label>Kebele</label></td>
                                <td><input type="text" name="kebele" id="kebele" maxlength="10" required/></td>
                            </tr>
                            <tr>
                                <td><label>City</label></td>
                                <td><input type="text" value="DT" name="city" readonly="readonly"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <input type="submit" name="ok" value="Save" class="button_example"/>
                                    <input type="reset" value="Reset" class="button_example"/>
                                </td>
                            </tr>
                            <tr><td><br></td></tr>
                        </form>
                    </table>
                    <br><br><br><br>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#E6E6FA" align="center">
            <div id="bottom">
                <p style="text-align:center;padding-right:20px;">Copyright &copy; 2017 EC.</p>
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