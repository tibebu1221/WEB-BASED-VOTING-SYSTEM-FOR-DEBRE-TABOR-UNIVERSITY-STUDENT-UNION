<?php
include("connection.php");
session_start();

// Check if user is logged in and has officer role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as an election officer.');
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

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname'] ?? '');
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Initialize variables
$success = '';
$error = '';

// Check if e_requests table exists, create if not (same as in department)
$createRequestsTable = "CREATE TABLE IF NOT EXISTS e_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    cgpa DECIMAL(3,2) NOT NULL,
    submitted_by VARCHAR(100) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    processed_at DATETIME DEFAULT NULL,
    processed_by VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
)";
$conn->query($createRequestsTable);

// Check nomination_logs table exists, create if not
$createLogsTable = "CREATE TABLE IF NOT EXISTS nomination_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id VARCHAR(50) NOT NULL,
    candidate_name VARCHAR(100),
    action VARCHAR(50) NOT NULL,
    performed_by VARCHAR(100),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    department VARCHAR(100),
    cgpa DECIMAL(3,2),
    status VARCHAR(20) DEFAULT 'pending',
    INDEX idx_candidate_id (candidate_id),
    INDEX idx_timestamp (timestamp)
)";
$conn->query($createLogsTable);

// Handle requests from department (GET parameter)
if (isset($_GET['action']) && $_GET['action'] === 'process_request') {
    if (isset($_GET['request_id']) && isset($_GET['decision'])) {
        $request_id = intval($_GET['request_id']);
        $decision = $_GET['decision']; // 'approve' or 'reject'
        $notes = $_GET['notes'] ?? '';
        
        // Get the request details
        $stmt = $conn->prepare("SELECT * FROM e_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            
            // Update request status
            $status = ($decision === 'approve') ? 'approved' : 'rejected';
            $updateStmt = $conn->prepare("UPDATE e_requests SET status = ?, processed_at = NOW(), processed_by = ?, notes = ? WHERE id = ?");
            $updateStmt->bind_param("sssi", $status, $_SESSION['u_id'], $notes, $request_id);
            
            if ($updateStmt->execute()) {
                // Log the action
                $logAction = ($decision === 'approve') ? 'officer_approved' : 'officer_rejected';
                $logStmt = $conn->prepare("INSERT INTO nomination_logs (candidate_id, candidate_name, action, performed_by, department, cgpa, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $logStmt->bind_param("sssssds", 
                    $request['student_id'],
                    $request['student_name'],
                    $logAction,
                    $_SESSION['u_id'],
                    $request['department'],
                    $request['cgpa'],
                    $status
                );
                $logStmt->execute();
                $logStmt->close();
                
                // If approved, create discipline request
                if ($decision === 'approve') {
                    // Insert into request table for discipline committee
                    $requestStmt = $conn->prepare("INSERT INTO request (candidateID, officeID, submitted_at, discipline_status) VALUES (?, ?, NOW(), 'pending')");
                    $requestStmt->bind_param("ss", $request['student_id'], $_SESSION['u_id']);
                    $requestStmt->execute();
                    $requestStmt->close();
                    
                    $success = "✅ Request approved and forwarded to Discipline Committee.";
                } else {
                    $success = "✅ Request rejected.";
                }
            } else {
                $error = "❌ Error processing request: " . $updateStmt->error;
            }
            $updateStmt->close();
        } else {
            $error = "❌ Request not found.";
        }
        $stmt->close();
        
        // Redirect to clear GET parameters
        header("Location: e_officer_send_request.php?success=" . urlencode($success) . "&error=" . urlencode($error));
        exit();
    }
}

// Get pending department requests
$pendingRequests = [];
$requestsStmt = $conn->prepare("SELECT * FROM e_requests WHERE status = 'pending' ORDER BY submitted_at DESC");
$requestsStmt->execute();
$requestsResult = $requestsStmt->get_result();
while ($row = $requestsResult->fetch_assoc()) {
    $pendingRequests[] = $row;
}
$requestsStmt->close();

// Get processed requests (recent)
$processedRequests = [];
$processedStmt = $conn->prepare("SELECT * FROM e_requests WHERE status != 'pending' ORDER BY processed_at DESC LIMIT 10");
$processedStmt->execute();
$processedResult = $processedStmt->get_result();
while ($row = $processedResult->fetch_assoc()) {
    $processedRequests[] = $row;
}
$processedStmt->close();

// Get recent discipline requests
$disciplineRequests = [];
$disciplineStmt = $conn->prepare("SELECT candidateID, officeID, submitted_at, discipline_status FROM request WHERE discipline_status = 'pending' ORDER BY submitted_at DESC LIMIT 5");
$disciplineStmt->execute();
$disciplineResult = $disciplineStmt->get_result();
while ($row = $disciplineResult->fetch_assoc()) {
    $disciplineRequests[] = $row;
}
$disciplineStmt->close();

// Get total counts
$totalPending = count($pendingRequests);
$totalDisciplinePending = count($disciplineRequests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Officer - Nomination Management</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-dark: #0d1a4a;
            --secondary: #ffcc00;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
        }

        body {
            background: linear-gradient(135deg, #0d1a4a 0%, #1a2a6c 50%, #0d1a4a 100%);
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 204, 0, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(26, 42, 108, 0.2) 0%, transparent 40%);
            z-index: -1;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin: 20px auto;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            padding: 25px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffcc00, #ff6b6b, #ffcc00);
        }

        .header img {
            height: 120px;
            width: auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .header img:hover {
            transform: scale(1.05);
        }

        nav {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            position: relative;
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
            position: relative;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 18px 24px;
            display: block;
            font-weight: 500;
            font-size: 15px;
            letter-spacing: 0.3px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        nav ul li a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: width 0.3s ease;
        }

        nav ul li a:hover::before,
        nav ul li a.active::before {
            width: 80%;
        }

        nav ul li a:hover,
        nav ul li a.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--secondary);
        }

        .content-wrapper {
            display: flex;
            flex-wrap: wrap;
            min-height: 600px;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 80%;
            background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.1), transparent);
        }

        .sidebar img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.15),
                inset 0 0 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .sidebar img:hover {
            transform: rotate(5deg) scale(1.05);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.25),
                inset 0 0 30px rgba(0, 0, 0, 0.15);
        }

        .user-info {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        .user-info h3 {
            color: var(--primary);
            margin: 0 0 5px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .user-info p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            background: var(--light);
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(26, 42, 108, 0.3);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }

        .welcome-card h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
        }

        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
            position: relative;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            text-align: center;
            border-top: 4px solid var(--primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 8px 30px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .stat-card h4 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .count {
            font-size: 42px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 10px;
            font-family: 'Inter', sans-serif;
        }

        .stat-card p {
            color: #64748b;
            font-size: 13px;
            margin: 0;
        }

        .tab-container {
            display: flex;
            gap: 8px;
            margin-bottom: 30px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .tab-button {
            flex: 1;
            padding: 16px 20px;
            background: #f1f5f9;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-button:hover {
            background: #e2e8f0;
            color: var(--primary);
            transform: translateY(-2px);
        }

        .tab-button.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 42, 108, 0.3);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .requests-box {
            background: white;
            border-radius: 20px;
            box-shadow: 
                0 10px 35px rgba(0, 0, 0, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .requests-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .requests-box h3 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .request-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .request-item:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(26, 42, 108, 0.15),
                inset 0 0 0 1px rgba(26, 42, 108, 0.1);
        }

        .request-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .request-item:hover::before {
            opacity: 1;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-info {
            flex: 1;
            min-width: 200px;
        }

        .student-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 18px;
            margin-bottom: 5px;
            display: block;
        }

        .student-id {
            color: #64748b;
            font-family: 'Roboto Mono', monospace;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 13px;
            display: inline-block;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .detail-icon {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-weight: 600;
            color: var(--primary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 2px;
        }

        .detail-value {
            color: var(--dark);
            font-size: 15px;
            font-weight: 500;
        }

        .timestamp {
            font-size: 12px;
            color: #94a3b8;
            text-align: right;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .empty-state .subtext {
            font-size: 14px;
            opacity: 0.7;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 140px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-approve {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .btn-reject {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--info), #2563eb);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #f59e0b);
            color: #92400e;
        }

        .status-approved {
            background: linear-gradient(135deg, #d1fae5, #10b981);
            color: #065f46;
        }

        .status-rejected {
            background: linear-gradient(135deg, #fee2e2, #ef4444);
            color: #991b1b;
        }

        footer {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 204, 0, 0.5), transparent);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 24px;
            max-width: 500px;
            width: 90%;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        @keyframes modalSlideIn {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .notification {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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

        .error {
            background: linear-gradient(135deg, #fee2e2, #ef4444);
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .success {
            background: linear-gradient(135deg, #d1fae5, #10b981);
            color: #065f46;
            border-left: 4px solid #059669;
        }

        @media (max-width: 1024px) {
            .content-wrapper {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                padding: 20px;
            }
            
            .sidebar img {
                width: 150px;
                height: 150px;
            }
            
            .main-content {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header img {
                height: 80px;
            }
            
            nav ul li a {
                padding: 14px 16px;
                font-size: 14px;
            }
            
            .welcome-card h1 {
                font-size: 24px;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .tab-container {
                flex-direction: column;
            }
            
            .request-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        .floating-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 20px 25px;
            border-radius: 16px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 400px;
        }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(100px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .floating-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .floating-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .refresh-indicator {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 12px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="e_officer.php">
                <img src="img/logo.jpg" alt="Election Logo">
            </a>
            
        </div>

        <nav>
            <ul>
                <li><a href="e_officer.php">Home</a></li>
                <li><a href="o_comment.php">V_Comment</a></li>             
                <li><a class="active" href="e_officer_send_request.php">Nomination Management</a></li>
                <li><a href="dc_requests.php">Discipline Requests</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <div class="content-wrapper">
            <div class="sidebar">
                <img src=dessie.JPG alt="Officer Profile">
                <div class="user-info">
                    <h3><?php echo "$FirstName $middleName"; ?></h3>
                    <p>Election Officer</p>
                    <p class="text-xs mt-2"><i class="fas fa-id-badge mr-1"></i> ID: <?php echo htmlspecialchars($_SESSION['u_id']); ?></p>
                </div>
            </div>

            <div class="main-content">
                <!-- Notifications -->
                <?php if (!empty($error)): ?>
                    <div class="notification error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="notification success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h1>Welcome, <?php echo "$FirstName $middleName"; ?>!</h1>
                    <p>Manage department nominations and forward to discipline committee</p>
                </div>
                
                <!-- Statistics -->
                <div class="stats-container">
                    <div class="stat-card">
                        <h4>Pending Department Requests</h4>
                        <div class="count"><?php echo $totalPending; ?></div>
                        <p>Awaiting your review</p>
                    </div>
                    <div class="stat-card">
                        <h4>Discipline Requests</h4>
                        <div class="count"><?php echo $totalDisciplinePending; ?></div>
                        <p>Forwarded for review</p>
                    </div>
                    <div class="stat-card">
                        <h4>Processed Today</h4>
                        <div class="count"><?php echo count($processedRequests); ?></div>
                        <p>Completed reviews</p>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tab-container">
                    <button class="tab-button active" onclick="showTab('department')">
                        <i class="fas fa-building"></i>
                        Department Requests
                        <?php if ($totalPending > 0): ?>
                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full ml-2">
                                <?php echo $totalPending; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-button" onclick="showTab('discipline')">
                        <i class="fas fa-clipboard-check"></i>
                        Discipline Requests
                        <?php if ($totalDisciplinePending > 0): ?>
                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full ml-2">
                                <?php echo $totalDisciplinePending; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-button" onclick="showTab('processed')">
                        <i class="fas fa-history"></i>
                        Processed History
                    </button>
                </div>
                
                <!-- Department Requests Tab -->
                <div id="department" class="tab-content active">
                    <div class="requests-box">
                        <h3>
                            <i class="fas fa-inbox"></i>
                            Department Nomination Requests
                            <span class="text-sm font-normal text-gray-500 ml-2">
                                (Review and forward to discipline committee)
                            </span>
                        </h3>
                        
                        <?php if (empty($pendingRequests)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>All caught up!</p>
                                <p class="subtext">No pending department requests at the moment</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($pendingRequests as $request): ?>
                                <div class="request-item">
                                    <div class="request-header">
                                        <div class="student-info">
                                            <span class="student-name"><?php echo htmlspecialchars($request['student_name']); ?></span>
                                            <span class="student-id"><?php echo htmlspecialchars($request['student_id']); ?></span>
                                        </div>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-clock"></i>
                                            Pending Review
                                        </span>
                                    </div>
                                    
                                    <div class="request-details">
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div class="detail-content">
                                                <span class="detail-label">Department</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($request['department']); ?></span>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div class="detail-content">
                                                <span class="detail-label">CGPA</span>
                                                <span class="detail-value <?php echo $request['cgpa'] >= 2.75 ? 'text-green-600 font-bold' : 'text-red-600'; ?>">
                                                    <?php echo number_format($request['cgpa'], 2); ?>
                                                    <?php if ($request['cgpa'] >= 2.75): ?>
                                                        <i class="fas fa-check-circle ml-1 text-green-500"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-exclamation-circle ml-1 text-red-500"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="detail-content">
                                                <span class="detail-label">Submitted By</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($request['submitted_by']); ?></span>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="detail-content">
                                                <span class="detail-label">Time</span>
                                                <span class="detail-value"><?php echo date('H:i', strtotime($request['submitted_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="timestamp">
                                        <i class="far fa-clock"></i>
                                        Submitted: <?php echo date('M d, Y H:i', strtotime($request['submitted_at'])); ?>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-approve" onclick="processRequest(<?php echo $request['id']; ?>, 'approve')">
                                            <i class="fas fa-check"></i>
                                            Approve & Forward
                                        </button>
                                        <button class="btn btn-reject" onclick="processRequest(<?php echo $request['id']; ?>, 'reject')">
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                        <button class="btn btn-view" onclick="viewDetails(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Discipline Requests Tab -->
                <div id="discipline" class="tab-content">
                    <div class="requests-box">
                        <h3>
                            <i class="fas fa-clipboard-check"></i>
                            Pending Discipline Requests
                            <span class="text-sm font-normal text-gray-500 ml-2">
                                (Forwarded for discipline committee review)
                            </span>
                        </h3>
                        
                        <?php if (empty($disciplineRequests)): ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard"></i>
                                <p>No pending discipline requests</p>
                                <p class="subtext">Approved nominations will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($disciplineRequests as $request): ?>
                                <div class="request-item">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800 text-lg mb-2">
                                                Candidate: <?php echo htmlspecialchars($request['candidateID']); ?>
                                            </h4>
                                            <div class="space-y-2">
                                                <p class="text-sm text-gray-600">
                                                    <i class="fas fa-briefcase mr-2"></i>
                                                    Office: <?php echo htmlspecialchars($request['officeID']); ?>
                                                </p>
                                                <p class="text-xs">
                                                    <i class="fas fa-hourglass-half mr-2"></i>
                                                    Status: 
                                                    <span class="font-bold text-amber-600">
                                                        <?php echo htmlspecialchars($request['discipline_status']); ?>
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <i class="far fa-clock mr-2"></i>
                                                    Submitted: <?php echo date('M d, Y H:i', strtotime($request['submitted_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i>
                                                Pending
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <a href="dc_requests.php?candidate=<?php echo urlencode($request['candidateID']); ?>" 
                                           class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            View in Discipline Portal
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-6">
                                <a href="dc_requests.php" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-list mr-2"></i>
                                    View All Discipline Requests
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Processed Requests Tab -->
                <div id="processed" class="tab-content">
                    <div class="requests-box">
                        <h3>
                            <i class="fas fa-history"></i>
                            Recently Processed Requests
                            <span class="text-sm font-normal text-gray-500 ml-2">
                                (Your recent decisions)
                            </span>
                        </h3>
                        
                        <?php if (empty($processedRequests)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>No processed requests found</p>
                                <p class="subtext">Your decisions will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($processedRequests as $request): ?>
                                <div class="request-item">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800 text-lg mb-2">
                                                <?php echo htmlspecialchars($request['student_name']); ?>
                                            </h4>
                                            <div class="space-y-2">
                                                <p class="text-sm text-gray-600">
                                                    <i class="fas fa-id-card mr-2"></i>
                                                    ID: <?php echo htmlspecialchars($request['student_id']); ?>
                                                </p>
                                                <p class="text-xs">
                                                    <i class="fas fa-tag mr-2"></i>
                                                    Status: 
                                                    <span class="font-bold <?php echo $request['status'] === 'approved' ? 'text-green-600' : 'text-red-600'; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($request['status'])); ?>
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <i class="fas fa-calendar-check mr-2"></i>
                                                    Processed: <?php echo date('M d, Y H:i', strtotime($request['processed_at'])); ?>
                                                    <?php if ($request['processed_by']): ?>
                                                        by <?php echo htmlspecialchars($request['processed_by']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if ($request['notes']): ?>
                                                    <div class="bg-gray-50 p-3 rounded-lg mt-2">
                                                        <p class="text-xs text-gray-600">
                                                            <i class="fas fa-sticky-note mr-2"></i>
                                                            <span class="font-medium">Note:</span> 
                                                            <?php echo htmlspecialchars($request['notes']); ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="status-badge <?php echo $request['status'] === 'approved' ? 'status-approved' : 'status-rejected'; ?>">
                                                <i class="fas fa-<?php echo $request['status'] === 'approved' ? 'check' : 'times'; ?>"></i>
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> EC | Secure Online Voting System</p>
            <p class="text-xs opacity-75 mt-2">Election Officer Portal v2.0</p>
        </footer>
    </div>
    
    <!-- Process Request Modal -->
    <div id="processModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" class="text-2xl font-bold mb-2 text-gray-800"></h3>
            <p id="modalSubtitle" class="text-gray-600 mb-6"></p>
            
            <form id="processForm" method="GET" action="">
                <input type="hidden" name="action" value="process_request">
                <input type="hidden" id="modalRequestId" name="request_id">
                <input type="hidden" id="modalDecision" name="decision">
                
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-comment-dots mr-2"></i>
                        Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="4" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Add any comments or notes about your decision..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">This note will be recorded with your decision</p>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" 
                        class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmit" 
                        class="px-5 py-2.5 rounded-xl text-white font-medium transition-all hover:shadow-lg">
                        <span id="submitText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Refresh Indicator -->
    <div class="refresh-indicator">
        <i class="fas fa-sync-alt animate-spin"></i>
        <span>Auto-refresh in <span id="refreshTimer">60</span>s</span>
    </div>
    
    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.currentTarget.classList.add('active');
            
            // Add a subtle animation
            document.getElementById(tabName).style.animation = 'none';
            setTimeout(() => {
                document.getElementById(tabName).style.animation = 'fadeIn 0.4s ease';
            }, 10);
        }
        
        // Process request modal
        function processRequest(requestId, decision) {
            const modal = document.getElementById('processModal');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const submitBtn = document.getElementById('modalSubmit');
            const submitText = document.getElementById('submitText');
            const form = document.getElementById('processForm');
            
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('modalDecision').value = decision;
            document.getElementById('notes').value = '';
            
            if (decision === 'approve') {
                title.textContent = 'Approve Nomination';
                subtitle.textContent = 'This nomination will be forwarded to the Discipline Committee for final review.';
                submitBtn.className = 'px-5 py-2.5 rounded-xl text-white font-medium transition-all hover:shadow-lg bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700';
                submitText.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Approve & Forward';
            } else {
                title.textContent = 'Reject Nomination';
                subtitle.textContent = 'This nomination will be rejected and the department will be notified.';
                submitBtn.className = 'px-5 py-2.5 rounded-xl text-white font-medium transition-all hover:shadow-lg bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700';
                submitText.innerHTML = '<i class="fas fa-ban mr-2"></i>Reject Nomination';
            }
            
            modal.style.display = 'flex';
        }
        
        // View details
        function viewDetails(requestId) {
            // Create a simple alert for now - can be expanded to show a detailed modal
            const modal = document.getElementById('processModal');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const form = document.getElementById('processForm');
            
            title.textContent = 'Request Details';
            subtitle.textContent = 'Detailed information about this nomination request.';
            form.style.display = 'none';
            
            // Fetch additional details via AJAX (simplified for now)
            fetch(`get_request_details.php?id=${requestId}`)
                .then(response => response.json())
                .then(data => {
                    const detailsDiv = document.createElement('div');
                    detailsDiv.className = 'space-y-3';
                    detailsDiv.innerHTML = `
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-bold text-lg mb-2">Nomination Information</h4>
                            <p>Request ID: ${requestId}</p>
                            <p>Submitted: ${new Date().toLocaleDateString()}</p>
                        </div>
                    `;
                    form.parentNode.insertBefore(detailsDiv, form.nextSibling);
                })
                .catch(error => {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'p-4 bg-red-50 text-red-700 rounded-lg';
                    errorDiv.textContent = 'Unable to load additional details.';
                    form.parentNode.insertBefore(errorDiv, form.nextSibling);
                });
            
            modal.style.display = 'flex';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('processModal').style.display = 'none';
            const form = document.getElementById('processForm');
            form.style.display = 'block';
            
            // Remove any dynamically added content
            const extraContent = document.querySelector('#processModal .modal-content div:not(.mb-6):not(.flex)');
            if (extraContent) {
                extraContent.remove();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('processModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Auto-refresh countdown
        let refreshTime = 60;
        const refreshTimer = document.getElementById('refreshTimer');
        
        const countdown = setInterval(() => {
            refreshTime--;
            refreshTimer.textContent = refreshTime;
            
            if (refreshTime <= 0) {
                clearInterval(countdown);
                location.reload();
            }
        }, 1000);
        
        // Reset countdown on user interaction
        document.addEventListener('click', () => {
            refreshTime = 60;
        });
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', () => {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.stat-card, .request-item');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
            
            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.7);
                        transform: scale(0);
                        animation: ripple-animation 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        top: ${y}px;
                        left: ${x}px;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        });
        
        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php
$conn->close();
?>