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
    exit();
}

// Fetch user details
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$FirstName = htmlspecialchars($row['fname']);
$middleName = htmlspecialchars($row['mname']);
$stmt->close();

// Handle form submission
$errors = [];
$success = "";
$formData = [
    'fname' => '',
    'mname' => '',
    'lname' => '',
    'vid' => '',
    'sex' => '',
    'age' => '',
    'year' => '',
    'department' => '',
    'phone' => '',
    'email' => '',
    'username' => ''
];

if (isset($_POST['ok'])) {
    $formData = array_map('trim', $_POST);
    $age = (int)$formData['age'];
    $is_active = 1;

    // Server-side validation
    $requiredFields = ['fname', 'mname', 'lname', 'vid', 'sex', 'age', 'year', 'department', 'phone', 'email', 'username', 'pass'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required!";
        }
    }

    if (empty($errors)) {
        if ($age < 18) {
            $errors[] = "Voter must be at least 18 years old!";
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format!";
        }
        if (!preg_match('/^\d{10}$/', $formData['phone'])) {
            $errors[] = "Phone number must be 10 digits!";
        }
        if (strlen($formData['pass']) < 6) {
            $errors[] = "Password must be at least 6 characters long!";
        }
    }

    if (empty($errors)) {
        // Check for duplicate voter ID
        $stmt = $conn->prepare("SELECT vid FROM voter WHERE vid = ?");
        $stmt->bind_param("s", $formData['vid']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Voter ID is already used!";
        }
        $stmt->close();

        if (empty($errors)) {
            // Check for duplicate phone number
            $stmt = $conn->prepare("SELECT phone FROM voter WHERE phone = ?");
            $stmt->bind_param("s", $formData['phone']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Phone number is already used!";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            // Check for duplicate username
            $stmt = $conn->prepare("SELECT username FROM voter WHERE username = ?");
            $stmt->bind_param("s", $formData['username']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Username is already used!";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            // Check for duplicate email
            $stmt = $conn->prepare("SELECT email FROM voter WHERE email = ?");
            $stmt->bind_param("s", $formData['email']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Email is already registered!";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            // Insert voter data
            $pass_hash = password_hash($formData['pass'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO voter (vid, u_id, fname, mname, lname, age, sex, year, department, phone, email, username, password, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("sssssisssssssi", $formData['vid'], $user_id, $formData['fname'], $formData['mname'], $formData['lname'], $age, $formData['sex'], $formData['year'], $formData['department'], $formData['phone'], $formData['email'], $formData['username'], $pass_hash, $is_active);
            
            if ($stmt->execute()) {
                $success = "Voter registered successfully!";
                // Clear form data
                $formData = array_fill_keys(array_keys($formData), '');
                $formData['sex'] = '';
                $formData['year'] = '';
                $formData['department'] = '';
                
                // Redirect after 2 seconds
                header("refresh:2;url=reg_voter.php");
            } else {
                $errors[] = "Error registering voter: " . htmlspecialchars($conn->error);
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
    <title>Add New Voter | Officer Portal</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ====== BASE STYLES ====== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }

        /* ====== CONTAINER ====== */
        .registration-container {
            width: 100%;
            max-width: 1200px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border: 1px solid #e1e8ed;
        }

        /* ====== HEADER ====== */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3498db;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #9b59b6);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-img {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: rotate(5deg) scale(1.05);
        }

        .header-text h1 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .header-text p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
            opacity: 0.95;
            font-weight: 500;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 14px 28px;
            border-radius: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .user-info i {
            font-size: 22px;
            color: #3498db;
            background: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info span {
            color: white;
            font-weight: 700;
            font-size: 17px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* ====== NAVIGATION ====== */
        .navbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 5px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            flex: 1;
            position: relative;
        }

        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 3px solid transparent;
        }

        .nav-link i {
            font-size: 22px;
            margin-bottom: 10px;
            color: #94a3b8;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: linear-gradient(to bottom, #f1f5f9, white);
            color: #3498db;
            transform: translateY(-2px);
        }

        .nav-link:hover i {
            color: #3498db;
            transform: scale(1.1);
        }

        .nav-link.active {
            background: linear-gradient(to bottom, #f1f5f9, white);
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .nav-link.active i {
            color: #3498db;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            right: 0;
            top: 30%;
            height: 40%;
            width: 1px;
            background: linear-gradient(to bottom, transparent, #e2e8f0, transparent);
        }

        .nav-item:last-child::after {
            display: none;
        }

        /* ====== MAIN CONTENT ====== */
        .main-content {
            padding: 40px;
            background: #f8fafc;
            min-height: 600px;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: right;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 150px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3498db);
        }

        .welcome-text {
            font-size: 18px;
            color: #64748b;
            font-weight: 500;
        }

        .welcome-text strong {
            color: #3498db;
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #9b59b6);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 32px;
            color: #2c3e50;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 20px;
            letter-spacing: -0.5px;
        }

        .section-header h2 i {
            color: #3498db;
            font-size: 36px;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .back-btn:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Messages */
        .message {
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.error {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            border-color: #fc8181;
            color: #c53030;
        }

        .message.success {
            background: linear-gradient(135deg, #f0fff4, #c6f6d5);
            border-color: #68d391;
            color: #276749;
        }

        .message i {
            font-size: 28px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* User ID Display */
        .user-id-display {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 25px 30px;
            border-radius: 15px;
            border: 2px dashed #94a3b8;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .user-id-display:hover {
            border-color: #3498db;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .user-id-text {
            font-size: 18px;
            color: #475569;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-id-text i {
            font-size: 24px;
            color: #3498db;
            background: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .user-id-value {
            font-weight: 800;
            color: #2c3e50;
            font-size: 22px;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.5px;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 35px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 700;
            color: #2c3e50;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.3px;
        }

        .form-label i {
            color: #3498db;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            padding: 6px;
        }

        .form-control {
            width: 100%;
            padding: 18px 24px;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
            transform: translateY(-1px);
        }

        .form-control.error {
            border-color: #e53e3e;
            background: linear-gradient(135deg, #fff5f5, white);
        }

        .form-control.success {
            border-color: #38a169;
            background: linear-gradient(135deg, #f0fff4, white);
        }

        .error-text {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .success-text {
            color: #38a169;
            font-size: 14px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        /* Radio Group */
        .radio-group {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            padding: 12px 20px;
            background: #f8fafc;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .radio-label:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .radio-label input[type="radio"] {
            accent-color: #3498db;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .radio-label i {
            font-size: 20px;
            color: #64748b;
        }

        .radio-label input[type="radio"]:checked + i {
            color: #3498db;
        }

        /* Select Styling */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='%233498db' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 18px;
            padding-right: 55px;
            cursor: pointer;
        }

        /* Password Container */
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 20px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        /* Password Strength */
        .password-strength {
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
            margin-top: 10px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: all 0.3s ease;
            background: linear-gradient(90deg, #e53e3e, #ed8936, #ecc94b, #48bb78, #38a169);
        }

        .strength-text {
            font-size: 14px;
            color: #64748b;
            margin-top: 8px;
            font-weight: 500;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 20px 45px;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 200px;
            letter-spacing: 0.5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            color: white;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 15px 35px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9, #27ae60);
        }

        .btn-reset {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            color: white;
            box-shadow: 0 8px 25px rgba(100, 116, 139, 0.3);
        }

        .btn-reset:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 15px 35px rgba(100, 116, 139, 0.4);
            background: linear-gradient(135deg, #64748b, #475569);
        }

        .btn:active {
            transform: translateY(-1px) scale(1.01);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #2c3e50, #1a202c);
            padding: 30px 40px;
            border-top: 3px solid #3498db;
            text-align: center;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #2ecc71, #9b59b6);
        }

        .footer-text {
            color: #cbd5e1;
            font-size: 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .footer-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .footer-link:hover {
            color: #3498db;
            transform: translateY(-2px);
        }

        /* ====== RESPONSIVE DESIGN ====== */
        @media (max-width: 1100px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 30px 25px;
            }
            
            .logo-section {
                flex-direction: column;
                text-align: center;
            }
            
            .section-header {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 calc(50% - 2px);
            }
            
            .nav-link {
                padding: 18px 8px;
                font-size: 13px;
            }
            
            .nav-link i {
                font-size: 20px;
                margin-bottom: 8px;
            }
            
            .main-content {
                padding: 25px;
            }
            
            .form-section {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-text h1 {
                font-size: 22px;
            }
            
            .section-header h2 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .nav-item {
                flex: 0 0 100%;
            }
            
            .nav-link {
                padding: 15px;
                font-size: 14px;
                flex-direction: row;
                justify-content: center;
                gap: 15px;
            }
            
            .nav-link i {
                margin-bottom: 0;
                font-size: 18px;
            }
            
            body {
                padding: 10px;
            }
            
            .registration-container {
                border-radius: 15px;
            }
            
            .header, .main-content, .footer {
                padding: 20px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .form-control {
                padding: 16px 20px;
                font-size: 15px;
            }
            
            .btn {
                padding: 18px 25px;
                font-size: 16px;
                min-width: auto;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-item::after {
                display: none;
            }
        }

        /* ====== ANIMATIONS ====== */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .logo-img {
            animation: float 3s ease-in-out infinite;
        }

        /* ====== CUSTOM SCROLLBAR ====== */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9, #27ae60);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <img src="img/logo.jpg" alt="System Logo" class="logo-img">
                <div class="header-text">
                    <h1>Online Voting System</h1>
                    <p>Ethiopian Election Commission - Voter Registration</p>
                </div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo "$FirstName $middleName"; ?></span>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="e_officer.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_result.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="o_generate.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reg_voter.php" class="nav-link active">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Voter</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <p class="welcome-text">
                    Welcome back, <strong><?php echo "$FirstName $middleName"; ?></strong>
                </p>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-user-plus"></i>
                        Register New Voter
                    </h2>
                    <a href="reg_voter.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Voters List
                    </a>
                </div>

                <!-- Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Registration Failed</strong>
                            <?php foreach ($errors as $error): ?>
                                <p style="margin: 8px 0 0 0;">• <?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong><?php echo htmlspecialchars($success); ?></strong>
                            <p style="margin: 8px 0 0 0;">You will be redirected to the voters list shortly...</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User ID Display -->
                <div class="user-id-display">
                    <div class="user-id-text">
                        <i class="fas fa-user-tag"></i>
                        <span>Registration Officer:</span>
                    </div>
                    <div class="user-id-value">
                        <?php echo htmlspecialchars($FirstName . ' ' . $middleName); ?>
                    </div>
                </div>

                <!-- Voter Registration Form -->
                <form method="POST" action="" onsubmit="return validateForm()">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                    <div class="form-grid">
                        <!-- Personal Information Column -->
                        <div>
                            <div class="form-group">
                                <label class="form-label" for="fname">
                                    <i class="fas fa-user"></i>
                                    First Name
                                </label>
                                <input type="text" 
                                       name="fname" 
                                       id="fname" 
                                       class="form-control <?php echo (isset($errors) && in_array('First Name is required!', $errors)) ? 'error' : ''; ?>"
                                       placeholder="Enter first name"
                                       value="<?php echo htmlspecialchars($formData['fname']); ?>"
                                       onkeypress="return chkAlpha(event, 'error-fname')"
                                       required>
                                <span id="error-fname" class="error-text"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="mname">
                                    <i class="fas fa-user"></i>
                                    Middle Name
                                </label>
                                <input type="text" 
                                       name="mname" 
                                       id="mname" 
                                       class="form-control"
                                       placeholder="Enter middle name"
                                       value="<?php echo htmlspecialchars($formData['mname']); ?>"
                                       onkeypress="return chkAlpha(event, 'error-mname')"
                                       required>
                                <span id="error-mname" class="error-text"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="lname">
                                    <i class="fas fa-user"></i>
                                    Last Name
                                </label>
                                <input type="text" 
                                       name="lname" 
                                       id="lname" 
                                       class="form-control"
                                       placeholder="Enter last name"
                                       value="<?php echo htmlspecialchars($formData['lname']); ?>"
                                       onkeypress="return chkAlpha(event, 'error-lname')"
                                       required>
                                <span id="error-lname" class="error-text"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="vid">
                                    <i class="fas fa-id-card"></i>
                                    Voter ID
                                </label>
                                <input type="text" 
                                       name="vid" 
                                       id="vid" 
                                       class="form-control"
                                       placeholder="Enter voter ID (max 8 characters)"
                                       maxlength="8"
                                       value="<?php echo htmlspecialchars($formData['vid']); ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Demographic Information Column -->
                        <div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars"></i>
                                    Gender
                                </label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="sex" value="male" <?php echo ($formData['sex'] == 'male') ? 'checked' : ''; ?> required>
                                        <i class="fas fa-male"></i> Male
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="sex" value="female" <?php echo ($formData['sex'] == 'female') ? 'checked' : ''; ?> required>
                                        <i class="fas fa-female"></i> Female
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="age">
                                    <i class="fas fa-birthday-cake"></i>
                                    Age
                                </label>
                                <input type="number" 
                                       name="age" 
                                       id="age" 
                                       class="form-control"
                                       placeholder="Enter age"
                                       min="18"
                                       max="100"
                                       value="<?php echo htmlspecialchars($formData['age']); ?>"
                                       onkeypress="return isNumberKey(event)"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="year">
                                    <i class="fas fa-graduation-cap"></i>
                                    Academic Year
                                </label>
                                <select name="year" id="year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <option value="First Year" <?php echo ($formData['year'] == 'First Year') ? 'selected' : ''; ?>>First Year</option>
                                    <option value="Second Year" <?php echo ($formData['year'] == 'Second Year') ? 'selected' : ''; ?>>Second Year</option>
                                    <option value="Third Year" <?php echo ($formData['year'] == 'Third Year') ? 'selected' : ''; ?>>Third Year</option>
                                    <option value="Fourth Year" <?php echo ($formData['year'] == 'Fourth Year') ? 'selected' : ''; ?>>Fourth Year</option>
                                    <option value="Fifth Year" <?php echo ($formData['year'] == 'Fifth Year') ? 'selected' : ''; ?>>Fifth Year</option>
                                    <option value="Graduate" <?php echo ($formData['year'] == 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="department">
                                    <i class="fas fa-building"></i>
                                    Department
                                </label>
                                <select name="department" id="department" class="form-control" required>
                                    <option value="">Select Department</option>
                                    <option value="Computer Science" <?php echo ($formData['department'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                                    <option value="Information Technology" <?php echo ($formData['department'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                    <option value="Software Engineering" <?php echo ($formData['department'] == 'Software Engineering') ? 'selected' : ''; ?>>Software Engineering</option>
                                    <option value="Electrical Engineering" <?php echo ($formData['department'] == 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
                                    <option value="Mechanical Engineering" <?php echo ($formData['department'] == 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                    <option value="Civil Engineering" <?php echo ($formData['department'] == 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                                    <option value="Business Administration" <?php echo ($formData['department'] == 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                                    <option value="Accounting" <?php echo ($formData['department'] == 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                                    <option value="Economics" <?php echo ($formData['department'] == 'Economics') ? 'selected' : ''; ?>>Economics</option>
                                    <option value="Other" <?php echo ($formData['department'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Contact & Account Column -->
                        <div>
                            <div class="form-group">
                                <label class="form-label" for="phone">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-control"
                                       placeholder="10-digit phone number"
                                       maxlength="10"
                                       value="<?php echo htmlspecialchars($formData['phone']); ?>"
                                       onkeypress="return isNumberKey(event)"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control"
                                       placeholder="example@gmail.com"
                                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                                       onblur="validateEmail()"
                                       required>
                                <span id="error-email" class="error-text"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="username">
                                    <i class="fas fa-user-circle"></i>
                                    Username
                                </label>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control"
                                       placeholder="Choose a username (3-20 characters)"
                                       minlength="3"
                                       maxlength="20"
                                       value="<?php echo htmlspecialchars($formData['username']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">
                                    <i class="fas fa-lock"></i>
                                    Password
                                </label>
                                <div class="password-container">
                                    <input type="password" 
                                           name="pass" 
                                           id="password" 
                                           class="form-control"
                                           placeholder="Minimum 6 characters"
                                           minlength="6"
                                           required
                                           oninput="updatePasswordStrength()">
                                    <button type="button" class="toggle-password" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-meter" id="strength-meter"></div>
                                </div>
                                <div class="strength-text" id="strength-text"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="ok" class="btn btn-submit">
                            <i class="fas fa-user-plus"></i>
                            Register Voter
                        </button>
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-redo"></i>
                            Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <p class="footer-text">
                &copy; <?php echo date("Y"); ?> Ethiopian Election Commission | Secure Online Voting System
            </p>
            
            <div class="footer-links">
                <a href="#" class="footer-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Privacy Policy</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-file-contract"></i>
                    <span>Terms of Service</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Help Center</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Support</span>
                </a>
            </div>
        </footer>
    </div>

    <script>
        // Input validation functions
        function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        function chkAlpha(event, errorId) {
            var charCode = (event.which) ? event.which : event.keyCode;
            var isValid = ((charCode >= 65 && charCode <= 90) || 
                          (charCode >= 97 && charCode <= 122) || 
                          charCode === 32 || charCode === 0 || charCode === 8);
            
            if (!isValid) {
                document.getElementById(errorId).innerHTML = "Please enter letters only!";
                return false;
            }
            document.getElementById(errorId).innerHTML = "";
            return true;
        }

        function validateEmail() {
            var email = document.getElementById("email").value;
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var errorElement = document.getElementById("error-email");
            
            if (!emailPattern.test(email)) {
                errorElement.innerHTML = "Please enter a valid email address!";
                document.getElementById("email").classList.add("error");
                return false;
            }
            errorElement.innerHTML = "";
            document.getElementById("email").classList.remove("error");
            return true;
        }

        function togglePassword() {
            var passwordInput = document.getElementById("password");
            var toggleIcon = document.querySelector(".toggle-password i");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        // Password strength calculator
        function updatePasswordStrength() {
            var password = document.getElementById("password").value;
            var meter = document.getElementById("strength-meter");
            var text = document.getElementById("strength-text");
            
            if (password.length === 0) {
                meter.style.width = "0%";
                meter.style.background = "#e53e3e";
                text.innerHTML = "";
                return;
            }
            
            var strength = 0;
            if (password.length >= 6) strength += 20;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            meter.style.width = strength + "%";
            
            // Set color and text based on strength
            var strengthText, strengthColor;
            if (strength <= 20) {
                strengthText = "Very Weak";
                strengthColor = "#e53e3e";
            } else if (strength <= 40) {
                strengthText = "Weak";
                strengthColor = "#ed8936";
            } else if (strength <= 60) {
                strengthText = "Fair";
                strengthColor = "#ecc94b";
            } else if (strength <= 80) {
                strengthText = "Good";
                strengthColor = "#48bb78";
            } else {
                strengthText = "Strong";
                strengthColor = "#38a169";
            }
            
            meter.style.background = strengthColor;
            text.innerHTML = `Password Strength: <span style="color: ${strengthColor}; font-weight: bold;">${strengthText}</span>`;
        }

        // Main form validation
        function validateForm() {
            var age = document.getElementById("age").value;
            var phone = document.getElementById("phone").value;
            var password = document.getElementById("password").value;
            var emailValid = validateEmail();
            
            // Age validation
            if (age < 18) {
                alert("Voter must be at least 18 years old!");
                document.getElementById("age").focus();
                return false;
            }
            
            // Phone validation
            if (phone.length !== 10 || isNaN(phone)) {
                alert("Phone number must be exactly 10 digits!");
                document.getElementById("phone").focus();
                return false;
            }
            
            // Password validation
            if (password.length < 6) {
                alert("Password must be at least 6 characters long!");
                document.getElementById("password").focus();
                return false;
            }
            
            // Email validation
            if (!emailValid) {
                alert("Please enter a valid email address!");
                document.getElementById("email").focus();
                return false;
            }
            
            // Check required radio buttons
            var genderSelected = document.querySelector('input[name="sex"]:checked');
            if (!genderSelected) {
                alert("Please select gender!");
                return false;
            }
            
            // Check dropdown selections
            var year = document.getElementById("year").value;
            var department = document.getElementById("department").value;
            if (!year || year === "") {
                alert("Please select academic year!");
                document.getElementById("year").focus();
                return false;
            }
            if (!department || department === "") {
                alert("Please select department!");
                document.getElementById("department").focus();
                return false;
            }
            
            return true;
        }

        // Initialize form with focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("fname").focus();
            
            // Add input event listeners for real-time validation
            document.getElementById("age").addEventListener("input", function() {
                if (this.value < 18 && this.value !== "") {
                    this.classList.add("error");
                } else {
                    this.classList.remove("error");
                }
            });
            
            document.getElementById("phone").addEventListener("input", function() {
                if (this.value.length !== 10 && this.value !== "") {
                    this.classList.add("error");
                } else {
                    this.classList.remove("error");
                }
            });
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
$conn->close();
?>