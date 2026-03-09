<?php
session_start();
include("connection.php"); // Ensure this uses MySQLi connection

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as an anmin.');
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
    $middleName = htmlspecialchars($row['mname'] ?? ''); // Use null coalescing for safety
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Handle form submission
if (isset($_POST['ok'])) {
    $fname = $_POST['fname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $age = $_POST['age'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $utype = $_POST['utype'] ?? '';
    $user = $_POST['user'] ?? '';
    // $station = $_POST['station'] ?? ''; // REMOVED STATION
    $pass = $_POST['pass'] ?? '';
    $cpass = $_POST['cpass'] ?? '';
    
    // Set a default/empty value for station for the database, if the column is NOT NULL, you might need a placeholder.
    // Assuming the 'station' column can handle NULL or an empty string.
    $station = "N/A"; 

    // Server-side validation
    // Removed $station from the required check
    if (empty($fname) || empty($mname) || empty($lname) || empty($user_id) || empty($sex) || empty($age) || empty($phone) || empty($email) || empty($utype) || empty($user) || empty($pass) || empty($cpass)) {
        echo '<div class="alert alert-danger">All fields are required.</div>';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fname) || !preg_match('/^[a-zA-Z\s]+$/', $mname) || !preg_match('/^[a-zA-Z\s]+$/', $lname)) {
        echo '<div class="alert alert-danger">Names must contain only letters and spaces.</div>';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $user_id)) {
        echo '<div class="alert alert-danger">User ID must be alphanumeric.</div>';
    } elseif (!in_array($sex, ['male', 'female'])) {
        echo '<div class="alert alert-danger">Sex must be male or female.</div>';
    } elseif (!filter_var($age, FILTER_VALIDATE_INT) || $age < 18 || $age > 120) {
        echo '<div class="alert alert-danger">Age must be a number between 18 and 120.</div>';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        echo '<div class="alert alert-danger">Phone number must be exactly 10 digits.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<div class="alert alert-danger">Invalid email format.</div>';
    } elseif (!in_array($utype, ['admin', 'officer', 'discipline_committee', 'department'])) {
        echo '<div class="alert alert-danger">User type must be admin, discipline_committee, department or officer.</div>';
    } elseif (strlen($user) < 3 || strlen($user) > 50) {
        echo '<div class="alert alert-danger">Username must be between 3 and 50 characters.</div>';
    } elseif (strlen($pass) < 6) {
        echo '<div class="alert alert-danger">Password must be at least 6 characters.</div>';
    } elseif ($pass !== $cpass) {
        echo '<div class="alert alert-danger">Passwords do not match.</div>';
    } else {
        // Check for duplicate user_id or phone
        $stmt = $conn->prepare("SELECT u_id FROM user WHERE u_id = ? OR phone = ?");
        $stmt->bind_param("ss", $user_id, $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['u_id'] === $user_id) {
                echo '<div class="alert alert-danger">User ID is already used.</div>';
            } else {
                echo '<div class="alert alert-danger">Phone number is already used.</div>';
            }
            $stmt->close();
        } else {
            $stmt->close();
            // Insert new user
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            // Updated SQL: removed 'station' column from the VALUES list
            $stmt = $conn->prepare("INSERT INTO user (fname, mname, lname, u_id, sex, age, phone, email, role, username, station, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssssissssss", $fname, $mname, $lname, $user_id, $sex, $age, $phone, $email, $utype, $user, $station, $hashed_pass);
            
            if ($stmt->execute()) {
                echo '<div class="alert alert-success">Account created successfully!</div>';
                // Redirect back to create.php (or manage_account.php)
                echo '<script>setTimeout(() => { window.location = "create.php"; }, 3000);</script>'; 
            } else {
                echo '<div class="alert alert-danger">Error creating account: ' . htmlspecialchars($conn->error) . '</div>';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Create User</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- FULL SCREEN BASE LAYOUT --- */
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Changed to column to handle full-width header/footer */
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            width: 100%;
            max-width: none; /* Make it full width */
            background: rgba(255, 255, 255, 0.95);
            border-radius: 0; /* Remove border radius for full-screen look */
            box-shadow: none;
            overflow: hidden;
            flex-grow: 1; /* Allow content to fill vertical space */
        }
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
            margin: 0;
            padding: 0;
        }
        nav ul li {
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
        .content-wrapper {
            display: flex;
            flex-grow: 1; /* Allows content area to fill vertical space */
            min-height: 500px;
        }
        .sidebar {
            width: 250px; /* Adjusted width */
            flex-shrink: 0;
            background: #f0f4f8;
            padding: 25px;
            border-right: 1px solid #e0e6ed;
            text-align: center; /* Center image in sidebar */
        }
        .sidebar img {
             width: 100%;
             max-width: 200px; /* Better control over image size */
             height: auto;
             border-radius: 8px;
             object-fit: contain;
        }
        .main-content {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #fff; /* Ensure white background for content area */
        }
        /* --- FORM STYLING --- */
        .create-form {
            width: 100%;
            max-width: 600px; /* Increased max width for better form presentation */
            background: #f9f9f9; /* Light background for the form container */
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid #eee;
        }
        .create-form table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px; /* Add vertical spacing between rows */
        }
        .create-form th {
            background-color: #1a2a6c; /* Dark blue header */
            color: white;
            padding: 15px;
            font-size: 18px;
            border-radius: 8px 8px 0 0;
        }
        .create-form td {
            padding: 10px 0;
            vertical-align: top;
            font-weight: 500;
        }
        .create-form input[type="text"],
        .create-form input[type="password"],
        .create-form input[type="email"],
        .create-form select {
            width: 95%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .create-form input[type="text"]:focus,
        .create-form input[type="password"]:focus,
        .create-form input[type="email"]:focus,
        .create-form select:focus {
            border-color: #1a2a6c;
            outline: none;
        }
        .create-form input[type="radio"] {
            margin: 0 10px;
            transform: scale(1.2);
            cursor: pointer;
        }
        .create-form input[type="submit"],
        .create-form input[type="reset"] {
            background: linear-gradient(to right, #1a2a6c, #2c3e50);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 10px;
            transition: background 0.3s, transform 0.2s;
        }
        .create-form input[type="submit"]:hover,
        .create-form input[type="reset"]:hover {
            background: linear-gradient(to right, #2c3e50, #1a2a6c);
            transform: translateY(-1px);
        }
        .close-link img {
            width: 24px;
            height: 24px;
            vertical-align: middle;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
            text-align: center;
            width: 100%;
            max-width: 600px;
        }
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .error {
            color: #d32f2f;
            font-size: 12px;
            padding-left: 10px;
            width: 30%; /* Gives error column space */
        }
        footer {
            background: #1a2a6c;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            flex-shrink: 0;
            width: 100%;
        }
        /* --- Responsive Adjustments --- */
        @media (max-width: 900px) {
            .content-wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #e0e6ed;
            }
            .main-content {
                padding: 15px;
            }
            .create-form {
                padding: 15px;
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
            .create-form table {
                font-size: 14px;
            }
            .create-form th {
                font-size: 16px;
            }
            .create-form td:first-child {
                width: 35%; /* Adjust label width */
            }
        }
    </style>
    <script>
        function validateForm() {
            const fname = document.getElementById('fn').value.trim();
            const mname = document.getElementById('mn').value.trim();
            const lname = document.getElementById('ln').value.trim();
            const user_id = document.getElementById('user_id').value.trim();
            const sex = document.querySelector('input[name="sex"]:checked');
            const age = document.getElementById('age').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('email').value.trim();
            const utype = document.getElementById('utype').value;
            const user = document.getElementById('user').value.trim();
            // const station = document.getElementById('station').value.trim(); // REMOVED STATION
            const pass = document.getElementById('pass').value;
            const cpass = document.getElementById('cpass').value;

            const nameRegex = /^[a-zA-Z\s]+$/;
            const userIdRegex = /^[a-zA-Z0-9]+$/;
            const phoneRegex = /^\d{10}$/;
            // Updated email regex for better client-side validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; 
            const errors = document.querySelectorAll('.error');

            // Clear previous errors
            errors.forEach(error => error.textContent = '');

            let isValid = true;

            // Removed 'station' from the check below
            if (!fname || !mname || !lname || !user_id || !sex || !age || !phone || !email || !utype || !user || !pass || !cpass) {
                alert('All fields are required.');
                isValid = false;
            } else {
                if (!nameRegex.test(fname)) {
                    document.getElementById('error12').textContent = 'First name must contain only letters and spaces.';
                    isValid = false;
                }
                if (!nameRegex.test(mname)) {
                    document.getElementById('error11').textContent = 'Middle name must contain only letters and spaces.';
                    isValid = false;
                }
                if (!nameRegex.test(lname)) {
                    document.getElementById('error10').textContent = 'Last name must contain only letters and spaces.';
                    isValid = false;
                }
                if (!userIdRegex.test(user_id)) {
                    document.getElementById('error_user_id').textContent = 'User ID must be alphanumeric.';
                    isValid = false;
                }
                if (!sex || !['male', 'female'].includes(sex.value)) {
                    // Check if sex is selected and valid
                    document.getElementById('error_sex').textContent = 'Please select male or female.';
                    isValid = false;
                }
                if (!/^\d+$/.test(age) || parseInt(age) < 18 || parseInt(age) > 120) {
                    document.getElementById('error_age').textContent = 'Age must be a number between 18 and 120.';
                    isValid = false;
                }
                if (!phoneRegex.test(phone)) {
                    document.getElementById('error_phone').textContent = 'Phone number must be exactly 10 digits.';
                    isValid = false;
                }
                if (!emailRegex.test(email)) {
                    document.getElementById('error_email').textContent = 'Invalid email format.';
                    isValid = false;
                }
                if (!['admin', 'officer', 'discipline_committee', 'department'].includes(utype)) {
                    document.getElementById('error_utype').textContent = 'Please select a valid user type.';
                    isValid = false;
                }
                if (user.length < 3 || user.length > 50) {
                    document.getElementById('error_user').textContent = 'Username must be between 3 and 50 characters.';
                    isValid = false;
                }
                
                // REMOVED STATION VALIDATION
                
                if (pass.length < 6) {
                    document.getElementById('error_pass').textContent = 'Password must be at least 6 characters.';
                    isValid = false;
                }
                if (pass !== cpass) {
                    document.getElementById('error_cpass').textContent = 'Passwords do not match.';
                    isValid = false;
                }
            }

            return isValid;
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
                <li><a class="active" href="manage_account.php">Manage Account</a></li>
                <li><a href="a_generate.php">Generate Report</a></li>
                <li><a href="a_candidate.php">Candidates</a></li>
                <li><a href="voters.php">Voters</a></li>
                <li><a href="adminv_result.php">Result</a></li>
                <li><a href="setDate.php">Set Date</a></li>
                <li><a href="v_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <img src="deve/a.JPG" alt="Admin Sidebar Image">
            </div>
            <div class="main-content">
                <div class="create-form">
                    <form action="create.php" method="post" onsubmit="return validateForm()">
                        <table>
                            <tr>
                                <th colspan="3">
                                    Create User Account &nbsp;&nbsp;
                                    <a href="manage_account.php" title="Close" class="close-link">
                                        <img src="img/close_icon.gif" alt="Close">
                                    </a>
                                </th>
                            </tr>
                            <tr>
                                <td>First Name:</td>
                                <td><input type="text" name="fname" id="fn" placeholder="First Name" required value="<?php echo htmlspecialchars($_POST['fname'] ?? ''); ?>"></td>
                                <td class="error" id="error12"></td>
                            </tr>
                            <tr>
                                <td>Middle Name:</td>
                                <td><input type="text" name="mname" id="mn" placeholder="Middle Name" required value="<?php echo htmlspecialchars($_POST['mname'] ?? ''); ?>"></td>
                                <td class="error" id="error11"></td>
                            </tr>
                            <tr>
                                <td>Last Name:</td>
                                <td><input type="text" name="lname" id="ln" placeholder="Last Name" required value="<?php echo htmlspecialchars($_POST['lname'] ?? ''); ?>"></td>
                                <td class="error" id="error10"></td>
                            </tr>
                            <tr>
                                <td>User ID:</td>
                                <td><input type="text" name="user_id" id="user_id" placeholder="User ID" required value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>"></td>
                                <td class="error" id="error_user_id"></td>
                            </tr>
                            <tr>
                                <td>Sex:</td>
                                <td>
                                    <input type="radio" name="sex" value="male" id="sex_m" <?php echo (($_POST['sex'] ?? '') === 'male') ? 'checked' : ''; ?> required> <label for="sex_m">Male</label>
                                    <input type="radio" name="sex" value="female" id="sex_f" <?php echo (($_POST['sex'] ?? '') === 'female') ? 'checked' : ''; ?>> <label for="sex_f">Female</label>
                                </td>
                                <td class="error" id="error_sex"></td>
                            </tr>
                            <tr>
                                <td>Age:</td>
                                <td><input type="text" name="age" id="age" maxlength="3" placeholder="Age" required value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>"></td>
                                <td class="error" id="error_age"></td>
                            </tr>
                            <tr>
                                <td>Phone No:</td>
                                <td><input type="text" name="phone" id="phone" maxlength="10" placeholder="Phone Number" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"></td>
                                <td class="error" id="error_phone"></td>
                            </tr>
                            <tr>
                                <td>Email:</td>
                                <td><input type="email" name="email" id="email" placeholder="example@gmail.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"></td>
                                <td class="error" id="error_email"></td>
                            </tr>
                            <tr>
                                <td>User Type:</td>
                                <td>
                                    <?php $current_utype = $_POST['utype'] ?? ''; ?>
                                    <select name="utype" id="utype" required>
                                         <option value="" disabled selected hidden>Select User Role</option> 
                                        <option value="admin" <?php echo ($current_utype === 'admin') ? 'selected' : ''; ?>>System Admin</option>
                                        <option value="officer" <?php echo ($current_utype === 'officer') ? 'selected' : ''; ?>>Registrar Office</option>
                                        <option value="discipline_committee" <?php echo ($current_utype === 'discipline_committee') ? 'selected' : ''; ?>> Discipline Committee</option>
                                        <option value="department" <?php echo ($current_utype === 'department') ? 'selected' : ''; ?>>Department</option>
                                    </select>
                                </td>
                                <td class="error" id="error_utype"></td>
                            </tr>
                            <tr>
                                <td>Username:</td>
                                <td><input type="text" name="user" id="user" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['user'] ?? ''); ?>"></td>
                                <td class="error" id="error_user"></td>
                            </tr>
                            
                            <tr>
                                <td>Password:</td>
                                <td><input type="password" name="pass" id="pass" placeholder="Password (Min 6 chars)" required></td>
                                <td class="error" id="error_pass"></td>
                            </tr>
                            <tr>
                                <td>Confirm Password:</td>
                                <td><input type="password" name="cpass" id="cpass" placeholder="Confirm Password" required></td>
                                <td class="error" id="error_cpass"></td>
                            </tr>
                            <tr>
                                <td colspan="3" align="center">
                                    <input type="submit" name="ok" value="Save">
                                    <input type="reset" value="Reset">
                                </td>
                            </tr>
                        </table>
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