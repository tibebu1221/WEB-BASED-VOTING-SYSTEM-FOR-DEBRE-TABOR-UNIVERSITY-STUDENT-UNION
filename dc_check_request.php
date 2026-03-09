<?php
include("connection.php");
session_start();

// ---------- 1. AUTHENTICATION ----------
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'discipline_committee') {
    header("Location: login.php");
    exit();
}

// ---------- 2. USER INFO ----------
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$FirstName = htmlspecialchars($user['fname'] ?? '');
$MiddleName = htmlspecialchars($user['mname'] ?? '');
$initials = strtoupper(substr($FirstName, 0, 1) . (!empty($MiddleName) ? substr($MiddleName, 0, 1) : ''));
$stmt->close();

// ---------- 3. CSRF TOKEN ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ---------- 4. DETECT CANDIDATE ID COLUMN NAME ----------
$candidate_id_column = 'id'; // Default assumption
$result = $conn->query("SHOW COLUMNS FROM candidate");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $field = strtolower($row['Field']);
        if (strpos($field, 'cand') !== false || strpos($field, 'id') !== false) {
            $candidate_id_column = $row['Field'];
            break;
        }
    }
}

// ---------- 5. CHECK AND CREATE DISCIPLINE RECORDS TABLE IF NEEDED ----------
$conn->query("CREATE TABLE IF NOT EXISTS student_discipline_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    incident_date DATE NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    status ENUM('pending', 'resolved', 'warning_issued', 'suspended', 'expelled') DEFAULT 'pending',
    action_taken TEXT,
    resolved_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_incident_date (incident_date)
)");

// ---------- 6. CHECK AND ADD DISCIPLINE COLUMNS TO REQUEST TABLE IF NEEDED ----------
$result = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
if (!$result || $result->num_rows === 0) {
    // Add discipline columns if they don't exist
    $conn->query("ALTER TABLE request ADD COLUMN discipline_status VARCHAR(50) DEFAULT 'pending'");
    $conn->query("ALTER TABLE request ADD COLUMN review_notes TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE request ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL");
}

// ---------- 7. DISCIPLINE CHECK FUNCTIONS ----------
function hasDisciplineIssues($conn, $student_id, $candidate_name = null) {
    $issues_count = 0;
    
    // Check by student ID
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM student_discipline_records 
        WHERE student_id = ? 
        AND status IN ('pending', 'warning_issued', 'suspended', 'expelled')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $issues_count += $result['count'] ?? 0;
    $stmt->close();
    
    // If no issues found by ID and name provided, check by name
    if ($issues_count === 0 && $candidate_name) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM student_discipline_records 
            WHERE student_name LIKE ? 
            AND status IN ('pending', 'warning_issued', 'suspended', 'expelled')
        ");
        $search_name = "%" . $candidate_name . "%";
        $stmt->bind_param("s", $search_name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $issues_count += $result['count'] ?? 0;
        $stmt->close();
    }
    
    return $issues_count > 0;
}

function getCandidateStudentId($conn, $candidate_id, $candidate_id_column) {
    $stmt = $conn->prepare("SELECT u_id FROM candidate WHERE `$candidate_id_column` = ?");
    $stmt->bind_param("s", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['u_id'];
    }
    return null;
}

function getCandidateInfo($conn, $candidate_id, $candidate_id_column) {
    $stmt = $conn->prepare("
        SELECT u_id, fname, mname, lname 
        FROM candidate 
        WHERE `$candidate_id_column` = ?
    ");
    $stmt->bind_param("s", $candidate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $candidate_name = trim($row['fname'] . ' ' . 
                              ($row['mname'] ? $row['mname'] . ' ' : '') . 
                              $row['lname']);
        return [
            'student_id' => $row['u_id'],
            'student_name' => $candidate_name
        ];
    }
    return null;
}

// ---------- 8. HANDLE DISCIPLINE RECORDS ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf_token, $_POST['token'] ?? '')) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_discipline_record':
                $student_id = trim($_POST['student_id']);
                $student_name = trim($_POST['student_name']);
                $incident_date = $_POST['incident_date'];
                $incident_type = trim($_POST['incident_type']);
                $description = trim($_POST['description']);
                $severity = $_POST['severity'];
                $status = $_POST['status'];
                $action_taken = trim($_POST['action_taken'] ?? '');
                $resolved_date = !empty($_POST['resolved_date']) ? $_POST['resolved_date'] : null;
                
                if (empty($student_id) || empty($student_name) || empty($incident_date) || 
                    empty($incident_type) || empty($description) || empty($severity)) {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'All required fields must be filled!'];
                    header("Location: dc_check_request.php");
                    exit();
                }
                
                $stmt = $conn->prepare("INSERT INTO student_discipline_records 
                    (student_id, student_name, incident_date, incident_type, description, 
                     severity, status, action_taken, resolved_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $student_id, $student_name, $incident_date, 
                    $incident_type, $description, $severity, $status, $action_taken, $resolved_date);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type'=>'success','text'=>'Discipline record added successfully!'];
                } else {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Error adding discipline record! ' . $stmt->error];
                }
                $stmt->close();
                header("Location: dc_check_request.php");
                exit();
                
            case 'update_discipline_record':
                $record_id = intval($_POST['record_id']);
                $student_id = trim($_POST['student_id']);
                $student_name = trim($_POST['student_name']);
                $incident_date = $_POST['incident_date'];
                $incident_type = trim($_POST['incident_type']);
                $description = trim($_POST['description']);
                $severity = $_POST['severity'];
                $status = $_POST['status'];
                $action_taken = trim($_POST['action_taken'] ?? '');
                $resolved_date = !empty($_POST['resolved_date']) ? $_POST['resolved_date'] : null;
                
                $stmt = $conn->prepare("UPDATE student_discipline_records SET 
                    student_id = ?, student_name = ?, incident_date = ?, incident_type = ?, 
                    description = ?, severity = ?, status = ?, action_taken = ?, resolved_date = ? 
                    WHERE record_id = ?");
                $stmt->bind_param("sssssssssi", $student_id, $student_name, $incident_date, 
                    $incident_type, $description, $severity, $status, $action_taken, $resolved_date, $record_id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type'=>'success','text'=>'Discipline record updated successfully!'];
                } else {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Error updating discipline record! ' . $stmt->error];
                }
                $stmt->close();
                header("Location: dc_check_request.php");
                exit();
                
            case 'delete_discipline_record':
                $record_id = intval($_POST['record_id']);
                $stmt = $conn->prepare("DELETE FROM student_discipline_records WHERE record_id = ?");
                $stmt->bind_param("i", $record_id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = ['type'=>'success','text'=>'Discipline record deleted successfully!'];
                } else {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Error deleting discipline record! ' . $stmt->error];
                }
                $stmt->close();
                header("Location: dc_check_request.php");
                exit();
                
            case 'update_discipline_status':
                $requestID = intval($_POST['requestID']);
                $discipline_status = $_POST['discipline_status'];
                $review_notes = trim($_POST['review_notes'] ?? '');
                
                $valid_statuses = ['clear', 'disciplinary_action'];
                if (!in_array($discipline_status, $valid_statuses)) {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Invalid discipline status!'];
                    header("Location: dc_check_request.php");
                    exit();
                }
                
                if (empty($review_notes)) {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Review notes are required!'];
                    header("Location: dc_check_request.php");
                    exit();
                }
                
                // Get candidate ID from request
                $stmt = $conn->prepare("SELECT candidateID FROM request WHERE requestID = ?");
                $stmt->bind_param("i", $requestID);
                $stmt->execute();
                $requestData = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if (!$requestData) {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Request not found!'];
                    header("Location: dc_check_request.php");
                    exit();
                }
                
                $candidate_id = $requestData['candidateID'];
                
                // CRITICAL: Check if candidate has discipline issues
                if ($discipline_status === 'clear') {
                    // Get candidate info for checking
                    $candidate_info = getCandidateInfo($conn, $candidate_id, $candidate_id_column);
                    
                    if ($candidate_info) {
                        $student_id = $candidate_info['student_id'];
                        $student_name = $candidate_info['student_name'];
                        
                        // Check for discipline issues
                        if (hasDisciplineIssues($conn, $student_id, $student_name)) {
                            $_SESSION['msg'] = ['type'=>'error','text'=>'Cannot clear request! Candidate has unresolved discipline issues.'];
                            header("Location: dc_check_request.php");
                            exit();
                        }
                    } else {
                        // Candidate not found - still proceed but log warning
                        error_log("Candidate not found for request ID: $requestID");
                    }
                }
                
                // If all checks pass, update the request
                $stmt = $conn->prepare("UPDATE request SET discipline_status = ?, review_notes = ?, reviewed_at = NOW() WHERE requestID = ?");
                $stmt->bind_param("ssi", $discipline_status, $review_notes, $requestID);
                
                if ($stmt->execute()) {
                    $status_text = $discipline_status === 'clear' ? 'Cleared' : 'Rejected due to issues';
                    $_SESSION['msg'] = ['type'=>'success','text'=>'Discipline review updated successfully! Request ' . $status_text];
                } else {
                    $_SESSION['msg'] = ['type'=>'error','text'=>'Error updating discipline status! ' . $stmt->error];
                }
                $stmt->close();
                header("Location: dc_check_request.php");
                exit();
        }
    }
}

// ---------- 9. FETCH DISCIPLINE RECORDS ----------
$discipline_records = [];
$discipline_stats = [
    'total' => 0,
    'pending' => 0,
    'resolved' => 0,
    'warning_issued' => 0,
    'suspended' => 0,
    'expelled' => 0
];

// Get search parameters for discipline records
$search_student_id = $_GET['search_student_id'] ?? '';
$search_student_name = $_GET['search_student_name'] ?? '';
$search_status = $_GET['search_status'] ?? '';
$search_from_date = $_GET['search_from_date'] ?? '';
$search_to_date = $_GET['search_to_date'] ?? '';

// Build query for discipline records
$disc_sql = "SELECT * FROM student_discipline_records WHERE 1=1";
$disc_params = [];
$disc_types = '';

if (!empty($search_student_id)) {
    $disc_sql .= " AND student_id LIKE ?";
    $disc_params[] = "%$search_student_id%";
    $disc_types .= 's';
}

if (!empty($search_student_name)) {
    $disc_sql .= " AND student_name LIKE ?";
    $disc_params[] = "%$search_student_name%";
    $disc_types .= 's';
}

if (!empty($search_status)) {
    $disc_sql .= " AND status = ?";
    $disc_params[] = $search_status;
    $disc_types .= 's';
}

if (!empty($search_from_date)) {
    $disc_sql .= " AND incident_date >= ?";
    $disc_params[] = $search_from_date;
    $disc_types .= 's';
}

if (!empty($search_to_date)) {
    $disc_sql .= " AND incident_date <= ?";
    $disc_params[] = $search_to_date;
    $disc_types .= 's';
}

$disc_sql .= " ORDER BY incident_date DESC, created_at DESC LIMIT 50";

// Fetch discipline records
if ($stmt = $conn->prepare($disc_sql)) {
    if (!empty($disc_params)) {
        $stmt->bind_param($disc_types, ...$disc_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $discipline_records[] = $row;
    }
    $stmt->close();
}

// Fetch discipline statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'warning_issued' THEN 1 ELSE 0 END) as warning_issued,
    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
    SUM(CASE WHEN status = 'expelled' THEN 1 ELSE 0 END) as expelled
    FROM student_discipline_records";

if ($result = $conn->query($stats_sql)) {
    if ($row = $result->fetch_assoc()) {
        $discipline_stats = [
            'total' => $row['total'] ?? 0,
            'pending' => $row['pending'] ?? 0,
            'resolved' => $row['resolved'] ?? 0,
            'warning_issued' => $row['warning_issued'] ?? 0,
            'suspended' => $row['suspended'] ?? 0,
            'expelled' => $row['expelled'] ?? 0
        ];
    }
}

// ---------- 10. FETCH REQUESTS FOR REVIEW ----------
$requests = [];
$pending_review_count = 0;
$all_requests_count = 0;

// Check columns in candidate table
$candidate_columns = [];
$result = $conn->query("SHOW COLUMNS FROM candidate");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $candidate_columns[] = $row['Field'];
    }
}

// Build query for requests that need discipline review
$sql = "SELECT r.requestID, r.candidateID, r.officeID, r.submitted_at, 
               r.discipline_status, r.review_notes, r.reviewed_at";

// Add candidate name columns if they exist
if (in_array('fname', $candidate_columns)) {
    $sql .= ", c.fname AS cand_fname";
}
if (in_array('mname', $candidate_columns)) {
    $sql .= ", c.mname AS cand_mname";
}
if (in_array('lname', $candidate_columns)) {
    $sql .= ", c.lname AS cand_lname";
}
if (in_array('u_id', $candidate_columns)) {
    $sql .= ", c.u_id AS cand_u_id";
}
if (in_array('cgpa', $candidate_columns)) {
    $sql .= ", c.cgpa";
}
if (in_array('year', $candidate_columns)) {
    $sql .= ", c.year";
}

$sql .= " FROM request r";
$sql .= " LEFT JOIN candidate c ON r.candidateID = c.`$candidate_id_column`";

// Get search parameters for requests
$search_id = $_GET['search_id'] ?? '';
$search_name = $_GET['search_name'] ?? '';

$where_conditions = [];
$sql_params = [];
$types = '';

if (!empty($search_id)) {
    $where_conditions[] = "(r.candidateID LIKE ? OR r.requestID = ?)";
    $search_pattern = "%$search_id%";
    $sql_params[] = $search_pattern;
    $sql_params[] = intval($search_id);
    $types .= 'si';
}

if (!empty($search_name)) {
    $name_conditions = [];
    $search_name_pattern = "%$search_name%";
    
    if (in_array('fname', $candidate_columns)) {
        $name_conditions[] = "c.fname LIKE ?";
        $sql_params[] = $search_name_pattern;
        $types .= 's';
    }
    if (in_array('lname', $candidate_columns)) {
        $name_conditions[] = "c.lname LIKE ?";
        $sql_params[] = $search_name_pattern;
        $types .= 's';
    }
    
    if (!empty($name_conditions)) {
        $where_conditions[] = "(" . implode(" OR ", $name_conditions) . ")";
    }
}

// Add WHERE clause if we have conditions
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Default: Show only pending discipline review
if (empty($where_conditions)) {
    $sql .= " WHERE (r.discipline_status IS NULL OR r.discipline_status = '' OR r.discipline_status = 'pending')";
} else {
    $sql .= " AND (r.discipline_status IS NULL OR r.discipline_status = '' OR r.discipline_status = 'pending')";
}

$sql .= " ORDER BY r.submitted_at DESC LIMIT 50";

// Prepare and execute the query
if ($stmt = $conn->prepare($sql)) {
    if (!empty($sql_params)) {
        $stmt->bind_param($types, ...$sql_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check if candidate has discipline issues for UI display
        $candidate_info = getCandidateInfo($conn, $row['candidateID'], $candidate_id_column);
        $has_discipline_issues = false;
        
        if ($candidate_info) {
            $has_discipline_issues = hasDisciplineIssues($conn, $candidate_info['student_id'], $candidate_info['student_name']);
        }
        
        $row['has_discipline_issues'] = $has_discipline_issues;
        $requests[] = $row;
        $all_requests_count++;
        
        $discipline_status = $row['discipline_status'] ?? '';
        if (empty($discipline_status) || $discipline_status === 'pending') {
            $pending_review_count++;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Request Validity | Discipline Committee</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --sidebar-bg: linear-gradient(135deg, #1e1b4b, #312e81);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            justify-content: center;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
        }
        
        .logo-text h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .logo-text p {
            font-size: 0.85rem;
            opacity: 0.8;
            font-weight: 300;
        }
        
        .user-card {
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.05);
            margin: 20px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--warning), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: capitalize;
        }
        
        .user-info p {
            font-size: 0.85rem;
            opacity: 0.8;
            color: #e0e0e0;
        }
        
        .nav-menu {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-item.active {
            background: rgba(139, 92, 246, 0.2);
            color: white;
            border-left: 4px solid var(--primary);
        }
        
        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nav-text {
            font-size: 0.95rem;
        }
        
        .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .logout-section {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logout-btn {
            width: 100%;
            padding: 14px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            border: none;
            font-family: inherit;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        /* Header */
        .header {
            margin-bottom: 40px;
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 30px;
            background: white;
            border-radius: var(--radius);
            padding: 5px;
            box-shadow: var(--card-shadow);
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px 20px;
            background: none;
            border: none;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            background: #f9fafb;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Content Area */
        .content-area {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            padding: 40px;
            margin-bottom: 40px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .content-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Search Form */
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: #f9fafb;
            padding: 25px;
            border-radius: var(--radius-sm);
            border: 1px solid #e5e7eb;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .search-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            align-self: flex-end;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        
        .reset-btn {
            padding: 12px 25px;
            background: var(--light);
            color: var(--gray);
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            align-self: flex-end;
        }
        
        .reset-btn:hover {
            background: #f3f4f6;
        }
        
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-icon {
            font-size: 1.2rem;
        }
        
        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            padding: 25px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }
        
        .close-modal:hover {
            color: var(--danger);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        /* Tables */
        .requests-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .data-table th {
            background: #f9fafb;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: #f9fafb;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .status-resolved {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        
        .status-warning {
            background: rgba(251, 191, 36, 0.1);
            color: #ca8a04;
        }
        
        .status-suspended {
            background: rgba(249, 115, 22, 0.1);
            color: #ea580c;
        }
        
        .status-expelled {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .severity-low {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }
        
        .severity-medium {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }
        
        .severity-high {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            border: none;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--gray);
            border: 1px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f3f4f6;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #0da271);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: flex-end;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #e5e7eb;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 40px;
        }
        
        /* Discipline Warning */
        .discipline-warning {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .discipline-warning i {
            margin-top: 2px;
        }
        
        .discipline-warning-content {
            flex: 1;
        }
        
        .discipline-warning small {
            display: block;
            margin-top: 4px;
            opacity: 0.8;
        }
        
        /* Radio Input Disabled State */
        .radio-input:disabled + label {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .radio-input:disabled + label:hover {
            border-color: #e5e7eb;
            transform: none;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header,
            .user-card,
            .nav-text,
            .logo-text,
            .logout-btn span {
                display: none;
            }
            
            .logo {
                justify-content: center;
            }
            
            .user-card {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-item {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-icon {
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
                width: 280px;
            }
            
            .sidebar-header,
            .user-card,
            .nav-text,
            .logo-text,
            .logout-btn span {
                display: block;
            }
            
            .logo {
                justify-content: flex-start;
            }
            
            .nav-item {
                justify-content: flex-start;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn, .reset-btn {
                align-self: stretch;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Review Request Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-shield"></i>
                    Update Discipline Review
                </h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reviewForm" method="post">
                    <input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_discipline_status">
                    <input type="hidden" name="requestID" id="modalRequestID">
                    
                    <div class="form-group">
                        <label for="discipline_status">Discipline Status:</label>
                        <div class="radio-group" style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <input type="radio" id="status_clear" name="discipline_status" value="clear" class="radio-input" required>
                                <label for="status_clear" style="display: block; padding: 10px; border: 2px solid #e5e7eb; border-radius: var(--radius-sm); text-align: center; cursor: pointer;">
                                    <i class="fas fa-check-circle"></i><br>
                                    Clear
                                </label>
                            </div>
                            <div style="flex: 1;">
                                <input type="radio" id="status_action" name="discipline_status" value="disciplinary_action" class="radio-input" required>
                                <label for="status_action" style="display: block; padding: 10px; border: 2px solid #e5e7eb; border-radius: var(--radius-sm); text-align: center; cursor: pointer;">
                                    <i class="fas fa-times-circle"></i><br>
                                    Issues Found
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_notes">Review Notes:</label>
                        <textarea name="review_notes" id="review_notes" class="form-control" placeholder="Enter detailed review notes..." required style="min-height: 100px;"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelReview">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Update Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Discipline Record Modal -->
    <div class="modal-overlay" id="disciplineRecordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="modalTitle">Add Discipline Record</span>
                </h3>
                <button class="close-modal" id="closeRecordModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="disciplineRecordForm" method="post">
                    <input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" id="recordAction" value="add_discipline_record">
                    <input type="hidden" name="record_id" id="recordId" value="">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="student_id">Student ID *</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="student_name">Student Name *</label>
                            <input type="text" id="student_name" name="student_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="incident_date">Incident Date *</label>
                            <input type="date" id="incident_date" name="incident_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="incident_type">Incident Type *</label>
                            <input type="text" id="incident_type" name="incident_type" class="form-control" required>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="severity">Severity *</label>
                            <select id="severity" name="severity" class="form-control" required>
                                <option value="">Select Severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="resolved">Resolved</option>
                                <option value="warning_issued">Warning Issued</option>
                                <option value="suspended">Suspended</option>
                                <option value="expelled">Expelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" required style="min-height: 100px;"></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="action_taken">Action Taken</label>
                        <textarea id="action_taken" name="action_taken" class="form-control" style="min-height: 80px;"></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="resolved_date">Resolved Date</label>
                        <input type="date" id="resolved_date" name="resolved_date" class="form-control">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelRecordModal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i>
                    Confirm Delete
                </h3>
                <button class="close-modal" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this discipline record? This action cannot be undone.</p>
                <form id="deleteForm" method="post" style="margin-top: 20px;">
                    <input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="delete_discipline_record">
                    <input type="hidden" name="record_id" id="deleteRecordId">
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="discipline_committee.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Discipline Committee</h2>
                        <p>Committee Portal</p>
                    </div>
                </a>
            </div>
            
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo $initials; ?>
                </div>
                <div class="user-info">
                    <h3><?php echo $FirstName . ' ' . $MiddleName; ?></h3>
                    <p>Discipline Committee</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="discipline_committee.php" class="nav-item">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="dc_check_request.php" class="nav-item active">
                    <i class="fas fa-user-shield nav-icon"></i>
                    <span class="nav-text">Check Validity</span>
                    <?php if ($pending_review_count > 0): ?>
                    <span class="badge"><?php echo $pending_review_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dc_manage_requests.php" class="nav-item">
                    <i class="fas fa-tasks nav-icon"></i>
                    <span class="nav-text">Manage Requests</span>
                </a>
                <a href="dc_generate_report.php" class="nav-item">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text">Results</span>
                </a>
            </nav>
            
            <div class="logout-section">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="page-title">
                    <i class="fas fa-user-shield" style="color: var(--primary); font-size: 2.5rem;"></i>
                    <h1>Check Request Validity</h1>
                </div>
                <p>Review candidate requests and manage student discipline records</p>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="requests-tab">
                    <i class="fas fa-user-check"></i> Review Requests
                    <?php if ($pending_review_count > 0): ?>
                    <span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;"><?php echo $pending_review_count; ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" data-tab="records-tab">
                    <i class="fas fa-exclamation-triangle"></i> Discipline Records
                    <?php if ($discipline_stats['pending'] > 0): ?>
                    <span style="background: var(--warning); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;"><?php echo $discipline_stats['pending']; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Requests Tab -->
            <div id="requests-tab" class="tab-content active">
                <div class="content-area">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-search"></i>
                            Search and Review Requests
                        </h2>
                        <div style="color: var(--gray); font-size: 0.9rem;">
                            <?php echo $pending_review_count; ?> requests pending discipline review
                        </div>
                    </div>
                    
                    <!-- Search Form -->
                    <form method="get" class="search-form">
                        <input type="hidden" name="tab" value="requests">
                        <div class="form-group">
                            <label for="search_id">Search by ID:</label>
                            <input type="text" id="search_id" name="search_id" class="form-control" 
                                   placeholder="Enter Request ID or Candidate ID" 
                                   value="<?php echo htmlspecialchars($search_id); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="search_name">Search by Name:</label>
                            <input type="text" id="search_name" name="search_name" class="form-control" 
                                   placeholder="Enter candidate name" 
                                   value="<?php echo htmlspecialchars($search_name); ?>">
                        </div>
                        
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        
                        <a href="dc_check_request.php?tab=requests" class="reset-btn">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                    
                    <?php if (isset($_SESSION['msg'])): $msg = $_SESSION['msg']; ?>
                    <div class="alert alert-<?php echo $msg['type']; ?>">
                        <i class="fas fa-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> alert-icon"></i>
                        <div><?php echo htmlspecialchars($msg['text']); ?></div>
                    </div>
                    <?php unset($_SESSION['msg']); endif; ?>
                    
                    <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>No Requests Found</h3>
                        <p>
                            <?php if (!empty($search_id) || !empty($search_name)): ?>
                            No candidate requests match your search criteria.
                            <?php else: ?>
                            There are no pending requests for discipline review at this time.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search_id) || !empty($search_name)): ?>
                        <a href="dc_check_request.php?tab=requests" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Clear Search
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="requests-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Candidate Information</th>
                                    <th>Office</th>
                                    <th>Submitted</th>
                                    <th>Discipline Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <?php
                                // Build candidate name from available columns
                                $candidate_name_parts = [];
                                if (isset($request['cand_fname'])) $candidate_name_parts[] = $request['cand_fname'];
                                if (isset($request['cand_mname'])) $candidate_name_parts[] = $request['cand_mname'];
                                if (isset($request['cand_lname'])) $candidate_name_parts[] = $request['cand_lname'];
                                
                                $candidate_name = !empty($candidate_name_parts) ? implode(' ', $candidate_name_parts) : 'Candidate ' . htmlspecialchars($request['candidateID']);
                                $candidate_u_id = $request['cand_u_id'] ?? 'N/A';
                                $candidate_cgpa = isset($request['cgpa']) ? number_format($request['cgpa'], 2) : 'N/A';
                                $candidate_year = $request['year'] ?? 'N/A';
                                
                                // Determine discipline status display
                                $discipline_status = $request['discipline_status'] ?? '';
                                $discipline_text = 'Pending Review';
                                $discipline_class = 'status-pending';
                                
                                // Check if candidate has discipline issues
                                $has_issues = $request['has_discipline_issues'] ?? false;
                                if ($has_issues) {
                                    $discipline_text .= ' (Has Issues)';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo htmlspecialchars($request['requestID']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <strong><?php echo htmlspecialchars($candidate_name); ?></strong>
                                            <small style="color: var(--gray);">ID: <?php echo htmlspecialchars($candidate_u_id); ?></small>
                                            <div style="display: flex; gap: 10px; margin-top: 5px; font-size: 0.85rem;">
                                                <?php if ($candidate_cgpa !== 'N/A'): ?>
                                                <span style="color: var(--gray);">CGPA: <?php echo $candidate_cgpa; ?></span>
                                                <?php endif; ?>
                                                <?php if ($candidate_year !== 'N/A'): ?>
                                                <span style="color: var(--gray);">Year: <?php echo htmlspecialchars($candidate_year); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($has_issues): ?>
                                            <div style="margin-top: 5px;">
                                                <span style="color: var(--danger); font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); padding: 2px 6px; border-radius: 4px;">
                                                    <i class="fas fa-exclamation-triangle"></i> Has discipline issues
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                                            <?php echo htmlspecialchars($request['officeID'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: var(--gray); font-size: 0.9rem;">
                                            <?php echo $request['submitted_at'] ? date("M d, Y", strtotime($request['submitted_at'])) : 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $discipline_class; ?>">
                                            <?php echo $discipline_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="openReviewModal(
                                                <?php echo $request['requestID']; ?>, 
                                                '<?php echo htmlspecialchars($candidate_name); ?>', 
                                                '<?php echo htmlspecialchars($candidate_u_id); ?>', 
                                                '<?php echo $candidate_cgpa; ?>', 
                                                '<?php echo htmlspecialchars($candidate_year); ?>', 
                                                '<?php echo htmlspecialchars($request['officeID'] ?? ''); ?>', 
                                                '<?php echo $discipline_status; ?>', 
                                                '<?php echo htmlspecialchars($request['review_notes'] ?? ''); ?>',
                                                <?php echo $has_issues ? 'true' : 'false'; ?>
                                            )">
                                                <i class="fas fa-edit"></i> Review
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Discipline Records Tab -->
            <div id="records-tab" class="tab-content">
                <div class="content-area">
                    <div class="content-header">
                        <h2>
                            <i class="fas fa-exclamation-triangle"></i>
                            Student Discipline Records
                        </h2>
                        <button class="btn btn-success" onclick="openAddRecordModal()">
                            <i class="fas fa-plus"></i> Add New Record
                        </button>
                    </div>
                    
                    <!-- Statistics -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                        <div style="background: #f9fafb; padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--primary);"><?php echo $discipline_stats['total']; ?></div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Total Records</div>
                        </div>
                        <div style="background: #f9fafb; padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #d97706;"><?php echo $discipline_stats['pending']; ?></div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Pending</div>
                        </div>
                        <div style="background: #f9fafb; padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #059669;"><?php echo $discipline_stats['resolved']; ?></div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Resolved</div>
                        </div>
                        <div style="background: #f9fafb; padding: 15px; border-radius: var(--radius-sm); text-align: center;">
                            <div style="font-size: 2rem; font-weight: 700; color: #dc2626;"><?php echo $discipline_stats['expelled'] + $discipline_stats['suspended']; ?></div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Suspended/Expelled</div>
                        </div>
                    </div>
                    
                    <!-- Search Form for Records -->
                    <form method="get" class="search-form">
                        <input type="hidden" name="tab" value="records">
                        <div class="form-group">
                            <label for="search_student_id">Student ID:</label>
                            <input type="text" id="search_student_id" name="search_student_id" class="form-control" 
                                   placeholder="Enter Student ID" 
                                   value="<?php echo htmlspecialchars($search_student_id); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="search_student_name">Student Name:</label>
                            <input type="text" id="search_student_name" name="search_student_name" class="form-control" 
                                   placeholder="Enter student name" 
                                   value="<?php echo htmlspecialchars($search_student_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="search_status">Status:</label>
                            <select id="search_status" name="search_status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $search_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="resolved" <?php echo $search_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="warning_issued" <?php echo $search_status === 'warning_issued' ? 'selected' : ''; ?>>Warning Issued</option>
                                <option value="suspended" <?php echo $search_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="expelled" <?php echo $search_status === 'expelled' ? 'selected' : ''; ?>>Expelled</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; width: 100%;">
                            <div class="form-group">
                                <label for="search_from_date">From Date:</label>
                                <input type="date" id="search_from_date" name="search_from_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($search_from_date); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="search_to_date">To Date:</label>
                                <input type="date" id="search_to_date" name="search_to_date" class="form-control" 
                                       value="<?php echo htmlspecialchars($search_to_date); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        
                        <a href="dc_check_request.php?tab=records" class="reset-btn">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                    
                    <?php if (empty($discipline_records)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>No Discipline Records Found</h3>
                        <p>
                            <?php if (!empty($search_student_id) || !empty($search_student_name) || !empty($search_status)): ?>
                            No discipline records match your search criteria.
                            <?php else: ?>
                            No student discipline records found. Click "Add New Record" to create one.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search_student_id) || !empty($search_student_name) || !empty($search_status)): ?>
                        <a href="dc_check_request.php?tab=records" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Clear Search
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="requests-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Record ID</th>
                                    <th>Student Information</th>
                                    <th>Incident Details</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discipline_records as $record): ?>
                                <?php
                                // Determine status badge class
                                $status_class = '';
                                switch ($record['status']) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'resolved': $status_class = 'status-resolved'; break;
                                    case 'warning_issued': $status_class = 'status-warning'; break;
                                    case 'suspended': $status_class = 'status-suspended'; break;
                                    case 'expelled': $status_class = 'status-expelled'; break;
                                }
                                
                                // Determine severity badge class
                                $severity_class = '';
                                switch ($record['severity']) {
                                    case 'low': $severity_class = 'severity-low'; break;
                                    case 'medium': $severity_class = 'severity-medium'; break;
                                    case 'high': $severity_class = 'severity-high'; break;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo htmlspecialchars($record['record_id']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <strong><?php echo htmlspecialchars($record['student_name']); ?></strong>
                                            <small style="color: var(--gray);">ID: <?php echo htmlspecialchars($record['student_id']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column;">
                                            <strong><?php echo htmlspecialchars($record['incident_type']); ?></strong>
                                            <small style="color: var(--gray);">
                                                <?php echo date("M d, Y", strtotime($record['incident_date'])); ?>
                                            </small>
                                            <small style="margin-top: 5px;"><?php echo substr(htmlspecialchars($record['description']), 0, 50); ?>...</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $severity_class; ?>">
                                            <?php echo ucfirst($record['severity']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="openEditRecordModal(
                                                <?php echo $record['record_id']; ?>,
                                                '<?php echo htmlspecialchars($record['student_id']); ?>',
                                                '<?php echo htmlspecialchars($record['student_name']); ?>',
                                                '<?php echo $record['incident_date']; ?>',
                                                '<?php echo htmlspecialchars($record['incident_type']); ?>',
                                                '<?php echo htmlspecialchars($record['description']); ?>',
                                                '<?php echo $record['severity']; ?>',
                                                '<?php echo $record['status']; ?>',
                                                '<?php echo htmlspecialchars($record['action_taken'] ?? ''); ?>',
                                                '<?php echo $record['resolved_date'] ?? ''; ?>'
                                            )">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?php echo $record['record_id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <p>© <?php echo date("Y"); ?> Electoral Commission | Discipline Committee Portal</p>
                <p><i class="fas fa-shield-alt"></i> Ensuring Candidate Integrity and Compliance</p>
            </footer>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            menuToggle.innerHTML = sidebar.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 768) {
                if (sidebar && !sidebar.contains(event.target) && menuToggle && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
        
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // Update URL parameter
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId.replace('-tab', ''));
                history.replaceState({}, '', url);
            });
        });
        
        // Check for tab parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            const tabId = activeTab + '-tab';
            const tabButton = document.querySelector(`[data-tab="${tabId}"]`);
            if (tabButton) {
                tabButton.click();
            }
        }
        
        // Review Modal functionality
        const reviewModal = document.getElementById('reviewModal');
        const closeModal = document.getElementById('closeModal');
        const cancelReview = document.getElementById('cancelReview');
        const reviewForm = document.getElementById('reviewForm');
        
        function openReviewModal(requestID, candidateName, candidateID, candidateCGPA, candidateYear, office, currentStatus, currentNotes, hasDisciplineIssues = false) {
            // Set request ID
            document.getElementById('modalRequestID').value = requestID;
            
            // Clear any existing warnings
            const existingWarning = document.getElementById('disciplineWarning');
            if (existingWarning) {
                existingWarning.remove();
            }
            
            // Set current status if exists
            document.querySelectorAll('input[name="discipline_status"]').forEach(radio => {
                radio.checked = false;
                radio.disabled = false;
            });
            
            if (currentStatus === 'clear') {
                document.getElementById('status_clear').checked = true;
            } else if (currentStatus === 'disciplinary_action') {
                document.getElementById('status_action').checked = true;
            }
            
            // Set current notes
            document.getElementById('review_notes').value = currentNotes || '';
            
            // If candidate has discipline issues, disable "clear" option and show warning
            if (hasDisciplineIssues) {
                const clearRadio = document.getElementById('status_clear');
                clearRadio.disabled = true;
                
                // Show warning message
                const warningDiv = document.createElement('div');
                warningDiv.id = 'disciplineWarning';
                warningDiv.className = 'discipline-warning';
                warningDiv.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="discipline-warning-content">
                        <strong>Warning:</strong> This candidate has unresolved discipline issues. 
                        Cannot clear request until issues are resolved.
                    </div>
                `;
                
                // Insert warning at the top of the form
                reviewForm.insertBefore(warningDiv, reviewForm.firstChild);
                
                // If no status is selected, auto-select "disciplinary_action"
                if (!document.querySelector('input[name="discipline_status"]:checked')) {
                    document.getElementById('status_action').checked = true;
                }
            }
            
            // Show modal
            reviewModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeReviewModal() {
            reviewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Clear warning when modal closes
            const warning = document.getElementById('disciplineWarning');
            if (warning) {
                warning.remove();
            }
            
            // Re-enable all radio buttons
            document.querySelectorAll('input[name="discipline_status"]').forEach(radio => {
                radio.disabled = false;
            });
        }
        
        closeModal?.addEventListener('click', closeReviewModal);
        cancelReview?.addEventListener('click', closeReviewModal);
        
        reviewModal?.addEventListener('click', (event) => {
            if (event.target === reviewModal) {
                closeReviewModal();
            }
        });
        
        // Discipline Records Modal functionality
        const disciplineRecordModal = document.getElementById('disciplineRecordModal');
        const closeRecordModal = document.getElementById('closeRecordModal');
        const cancelRecordModal = document.getElementById('cancelRecordModal');
        const disciplineRecordForm = document.getElementById('disciplineRecordForm');
        const modalTitle = document.getElementById('modalTitle');
        const recordAction = document.getElementById('recordAction');
        const recordId = document.getElementById('recordId');
        
        function openAddRecordModal() {
            modalTitle.textContent = 'Add Discipline Record';
            recordAction.value = 'add_discipline_record';
            recordId.value = '';
            
            // Reset form
            disciplineRecordForm.reset();
            document.getElementById('resolved_date').value = '';
            
            // Show modal
            disciplineRecordModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function openEditRecordModal(id, studentId, studentName, incidentDate, incidentType, description, severity, status, actionTaken, resolvedDate) {
            modalTitle.textContent = 'Edit Discipline Record';
            recordAction.value = 'update_discipline_record';
            recordId.value = id;
            
            // Fill form
            document.getElementById('student_id').value = studentId;
            document.getElementById('student_name').value = studentName;
            document.getElementById('incident_date').value = incidentDate;
            document.getElementById('incident_type').value = incidentType;
            document.getElementById('description').value = description;
            document.getElementById('severity').value = severity;
            document.getElementById('status').value = status;
            document.getElementById('action_taken').value = actionTaken || '';
            document.getElementById('resolved_date').value = resolvedDate || '';
            
            // Show modal
            disciplineRecordModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeRecordModalFunc() {
            disciplineRecordModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        closeRecordModal?.addEventListener('click', closeRecordModalFunc);
        cancelRecordModal?.addEventListener('click', closeRecordModalFunc);
        
        disciplineRecordModal?.addEventListener('click', (event) => {
            if (event.target === disciplineRecordModal) {
                closeRecordModalFunc();
            }
        });
        
        // Delete Modal functionality
        const deleteModal = document.getElementById('deleteModal');
        const closeDeleteModal = document.getElementById('closeDeleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const deleteForm = document.getElementById('deleteForm');
        const deleteRecordId = document.getElementById('deleteRecordId');
        
        function openDeleteModal(recordId) {
            deleteRecordId.value = recordId;
            deleteModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeleteModalFunc() {
            deleteModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        closeDeleteModal?.addEventListener('click', closeDeleteModalFunc);
        cancelDelete?.addEventListener('click', closeDeleteModalFunc);
        
        deleteModal?.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeDeleteModalFunc();
            }
        });
        
        // Form validation
        reviewForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const statusSelected = document.querySelector('input[name="discipline_status"]:checked');
            const reviewNotes = document.getElementById('review_notes').value.trim();
            
            if (!statusSelected) {
                alert('Please select a discipline status.');
                return;
            }
            
            if (!reviewNotes) {
                alert('Please enter review notes.');
                return;
            }
            
            // Additional check: if trying to clear with discipline issues
            if (statusSelected.value === 'clear') {
                const clearRadio = document.getElementById('status_clear');
                if (clearRadio.disabled) {
                    alert('Cannot clear request. Candidate has unresolved discipline issues.');
                    return;
                }
            }
            
            this.submit();
        });
        
        disciplineRecordForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const requiredFields = ['student_id', 'student_name', 'incident_date', 'incident_type', 'description', 'severity', 'status'];
            for (const field of requiredFields) {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    alert(`Please fill in the ${field.replace('_', ' ')} field.`);
                    input.focus();
                    return;
                }
            }
            
            this.submit();
        });
        
        // Auto-hide messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>