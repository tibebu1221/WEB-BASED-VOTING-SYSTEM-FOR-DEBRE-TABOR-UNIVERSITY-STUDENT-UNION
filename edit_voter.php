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

// Initialize variables
$voter = null;
$errors = [];
$success = "";
$ctrl = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';

// Fetch voter details only if key is provided (for initial page load)
if (!empty($ctrl)) {
    $stmt = $conn->prepare("SELECT * FROM voter WHERE vid = ?");
    $stmt->bind_param("s", $ctrl);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $voter = $result->fetch_assoc();
    } else {
        $errors[] = "Voter not found!";
    }
    $stmt->close();
} elseif (isset($_POST['update'])) {
    // When form is submitted, get voter ID from POST and fetch voter data
    $ctrl = trim($_POST['vid']);
    if (!empty($ctrl)) {
        $stmt = $conn->prepare("SELECT * FROM voter WHERE vid = ?");
        $stmt->bind_param("s", $ctrl);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $voter = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Handle form submission
if (isset($_POST['update'])) {
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $vid = trim($_POST['vid']);
    $age = (int)$_POST['age'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $sex = trim($_POST['sex']);
    $year = trim($_POST['year']);
    $department = trim($_POST['department']);
    
    // Get current password or hash new one
    $current_password = $voter ? $voter['password'] : '';
    $password = !empty(trim($_POST['password'])) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : $current_password;
    
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : ($voter ? $voter['is_active'] : 1);

    // Server-side validation
    if (empty($fname) || empty($mname) || empty($lname) || empty($vid) || empty($sex) || empty($age) || empty($year) || empty($department) || empty($phone) || empty($email)) {
        $errors[] = "All fields are required!";
    } elseif ($age < 18) {
        $errors[] = "Voter must be at least 18 years old!";
    } elseif (!preg_match('/^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})$/', $email)) {
        $errors[] = "Invalid email format!";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits!";
    } elseif ($vid !== $ctrl) {
        $errors[] = "Voter ID cannot be changed!";
    } elseif (!empty(trim($_POST['password'])) && strlen(trim($_POST['password'])) < 6) {
        $errors[] = "Password must be at least 6 characters long!";
    } else {
        // Check for duplicate phone number (excluding current voter)
        $stmt = $conn->prepare("SELECT * FROM voter WHERE phone = ? AND vid != ?");
        $stmt->bind_param("ss", $phone, $vid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Phone number is already used!";
        } else {
            // Check for duplicate email (excluding current voter)
            $stmt = $conn->prepare("SELECT * FROM voter WHERE email = ? AND vid != ?");
            $stmt->bind_param("ss", $email, $vid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Email is already used!";
            } else {
                // Update voter data with new column structure
                $stmt = $conn->prepare("UPDATE voter SET fname = ?, mname = ?, lname = ?, sex = ?, age = ?, year = ?, department = ?, phone = ?, email = ?, password = ?, is_active = ? WHERE vid = ?");
                $stmt->bind_param("ssssisssssis", $fname, $mname, $lname, $sex, $age, $year, $department, $phone, $email, $password, $is_active, $vid);
                if ($stmt->execute()) {
                    $success = "Voter updated successfully!";
                    $stmt->close();
                    header("Location: reg_voter.php");
                    ob_end_flush();
                    exit();
                } else {
                    $errors[] = "Error updating voter: " . htmlspecialchars($conn->error);
                }
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
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        function chkAlpha(event, err) {
            var charCode = (event.which) ? event.which : event.keyCode;
            if (!((charCode >= 65 && charCode <= 90) || (charCode >= 97 && charCode <= 122) || charCode === 0 || charCode === 8)) {
                document.getElementById(err).innerHTML = "Please enter letters only!";
                return false;
            }
            document.getElementById(err).innerHTML = "";
            return true;
        }

        function chkeid() {
            var e = document.getElementById("email").value;
            var atpos = e.indexOf("@");
            var dotpos = e.lastIndexOf(".");
            document.getElementById("error_email").innerHTML = (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= e.length) ? "Invalid email" : "";
        }

        function validateForm() {
            var fname = document.getElementById("fname").value;
            var mname = document.getElementById("mname").value;
            var lname = document.getElementById("lname").value;
            var age = document.getElementById("age").value;
            var year = document.getElementById("year").value;
            var department = document.getElementById("department").value;
            var phone = document.getElementById("phone").value;
            var email = document.getElementById("email").value;
            var password = document.getElementById("password").value;

            if (!fname || !mname || !lname || !age || !year || !department || !phone || !email) {
                alert("All required fields must be filled!");
                return false;
            }
            if (age < 18) {
                alert("Voter must be at least 18 years old!");
                return false;
            }
            if (!/^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})$/.test(email)) {
                alert("Invalid email format!");
                return false;
            }
            if (phone.length !== 10 || isNaN(phone)) {
                alert("Phone number must be 10 digits!");
                return false;
            }
            if (password && password.length < 6) {
                alert("Password must be at least 6 characters long!");
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
                    echo '<li class="active"><a href="reg_voter.php">Voter</a></li>';
                }
                ?>
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
<table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="500px">
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
                    } elseif (!$voter && !isset($_POST['update'])) {
                        echo '<p style="color:red;">No voter data to edit! Please go back to voter list.</p>';
                    } else {
                        // If voter data is not available (e.g., after form submission with errors), use POST values
                        $display_voter = $voter ?: [
                            'fname' => $_POST['fname'] ?? '',
                            'mname' => $_POST['mname'] ?? '',
                            'lname' => $_POST['lname'] ?? '',
                            'vid' => $_POST['vid'] ?? $ctrl,
                            'age' => $_POST['age'] ?? '',
                            'sex' => $_POST['sex'] ?? '',
                            'year' => $_POST['year'] ?? '',
                            'department' => $_POST['department'] ?? '',
                            'phone' => $_POST['phone'] ?? '',
                            'email' => $_POST['email'] ?? '',
                            'is_active' => $_POST['is_active'] ?? 1
                        ];
                    ?>
                    <form id="form1" method="POST" action="edit_voter.php<?php echo !empty($ctrl) ? '?key=' . htmlspecialchars($ctrl) : ''; ?>" onsubmit="return validateForm()">
                        <table valign="top" align="center" style="border-radius:5px;border:1px solid #336699">
                            <tr>
                                <th colspan="2" bgcolor="#2f4f4f" height="25px">
                                    <font color="white">Edit Voter Information</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="reg_voter.php" title="Close"><img src="img/close_icon.gif"></a>
                                </th>
                            </tr>
                            <tr>
                                <td><input type="hidden" name="vid" value="<?php echo htmlspecialchars($display_voter['vid']); ?>"></td>
                            </tr>
                            <tr>
                                <td>First Name:</td>
                                <td>
                                    <input type="text" name="fname" id="fname" value="<?php echo htmlspecialchars($display_voter['fname']); ?>" onkeypress="return chkAlpha(event, 'error_fname')" required>
                                    <span id="error_fname" style="color:red"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Middle Name:</td>
                                <td>
                                    <input type="text" name="mname" id="mname" value="<?php echo htmlspecialchars($display_voter['mname']); ?>" onkeypress="return chkAlpha(event, 'error_mname')" required>
                                    <span id="error_mname" style="color:red"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Last Name:</td>
                                <td>
                                    <input type="text" name="lname" id="lname" value="<?php echo htmlspecialchars($display_voter['lname']); ?>" onkeypress="return chkAlpha(event, 'error_lname')" required>
                                    <span id="error_lname" style="color:red"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Age:</td>
                                <td><input type="text" id="age" name="age" value="<?php echo htmlspecialchars($display_voter['age']); ?>" onkeypress="return isNumberKey(event)" required></td>
                            </tr>
                            <tr>
                                <td>Sex:</td>
                                <td>
                                    <input type="radio" name="sex" value="male" <?php echo ($display_voter['sex'] ?? '') == 'male' ? 'checked' : ''; ?> required>Male
                                    <input type="radio" name="sex" value="female" <?php echo ($display_voter['sex'] ?? '') == 'female' ? 'checked' : ''; ?> required>Female
                                </td>
                            </tr>
                            <tr>
                                <td>Year:</td>
                                <td>
                                    <select name="year" id="year" required style="width:145px;">
                                        <option value="">Select Year</option>
                                        <option value="First Year" <?php echo ($display_voter['year'] ?? '') == 'First Year' ? 'selected' : ''; ?>>First Year</option>
                                        <option value="Second Year" <?php echo ($display_voter['year'] ?? '') == 'Second Year' ? 'selected' : ''; ?>>Second Year</option>
                                        <option value="Third Year" <?php echo ($display_voter['year'] ?? '') == 'Third Year' ? 'selected' : ''; ?>>Third Year</option>
                                        <option value="Fourth Year" <?php echo ($display_voter['year'] ?? '') == 'Fourth Year' ? 'selected' : ''; ?>>Fourth Year</option>
                                        <option value="Fifth Year" <?php echo ($display_voter['year'] ?? '') == 'Fifth Year' ? 'selected' : ''; ?>>Fifth Year</option>
                                        <option value="Graduate" <?php echo ($display_voter['year'] ?? '') == 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Department:</td>
                                <td>
                                    <select name="department" id="department" required style="width:145px;">
                                        <option value="">Select Department</option>
                                        <option value="Computer Science" <?php echo ($display_voter['department'] ?? '') == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Information Technology" <?php echo ($display_voter['department'] ?? '') == 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Software Engineering" <?php echo ($display_voter['department'] ?? '') == 'Software Engineering' ? 'selected' : ''; ?>>Software Engineering</option>
                                        <option value="Electrical Engineering" <?php echo ($display_voter['department'] ?? '') == 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                                        <option value="Mechanical Engineering" <?php echo ($display_voter['department'] ?? '') == 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?php echo ($display_voter['department'] ?? '') == 'Civil Engineering' ? 'selected' : ''; ?>>Civil Engineering</option>
                                        <option value="Business Administration" <?php echo ($display_voter['department'] ?? '') == 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Accounting" <?php echo ($display_voter['department'] ?? '') == 'Accounting' ? 'selected' : ''; ?>>Accounting</option>
                                        <option value="Economics" <?php echo ($display_voter['department'] ?? '') == 'Economics' ? 'selected' : ''; ?>>Economics</option>
                                        <option value="Other" <?php echo ($display_voter['department'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Phone:</td>
                                <td><input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($display_voter['phone']); ?>" onkeypress="return isNumberKey(event)" maxlength="10" required></td>
                            </tr>
                            <tr>
                                <td>Email:</td>
                                <td>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($display_voter['email']); ?>" onblur="chkeid()" required>
                                    <span id="error_email" style="color:red"></span>
                                </td>
                            </tr>
                            <tr>
                                <td>Active Status:</td>
                                <td>
                                    <select name="is_active" style="width:145px;">
                                        <option value="1" <?php echo ($display_voter['is_active'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo ($display_voter['is_active'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Password:</td>
                                <td><input type="password" name="password" id="password" placeholder="Leave blank to keep unchanged"></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center"><input type="submit" name="update" value="Save Changes" class="button_example"></td>
                            </tr>
                        </table>
                    </form>
                    <?php
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
                <p style="text-align:center;padding-right:20px;">Copyright &copy; 2009 EC.</p>
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