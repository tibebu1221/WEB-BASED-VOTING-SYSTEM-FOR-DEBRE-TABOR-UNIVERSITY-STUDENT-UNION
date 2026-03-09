<?php
session_start();
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['u_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$action = $data['action'] ?? '';
$studentId = strtoupper(trim($data['studentId'] ?? ''));

// Validate action
if (!in_array($action, ['create', 'update', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

if ($action === 'delete') {
    // Delete student
    if (empty($studentId)) {
        echo json_encode(['success' => false, 'message' => 'Student ID required for deletion']);
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM department_students WHERE student_id = ? AND created_by LIKE ?");
    $searchPattern = $_SESSION['u_id'] . '%';
    $stmt->bind_param("ss", $studentId, $searchPattern);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// For create/update actions
$studentData = [
    'name' => trim($data['name'] ?? ''),
    'studentId' => $studentId,
    'email' => trim($data['email'] ?? ''),
    'department' => trim($data['department'] ?? ''),
    'section' => trim($data['section'] ?? ''),
    'cgpa' => floatval($data['cgpa'] ?? 0),
    'isActive' => $data['isActive'] ?? true,
    'isNominated' => $data['isNominated'] ?? false,
    'created_by' => $_SESSION['u_id']
];

// Validate required fields
$requiredFields = ['name', 'studentId', 'email', 'department', 'section'];
foreach ($requiredFields as $field) {
    if (empty($studentData[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field is required"]);
        exit();
    }
}

// Validate name length
if (strlen($studentData['name']) < 2) {
    echo json_encode(['success' => false, 'message' => 'Name must be at least 2 characters']);
    exit();
}

// Validate student ID format
if (!preg_match('/^[A-Z0-9\-_]+$/i', $studentData['studentId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Student ID format. Only letters, numbers, hyphens and underscores allowed']);
    exit();
}

// Validate student ID length
if (strlen($studentData['studentId']) < 3 || strlen($studentData['studentId']) > 50) {
    echo json_encode(['success' => false, 'message' => 'Student ID must be between 3 and 50 characters']);
    exit();
}

// Validate email
if (!filter_var($studentData['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate CGPA
if ($studentData['cgpa'] < 0 || $studentData['cgpa'] > 4) {
    echo json_encode(['success' => false, 'message' => 'CGPA must be between 0.00 and 4.00']);
    exit();
}

// Check if student ID already exists (excluding current record if editing)
$checkQuery = "SELECT student_id FROM department_students WHERE student_id = ? AND created_by LIKE ?";
$searchPattern = $_SESSION['u_id'] . '%';
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ss", $studentData['studentId'], $searchPattern);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    if ($action === 'update') {
        // For update, get the current student's ID
        $currentRow = $checkResult->fetch_assoc();
        // Check if we're updating the same record
        $editIndex = $data['editIndex'] ?? null;
        if ($editIndex !== null) {
            // Get the student at editIndex position for this user
            $positionStmt = $conn->prepare("SELECT student_id FROM department_students WHERE created_by LIKE ? ORDER BY student_name LIMIT ?,1");
            $positionStmt->bind_param("si", $searchPattern, $editIndex);
            $positionStmt->execute();
            $positionResult = $positionStmt->get_result();
            
            if ($positionRow = $positionResult->fetch_assoc()) {
                // If it's the same student, allow update
                if ($positionRow['student_id'] === $currentRow['student_id']) {
                    // It's the same student, proceed with update
                } else {
                    // Different student with same ID
                    echo json_encode(['success' => false, 'message' => 'Student ID already exists']);
                    $positionStmt->close();
                    $checkStmt->close();
                    $conn->close();
                    exit();
                }
            }
            $positionStmt->close();
        }
    } else {
        // For create, duplicate is not allowed
        echo json_encode(['success' => false, 'message' => 'Student ID already exists']);
        $checkStmt->close();
        $conn->close();
        exit();
    }
}
$checkStmt->close();

// Check for duplicate email (optional - remove if not needed)
$emailCheckStmt = $conn->prepare("SELECT student_id FROM department_students WHERE email = ? AND created_by LIKE ?");
$emailCheckStmt->bind_param("ss", $studentData['email'], $searchPattern);
$emailCheckStmt->execute();
$emailCheckResult = $emailCheckStmt->get_result();

if ($emailCheckResult->num_rows > 0) {
    if ($action === 'update') {
        $emailRow = $emailCheckResult->fetch_assoc();
        // Check if it's the same student
        if ($emailRow['student_id'] !== $studentData['studentId']) {
            echo json_encode(['success' => false, 'message' => 'Email already exists for another student']);
            $emailCheckStmt->close();
            $conn->close();
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $emailCheckStmt->close();
        $conn->close();
        exit();
    }
}
$emailCheckStmt->close();

// Insert or update student
if ($action === 'create') {
    $stmt = $conn->prepare("INSERT INTO department_students (student_id, student_name, email, department, section, cgpa, is_active, is_nominated, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdiss", 
        $studentData['studentId'],
        $studentData['name'],
        $studentData['email'],
        $studentData['department'],
        $studentData['section'],
        $studentData['cgpa'],
        $studentData['isActive'],
        $studentData['isNominated'],
        $studentData['created_by']
    );
} else {
    // Update existing student
    $stmt = $conn->prepare("UPDATE department_students SET student_name = ?, email = ?, department = ?, section = ?, cgpa = ?, is_active = ?, is_nominated = ? WHERE student_id = ? AND created_by LIKE ?");
    $stmt->bind_param("ssssdiss", 
        $studentData['name'],
        $studentData['email'],
        $studentData['department'],
        $studentData['section'],
        $studentData['cgpa'],
        $studentData['isActive'],
        $studentData['isNominated'],
        $studentData['studentId'],
        $searchPattern
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>