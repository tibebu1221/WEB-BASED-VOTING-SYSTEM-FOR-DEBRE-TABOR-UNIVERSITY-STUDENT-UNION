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
        // Look for columns that might contain candidate ID
        if (strpos($field, 'cand') !== false || strpos($field, 'id') !== false) {
            $candidate_id_column = $row['Field'];
            break;
        }
    }
}

// ---------- 5. FETCH REQUESTS FOR DISCIPLINE REVIEW ----------
$requests = [];
$pending_review_count = 0;
$cleared_count = 0;
$disciplinary_action_count = 0;

// First, let's check what columns exist in the candidate table
$candidate_columns = [];
$result = $conn->query("SHOW COLUMNS FROM candidate");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $candidate_columns[] = $row['Field'];
    }
}

// Also check what columns exist in the request table
$request_columns = [];
$result = $conn->query("SHOW COLUMNS FROM request");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $request_columns[] = $row['Field'];
    }
}

// Build query based on available columns - Get all requests that need discipline review
$sql = "SELECT r.requestID, r.candidateID, r.officeID, r.status, r.submitted_at, r.discipline_status";

// Add review_notes column only if it exists
if (in_array('review_notes', $request_columns)) {
    $sql .= ", r.review_notes";
}

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
$sql .= " WHERE (r.discipline_status IS NULL OR r.discipline_status = '')";
$sql .= " ORDER BY r.submitted_at DESC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
        if (isset($row['discipline_status']) && $row['discipline_status'] === 'clear') {
            $cleared_count++;
        } elseif (isset($row['discipline_status']) && $row['discipline_status'] === 'disciplinary_action') {
            $disciplinary_action_count++;
        } else {
            $pending_review_count++;
        }
    }
    $result->free();
}

// Get total reviewed count for stats
$total_reviewed = $cleared_count + $disciplinary_action_count;

// ---------- 6. HANDLE DISCIPLINE REVIEW ACTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf_token, $_POST['token'] ?? '')) {
    if (isset($_POST['action']) && $_POST['action'] === 'update_discipline_status') {
        $requestID = intval($_POST['requestID']);
        $discipline_status = $_POST['discipline_status'];
        $review_notes = trim($_POST['review_notes'] ?? '');
        
        // Validate status
        $valid_statuses = ['clear', 'disciplinary_action'];
        if (!in_array($discipline_status, $valid_statuses)) {
            $_SESSION['msg'] = ['type'=>'error','text'=>'Invalid discipline status!'];
            header("Location: dc_rev_requests.php");
            exit();
        }
        
        // Check if review_notes column exists
        if (in_array('review_notes', $request_columns)) {
            // Check if reviewed_at column exists
            if (in_array('reviewed_at', $request_columns)) {
                // Update with review_notes and reviewed_at
                $stmt = $conn->prepare("UPDATE request SET discipline_status = ?, review_notes = ?, reviewed_at = NOW() WHERE requestID = ?");
                $stmt->bind_param("ssi", $discipline_status, $review_notes, $requestID);
            } else {
                // Update with only review_notes
                $stmt = $conn->prepare("UPDATE request SET discipline_status = ?, review_notes = ? WHERE requestID = ?");
                $stmt->bind_param("ssi", $discipline_status, $review_notes, $requestID);
            }
        } else {
            // Check if reviewed_at column exists
            if (in_array('reviewed_at', $request_columns)) {
                // Update without review_notes but with reviewed_at
                $stmt = $conn->prepare("UPDATE request SET discipline_status = ?, reviewed_at = NOW() WHERE requestID = ?");
                $stmt->bind_param("si", $discipline_status, $requestID);
            } else {
                // Update without review_notes or reviewed_at
                $stmt = $conn->prepare("UPDATE request SET discipline_status = ? WHERE requestID = ?");
                $stmt->bind_param("si", $discipline_status, $requestID);
            }
        }
        
        if ($stmt->execute()) {
            $_SESSION['msg'] = ['type'=>'success','text'=>'Discipline review submitted successfully!'];
        } else {
            $_SESSION['msg'] = ['type'=>'error','text'=>'Error updating discipline status!'];
        }
        
        $stmt->close();
        header("Location: dc_rev_requests.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Requests | Discipline Committee</title>
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
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-pending {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }
        
        .icon-cleared {
            background: linear-gradient(135deg, var(--success), #059669);
        }
        
        .icon-disciplinary {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }
        
        .icon-total {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }
        
        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        /* Main Content Area */
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
        
        /* Review Form Modal */
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
            max-width: 500px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-option {
            flex: 1;
        }
        
        .radio-input {
            display: none;
        }
        
        .radio-label {
            display: block;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-sm);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }
        
        .radio-input:checked + .radio-label {
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.05);
        }
        
        .radio-label.cleared {
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.3);
        }
        
        .radio-input:checked + .radio-label.cleared {
            border-color: var(--success);
            background: rgba(16, 185, 129, 0.1);
        }
        
        .radio-label.disciplinary {
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .radio-input:checked + .radio-label.disciplinary {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
        }
        
        /* Requests Table */
        .requests-container {
            overflow-x: auto;
        }
        
        .requests-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        .requests-table th {
            background: #f9fafb;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .requests-table td {
            padding: 20px 15px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        .requests-table tr:hover {
            background: #f9fafb;
        }
        
        .request-id {
            font-weight: 600;
            color: var(--primary);
        }
        
        .candidate-id {
            font-family: monospace;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .office-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .date-time {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
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
        
        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .discipline-status {
            font-weight: 600;
        }
        
        .discipline-clear {
            color: var(--success);
        }
        
        .discipline-action {
            color: var(--danger);
        }
        
        .discipline-pending {
            color: var(--warning);
        }
        
        /* Review Button */
        .review-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            font-size: 0.9rem;
        }
        
        .review-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        
        .review-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
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
        
        /* Candidate Info Card */
        .candidate-info {
            background: #f9fafb;
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .candidate-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .candidate-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 1.2rem;
        }
        
        .candidate-details h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .candidate-details p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .candidate-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid #e5e7eb;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        /* Notes Preview */
        .notes-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--gray);
            font-size: 0.9rem;
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
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .requests-table {
                display: block;
                overflow-x: auto;
            }
            
            .radio-group {
                flex-direction: column;
            }
            
            .candidate-stats {
                grid-template-columns: repeat(2, 1fr);
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
    
    <!-- Review Form Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-shield"></i>
                    Review Candidate Discipline
                </h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="candidateInfoCard" class="candidate-info">
                    <!-- Candidate info will be loaded here via JavaScript -->
                </div>
                
                <form id="reviewForm" method="post">
                    <input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="update_discipline_status">
                    <input type="hidden" name="requestID" id="modalRequestID">
                    
                    <div class="form-group">
                        <label for="discipline_status">Discipline Status:</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="status_clear" name="discipline_status" value="clear" class="radio-input" required>
                                <label for="status_clear" class="radio-label cleared">
                                    <i class="fas fa-check-circle"></i><br>
                                    Clear
                                </label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="status_action" name="discipline_status" value="disciplinary_action" class="radio-input" required>
                                <label for="status_action" class="radio-label disciplinary">
                                    <i class="fas fa-times-circle"></i><br>
                                    Issues Found
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_notes">Review Notes:</label>
                        <textarea name="review_notes" id="review_notes" class="form-control" placeholder="Enter detailed review notes..." required></textarea>
                        <small style="color: var(--gray); font-size: 0.85rem;">Please provide detailed notes about your discipline review findings.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelReview">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Review
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
                <a href="e_officer.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div class="logo-text">
                        <h2>Election Committee Panel</h2>
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
                <a href="e_officer.php" class="nav-item">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="dc_rev_requests.php" class="nav-item active">
                    <i class="fas fa-user-shield nav-icon"></i>
                    <span class="nav-text">Review Requests</span>
                    <?php if ($pending_review_count > 0): ?>
                    <span class="badge"><?php echo $pending_review_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="dc_requests.php" class="nav-item">
                    <i class="fas fa-tasks nav-icon"></i>
                    <span class="nav-text">All Requests</span>
                </a>
                <a href="o_result.php" class="nav-item">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text">Results</span>
                </a>
                <a href="o_comment.php" class="nav-item">
                    <i class="fas fa-comments nav-icon"></i>
                    <span class="nav-text">Comments</span>
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
                    <h1>Review Candidate Requests</h1>
                </div>
                <p>Review candidate discipline records and provide clearance status</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_review_count; ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-cleared">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $cleared_count; ?></h3>
                        <p>Cleared</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-disciplinary">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $disciplinary_action_count; ?></h3>
                        <p>Issues Found</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($requests); ?></h3>
                        <p>Total for Review</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="content-area">
                <div class="content-header">
                    <h2>
                        <i class="fas fa-users"></i>
                        Candidate Requests for Review
                    </h2>
                    <div style="color: var(--gray); font-size: 0.9rem;">
                        Showing requests that need discipline review
                    </div>
                </div>
                
                <?php if (isset($_SESSION['msg'])): $msg = $_SESSION['msg']; ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <i class="fas fa-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> alert-icon"></i>
                    <div><?php echo htmlspecialchars($msg['text']); ?></div>
                </div>
                <?php unset($_SESSION['msg']); endif; ?>
                
                <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>All Caught Up!</h3>
                    <p>There are no candidate requests pending discipline review at this time.</p>
                    <a href="e_officer.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Return to Dashboard
                    </a>
                </div>
                <?php else: ?>
                <div class="requests-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Candidate Information</th>
                                <th>Office</th>
                                <th>Submitted</th>
                                <th>Nomination Status</th>
                                <th>Discipline Status</th>
                                <th>Review Notes</th>
                                <th>Action</th>
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
                            $discipline_class = '';
                            $discipline_text = 'Pending Review';
                            
                            if ($discipline_status === 'clear') {
                                $discipline_class = 'discipline-clear';
                                $discipline_text = 'Cleared';
                            } elseif ($discipline_status === 'disciplinary_action') {
                                $discipline_class = 'discipline-action';
                                $discipline_text = 'Issues Found';
                            } else {
                                $discipline_class = 'discipline-pending';
                            }
                            
                            // Get review notes if available
                            $review_notes = isset($request['review_notes']) ? htmlspecialchars($request['review_notes']) : 'No review notes';
                            ?>
                            <tr>
                                <td>
                                    <span class="request-id">#<?php echo htmlspecialchars($request['requestID']); ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <strong><?php echo htmlspecialchars($candidate_name); ?></strong>
                                        <small class="candidate-id">ID: <?php echo htmlspecialchars($candidate_u_id); ?></small>
                                        <div style="display: flex; gap: 10px; margin-top: 5px; font-size: 0.85rem;">
                                            <?php if ($candidate_cgpa !== 'N/A'): ?>
                                            <span style="color: var(--gray);">CGPA: <?php echo $candidate_cgpa; ?></span>
                                            <?php endif; ?>
                                            <?php if ($candidate_year !== 'N/A'): ?>
                                            <span style="color: var(--gray);">Year: <?php echo htmlspecialchars($candidate_year); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="office-badge"><?php echo htmlspecialchars($request['officeID'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="date-time">
                                        <?php echo $request['submitted_at'] ? date("M d, Y", strtotime($request['submitted_at'])) : 'N/A'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="discipline-status <?php echo $discipline_class; ?>">
                                        <?php echo $discipline_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="notes-preview" title="<?php echo $review_notes; ?>">
                                        <?php echo $review_notes; ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="review-btn" onclick="openReviewModal(<?php echo $request['requestID']; ?>, '<?php echo htmlspecialchars($candidate_name); ?>', '<?php echo htmlspecialchars($candidate_u_id); ?>', '<?php echo $candidate_cgpa; ?>', '<?php echo htmlspecialchars($candidate_year); ?>', '<?php echo htmlspecialchars($request['officeID'] ?? ''); ?>')">
                                        <i class="fas fa-edit"></i> Review
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
        
        // Review Modal functionality
        const reviewModal = document.getElementById('reviewModal');
        const closeModal = document.getElementById('closeModal');
        const cancelReview = document.getElementById('cancelReview');
        const reviewForm = document.getElementById('reviewForm');
        const candidateInfoCard = document.getElementById('candidateInfoCard');
        const modalRequestID = document.getElementById('modalRequestID');
        
        function openReviewModal(requestID, candidateName, candidateID, candidateCGPA, candidateYear, office) {
            // Set request ID
            modalRequestID.value = requestID;
            
            // Clear any previous selections
            document.querySelectorAll('input[name="discipline_status"]').forEach(radio => {
                radio.checked = false;
            });
            document.getElementById('review_notes').value = '';
            
            // Build candidate info card
            candidateInfoCard.innerHTML = `
                <div class="candidate-header">
                    <div class="candidate-avatar">
                        ${candidateName.charAt(0).toUpperCase()}
                    </div>
                    <div class="candidate-details">
                        <h4>${candidateName}</h4>
                        <p>Student ID: ${candidateID}</p>
                    </div>
                </div>
                <div class="candidate-stats">
                    <div class="stat-item">
                        <div class="stat-value">${candidateCGPA}</div>
                        <div class="stat-label">CGPA</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${candidateYear}</div>
                        <div class="stat-label">Year</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${office}</div>
                        <div class="stat-label">Office</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">#${requestID}</div>
                        <div class="stat-label">Request ID</div>
                    </div>
                </div>
            `;
            
            // Show modal
            reviewModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeReviewModal() {
            reviewModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal events
        closeModal?.addEventListener('click', closeReviewModal);
        cancelReview?.addEventListener('click', closeReviewModal);
        
        // Close modal when clicking outside
        reviewModal?.addEventListener('click', (event) => {
            if (event.target === reviewModal) {
                closeReviewModal();
            }
        });
        
        // Handle form submission
        reviewForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
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
            
            // Submit form
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