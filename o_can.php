<?php
ob_start();
include("connection.php");
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

// Handle form submission
$errors = [];
$success = "";
$candidate_validated = false;
$candidate_data = null;

// Check candidate ID validation first
if (isset($_POST['validate_candidate'])) {
    $candidate_id = trim($_POST['candidate_id']);
    
    if (empty($candidate_id)) {
        $errors[] = "Please enter a Candidate ID for validation";
    } else {
        // FIRST: Check if candidate ID is already registered in candidate table
        $check_stmt = $conn->prepare("SELECT * FROM candidate WHERE c_id = ?");
        $check_stmt->bind_param("s", $candidate_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Candidate ID is already registered
            $errors[] = "❌ Candidate ID <strong>$candidate_id</strong> is already registered and cannot be used again!";
            $check_stmt->close();
        } else {
            // Candidate ID is not registered yet, check discipline status
            $check_stmt->close();
            
            // Check if candidate has been submitted for discipline review
            $stmt = $conn->prepare("SELECT discipline_status FROM request WHERE candidateID = ?");
            $stmt->bind_param("s", $candidate_id);
            $stmt->execute();
            $request_result = $stmt->get_result();
            
            if ($request_result->num_rows > 0) {
                // Candidate has a discipline record
                $request_data = $request_result->fetch_assoc();
                $discipline_status = $request_data['discipline_status'];
                
                if ($discipline_status === 'clear') {
                    // Candidate is cleared for registration
                    $_SESSION['valid_candidate_id'] = $candidate_id;
                    $candidate_validated = true;
                    $success = "✅ Candidate ID <strong>$candidate_id</strong> is cleared for registration. This ID has not been used before.";
                } elseif ($discipline_status === 'disciplinary_action') {
                    $errors[] = "❌ Candidate ID <strong>$candidate_id</strong> has disciplinary issues and cannot be registered.";
                } elseif ($discipline_status === 'pending') {
                    $errors[] = "⚠️ Candidate ID <strong>$candidate_id</strong> is still pending discipline review.";
                } else {
                    $errors[] = "❌ Unknown discipline status for candidate ID: $candidate_id";
                }
            } else {
                $errors[] = "❌ No discipline record found for Candidate ID: $candidate_id. Please ensure the candidate has been submitted for discipline review.";
            }
            $stmt->close();
        }
    }
}

// Check if candidate is already validated from session
if (isset($_SESSION['valid_candidate_id'])) {
    // Double-check that candidate ID is not already registered
    $candidate_id = $_SESSION['valid_candidate_id'];
    $check_stmt = $conn->prepare("SELECT * FROM candidate WHERE c_id = ?");
    $check_stmt->bind_param("s", $candidate_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        // Candidate ID is still available
        $candidate_validated = true;
    } else {
        // Candidate ID was registered in another session/window
        unset($_SESSION['valid_candidate_id']);
        $errors[] = "❌ Candidate ID <strong>$candidate_id</strong> was registered in another session. Please use a different ID.";
    }
    $check_stmt->close();
}

// Handle candidate registration submission
if (isset($_POST['ok']) && $candidate_validated) {
    // Get candidate data from session
    $c_id = $_SESSION['valid_candidate_id'];
    
    // DOUBLE-CHECK: Ensure candidate ID is not already registered (race condition prevention)
    $check_stmt = $conn->prepare("SELECT * FROM candidate WHERE c_id = ?");
    $check_stmt->bind_param("s", $c_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $errors[] = "❌ Candidate ID <strong>$c_id</strong> was just registered by another user. Please verify with a different ID.";
        $check_stmt->close();
    } else {
        $check_stmt->close();
        
        // Get form data with proper trimming
        $fname = isset($_POST['fname']) ? trim($_POST['fname']) : '';
        $mname = isset($_POST['mname']) ? trim($_POST['mname']) : '';
        $lname = isset($_POST['lname']) ? trim($_POST['lname']) : '';
        $sex = isset($_POST['sex']) ? trim($_POST['sex']) : '';
        $age = isset($_POST['age']) ? (int)trim($_POST['age']) : 0;
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
        $year = isset($_POST['year']) ? trim($_POST['year']) : '';
        $department = isset($_POST['department']) ? trim($_POST['department']) : '';
        $cgpa = isset($_POST['cgpa']) ? (float)trim($_POST['cgpa']) : 0.0;
        $experience = isset($_POST['experience']) ? (int)trim($_POST['experience']) : 0;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $passw = isset($_POST['passw']) ? trim($_POST['passw']) : '';

        // Server-side validation
        $validation_errors = [];
        
        if (empty($fname)) $validation_errors[] = "First name is required!";
        if (empty($mname)) $validation_errors[] = "Middle name is required!";
        if (empty($lname)) $validation_errors[] = "Last name is required!";
        if (empty($sex)) $validation_errors[] = "Gender is required!";
        if (empty($age)) $validation_errors[] = "Age is required!";
        if (empty($phone)) $validation_errors[] = "Phone number is required!";
        if (empty($email)) $validation_errors[] = "Email is required!";
        if (empty($student_id)) $validation_errors[] = "Student ID is required!";
        if (empty($year)) $validation_errors[] = "Academic Year is required!";
        if (empty($department)) $validation_errors[] = "Department is required!";
        if (empty($experience) && $experience !== 0) $validation_errors[] = "Experience is required!";
        if (empty($username)) $validation_errors[] = "Username is required!";
        if (empty($passw)) $validation_errors[] = "Password is required!";
        
        if (!empty($validation_errors)) {
            $errors = array_merge($errors, $validation_errors);
        }
        
        // Additional validations only if basic fields are filled
        if (empty($validation_errors)) {
            if (!preg_match('/^[A-Za-z\s]{1,50}$/', $fname)) {
                $errors[] = "First name must be letters and spaces only!";
            }
            if (!preg_match('/^[A-Za-z\s]{1,50}$/', $mname)) {
                $errors[] = "Middle name must be letters and spaces only!";
            }
            if (!preg_match('/^[A-Za-z\s]{1,50}$/', $lname)) {
                $errors[] = "Last name must be letters and spaces only!";
            }
            if (!in_array($sex, ['male', 'female'])) {
                $errors[] = "Invalid gender selection!";
            }
            if ($age < 21 || $age > 120) {
                $errors[] = "Age must be between 21 and 120!";
            }
            if (!preg_match('/^\d{10}$/', $phone)) {
                $errors[] = "Phone number must be exactly 10 digits!";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format!";
            }
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $student_id)) {
                $errors[] = "Invalid Student ID format!";
            }
            if ($year < 1 || $year > 6) {
                $errors[] = "Academic Year must be between 1 and 6!";
            }
            if ($cgpa < 0 || $cgpa > 4.0) {
                $errors[] = "CGPA must be between 0 and 4.0!";
            }
            if ($experience < 0) {
                $errors[] = "Experience must be a non-negative number!";
            }
            if (!preg_match('/^[A-Za-z0-9]{1,50}$/', $username)) {
                $errors[] = "Username must be alphanumeric and up to 50 characters!";
            }
            if (strlen($passw) < 6) {
                $errors[] = "Password must be at least 6 characters!";
            }
        }

        // File upload handling
        $candidate_photo = '';
        if (empty($errors)) {
            if (isset($_FILES['candidate_photo']) && $_FILES['candidate_photo']['error'] === UPLOAD_ERR_OK) {
                $photo_tmp = $_FILES['candidate_photo']['tmp_name'];
                $photo_name = basename($_FILES['candidate_photo']['name']);
                $photo_ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
                
                if (!in_array($photo_ext, ['jpg', 'jpeg', 'png'])) {
                    $errors[] = "Candidate photo must be a JPG or PNG file!";
                } else {
                    $photo_new_name = 'candidate_' . $c_id . '.' . $photo_ext;
                    $photo_path = 'Uploads/candidates/' . $photo_new_name;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir('Uploads/candidates')) {
                        mkdir('Uploads/candidates', 0777, true);
                    }
                    
                    if (!move_uploaded_file($photo_tmp, $photo_path)) {
                        $errors[] = "Failed to upload candidate photo!";
                    } else {
                        $candidate_photo = $photo_path;
                    }
                }
            } else {
                if (!isset($_FILES['candidate_photo'])) {
                    $errors[] = "Candidate photo field is missing!";
                } elseif ($_FILES['candidate_photo']['error'] === UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Candidate photo is required!";
                } else {
                    $errors[] = "File upload error: " . $_FILES['candidate_photo']['error'];
                }
            }
        }

        // Check for duplicates (for fields other than candidate ID)
        if (empty($errors)) {
            // Check phone
            $stmt = $conn->prepare("SELECT * FROM candidate WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Phone number is already used by another candidate!";
            }
            $stmt->close();

            // Check username
            $stmt = $conn->prepare("SELECT * FROM candidate WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Username is already used by another candidate!";
            }
            $stmt->close();
            
            // Check email
            $stmt = $conn->prepare("SELECT * FROM candidate WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Email is already used by another candidate!";
            }
            $stmt->close();
            
            // Check student_id
            $stmt = $conn->prepare("SELECT * FROM candidate WHERE student_id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Student ID is already used by another candidate!";
            }
            $stmt->close();
        }

        // Insert candidate data
        if (empty($errors)) {
            $hashed_password = password_hash($passw, PASSWORD_DEFAULT);
            
            // Insert new candidate (candidate ID should NOT exist at this point due to earlier checks)
            $stmt = $conn->prepare("INSERT INTO candidate (c_id, u_id, fname, mname, lname, sex, age, student_id, year, department, phone, email, experience, candidate_photo, username, password, cgpa, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssssissssssssss", $c_id, $user_id, $fname, $mname, $lname, $sex, $age, $student_id, $year, $department, $phone, $email, $experience, $candidate_photo, $username, $hashed_password, $cgpa);
            
            if ($stmt->execute()) {
                $success = "✅ Candidate registered successfully! Candidate ID <strong>$c_id</strong> has been used and cannot be registered again.";
                $stmt->close();
                
                // Clear session data
                unset($_SESSION['valid_candidate_id']);
                unset($_SESSION['candidate_data']);
                
                header("Location: ov_candidate.php");
                ob_end_flush();
                exit();
            } else {
                // Check if it's a duplicate entry error
                if ($conn->errno === 1062) { // MySQL duplicate entry error code
                    $errors[] = "❌ Candidate ID <strong>$c_id</strong> was just registered by another user. Please use a different candidate ID.";
                } else {
                    $errors[] = "Error registering candidate: " . htmlspecialchars($conn->error);
                }
                $stmt->close();
            }
        } else {
            // If there are errors, keep the form filled with submitted data
            $_POST['form_data'] = [
                'fname' => $fname,
                'mname' => $mname,
                'lname' => $lname,
                'sex' => $sex,
                'age' => $age,
                'phone' => $phone,
                'email' => $email,
                'student_id' => $student_id,
                'year' => $year,
                'department' => $department,
                'cgpa' => $cgpa,
                'experience' => $experience,
                'username' => $username
            ];
        }
    }
}

// Clear validation if requested
if (isset($_GET['clear']) && $_GET['clear'] == 'true') {
    unset($_SESSION['valid_candidate_id']);
    unset($_SESSION['candidate_data']);
    header("Location: o_can.php");
    exit();
}

// Check if we have form data to repopulate
$form_data = isset($_POST['form_data']) ? $_POST['form_data'] : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Online Voting - Candidate Registration</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .banner {
            width: 900px;
            height: 50px;
            background-color: #2F4F4F;
            overflow: hidden;
            white-space: nowrap;
            position: relative;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            font-size: 18px;
            line-height: 50px;
            text-align: center;
        }
        .scrolling-text {
            display: inline-block;
            animation: scroll-left 30s linear infinite, change-color 120s linear infinite;
            padding-left: 100%;
        }
        @keyframes scroll-left {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        @keyframes change-color {
            0%, 25% {
                color: #ffffff;
            }
            25%, 50% {
                color: #ffff00;
            }
            50%, 75% {
                color: #00ffff;
            }
            75%, 100% {
                color: #ff69b4;
            }
        }
        .banner:hover .scrolling-text {
            animation-play-state: paused;
        }
        
        /* Validation Styles */
        .validation-section {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .validation-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .validation-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 2px solid #6c757d;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .validation-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .validation-button:hover {
            background-color: #0056b3;
        }
        
        .validation-message {
            margin-top: 15px;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        
        .validation-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .validation-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .candidate-info {
            background: #e9f7ef;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .candidate-info h3 {
            color: #155724;
            margin-top: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-cleared {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-available {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .disabled-form {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .clear-validation {
            display: inline-block;
            padding: 10px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
            transition: background-color 0.3s;
        }
        
        .clear-validation:hover {
            background-color: #545b62;
            text-decoration: none;
            color: white;
        }
        
        .validation-info {
            margin-top: 10px;
            text-align: center;
            color: #666;
            font-size: 12px;
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
        }
        
        .one-time-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
            color: #856404;
        }
        
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .required-star {
            color: red;
        }
        
        /* Personal Information Section Styles */
        .personal-info-section {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border: 2px solid #2F4F4F;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            background-color: #2F4F4F;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-weight: 600;
            color: #2F4F4F;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .form-label i {
            color: #28a745;
            font-size: 12px;
        }
        
        .required {
            color: #dc3545;
            font-weight: bold;
        }
        
        .form-input {
            padding: 10px 12px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            border-color: #2F4F4F;
            box-shadow: 0 0 0 3px rgba(47, 79, 79, 0.1);
            outline: none;
        }
        
        .form-input.readonly {
            background-color: #e9ecef;
            cursor: not-allowed;
            border-color: #adb5bd;
            font-weight: bold;
            color: #495057;
        }
        
        .gender-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .gender-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .gender-option input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .validation-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .form-row {
            grid-column: span 2;
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-notes {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23495057' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 35px;
        }
        
        .file-input-container {
            position: relative;
        }
        
        .file-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            background-color: white;
        }
        
        .login-info-section {
            background: linear-gradient(to right, #e9ecef, #f8f9fa);
            border: 2px solid #343a40;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .button-primary {
            background-color: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .button-primary:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .button-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .button-secondary:hover {
            background-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .button-reset {
            background-color: #ffc107;
            color: #212529;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .button-reset:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .form-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .info-box h4 {
            color: #004085;
            margin-top: 0;
        }
    </style>
    <script type="text/javascript">
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        function chkAplha(evt, err) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (!((charCode >= 65 && charCode <= 90) || (charCode >= 97 && charCode <= 122) || charCode == 32 || charCode == 0 || charCode == 8)) {
                document.getElementById(err).innerHTML = "Please enter letters and spaces only!";
                return false;
            }
            document.getElementById(err).innerHTML = "";
            return true;
        }

        function chkblnk(eid, errid) {
            var x = document.getElementById(eid).value;
            document.getElementById(errid).innerHTML = x ? "" : "Please fill this field";
        }

        function chkeid() {
            var e = document.getElementById("email").value;
            var atpos = e.indexOf("@");
            var dotpos = e.lastIndexOf(".");
            document.getElementById("error2").innerHTML = (atpos < 4 || dotpos < atpos + 3) ? "Invalid email" : "";
        }

        function validateCandidate() {
            var candidateId = document.getElementById("candidate_id").value;
            if (!candidateId) {
                alert("Please enter a Candidate ID for validation");
                return false;
            }
            return true;
        }

        function validateForm() {
            <?php if (!$candidate_validated): ?>
            alert("Please validate the candidate ID first!");
            return false;
            <?php endif; ?>
            
            // Get form values
            var fname = document.getElementById("fname").value;
            var mname = document.getElementById("mname").value;
            var lname = document.getElementById("lname").value;
            var sexElements = document.getElementsByName("sex");
            var sex = '';
            for (var i = 0; i < sexElements.length; i++) {
                if (sexElements[i].checked) {
                    sex = sexElements[i].value;
                    break;
                }
            }
            var age = document.getElementById("age").value;
            var phone = document.getElementById("phone").value;
            var email = document.getElementById("email").value;
            var student_id = document.getElementById("student_id").value;
            var year = document.getElementById("year").value;
            var department = document.getElementById("department").value;
            var cgpa = document.getElementById("cgpa").value;
            var experience = document.getElementById("experience").value;
            var username = document.getElementById("username").value;
            var passw = document.getElementById("passw").value;
            var candidate_photo = document.getElementById("candidate_photo").value;

            // Check required fields
            if (!fname || fname.trim() === '') {
                alert("First name is required!");
                document.getElementById("fname").focus();
                return false;
            }
            if (!mname || mname.trim() === '') {
                alert("Middle name is required!");
                document.getElementById("mname").focus();
                return false;
            }
            if (!lname || lname.trim() === '') {
                alert("Last name is required!");
                document.getElementById("lname").focus();
                return false;
            }
            if (!sex) {
                alert("Please select gender!");
                return false;
            }
            if (!age || age.trim() === '') {
                alert("Age is required!");
                document.getElementById("age").focus();
                return false;
            }
            if (!phone || phone.trim() === '') {
                alert("Phone number is required!");
                document.getElementById("phone").focus();
                return false;
            }
            if (!email || email.trim() === '') {
                alert("Email is required!");
                document.getElementById("email").focus();
                return false;
            }
            if (!student_id || student_id.trim() === '') {
                alert("Student ID is required!");
                document.getElementById("student_id").focus();
                return false;
            }
            if (!year) {
                alert("Academic Year is required!");
                document.getElementById("year").focus();
                return false;
            }
            if (!department || department.trim() === '') {
                alert("Department is required!");
                document.getElementById("department").focus();
                return false;
            }
            if (!cgpa || cgpa.trim() === '') {
                alert("CGPA is required!");
                document.getElementById("cgpa").focus();
                return false;
            }
            if (!experience || experience.trim() === '') {
                alert("Experience is required!");
                document.getElementById("experience").focus();
                return false;
            }
            if (!username || username.trim() === '') {
                alert("Username is required!");
                document.getElementById("username").focus();
                return false;
            }
            if (!passw || passw.trim() === '') {
                alert("Password is required!");
                document.getElementById("passw").focus();
                return false;
            }
            if (!candidate_photo) {
                alert("Candidate photo is required!");
                document.getElementById("candidate_photo").focus();
                return false;
            }

            // Validate age
            var ageNum = parseInt(age);
            if (isNaN(ageNum) || ageNum < 21 || ageNum > 120) {
                alert("Age must be a number between 21 and 120!");
                return false;
            }

            // Validate phone
            if (!/^\d{10}$/.test(phone)) {
                alert("Phone number must be exactly 10 digits!");
                return false;
            }

            // Validate email
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                alert("Please enter a valid email address!");
                return false;
            }

            // Validate Academic Year (1-6)
            var yearNum = parseInt(year);
            if (isNaN(yearNum) || yearNum < 1 || yearNum > 6) {
                alert("Academic Year must be between 1st and 6th year!");
                return false;
            }

            // Validate CGPA
            var cgpaNum = parseFloat(cgpa);
            if (isNaN(cgpaNum) || cgpaNum < 0 || cgpaNum > 4.0) {
                alert("CGPA must be between 0 and 4.0!");
                return false;
            }

            // Validate experience
            var expNum = parseInt(experience);
            if (isNaN(expNum) || expNum < 0) {
                alert("Experience must be a non-negative number!");
                return false;
            }

            // Validate password length
            if (passw.length < 6) {
                alert("Password must be at least 6 characters!");
                return false;
            }

            // Confirm that candidate understands this is one-time registration
            var confirmMessage = "IMPORTANT: This Candidate ID can only be used ONCE for registration.\n\n" +
                                "Once registered, this Candidate ID cannot be used again.\n\n" +
                                "Do you want to proceed with registration?";
            
            if (!confirm(confirmMessage)) {
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
    <!-- Scrolling Banner -->
    <tr>
        <td colspan="2">
            <div class="banner">
                <div class="scrolling-text">
                    Welcome to Debre Tabor University Student Union Voting System! Candidate ID can only be used ONCE for registration.
                </div>
            </div>
        </td>
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
                <?php
                $resultDate = $conn->query("SELECT * FROM candidate_reg_date");
                $dateRes = $resultDate->fetch_assoc();
                $startDate = $dateRes['start'];
                $endDate = $dateRes['end'];
                $current = date("Y-m-d");
                if ($current >= $startDate && $current <= $endDate) {
                    echo '<li class="active"><a href="ov_candidate.php">Candidates</a></li>';
                }
                ?>
                <li><a href="o_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </td>
    </tr>
</table>

<!-- Candidate Validation Section -->
<div class="validation-section">
    <h3 style="text-align:center;color:#2F4F4F;">Candidate Validation</h3>
    
    <div class="one-time-note">
        <i class="fas fa-exclamation-triangle"></i> IMPORTANT: Each Candidate ID can only be registered ONCE in the system.
    </div>
    
    <?php if (!$candidate_validated): ?>
    <form method="post" class="validation-form" onsubmit="return validateCandidate()">
        <input type="text" name="candidate_id" id="candidate_id" class="validation-input" 
               placeholder="Enter Candidate ID for validation" required>
        <button type="submit" name="validate_candidate" class="validation-button">
            <i class="fas fa-check-circle"></i> Validate Candidate
        </button>
    </form>
    <div class="validation-info">
        <i class="fas fa-info-circle"></i> Candidate must: 1) Have "Cleared" discipline status 2) NOT be already registered
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="validation-message validation-error">
        <?php foreach ($errors as $error): ?>
        <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success && !isset($_POST['ok'])): ?>
    <div class="validation-message validation-success">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($candidate_validated): ?>
    <div class="candidate-info">
        <h3>✅ Candidate ID Available for Registration!</h3>
        <p><strong>Candidate ID:</strong> <?php echo $_SESSION['valid_candidate_id']; ?></p>
        <p><strong>Status:</strong> <span class="status-badge status-available">AVAILABLE FOR FIRST-TIME USE</span></p>
        <p><small>This candidate ID has passed discipline review and is available for one-time registration.</small></p>
        <a href="?clear=true" class="clear-validation">Clear Validation</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($candidate_validated): ?>
<table align="center" bgcolor="D3D3D3" style="width:900px;border:1px solid gray;border-radius:1px;" height="auto">
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
                    
                    <div class="info-box">
                        <h4><i class="fas fa-exclamation-circle"></i> Important Notice</h4>
                        <p>Candidate ID <strong><?php echo $_SESSION['valid_candidate_id']; ?></strong> is validated and available.</p>
                        <p>This Candidate ID can only be registered ONCE. After registration, it cannot be used again.</p>
                    </div>
                    
                    <?php
                    if (isset($_POST['ok']) && !empty($errors)) {
                        echo '<div class="validation-message validation-error">';
                        foreach ($errors as $error) {
                            echo '<p>' . htmlspecialchars($error) . '</p>';
                        }
                        echo '</div>';
                    }
                    ?>
                    
                    <form action="o_can.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                        <!-- Hidden field for candidate ID -->
                        <input type="hidden" name="c_id" value="<?php echo $_SESSION['valid_candidate_id']; ?>">
                        
                        <!-- PART I: PERSONAL INFORMATION -->
                        <div class="personal-info-section">
                            <div class="section-header">
                                <h3><i class="fas fa-user-circle"></i> Part I. Personal Information</h3>
                            </div>
                            
                            <div class="form-grid">
                                <!-- Row 1: User ID and Candidate ID -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-card"></i> User ID <span class="required">*</span>
                                    </label>
                                    <input type="text" name="user_id" class="form-input readonly" 
                                           value="<?php echo htmlspecialchars($user_id); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-fingerprint"></i> Candidate ID <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-input readonly" 
                                           value="<?php echo $_SESSION['valid_candidate_id']; ?>" 
                                           readonly>
                                    <div class="validation-badge">
                                        <i class="fas fa-check-circle"></i> Validated & Available
                                    </div>
                                </div>
                                
                                <!-- Row 2: First Name and Middle Name -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> First Name <span class="required">*</span>
                                    </label>
                                    <input type="text" name="fname" id="fname" class="form-input"
                                           value="<?php echo isset($form_data['fname']) ? htmlspecialchars($form_data['fname']) : ''; ?>"
                                           placeholder="Enter first name" required>
                                    <div class="error-message" id="fnameError"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Middle Name <span class="required">*</span>
                                    </label>
                                    <input type="text" name="mname" id="mname" class="form-input"
                                           value="<?php echo isset($form_data['mname']) ? htmlspecialchars($form_data['mname']) : ''; ?>"
                                           placeholder="Enter middle name" required>
                                    <div class="error-message" id="mnameError"></div>
                                </div>
                                
                                <!-- Row 3: Last Name and Gender -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Last Name <span class="required">*</span>
                                    </label>
                                    <input type="text" name="lname" id="lname" class="form-input"
                                           value="<?php echo isset($form_data['lname']) ? htmlspecialchars($form_data['lname']) : ''; ?>"
                                           placeholder="Enter last name" required>
                                    <div class="error-message" id="lnameError"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-venus-mars"></i> Gender <span class="required">*</span>
                                    </label>
                                    <div class="gender-group">
                                        <?php 
                                        $sex_value = isset($form_data['sex']) ? $form_data['sex'] : '';
                                        ?>
                                        <div class="gender-option">
                                            <input type="radio" name="sex" value="male" 
                                                   id="male" <?php echo $sex_value == 'male' ? 'checked' : ''; ?> required>
                                            <label for="male">Male</label>
                                        </div>
                                        <div class="gender-option">
                                            <input type="radio" name="sex" value="female" 
                                                   id="female" <?php echo $sex_value == 'female' ? 'checked' : ''; ?> required>
                                            <label for="female">Female</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Row 4: Age and Phone -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-birthday-cake"></i> Age <span class="required">*</span>
                                    </label>
                                    <input type="number" name="age" id="age" class="form-input" min="21" max="120"
                                           value="<?php echo isset($form_data['age']) ? htmlspecialchars($form_data['age']) : ''; ?>"
                                           placeholder="Minimum 21 years" required>
                                    <div class="form-notes">Must be between 21 and 120 years</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Phone Number <span class="required">*</span>
                                    </label>
                                    <input type="tel" name="phone" id="phone" class="form-input" maxlength="10"
                                           value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>"
                                           placeholder="10-digit number" required>
                                    <div class="form-notes">Format: 0912345678</div>
                                </div>
                                
                                <!-- Row 5: Email and Student ID -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Email Address <span class="required">*</span>
                                    </label>
                                    <input type="email" name="email" id="email" class="form-input"
                                           value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>"
                                           placeholder="example@dbtu.edu.et" required>
                                    <div class="error-message" id="error2"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-badge"></i> Student ID <span class="required">*</span>
                                    </label>
                                    <input type="text" name="student_id" id="student_id" class="form-input"
                                           value="<?php echo isset($form_data['student_id']) ? htmlspecialchars($form_data['student_id']) : ''; ?>"
                                           placeholder="Enter student ID" required>
                                </div>
                                
                                <!-- Row 6: Academic Year and Department -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-graduation-cap"></i> Academic Year <span class="required">*</span>
                                    </label>
                                    <select name="year" id="year" class="form-input" required>
                                        <option value="">-- Select Academic Year --</option>
                                        <?php
                                        $year_value = isset($form_data['year']) ? $form_data['year'] : '';
                                        $years = [
                                            1 => '1st Year (Freshman)',
                                            2 => '2nd Year (Sophomore)', 
                                            3 => '3rd Year (Junior)',
                                            4 => '4th Year (Senior)',
                                            5 => '5th Year',
                                            6 => '6th Year'
                                        ];
                                        
                                        foreach ($years as $num => $label) {
                                            $selected = ($year_value == $num) ? 'selected' : '';
                                            echo "<option value='$num' $selected>$label</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-university"></i> Department <span class="required">*</span>
                                    </label>
                                    <input type="text" name="department" id="department" class="form-input"
                                           value="<?php echo isset($form_data['department']) ? htmlspecialchars($form_data['department']) : ''; ?>"
                                           placeholder="Enter department" required>
                                </div>
                                
                                <!-- Row 7: CGPA and Experience -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-chart-line"></i> CGPA <span class="required">*</span>
                                    </label>
                                    <input type="number" name="cgpa" id="cgpa" class="form-input" min="0" max="4.0" step="0.01"
                                           value="<?php echo isset($form_data['cgpa']) ? htmlspecialchars($form_data['cgpa']) : ''; ?>"
                                           placeholder="0.00 - 4.00" required>
                                    <div class="form-notes">Scale: 0.0 to 4.0</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-briefcase"></i> Experience (years) <span class="required">*</span>
                                    </label>
                                    <input type="number" name="experience" id="experience" class="form-input" min="0"
                                           value="<?php echo isset($form_data['experience']) ? htmlspecialchars($form_data['experience']) : ''; ?>"
                                           placeholder="Years of experience" required>
                                    <div class="form-notes">Leadership/Related experience</div>
                                </div>
                                
                                <!-- Row 8: Candidate Photo (full width) -->
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">
                                        <i class="fas fa-camera"></i> Candidate Photo <span class="required">*</span>
                                    </label>
                                    <input type="file" name="candidate_photo" id="candidate_photo" 
                                           class="file-input" accept=".jpg,.jpeg,.png" required>
                                    <div class="form-notes">
                                        Maximum 4MB | JPG, JPEG, or PNG format only
                                        <?php if (isset($_FILES['candidate_photo']['name']) && !empty($_FILES['candidate_photo']['name'])): ?>
                                            <br><span style="color:green;">Selected: <?php echo htmlspecialchars($_FILES['candidate_photo']['name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PART II: LOGIN INFORMATION -->
                        <div class="login-info-section">
                            <div class="section-header">
                                <h3><i class="fas fa-key"></i> Part II. Login Information</h3>
                            </div>
                            
                            <div class="form-grid">
                                <!-- Row 1: Username -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Username <span class="required">*</span>
                                    </label>
                                    <input type="text" name="username" id="username" class="form-input"
                                           value="<?php echo isset($form_data['username']) ? htmlspecialchars($form_data['username']) : ''; ?>"
                                           placeholder="Choose a username" required>
                                    <div class="form-notes">Alphanumeric, up to 50 characters</div>
                                </div>
                                
                                <!-- Row 2: Password -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-lock"></i> Password <span class="required">*</span>
                                    </label>
                                    <input type="password" name="passw" id="passw" class="form-input"
                                           value="<?php echo isset($form_data['passw']) ? htmlspecialchars($form_data['passw']) : ''; ?>"
                                           placeholder="Create a password" required>
                                    <div class="form-notes">Minimum 6 characters</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <div class="button-group">
                                <button type="submit" name="ok" class="button-primary">
                                    <i class="fas fa-user-plus"></i> Register Candidate (One-Time Use)
                                </button>
                                <button type="reset" class="button-reset">
                                    <i class="fas fa-redo"></i> Reset Form
                                </button>
                                <a href="?clear=true" class="button-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <br>
                </div>
            </div>
        </td>
    </tr>
</table>
<?php endif; ?>

<?php if (!$candidate_validated): ?>
<!-- Show message when no candidate is validated -->
<table align="center" bgcolor="D3D3D3" style="width:900px;border:1px solid gray;border-radius:1px;" height="200px">
    <tr valign="middle">
        <td colspan="2" style="text-align:center;">
            <div style="padding:50px;background-color:#f8f9fa;border-radius:10px;">
                <h3 style="color:#6c757d;">🔒 Candidate Registration Locked</h3>
                <p>Please validate a candidate ID first to proceed with registration.</p>
                <p><small>Enter a Candidate ID that has "Cleared" discipline status and has NOT been registered before.</small></p>
            </div>
        </td>
    </tr>
</table>
<?php endif; ?>

<table align="center" style="width:900px;border:1px solid gray;border-radius:1px;">
    <tr>
        <td colspan="2" bgcolor="#E6E6FA" align="center">
            <div id="bottom">
                <p style="text-align:center;padding-right:20px;">Copyright &copy; 2025 EC. | Secure Online Voting System</p>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
<?php
ob_end_flush();
$conn->close();
?>