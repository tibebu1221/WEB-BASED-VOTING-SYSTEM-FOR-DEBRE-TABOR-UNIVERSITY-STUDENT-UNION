<?php
// -------------------------------------------------
//  edituser.php  –  Admin – Edit User Account
// -------------------------------------------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require_once "connection.php";          // $conn = new mysqli(...)

// ---------- 1. AUTH ----------
if (
    !isset($_SESSION['u_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    echo '<script>alert("You are not authorized!");window.location="login.php";</script>';
    exit();
}

// ---------- 2. CSRF TOKEN ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------- 3. GET USER ID ----------
$ctrl = $_GET['key'] ?? '';
if (!preg_match('/^[a-zA-Z0-9]+$/', $ctrl)) {
    echo '<script>alert("Invalid user ID.");window.location="manage_account.php";</script>';
    exit();
}

// ---------- 4. FETCH USER (no password) ----------
$stmt = $conn->prepare(
    "SELECT fname, mname, lname, u_id, sex, role, username
     FROM user WHERE u_id = ?"
);
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    $stmt->close();
    echo '<script>alert("User not found.");window.location="manage_account.php";</script>';
    exit();
}
$row = $res->fetch_assoc();
$stmt->close();

// Sanitize fetched data for display in form
$r = [
    'fname'    => htmlspecialchars($row['fname'], ENT_QUOTES, 'UTF-8'),
    'mname'    => htmlspecialchars($row['mname'], ENT_QUOTES, 'UTF-8'),
    'lname'    => htmlspecialchars($row['lname'], ENT_QUOTES, 'UTF-8'),
    'u_id'     => htmlspecialchars($row['u_id'], ENT_QUOTES, 'UTF-8'),
    'sex'      => htmlspecialchars($row['sex'], ENT_QUOTES, 'UTF-8'),
    'role'     => htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'),
    'username' => htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
];

// ---------- 5. PROCESS FORM ----------
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid CSRF token.</div>';
    } else {

        // Collect and sanitize POST data
        $fname    = trim($_POST['fname'] ?? '');
        $mname    = trim($_POST['mname'] ?? '');
        $lname    = trim($_POST['lname'] ?? '');
        $us_id    = $_POST['us_id'] ?? ''; // hidden input: must match $ctrl
        $sex      = $_POST['sex'] ?? '';
        $role     = $_POST['role'] ?? '';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // ---- validation ----
        $errors = [];

        if ($us_id !== $ctrl) {
            $errors[] = 'User ID cannot be changed.';
        }
        if (empty($fname) || empty($mname) || empty($lname) || empty($sex) || empty($role) || empty($username)) {
            $errors[] = 'All fields except password are required.';
        }
        if (!preg_match('/^[A-Za-z\s]+$/', $fname) ||
            !preg_match('/^[A-Za-z\s]+$/', $mname) ||
            !preg_match('/^[A-Za-z\s]+$/', $lname)) {
            $errors[] = 'Names may contain only letters and spaces.';
        }
        if (!in_array($sex, ['M','F','Other'], true)) {
            $errors[] = 'Sex must be M, F or Other.';
        }
        if (!in_array($role, ['admin','officer','department'], true)) {
            $errors[] = 'Role must be admin , department or officer.';
        }
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be 3–50 characters.';
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'Password (if set) must be at least 6 characters.';
        }

        if ($errors) {
            $msg = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
        } else {
            // ---- username uniqueness (exclude current user) ----
            $stmt = $conn->prepare(
                "SELECT u_id FROM user WHERE username = ? AND u_id != ?"
            );
            $stmt->bind_param("ss", $username, $ctrl);
            $stmt->execute();
            $uniq = $stmt->get_result();
            $stmt->close();

            if ($uniq->num_rows > 0) {
                $msg = '<div class="alert alert-danger">Username already taken.</div>';
            } else {
                // ---- build UPDATE ----
                $sql    = "UPDATE user SET fname=?, mname=?, lname=?, sex=?, role=?, username=?";
                $types = "ssssss";
                $params = [$fname, $mname, $lname, $sex, $role, $username];

                if ($password !== '') {
                    $sql    .= ", password=?";
                    $types .= "s";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE u_id=?";
                $types .= "s";
                $params[] = $ctrl;

                $stmt = $conn->prepare($sql);
                // Bind parameters dynamically
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    echo '<script>alert("User updated successfully!");window.location="manage_account.php";</script>';
                    exit();
                } else {
                    $msg = '<div class="alert alert-danger">DB error: ' .
                            htmlspecialchars($stmt->error) . '</div>';
                }
                $stmt->close();
            }
        }
    }
    
    // Update the $r array with submitted data on error so form fields don't reset
    if ($errors || (isset($uniq) && $uniq->num_rows > 0)) {
        $r['fname'] = htmlspecialchars($fname, ENT_QUOTES, 'UTF-8');
        $r['mname'] = htmlspecialchars($mname, ENT_QUOTES, 'UTF-8');
        $r['lname'] = htmlspecialchars($lname, ENT_QUOTES, 'UTF-8');
        $r['sex'] = htmlspecialchars($sex, ENT_QUOTES, 'UTF-8');
        $r['role'] = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
        $r['username'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User – Online Voting</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="img/ethio_flag.JPG">
<link rel="stylesheet" href="main.css">
<link rel="stylesheet" href="menu.css">
<link rel="stylesheet"
       href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* --- FULL SCREEN & MODERN CSS ADJUSTMENTS --- */
    body {
        background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
        min-height: 100vh;
        display: flex;
        flex-direction: column; /* Use column layout for full vertical space */
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        width: 100%;
        max-width: none; /* REMOVED max-width: 900px */
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0; /* No border radius for edge-to-edge */
        box-shadow: none;
        overflow: hidden;
        flex-grow: 1;
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
    }
    nav ul {
        display: flex;
        list-style: none;
        justify-content: center;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
    }
    nav ul li a {
        color: #fff;
        text-decoration: none;
        padding: 15px 20px;
        display: block;
        font-weight: 500;
        transition: 0.3s;
        border-bottom: 3px solid transparent;
    }
    nav ul li a:hover, nav ul li a.active {
        background: rgba(255, 255, 255, 0.1);
        border-bottom: 3px solid #ffcc00;
    }

    .content-wrapper {
        display: flex;
        flex-grow: 1;
        min-height: calc(100vh - 220px); /* Adjust height for content area */
    }
    .sidebar {
        width: 250px; /* Slightly wider sidebar */
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
    
    /* --- Form Styling --- */
    .edit-form {
        max-width: 500px;
        width: 100%;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 30px; /* Increased padding */
    }
    .edit-form table {
        width: 100%;
        border-collapse: collapse;
    }
    .edit-form th {
        background: #1a2a6c; /* Match header color */
        color: #fff;
        padding: 15px; /* Increased padding */
        font-size: 18px;
        border-radius: 8px 8px 0 0;
    }
    .edit-form td {
        padding: 10px 0;
    }
    .edit-form tr td:first-child {
        width: 35%;
        font-weight: 500;
        color: #333;
    }

    .edit-form input[type=text],
    .edit-form input[type=password],
    .edit-form select {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
    }
    .edit-form input[readonly] {
        background: #f0f4f8;
        color: #666;
        cursor: not-allowed;
    }

    .edit-form input[type=submit] {
        width: 100%;
        background: linear-gradient(to right, #b21f1f, #c62828); /* Red/maroon save button */
        color: #fff;
        padding: 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        margin-top: 15px;
        transition: all 0.3s;
    }
    .edit-form input[type=submit]:hover {
        background: linear-gradient(to right, #c62828, #b21f1f);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        width: 100%;
        max-width: 500px;
    }
    .alert-danger {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }

    footer {
        background: #1a2a6c;
        color: #fff;
        text-align: center;
        padding: 20px;
        font-size: 14px;
        flex-shrink: 0;
    }
    
    /* --- Responsive Tweaks --- */
    @media (max-width: 900px) {
        .content-wrapper {
            flex-direction: column;
        }
        .sidebar {
            width: 100%;
            height: 150px;
            border-right: none;
            border-bottom: 1px solid #e0e6ed;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar img {
            max-width: 150px;
            height: auto;
        }
        .main-content {
            padding: 15px;
        }
    }
    @media (max-width: 600px) {
        nav ul {
            flex-direction: column;
        }
        nav ul li a {
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,.1);
            padding: 10px 20px;
        }
        .edit-form tr td:first-child {
            width: 100%;
            display: block;
            text-align: left;
            padding-top: 5px;
        }
        .edit-form td {
            display: block;
            padding: 5px 0 10px;
        }
        .edit-form th {
            font-size: 16px;
        }
    }
</style>
<script>
function formValidation(){
    const f=document.getElementById('fname').value.trim(),
          m=document.getElementById('mname').value.trim(),
          l=document.getElementById('lname').value.trim(),
          s=document.getElementById('sex').value,
          r=document.getElementById('role').value,
          u=document.getElementById('username').value.trim(),
          p=document.getElementById('password').value;
    const nameRe=/^[A-Za-z\s]+$/;
    
    if(!f||!m||!l||!s||!r||!u) {
        alert('All fields except password are required.');
        return false;
    }
    if(!nameRe.test(f)||!nameRe.test(m)||!nameRe.test(l)) {
        alert('Names: letters and spaces only.');
        return false;
    }
    if(!['M','F','Other'].includes(s)) {
        alert('Sex: M, F or Other.');
        return false;
    }
    if(!['admin','officer', 'department'].includes(r)) {
        alert('Role: admin, department or officer.');
        return false;
    }
    if(u.length<3||u.length>50) {
        alert('Username must be 3 to 50 characters.');
        return false;
    }
    if(p && p.length<6) {
        alert('Password (if set) must be at least 6 characters.');
        return false;
    }
    return true;
}
</script>
</head>
<body>
<div class="container">
    <div class="header"><a href="system_admin.php"><img src="img/logo.jpg" alt="Logo"></a></div>

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
        <div class="sidebar"><img src="deve/A.JPG" alt="Sidebar Image"></div>

        <div class="main-content">
            <?php echo $msg; ?>

            <div class="edit-form">
                <form method="POST" action="edituser.php?key=<?php echo urlencode($ctrl); ?>"
                      onsubmit="return formValidation()">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="us_id" value="<?php echo $r['u_id']; ?>">

                    <table>
                        <tr><th colspan="2">
                            <i class="fas fa-user-edit"></i> Edit User Account
                             &nbsp;
                            <a href="manage_account.php" title="Back to Manage Accounts">
                                <img src="img/close_icon.gif" alt="Close" width="24" height="24" style="vertical-align: middle;">
                            </a>
                        </th></tr>

                        <tr><td>First Name:</td><td><input type="text" name="fname" id="fname" value="<?php echo $r['fname']; ?>" required></td></tr>
                        <tr><td>Middle Name:</td><td><input type="text" name="mname" id="mname" value="<?php echo $r['mname']; ?>" required></td></tr>
                        <tr><td>Last Name:</td><td><input type="text" name="lname" id="lname" value="<?php echo $r['lname']; ?>" required></td></tr>

                        <tr><td>User ID:</td><td><input type="text" readonly value="<?php echo $r['u_id']; ?>" title="User ID cannot be changed."></td></tr>

                        <tr><td>Sex:</td>
                            <td><select name="sex" id="sex" required>
                                <option value="M"    <?php if($r['sex']==='M') echo 'selected'; ?>>Male</option>
                                <option value="F"    <?php if($r['sex']==='F') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if($r['sex']==='Other') echo 'selected'; ?>>Other</option>
                            </select></td></tr>

                        <tr><td>Role:</td>
                            <td><select name="role" id="role" required>
                                <option value="admin"    <?php if($r['role']==='admin') echo 'selected'; ?>>Admin</option>
                                <option value="officer" <?php if($r['role']==='officer') echo 'selected'; ?>>Officer</option>
                                <option value="department" <?php if($r['role']==='department') echo 'selected'; ?>>Department</option>
                            </select></td></tr>

                        <tr><td>Username:</td><td><input type="text" name="username" id="username" value="<?php echo $r['username']; ?>" required></td></tr>
                        <tr><td>Password:</td><td><input type="password" name="password" id="password" placeholder="New password (leave blank to keep current)"></td></tr>

                        <tr><td colspan="2" style="text-align:center;">
                            <input type="submit" name="update" value="Save Changes">
                        </td></tr>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <footer><p>Copyright &copy; <?php echo date("Y"); ?> EC. | Secure Online Voting System</p></footer>
</div>
</body>
</html>
<?php
$conn->close();
?>