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

// Fetch station details
$ctrl = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
$station = null;
$errors = [];
if (!empty($ctrl)) {
    $stmt = $conn->prepare("SELECT * FROM station WHERE psid = ?");
    $stmt->bind_param("s", $ctrl);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $station = $result->fetch_assoc();
    } else {
        $errors[] = "Station not found!";
    }
    $stmt->close();
} else {
    $errors[] = "Invalid station ID!";
}

// Handle form submission
$success = "";
if (isset($_POST['update'])) {
    $psname = trim($_POST['psname']);
    $kebele = trim($_POST['kebele']);
    $city = trim($_POST['city']);
    $psid = trim($_POST['psid']);

    // Server-side validation
    if (empty($psname) || empty($kebele) || empty($city)) {
        $errors[] = "All fields are required!";
    } elseif (!preg_match('/^[A-Za-z0-9\s]{1,10}$/', $psname)) {
        $errors[] = "Station name must be alphanumeric and up to 10 characters!";
    } elseif (!preg_match('/^[A-Za-z0-9\s]{1,10}$/', $kebele)) {
        $errors[] = "Kebele must be alphanumeric and up to 10 characters!";
    } elseif ($psid !== $ctrl) {
        $errors[] = "Station ID cannot be changed!";
    } else {
        // Check for duplicate station name (excluding current station)
        $stmt = $conn->prepare("SELECT * FROM station WHERE psname = ? AND psid != ?");
        $stmt->bind_param("ss", $psname, $psid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Station name is already used!";
        } else {
            // Update station data
            $stmt = $conn->prepare("UPDATE station SET psname = ?, kebele = ?, city = ? WHERE psid = ?");
            $stmt->bind_param("ssss", $psname, $kebele, $city, $psid);
            if ($stmt->execute()) {
                $success = "Station updated successfully!";
                $stmt->close();
                header("Location: stations.php");
                ob_end_flush();
                exit();
            } else {
                $errors[] = "Error updating station: " . htmlspecialchars($conn->error);
            }
        }
        $stmt->close();
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
            var city = document.getElementById("city").value;

            if (!psname || !kebele || !city) {
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
            if (!/^[A-Za-z\s]{1,50}$/.test(city)) {
                alert("City must be letters and spaces only!");
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
        <td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
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
<table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="400px">
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
                    <h1 align="right"></h1>
                    <?php
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
                        }
                    } elseif ($success) {
                        echo '<p style="color:green;">' . htmlspecialchars($success) . '</p>';
                    } elseif (!$station) {
                        echo '<p style="color:red;">No station data to edit!</p>';
                    } else {
                    ?>
                    <form id="form1" method="POST" action="edit_station.php" onsubmit="return validateForm()">
                        <table valign="top" align="center" style="border-radius:5px;border:1px solid #336699">
                            <tr>
                                <th colspan="2" bgcolor="#2f4f4f" height="25px">
                                    <font color="white">Edit Polling Station</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="stations.php" title="Close"><img src="img/close_icon.gif"></a>
                                </th>
                            </tr>
                            <tr>
                                <td><input type="hidden" name="psid" value="<?php echo htmlspecialchars($station['psid']); ?>"></td>
                            </tr>
                            <tr>
                                <td>Station Name:</td>
                                <td><input type="text" name="psname" id="psname" value="<?php echo htmlspecialchars($station['psname']); ?>" maxlength="10" required></td>
                            </tr>
                            <tr>
                                <td>Kebele:</td>
                                <td><input type="text" name="kebele" id="kebele" value="<?php echo htmlspecialchars($station['kebele']); ?>" maxlength="10" required></td>
                            </tr>
                            <tr>
                                <td>City:</td>
                                <td><input type="text" name="city" id="city" value="<?php echo htmlspecialchars($station['city']); ?>" readonly="readonly"></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center"><input type="submit" name="update" value="Save Changes" class="button_example"></td>
                            </tr>
                        </table>
                    </form>
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