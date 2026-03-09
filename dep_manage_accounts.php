<?php
session_start();
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['u_id'])) {
    header("Location: login.php?error=not_logged_in");
    exit();
}

// Define valid department roles
$valid_department_roles = [
    'department', 'Department', 'dep', 'DEP', 
    'registrar', 'Registrar', 'Department Officer', 'dept'
];

// Check if user has department role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $valid_department_roles, true)) {
    echo "<script>
        alert('Access Denied: Department authorization required. Please login as department staff.');
        window.location.href = 'login.php?error=department_only';
    </script>";
    exit();
}

// Set user_type
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = 'department';
}

// Clear any office-related session variables
unset($_SESSION['office_logged_in']);
unset($_SESSION['officer_role']);
unset($_SESSION['office_id']);

// Handle form submission
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Check if e_requests table exists, create if not
$createRequestsTable = "CREATE TABLE IF NOT EXISTS e_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
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

// Create students table for persistence with UNIQUE constraint
$createStudentsTable = "CREATE TABLE IF NOT EXISTS department_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    student_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    department VARCHAR(100) NOT NULL,
    section VARCHAR(10) NOT NULL,
    cgpa DECIMAL(3,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_nominated BOOLEAN DEFAULT FALSE,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_is_active (is_active),
    INDEX idx_student_id (student_id),
    UNIQUE KEY unique_student_id_created_by (student_id, created_by)
)";
$conn->query($createStudentsTable);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'submit_nominees') {
        // Decode nominees data
        $nomineesData = json_decode($_POST['nominees_data'], true);
        
        if (empty($nomineesData)) {
            $error = "No nominees selected for submission";
        } else {
            // Validate each nominee
            $validNominees = [];
            $submittedStudentIds = [];
            
            foreach ($nomineesData as $nominee) {
                // Check for duplicate student IDs in current submission
                if (in_array($nominee['studentId'], $submittedStudentIds)) {
                    continue; // Skip duplicate in same submission
                }
                
                if (!empty($nominee['studentId']) && !empty($nominee['name']) && 
                    isset($nominee['cgpa']) && $nominee['cgpa'] >= 2.75 && 
                    isset($nominee['isActive']) && $nominee['isActive']) {
                    
                    // Validate student ID format
                    if (!preg_match('/^[A-Z0-9\-_]+$/i', $nominee['studentId'])) {
                        continue; // Invalid ID format
                    }
                    
                    $validNominees[] = $nominee;
                    $submittedStudentIds[] = $nominee['studentId'];
                }
            }
            
            if (empty($validNominees)) {
                $error = "No valid eligible nominees found. Ensure CGPA ≥ 2.75 and active status.";
            } else {
                // Check for duplicate nominations already in e_requests
                $placeholders = str_repeat('?,', count($submittedStudentIds) - 1) . '?';
                $checkDuplicatesStmt = $conn->prepare("SELECT student_id FROM e_requests WHERE student_id IN ($placeholders) AND status = 'pending'");
                $checkDuplicatesStmt->bind_param(str_repeat('s', count($submittedStudentIds)), ...$submittedStudentIds);
                $checkDuplicatesStmt->execute();
                $duplicateResult = $checkDuplicatesStmt->get_result();
                $existingPendingIds = [];
                while ($row = $duplicateResult->fetch_assoc()) {
                    $existingPendingIds[] = $row['student_id'];
                }
                $checkDuplicatesStmt->close();
                
                // Insert into e_requests table
                $successCount = 0;
                $failedCount = 0;
                $duplicateCount = 0;
                $submitted_by = $_SESSION['u_id'] . ' - ' . ($_SESSION['name'] ?? 'Department Officer');
                
                foreach ($validNominees as $nominee) {
                    // Skip if already pending
                    if (in_array($nominee['studentId'], $existingPendingIds)) {
                        $duplicateCount++;
                        continue;
                    }
                    
                    // Validate student ID uniqueness in e_requests
                    $checkStmt = $conn->prepare("SELECT id FROM e_requests WHERE student_id = ? AND status = 'pending'");
                    $checkStmt->bind_param("s", $nominee['studentId']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows === 0) {
                        // Insert new request
                        $stmt = $conn->prepare("INSERT INTO e_requests (student_id, student_name, department, cgpa, submitted_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssds", 
                            $nominee['studentId'],
                            $nominee['name'],
                            $nominee['department'],
                            $nominee['cgpa'],
                            $submitted_by
                        );
                        
                        if ($stmt->execute()) {
                            $successCount++;
                            
                            // Update nomination status in department_students
                            $updateStmt = $conn->prepare("UPDATE department_students SET is_nominated = TRUE WHERE student_id = ? AND created_by LIKE ?");
                            $searchPattern = $_SESSION['u_id'] . '%';
                            $updateStmt->bind_param("ss", $nominee['studentId'], $searchPattern);
                            $updateStmt->execute();
                            $updateStmt->close();
                            
                            // Insert into nomination_logs for tracking
                            $logStmt = $conn->prepare("INSERT INTO nomination_logs (candidate_id, candidate_name, action, performed_by, department, cgpa, status) VALUES (?, ?, 'department_submission', ?, ?, ?, 'pending')");
                            $logStmt->bind_param("ssssd", 
                                $nominee['studentId'],
                                $nominee['name'],
                                $_SESSION['u_id'],
                                $nominee['department'],
                                $nominee['cgpa']
                            );
                            $logStmt->execute();
                            $logStmt->close();
                        } else {
                            $failedCount++;
                        }
                        $stmt->close();
                    } else {
                        $duplicateCount++;
                    }
                    $checkStmt->close();
                }
                
                if ($successCount > 0) {
                    $success = "✅ Successfully submitted $successCount nominees to Election Officer for review.";
                    if ($failedCount > 0) {
                        $success .= " ($failedCount failed)";
                    }
                    if ($duplicateCount > 0) {
                        $success .= " ($duplicateCount already pending)";
                    }
                    
                    // Store in session for confirmation
                    $_SESSION['last_nomination_count'] = $successCount;
                    $_SESSION['last_nomination_time'] = time();
                    
                    // Redirect to show success
                    header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode($success));
                    exit();
                } else {
                    $error = "❌ Failed to submit nominees. All nominees may already be pending review.";
                }
            }
        }
    }
}

// Get pending nominations count
$pendingCount = 0;
$pendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM e_requests WHERE status = 'pending'");
$pendingStmt->execute();
$pendingResult = $pendingStmt->get_result();
if ($row = $pendingResult->fetch_assoc()) {
    $pendingCount = $row['count'];
}
$pendingStmt->close();

// Get recently submitted nominations WITH STATUS
$recentSubmissions = [];
$recentStmt = $conn->prepare("SELECT id, student_id, student_name, department, cgpa, submitted_at, status, processed_at, notes FROM e_requests WHERE submitted_by LIKE ? ORDER BY submitted_at DESC LIMIT 5");
$searchPattern = $_SESSION['u_id'] . '%';
$recentStmt->bind_param("s", $searchPattern);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
while ($row = $recentResult->fetch_assoc()) {
    $recentSubmissions[] = $row;
}
$recentStmt->close();

// Get all submitted nominations with search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$dept_filter = isset($_GET['dept']) ? $_GET['dept'] : 'all';

// Build query for all submissions
$query = "SELECT id, student_id, student_name, department, cgpa, submitted_at, status, processed_at, notes, submitted_by 
          FROM e_requests 
          WHERE submitted_by LIKE ?";
          
$params = array($searchPattern);
$types = "s";

// Add search filters
if (!empty($search)) {
    $query .= " AND (student_name LIKE ? OR student_id LIKE ? OR department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add department filter
if ($dept_filter !== 'all') {
    $query .= " AND department = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

$query .= " ORDER BY submitted_at DESC";

// Prepare and execute query
$allSubmissionsStmt = $conn->prepare($query);

if (count($params) > 0) {
    // Bind parameters using spread operator
    $allSubmissionsStmt->bind_param($types, ...$params);
}

$allSubmissionsStmt->execute();
$allSubmissionsResult = $allSubmissionsStmt->get_result();
$allSubmissions = [];
while ($row = $allSubmissionsResult->fetch_assoc()) {
    $allSubmissions[] = $row;
}
$allSubmissionsStmt->close();

// Get counts for statistics
$countStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM e_requests WHERE submitted_by LIKE ?");
$countStmt->bind_param("s", $searchPattern);
$countStmt->execute();
$countResult = $countStmt->get_result();
$counts = $countResult->fetch_assoc();
if (!$counts) {
    $counts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
$countStmt->close();

// Get unique departments for filter
$deptStmt = $conn->prepare("SELECT DISTINCT department FROM e_requests WHERE submitted_by LIKE ? ORDER BY department");
$deptStmt->bind_param("s", $searchPattern);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();
$uniqueDepartments = [];
while ($row = $deptResult->fetch_assoc()) {
    $uniqueDepartments[] = $row['department'];
}
$deptStmt->close();

// Load students from database
$studentsFromDB = [];
$loadStmt = $conn->prepare("SELECT student_id, student_name, email, department, section, cgpa, is_active, is_nominated FROM department_students WHERE created_by LIKE ? ORDER BY student_name");
$loadStmt->bind_param("s", $searchPattern);
$loadStmt->execute();
$loadResult = $loadStmt->get_result();

// Validate student IDs for uniqueness within this user's records
$existingIds = [];
while ($row = $loadResult->fetch_assoc()) {
    // Check for duplicates in loaded data
    if (in_array($row['student_id'], $existingIds)) {
        $row['has_duplicate_id'] = true;
        $row['duplicate_error'] = "DUPLICATE ID - Please fix!";
    } else {
        $row['has_duplicate_id'] = false;
        $existingIds[] = $row['student_id'];
    }
    $studentsFromDB[] = $row;
}
$loadStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management & Election Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Status Visualization Styles */
        .status-visual {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 100px;
            justify-content: center;
        }
        
        .status-pending-visual {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 1px solid #f59e0b;
            animation: pulse 2s infinite;
        }
        
        .status-approved-visual {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .status-rejected-visual {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #7f1d1d;
            border: 1px solid #ef4444;
        }
        
        .status-card {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 120px;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .timeline-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 300px;
            margin: 0 auto;
            position: relative;
        }
        
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        
        .timeline-step::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            width: 80%;
            height: 3px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .timeline-step:last-child::after {
            display: none;
        }
        
        .timeline-step.completed::after {
            background: linear-gradient(to right, #10b981, #34d399);
        }
        
        .timeline-step.current::after {
            background: linear-gradient(to right, #10b981, #d1d5db);
        }
        
        .step-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            z-index: 2;
            position: relative;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .step-dot i {
            font-size: 12px;
        }
        
        .timeline-step.completed .step-dot {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .timeline-step.current .step-dot {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            animation: pulse 1.5s infinite;
        }
        
        .step-label {
            font-size: 11px;
            margin-top: 6px;
            text-align: center;
            color: #6b7280;
            font-weight: 500;
            max-width: 80px;
            line-height: 1.2;
        }
        
        .step-date {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 2px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        /* Table Responsive */
        .table-container {
            overflow-x: auto;
        }
        
        /* Status Filter Tabs */
        .status-tab {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .status-tab:hover {
            transform: translateY(-2px);
        }
        
        .status-tab.active {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .status-tab-pending {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        
        .status-tab-pending.active {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .status-tab-approved {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        .status-tab-approved.active {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .status-tab-rejected {
            background: #fee2e2;
            color: #7f1d1d;
            border-color: #fecaca;
        }
        
        .status-tab-rejected.active {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #7f1d1d;
        }
        
        .status-tab-all {
            background: #e0e7ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }
        
        .status-tab-all.active {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #3730a3;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900" x-data="studentManager()">

    <!-- Success Toast Notification -->
    <?php if($success): ?>
    <div class="toast" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)">
        <div class="bg-green-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <div>
                <p class="font-bold">Success!</p>
                <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button @click="show = false" class="ml-4 text-white hover:text-green-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Error Toast Notification -->
    <?php if($error): ?>
    <div class="toast" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)">
        <div class="bg-red-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <div>
                <p class="font-bold">Error!</p>
                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button @click="show = false" class="ml-4 text-white hover:text-red-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-6">
                <a href="dep.php" class="flex items-center text-gray-600 hover:text-indigo-600 transition-colors font-medium text-sm group">
                    <div class="bg-gray-100 group-hover:bg-indigo-50 p-2 rounded-lg mr-2 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    Back to Home
                </a>
                <div class="h-6 w-px bg-gray-200"></div>
                <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">
                    Class Representative Selection
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="flex items-center text-xs font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></span>
                    Pending: <?php echo $counts['pending'] ?? 0; ?> nominations
                </span>
                <div class="text-xs text-gray-500">
                    Logged in as: 
                    <span class="font-bold text-indigo-600">
                        Department Officer
                        (<?php echo isset($_SESSION['u_id']) ? 'ID: ' . $_SESSION['u_id'] : 'Not logged in'; ?>)
                    </span>
                </div>
                <a href="logout.php" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto p-6 md:p-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <nav class="flex text-gray-400 text-xs mb-2 font-bold uppercase tracking-widest">
                    <a href="dep.php" class="hover:text-indigo-600">Department</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-800">Student Management</span>
                </nav>
                <h2 class="text-4xl font-black text-gray-900 tracking-tight">Student Directory</h2>
                <p class="text-gray-500 mt-1">Add, edit, and nominate student leaders for Class Representative elections</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <div class="flex items-center text-sm bg-amber-50 text-amber-700 px-3 py-1.5 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>Minimum CGPA requirement: 2.75 for nomination</span>
                    </div>
                    <div class="flex items-center text-sm bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>
                        <span>Filter by: </span>
                        <select x-model="selectedDepartment" @change="filterStudents()" class="ml-2 bg-transparent border-none outline-none">
                            <option value="all">All Departments</option>
                            <option value="CS">Computer Science</option>
                            <option value="EE">Electrical Engineering</option>
                            <option value="ME">Mechanical Engineering</option>
                            <option value="CE">Civil Engineering</option>
                            <option value="BA">Business Administration</option>
                            <option value="IT">Information Technology</option>
                        </select>
                    </div>
                    <div class="flex items-center text-sm bg-green-50 text-green-700 px-3 py-1.5 rounded-lg">
                        <i class="fas fa-users mr-2"></i>
                        <span x-text="`Total: ${students.length} students`"></span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col gap-3">
                <button @click="openModal('add')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold transition-all flex items-center shadow-lg shadow-indigo-200 active:scale-95">
                    <i class="fas fa-user-plus mr-2"></i> Add New Student
                </button>
                <div class="text-xs text-gray-500 text-center">
                    <span class="font-bold" x-text="`Filtered: ${filteredStudents.length} students`"></span>
                </div>
            </div>
        </div>

        <!-- All Submitted Nominations Section -->
        <div class="mb-8 bg-white rounded-2xl shadow-sm border border-blue-100 overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-blue-800 flex items-center">
                            <i class="fas fa-list-check mr-2"></i>
                            All Submitted Nominations
                            <span class="ml-2 text-sm bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                                <?php echo $counts['total'] ?? 0; ?> total
                            </span>
                        </h3>
                        <p class="text-sm text-blue-600">Track all your nomination requests with advanced filtering</p>
                    </div>
                    
                    <!-- Search Box -->
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="flex flex-col sm:flex-row gap-2">
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search by name, ID, or department..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none w-full sm:w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <div class="flex gap-2">
                            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <select name="dept" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="all" <?php echo $dept_filter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                                <?php foreach($uniqueDepartments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $dept_filter === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            <?php if($search || $status_filter !== 'all' || $dept_filter !== 'all'): ?>
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-times mr-1"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                </form>
                </div>
                
                <!-- Status Statistics -->
                <div class="flex flex-wrap gap-3 mt-4">
                    <div class="status-tab status-tab-all <?php echo $status_filter === 'all' ? 'active' : ''; ?>" 
                         onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?status=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dept_filter !== 'all' ? '&dept=' . urlencode($dept_filter) : ''; ?>'">
                        <i class="fas fa-layer-group mr-2"></i>
                        All (<?php echo $counts['total'] ?? 0; ?>)
                    </div>
                    <div class="status-tab status-tab-pending <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" 
                         onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?status=pending<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dept_filter !== 'all' ? '&dept=' . urlencode($dept_filter) : ''; ?>'">
                        <i class="fas fa-clock mr-2"></i>
                        Pending (<?php echo $counts['pending'] ?? 0; ?>)
                    </div>
                    <div class="status-tab status-tab-approved <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" 
                         onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?status=approved<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dept_filter !== 'all' ? '&dept=' . urlencode($dept_filter) : ''; ?>'">
                        <i class="fas fa-check-circle mr-2"></i>
                        Approved (<?php echo $counts['approved'] ?? 0; ?>)
                    </div>
                    <div class="status-tab status-tab-rejected <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" 
                         onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?status=rejected<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dept_filter !== 'all' ? '&dept=' . urlencode($dept_filter) : ''; ?>'">
                        <i class="fas fa-times-circle mr-2"></i>
                        Rejected (<?php echo $counts['rejected'] ?? 0; ?>)
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <?php if (!empty($allSubmissions)): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGPA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Timeline</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($allSubmissions as $submission): 
                            // Determine status visual
                            $statusClass = '';
                            $statusIcon = '';
                            $statusText = '';
                            
                            switch($submission['status']) {
                                case 'approved':
                                    $statusClass = 'status-approved-visual';
                                    $statusIcon = 'fa-check-circle';
                                    $statusText = 'APPROVED';
                                    break;
                                case 'rejected':
                                    $statusClass = 'status-rejected-visual';
                                    $statusIcon = 'fa-times-circle';
                                    $statusText = 'REJECTED';
                                    break;
                                default:
                                    $statusClass = 'status-pending-visual';
                                    $statusIcon = 'fa-clock';
                                    $statusText = 'PENDING';
                            }
                        ?>
                        <tr class="hover:bg-blue-50/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold mr-3">
                                        <?php echo substr($submission['student_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <span class="font-medium block"><?php echo htmlspecialchars($submission['student_name']); ?></span>
                                        <span class="text-xs text-gray-500">
                                            Submitted: <?php echo date('M d, Y', strtotime($submission['submitted_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600 font-mono">
                                <?php echo htmlspecialchars($submission['student_id']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                    <?php echo htmlspecialchars($submission['department']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold <?php echo $submission['cgpa'] >= 2.75 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($submission['cgpa'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo date('d M Y', strtotime($submission['submitted_at'])); ?>
                                <div class="text-xs text-gray-400">
                                    <?php echo date('h:i A', strtotime($submission['submitted_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="timeline-container">
                                    <div class="timeline-step completed">
                                        <div class="step-dot">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <div class="step-label">Submitted</div>
                                        <div class="step-date"><?php echo date('M d', strtotime($submission['submitted_at'])); ?></div>
                                    </div>
                                    
                                    <?php if ($submission['status'] === 'pending'): ?>
                                    <div class="timeline-step current">
                                        <div class="step-dot">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="step-label">Under Review</div>
                                    </div>
                                    <?php else: ?>
                                    <div class="timeline-step completed">
                                        <div class="step-dot">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="step-label">Reviewed</div>
                                        <div class="step-date"><?php echo $submission['processed_at'] ? date('M d', strtotime($submission['processed_at'])) : ''; ?></div>
                                    </div>
                                    
                                    <?php if ($submission['status'] === 'approved'): ?>
                                    <div class="timeline-step current">
                                        <div class="step-dot">
                                            <i class="fas fa-share"></i>
                                        </div>
                                        <div class="step-label">Forwarded</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="status-visual <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    <span><?php echo $statusText; ?></span>
                                </div>
                                <?php if ($submission['status'] !== 'pending' && $submission['processed_at']): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo date('M d, H:i', strtotime($submission['processed_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <?php if ($submission['status'] === 'pending'): ?>
                                        <button class="text-xs text-blue-600 hover:text-blue-800 font-medium px-3 py-1 bg-blue-50 rounded-lg transition-colors" 
                                                onclick="viewRequestDetails('<?php echo $submission['id']; ?>', '<?php echo $submission['student_name']; ?>')">
                                            <i class="fas fa-eye mr-1"></i> Track Status
                                        </button>
                                    <?php elseif ($submission['status'] === 'approved'): ?>
                                        <span class="text-xs text-green-600 font-medium px-3 py-1 bg-green-50 rounded-lg text-center">
                                            <i class="fas fa-check mr-1"></i> Forwarded
                                        </span>
                                    <?php elseif ($submission['status'] === 'rejected'): ?>
                                        <button class="text-xs text-red-600 hover:text-red-800 font-medium px-3 py-1 bg-red-50 rounded-lg transition-colors" 
                                                onclick="showRejectionReason('<?php echo addslashes($submission['notes'] ?? 'No reason provided'); ?>', '<?php echo $submission['student_name']; ?>')">
                                            <i class="fas fa-comment-alt mr-1"></i> View Reason
                                        </button>
                                        <button class="text-xs text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1 bg-indigo-50 rounded-lg transition-colors mt-1"
                                                onclick="resubmitNomination('<?php echo $submission['student_id']; ?>', '<?php echo $submission['student_name']; ?>')">
                                            <i class="fas fa-redo mr-1"></i> Resubmit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-inbox text-5xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-600 mb-2">No nominations found</h3>
                    <p class="text-gray-500">
                        <?php if($search || $status_filter !== 'all' || $dept_filter !== 'all'): ?>
                            Try adjusting your search or filters
                        <?php else: ?>
                            You haven't submitted any nominations yet
                        <?php endif; ?>
                    </p>
                    <?php if($search || $status_filter !== 'all' || $dept_filter !== 'all'): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" 
                       class="mt-4 inline-block text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-times mr-1"></i> Clear all filters
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($allSubmissions)): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Showing <?php echo count($allSubmissions); ?> of <?php echo $counts['total'] ?? 0; ?> nominations
                        <?php if($search): ?>
                            matching "<span class="font-semibold"><?php echo htmlspecialchars($search); ?></span>"
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-500">
                        Last updated: <?php echo date('M d, Y h:i A'); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Student Management Table -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-400 text-xs uppercase font-black tracking-widest">
                        <th class="px-8 py-5">Full Information</th>
                        <th class="px-6 py-5">Student ID</th>
                        <th class="px-6 py-5">Department</th>
                        <th class="px-6 py-5">Section</th>
                        <th class="px-6 py-5">CGPA</th>
                        <th class="px-6 py-5">System Status</th>
                        <th class="px-6 py-5 text-center">Nomination</th>
                        <th class="px-8 py-5 text-right">Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(student, index) in filteredStudents" :key="student.id">
                        <tr class="hover:bg-indigo-50/30 transition-colors group"
                            :class="student.hasDuplicateId ? 'bg-red-50 border-l-4 border-l-red-500' : ''">
                            <td class="px-8 py-5">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold mr-4" x-text="student.name.charAt(0)"></div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-base" x-text="student.name"></div>
                                        <div class="text-sm text-gray-500" x-text="student.email"></div>
                                        <div x-show="student.hasDuplicateId" class="text-xs text-red-600 font-bold mt-1 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <span x-text="student.duplicateError"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5 font-mono text-sm text-gray-600" x-text="student.studentId"></td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-blue-100 text-blue-800': student.department === 'CS',
                                          'bg-green-100 text-green-800': student.department === 'EE',
                                          'bg-purple-100 text-purple-800': student.department === 'ME',
                                          'bg-amber-100 text-amber-800': student.department === 'CE',
                                          'bg-pink-100 text-pink-800': student.department === 'BA',
                                          'bg-teal-100 text-teal-800': student.department === 'IT',
                                          'bg-gray-100 text-gray-800': !['CS','EE','ME','CE','BA','IT'].includes(student.department)
                                      }">
                                    <i class="fas fa-building mr-1"></i>
                                    <span x-text="getDepartmentName(student.department)"></span>
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-users mr-1"></i>
                                    <span x-text="student.section"></span>
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center">
                                    <span class="font-bold text-lg" 
                                          :class="{
                                              'text-green-600': student.cgpa >= 3.5,
                                              'text-blue-600': student.cgpa >= 3.0 && student.cgpa < 3.5,
                                              'text-amber-600': student.cgpa >= 2.75 && student.cgpa < 3.0,
                                              'text-red-600': student.cgpa < 2.75
                                          }"
                                          x-text="student.cgpa.toFixed(2)">
                                    </span>
                                    <span x-show="student.cgpa < 2.75" 
                                          class="ml-2 text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">
                                        Below Min
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <button @click="toggleStatus(index)" 
                                    :class="student.isActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all hover:ring-2 hover:ring-offset-1"
                                    :title="student.isActive ? 'Click to Deactivate' : 'Click to Activate'">
                                    <span x-text="student.isActive ? '● Active' : '○ Inactive'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <button @click="toggleNomination(index)" 
                                        :disabled="!student.isActive || student.cgpa < 2.75 || student.hasDuplicateId"
                                        :class="student.isNominated ? 
                                               'bg-amber-500 text-white shadow-md shadow-amber-200' : 
                                               (student.cgpa >= 2.75 && !student.hasDuplicateId ? 
                                                'bg-gray-100 text-gray-600 hover:bg-gray-200' : 
                                                'bg-gray-50 text-gray-300')"
                                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all disabled:cursor-not-allowed"
                                        :title="student.cgpa < 2.75 ? 'CGPA below 2.75 - Not eligible' : (student.hasDuplicateId ? 'Duplicate ID - Please fix first' : '')">
                                    <i class="fas fa-trophy mr-1" x-show="student.isNominated"></i>
                                    <span x-text="student.isNominated ? 'Nominated' : 'Nominate'"></span>
                                </button>
                            </td>
                            <td class="px-8 py-5 text-right space-x-2">
                                <button @click="openModal('edit', getOriginalIndex(student.id))" class="p-2 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><i class="fas fa-pen"></i></button>
                                <button @click="deleteStudent(getOriginalIndex(student.id))" class="p-2 text-red-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div x-show="filteredStudents.length === 0" class="p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-users-slash text-5xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-600 mb-2">No students found</h3>
                <p class="text-gray-500" x-text="selectedDepartment === 'all' ? 'Add students using the \"Add New Student\" button' : 'No students in this department'"></p>
            </div>
        </div>

        <div class="mt-10 p-8 bg-indigo-900 rounded-3xl flex flex-col md:flex-row justify-between items-center shadow-2xl shadow-indigo-200">
            <div class="mb-4 md:mb-0">
                <h4 class="text-white text-xl font-bold">Ready to Finalize?</h4>
                <p class="text-indigo-200 text-sm" x-text="`${getEligibleNominees().length} students eligible for nomination`"></p>
                <p class="text-indigo-300 text-xs mt-1" x-text="`Selected: ${getNominatedEligible().length} nominees`"></p>
                <p class="text-indigo-200 text-xs mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Nominations will be sent to Election Officer for discipline committee review
                </p>
            </div>
            
            <!-- Form to submit nominees -->
            <form id="nominationForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="m-0">
                <input type="hidden" name="nominees_data" :value="JSON.stringify(getNominatedStudents())">
                <input type="hidden" name="action" value="submit_nominees">
                <button type="submit" 
                        :disabled="getNominatedEligible().length === 0"
                        :class="getNominatedEligible().length > 0 ? 
                               'bg-white text-indigo-900 hover:bg-indigo-50' : 
                               'bg-gray-300 text-gray-500 cursor-not-allowed'"
                        class="px-8 py-4 rounded-2xl font-black transition-all flex items-center group active:scale-95"
                        :title="getNominatedEligible().length === 0 ? 'No eligible students nominated' : ''"
                        @click="prepareSubmission($event)">
                    Submit Nominees to Election Officer
                    <i class="fas fa-paper-plane ml-3 transition-transform group-hover:translate-x-1"></i>
                </button>
            </form>
        </div>
    </main>

    <!-- Add/Edit Student Modal -->
    <div x-show="showModal" x-transition.opacity 
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
         x-cloak>
        <div @click.away="showModal = false"
             class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex justify-between items-center px-10 pt-8 pb-4 border-b">
                <h3 class="text-2xl font-black text-gray-900" 
                    x-text="editMode ? 'Edit Student Details' : 'Register Student'"></h3>
                <button @click="showModal = false" 
                        class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="overflow-y-auto px-10 py-6 space-y-5"
                 style="max-height: calc(90vh - 160px);">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                        Full Name
                        <span class="text-xs ml-1" 
                              :class="form.name && form.name.length >= 2 ? 'text-green-500' : 'text-red-500'"
                              x-text="form.name ? (form.name.length >= 2 ? '✓ Valid' : 'Must be at least 2 characters') : ''">
                        </span>
                    </label>
                    <input type="text" x-model="form.name" placeholder="e.g. John Doe"
                           class="w-full border rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800 font-medium transition-all"
                           :class="form.name ? (form.name.length >= 2 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') : 'bg-gray-50 border-gray-200'">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                        Student ID
                        <span class="text-xs ml-1" 
                              :class="form.studentId ? (validateStudentId(form.studentId).valid ? 'text-green-500' : 'text-red-500') : 'text-gray-400'"
                              x-text="form.studentId ? validateStudentId(form.studentId).message : ''">
                        </span>
                    </label>
                    <input type="text" x-model="form.studentId" placeholder="e.g. 2026-ADM-001"
                           @input="form.studentId = form.studentId.toUpperCase().replace(/[^A-Z0-9\-_]/g, '')"
                           :class="form.studentId ? (validateStudentId(form.studentId).valid ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') : 'bg-gray-50 border-gray-200'"
                           class="w-full border rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800 font-medium transition-all uppercase">
                    <p class="text-xs mt-1 text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Must be unique. Auto-converted to uppercase. Allowed: A-Z, 0-9, -, _
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                        Email Address
                        <span class="text-xs ml-1" 
                              :class="form.email ? (validateEmail(form.email).valid ? 'text-green-500' : 'text-red-500') : 'text-gray-400'"
                              x-text="form.email ? validateEmail(form.email).message : ''">
                        </span>
                    </label>
                    <input type="email" x-model="form.email" placeholder="student@example.com"
                           :class="form.email ? (validateEmail(form.email).valid ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') : 'bg-gray-50 border-gray-200'"
                           class="w-full border rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800 font-medium transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                            Department
                        </label>
                        <select x-model="form.department"
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none"
                                :class="form.department ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'">
                            <option value="">Select Department</option>
                            <option value="CS">Computer Science</option>
                            <option value="EE">Electrical Engineering</option>
                            <option value="ME">Mechanical Engineering</option>
                            <option value="CE">Civil Engineering</option>
                            <option value="BA">Business Administration</option>
                            <option value="IT">Information Technology</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                            Section
                        </label>
                        <select x-model="form.section"
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none"
                                :class="form.section ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'">
                            <option value="">Select Section</option>
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                            <option value="D">Section D</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                        CGPA (0.00 – 4.00)
                        <span class="text-xs ml-1" 
                              :class="form.cgpa ? (validateCGPA(form.cgpa).valid ? (validateCGPA(form.cgpa).isEligible ? 'text-green-500' : 'text-amber-500') : 'text-red-500') : 'text-gray-400'"
                              x-text="form.cgpa ? validateCGPA(form.cgpa).message : ''">
                        </span>
                    </label>
                    <input type="number" x-model="form.cgpa" min="0" max="4" step="0.01"
                           placeholder="e.g. 3.45"
                           @input="form.cgpa = form.cgpa.replace(/[^0-9.]/g, '')"
                           :class="form.cgpa ? (validateCGPA(form.cgpa).valid ? (validateCGPA(form.cgpa).isEligible ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200') : 'bg-red-50 border-red-200') : 'bg-gray-50 border-gray-200'"
                           class="w-full border rounded-2xl p-4 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <p class="text-xs mt-1 text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Minimum 2.75 required for nomination eligibility
                    </p>
                </div>
            </div>
            <div class="px-10 py-6 border-t bg-white">
                <button @click="saveStudent()"
                        :disabled="!isFormValid()"
                        :class="isFormValid() ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 cursor-not-allowed'"
                        class="w-full text-white py-4 rounded-2xl font-bold shadow-lg transition-all">
                    <span x-text="isFormValid() ? 'Confirm & Save Record' : 'Please fill all fields correctly'"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function studentManager() {
            return {
                students: <?php echo json_encode(array_map(function($student, $index) {
                    return [
                        'id' => $index + 1,
                        'name' => $student['student_name'],
                        'studentId' => $student['student_id'],
                        'email' => $student['email'],
                        'department' => $student['department'],
                        'section' => $student['section'],
                        'cgpa' => floatval($student['cgpa']),
                        'isActive' => boolval($student['is_active']),
                        'isNominated' => boolval($student['is_nominated']),
                        'hasDuplicateId' => $student['has_duplicate_id'] ?? false,
                        'duplicateError' => $student['duplicate_error'] ?? ''
                    ];
                }, $studentsFromDB, array_keys($studentsFromDB))); ?>,
                filteredStudents: [],
                selectedDepartment: 'all',
                showModal: false,
                editMode: false,
                editIndex: null,
                form: { name: '', studentId: '', email: '', department: '', section: '', cgpa: '' },

                init() {
                    if (this.students.length === 0) {
                        this.students = [
                            { id: 1, name: 'Alice Johnson', studentId: 'STU-2026-001', email: 'alice@university.com', department: 'CS', section: 'A', cgpa: 3.75, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 2, name: 'Marcus Wright', studentId: 'STU-2026-045', email: 'marcus@engineering.com', department: 'EE', section: 'B', cgpa: 3.25, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 3, name: 'Sarah Chen', studentId: 'STU-2026-012', email: 'sarah@csdepartment.com', department: 'CS', section: 'C', cgpa: 2.50, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 4, name: 'David Kim', studentId: 'STU-2026-078', email: 'david@mechanical.com', department: 'ME', section: 'A', cgpa: 3.8, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 5, name: 'Emma Wilson', studentId: 'STU-2026-099', email: 'emma@business.com', department: 'BA', section: 'D', cgpa: 2.9, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 6, name: 'James Miller', studentId: 'STU-2026-123', email: 'james@civil.com', department: 'CE', section: 'B', cgpa: 3.1, isActive: true, isNominated: false, hasDuplicateId: false },
                            { id: 7, name: 'Alex Turner', studentId: 'STU-2026-156', email: 'alex@itdepartment.com', department: 'IT', section: 'C', cgpa: 3.6, isActive: true, isNominated: false, hasDuplicateId: false }
                        ];
                    }
                    
                    // Check for duplicate IDs on initialization
                    this.checkAllStudentIds();
                    this.filterStudents();
                },

                checkAllStudentIds() {
                    const idCount = {};
                    this.students.forEach(student => {
                        idCount[student.studentId] = (idCount[student.studentId] || 0) + 1;
                    });
                    
                    this.students.forEach((student, index) => {
                        this.students[index].hasDuplicateId = idCount[student.studentId] > 1;
                        if (idCount[student.studentId] > 1) {
                            this.students[index].duplicateError = `Duplicate ID: ${student.studentId} appears ${idCount[student.studentId]} times`;
                        }
                    });
                },

                getDepartmentName(code) {
                    const departments = {
                        'CS': 'Computer Science',
                        'EE': 'Electrical Engineering',
                        'ME': 'Mechanical Engineering',
                        'CE': 'Civil Engineering',
                        'BA': 'Business Administration',
                        'IT': 'Information Technology',
                        'OTHER': 'Other'
                    };
                    return departments[code] || code;
                },

                filterStudents() {
                    if (this.selectedDepartment === 'all') {
                        this.filteredStudents = [...this.students];
                    } else {
                        this.filteredStudents = this.students.filter(student => 
                            student.department === this.selectedDepartment
                        );
                    }
                },

                getOriginalIndex(studentId) {
                    return this.students.findIndex(s => s.id === studentId);
                },

                validateStudentId(studentId) {
                    // Check if empty
                    if (!studentId || studentId.trim() === '') {
                        return { valid: false, message: 'Student ID is required' };
                    }
                    
                    // Check length
                    if (studentId.length < 3 || studentId.length > 50) {
                        return { valid: false, message: 'Student ID must be between 3 and 50 characters' };
                    }
                    
                    // Check format (alphanumeric with hyphens and underscores)
                    const idPattern = /^[A-Z0-9\-_]+$/i;
                    if (!idPattern.test(studentId)) {
                        return { valid: false, message: 'Only letters, numbers, hyphens (-), and underscores (_) allowed' };
                    }
                    
                    // Check for duplicate
                    const isDuplicate = this.students.some((student, index) => {
                        if (this.editMode && index === this.editIndex) return false;
                        return student.studentId.toUpperCase() === studentId.toUpperCase();
                    });
                    
                    if (isDuplicate) {
                        return { valid: false, message: 'Student ID already exists in the system' };
                    }
                    
                    return { valid: true, message: '✓ Available' };
                },

                validateEmail(email) {
                    // Check if empty
                    if (!email || email.trim() === '') {
                        return { valid: false, message: 'Email is required' };
                    }
                    
                    // Basic email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        return { valid: false, message: 'Invalid email format' };
                    }
                    
                    // Check for .com domain (optional requirement)
                    if (!email.toLowerCase().endsWith('.com')) {
                        return { valid: true, message: '✓ Valid (non-.com domain)' };
                    }
                    
                    // Check for duplicate
                    const isDuplicate = this.students.some((student, index) => {
                        if (this.editMode && index === this.editIndex) return false;
                        return student.email.toLowerCase() === email.toLowerCase();
                    });
                    
                    if (isDuplicate) {
                        return { valid: false, message: 'Email already exists in system' };
                    }
                    
                    return { valid: true, message: '✓ Valid' };
                },

                validateCGPA(cgpa) {
                    if (cgpa === '' || cgpa === null || cgpa === undefined) {
                        return { valid: false, message: 'CGPA is required' };
                    }
                    
                    const numCgpa = parseFloat(cgpa);
                    if (isNaN(numCgpa)) {
                        return { valid: false, message: 'CGPA must be a number' };
                    }
                    
                    if (numCgpa < 0 || numCgpa > 4) {
                        return { valid: false, message: 'CGPA must be between 0.00 and 4.00' };
                    }
                    
                    return { 
                        valid: true, 
                        message: numCgpa >= 2.75 ? '✓ Eligible' : '✗ Below 2.75 minimum',
                        isEligible: numCgpa >= 2.75
                    };
                },

                isFormValid() {
                    // Basic field validation
                    if (!this.form.name || this.form.name.trim() === '') {
                        return false;
                    }
                    
                    // Student ID validation
                    const idValidation = this.validateStudentId(this.form.studentId);
                    if (!idValidation.valid) {
                        return false;
                    }
                    
                    // Email validation
                    const emailValidation = this.validateEmail(this.form.email);
                    if (!emailValidation.valid) {
                        return false;
                    }
                    
                    // CGPA validation
                    const cgpaValidation = this.validateCGPA(this.form.cgpa);
                    if (!cgpaValidation.valid) {
                        return false;
                    }
                    
                    // Department and section validation
                    if (!this.form.department || !this.form.section) {
                        return false;
                    }
                    
                    return true;
                },

                openModal(mode, index = null) {
                    this.editMode = (mode === 'edit');
                    if (this.editMode && index !== null) {
                        this.editIndex = index;
                        this.form = { ...this.students[index] };
                        this.form.cgpa = this.form.cgpa.toString();
                    } else {
                        this.form = { name: '', studentId: '', email: '', department: '', section: '', cgpa: '' };
                        this.editIndex = null;
                    }
                    this.showModal = true;
                },

                saveStudent() {
                    if (!this.isFormValid()) {
                        let errorMessages = [];
                        
                        // Name validation
                        if (!this.form.name || this.form.name.trim() === '') {
                            errorMessages.push('Full Name is required');
                        } else if (this.form.name.length < 2) {
                            errorMessages.push('Full Name must be at least 2 characters');
                        }
                        
                        // Student ID validation
                        const idValidation = this.validateStudentId(this.form.studentId);
                        if (!idValidation.valid) {
                            errorMessages.push(`Student ID: ${idValidation.message}`);
                        }
                        
                        // Email validation
                        const emailValidation = this.validateEmail(this.form.email);
                        if (!emailValidation.valid) {
                            errorMessages.push(`Email: ${emailValidation.message}`);
                        }
                        
                        // CGPA validation
                        const cgpaValidation = this.validateCGPA(this.form.cgpa);
                        if (!cgpaValidation.valid) {
                            errorMessages.push(`CGPA: ${cgpaValidation.message}`);
                        }
                        
                        // Department validation
                        if (!this.form.department) {
                            errorMessages.push('Department is required');
                        }
                        
                        // Section validation
                        if (!this.form.section) {
                            errorMessages.push('Section is required');
                        }
                        
                        Swal.fire({
                            title: 'Validation Errors',
                            html: `<div class="text-left">
                                      <div class="mb-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                          <p class="text-red-600 font-bold mb-2">Please fix the following errors:</p>
                                          <ul class="list-disc pl-5 text-sm space-y-1">
                                              ${errorMessages.map(msg => `<li class="text-red-700">${msg}</li>`).join('')}
                                          </ul>
                                      </div>
                                      <p class="text-xs text-gray-600">
                                          <i class="fas fa-info-circle mr-1"></i>
                                          Student IDs must be unique across all records
                                      </p>
                                   </div>`,
                            icon: 'error',
                            confirmButtonText: 'Okay',
                            width: '500px'
                        });
                        return;
                    }
                    
                    const cgpa = parseFloat(this.form.cgpa);
                    
                    // Save to database
                    this.saveToDatabase({
                        ...this.form,
                        cgpa: cgpa,
                        isActive: this.editMode ? this.students[this.editIndex].isActive : true,
                        isNominated: this.editMode ? this.students[this.editIndex].isNominated : false
                    });
                    
                    if (this.editMode) {
                        this.students[this.editIndex] = { 
                            ...this.students[this.editIndex], 
                            ...this.form,
                            cgpa: cgpa,
                            hasDuplicateId: false,
                            duplicateError: ''
                        };
                        if (cgpa < 2.75) {
                            this.students[this.editIndex].isNominated = false;
                        }
                    } else {
                        this.students.push({
                            id: Date.now(),
                            ...this.form,
                            cgpa: cgpa,
                            isActive: true,
                            isNominated: false,
                            hasDuplicateId: false,
                            duplicateError: ''
                        });
                    }
                    
                    // Re-check for duplicate IDs
                    this.checkAllStudentIds();
                    this.filterStudents();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: `Student ${this.editMode ? 'updated' : 'added'} successfully`,
                        icon: 'success',
                        confirmButtonText: 'Okay',
                        timer: 2000
                    });
                    
                    this.showModal = false;
                },

                saveToDatabase(studentData) {
                    fetch('save_student.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ...studentData,
                            action: this.editMode ? 'update' : 'create',
                            editIndex: this.editMode ? this.editIndex : null
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            Swal.fire({
                                title: 'Database Error',
                                text: data.message || 'Failed to save student record',
                                icon: 'error',
                                confirmButtonText: 'Okay'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error saving student:', error);
                        Swal.fire({
                            title: 'Network Error',
                            text: 'Failed to connect to server',
                            icon: 'error',
                            confirmButtonText: 'Okay'
                        });
                    });
                },

                toggleStatus(index) {
                    const originalIndex = this.getOriginalIndex(this.filteredStudents[index].id);
                    this.students[originalIndex].isActive = !this.students[originalIndex].isActive;
                    if (!this.students[originalIndex].isActive) {
                        this.students[originalIndex].isNominated = false;
                    }
                    this.filterStudents();
                },

                toggleNomination(index) {
                    const originalIndex = this.getOriginalIndex(this.filteredStudents[index].id);
                    const student = this.students[originalIndex];
                    
                    if (student.hasDuplicateId) {
                        Swal.fire({
                            title: 'Duplicate Student ID',
                            text: 'Cannot nominate student with duplicate ID. Please fix the ID first.',
                            icon: 'error',
                            confirmButtonText: 'Okay'
                        });
                        return;
                    }
                    
                    if (!student.isActive) {
                        Swal.fire({
                            title: 'Inactive Student',
                            text: 'Cannot nominate an inactive student',
                            icon: 'warning',
                            confirmButtonText: 'Okay'
                        });
                        return;
                    }
                    
                    if (student.cgpa < 2.75) {
                        Swal.fire({
                            title: 'Not Eligible',
                            text: 'Student CGPA is below 2.75 - Not eligible for nomination',
                            icon: 'warning',
                            confirmButtonText: 'Okay'
                        });
                        return;
                    }
                    
                    student.isNominated = !student.isNominated;
                    this.filterStudents();
                },

                deleteStudent(index) {
                    Swal.fire({
                        title: 'Delete Student',
                        text: 'Warning: This will permanently remove this student record. This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Remove from database
                            this.deleteFromDatabase(this.students[index].studentId);
                            
                            // Remove from local array
                            this.students.splice(index, 1);
                            this.checkAllStudentIds();
                            this.filterStudents();
                            
                            Swal.fire(
                                'Deleted!',
                                'Student record has been deleted.',
                                'success'
                            );
                        }
                    });
                },

                deleteFromDatabase(studentId) {
                    fetch('save_student.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            studentId: studentId
                        })
                    })
                    .catch(error => {
                        console.error('Error deleting student:', error);
                    });
                },

                getEligibleNominees() {
                    return this.students.filter(s => s.isActive && s.cgpa >= 2.75 && !s.hasDuplicateId);
                },

                getNominatedStudents() {
                    return this.students.filter(s => s.isNominated);
                },

                getNominatedEligible() {
                    return this.students.filter(s => s.isNominated && s.isActive && s.cgpa >= 2.75 && !s.hasDuplicateId);
                },

                prepareSubmission(event) {
                    const nominated = this.getNominatedEligible();
                    
                    if (nominated.length === 0) {
                        event.preventDefault();
                        Swal.fire({
                            title: 'No Eligible Nominees',
                            html: `<div class="text-left">
                                      <p class="text-red-600 font-bold mb-2">Cannot submit nominations:</p>
                                      <ul class="list-disc pl-5 text-sm space-y-1">
                                          <li>No eligible students nominated</li>
                                          <li>Students must have CGPA ≥ 2.75</li>
                                          <li>Students must be active</li>
                                          <li>Students must have unique IDs</li>
                                      </ul>
                                   </div>`,
                            icon: 'warning',
                            confirmButtonText: 'Okay',
                            width: '500px'
                        });
                        return;
                    }

                    // Check for duplicate student IDs in nomination list
                    const studentIds = nominated.map(s => s.studentId);
                    const duplicateIds = studentIds.filter((id, index) => studentIds.indexOf(id) !== index);
                    
                    if (duplicateIds.length > 0) {
                        event.preventDefault();
                        Swal.fire({
                            title: 'Duplicate Student IDs',
                            html: `<div class="text-left">
                                      <p class="text-red-600 font-bold mb-2">Duplicate student IDs found in selection:</p>
                                      <ul class="list-disc pl-5">
                                          ${duplicateIds.map(id => `<li class="font-mono">${id}</li>`).join('')}
                                      </ul>
                                      <p class="mt-3 text-sm text-gray-600">Each student must have a unique ID before submitting.</p>
                                   </div>`,
                            icon: 'error',
                            confirmButtonText: 'Okay',
                            width: '500px'
                        });
                        return;
                    }

                    // Check for students with duplicate IDs in system (even if not selected)
                    const studentsWithDuplicateIds = this.students.filter(s => s.hasDuplicateId);
                    if (studentsWithDuplicateIds.length > 0) {
                        event.preventDefault();
                        Swal.fire({
                            title: 'Duplicate IDs in System',
                            html: `<div class="text-left">
                                      <p class="text-amber-600 font-bold mb-2">Warning: Some students have duplicate IDs:</p>
                                      <ul class="list-disc pl-5 text-sm">
                                          ${studentsWithDuplicateIds.map(s => `<li>${s.name} (${s.studentId})</li>`).join('')}
                                      </ul>
                                      <p class="mt-3 text-sm text-gray-600">Please fix duplicate IDs before submitting nominations.</p>
                                   </div>`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Continue Anyway',
                            cancelButtonText: 'Fix First',
                            width: '500px'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Remove duplicate IDs from nominated list
                                const cleanNominated = nominated.filter(s => !s.hasDuplicateId);
                                if (cleanNominated.length > 0) {
                                    // Update form data and submit
                                    const form = document.getElementById('nominationForm');
                                    const nomineesDataInput = form.querySelector('input[name="nominees_data"]');
                                    nomineesDataInput.value = JSON.stringify(cleanNominated);
                                    form.submit();
                                }
                            }
                        });
                        return;
                    }

                    const ineligibleNominated = this.students.filter(s => s.isNominated && (s.cgpa < 2.75 || !s.isActive || s.hasDuplicateId));
                    if (ineligibleNominated.length > 0) {
                        event.preventDefault();
                        const confirmRemove = confirm(`Warning: ${ineligibleNominated.length} nominated students are no longer eligible (CGPA < 2.75, inactive, or have duplicate IDs). Remove their nominations and continue?`);
                        
                        if (confirmRemove) {
                            ineligibleNominated.forEach(s => s.isNominated = false);
                            this.filterStudents();
                            setTimeout(() => {
                                document.getElementById('nominationForm').submit();
                            }, 100);
                        }
                    } else {
                        let deptBreakdown = {};
                        nominated.forEach(s => {
                            deptBreakdown[s.department] = (deptBreakdown[s.department] || 0) + 1;
                        });
                        
                        let breakdownText = '';
                        for (const [dept, count] of Object.entries(deptBreakdown)) {
                            breakdownText += `• ${this.getDepartmentName(dept)}: ${count} student(s)\n`;
                        }
                        
                        const confirmMsg = `You are about to submit ${nominated.length} eligible nominees to the Election Officer.\n\n` +
                                         `Department Breakdown:\n${breakdownText}\n` +
                                         `Eligibility Checklist:\n` +
                                         `• Unique Student ID: ✓\n` +
                                         `• Active status: ✓\n` +
                                         `• CGPA ≥ 2.75: ✓\n` +
                                         `• Valid email format: ✓\n\n` +
                                         `Nominees will be forwarded to Discipline Committee for review.\n\n` +
                                         `Proceed to submit?`;
                        
                        if (!confirm(confirmMsg)) {
                            event.preventDefault();
                        }
                    }
                }
            }
        }
        
        // Status visualization functions
        function showRejectionReason(reason, studentName) {
            Swal.fire({
                title: 'Rejection Reason',
                icon: 'error',
                html: `<div class="text-left">
                          <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-200">
                              <p class="font-bold text-red-800 text-lg mb-2">${studentName}</p>
                              <p class="text-sm text-gray-600 mb-3">The nomination was rejected by the Election Officer.</p>
                              <div class="bg-white p-3 rounded border">
                                  <p class="font-semibold text-gray-700 mb-1">Reason:</p>
                                  <p class="text-gray-800">${reason || 'No specific reason was provided.'}</p>
                              </div>
                          </div>
                          <div class="text-xs text-gray-500">
                              <i class="fas fa-info-circle mr-1"></i>
                              You can update the student's details and resubmit if eligible
                          </div>
                       </div>`,
                confirmButtonText: 'Okay',
                confirmButtonColor: '#dc3545',
                width: '500px'
            });
        }
        
        function viewRequestDetails(requestId, studentName) {
            Swal.fire({
                title: 'Nomination Status Tracker',
                icon: 'info',
                html: `<div class="text-center">
                          <div class="mb-6">
                              <div class="text-lg font-bold text-gray-800 mb-2">${studentName}</div>
                              <div class="flex justify-center mb-4">
                                  <div class="timeline-container">
                                      <div class="timeline-step completed">
                                          <div class="step-dot">
                                              <i class="fas fa-paper-plane"></i>
                                          </div>
                                          <div class="step-label">Submitted</div>
                                      </div>
                                      <div class="timeline-step current">
                                          <div class="step-dot">
                                              <i class="fas fa-clock"></i>
                                          </div>
                                          <div class="step-label">Under Review</div>
                                      </div>
                                  </div>
                              </div>
                              <div class="status-visual status-pending-visual inline-block">
                                  <i class="fas fa-clock"></i>
                                  <span>PENDING REVIEW</span>
                              </div>
                          </div>
                          <p class="text-sm text-gray-600 mb-4">Your nomination is currently being reviewed by the Election Officer.</p>
                          <div class="text-left bg-blue-50 p-4 rounded-lg border border-blue-200 text-xs">
                              <p class="font-bold text-blue-800 mb-2">Next Steps:</p>
                              <ul class="space-y-1 text-gray-700">
                                  <li><i class="fas fa-check-circle text-green-500 mr-2"></i> Election Officer will verify eligibility</li>
                                  <li><i class="fas fa-check-circle text-green-500 mr-2"></i> If approved, forwarded to Discipline Committee</li>
                                  <li><i class="fas fa-check-circle text-green-500 mr-2"></i> You'll receive status updates here</li>
                              </ul>
                          </div>
                       </div>`,
                confirmButtonText: 'Close',
                width: '500px'
            });
        }
        
        function resubmitNomination(studentId, studentName) {
            Swal.fire({
                title: 'Resubmit Nomination',
                icon: 'question',
                html: `<div class="text-left">
                          <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                              <p class="font-bold text-blue-800 text-lg mb-2">${studentName}</p>
                              <p class="text-sm text-gray-600 mb-3">ID: ${studentId}</p>
                              <div class="bg-white p-3 rounded border">
                                  <p class="font-semibold text-gray-700 mb-2">Before resubmitting:</p>
                                  <ul class="text-sm text-gray-600 space-y-1">
                                      <li><i class="fas fa-check-circle text-green-500 mr-2"></i> Ensure student CGPA ≥ 2.75</li>
                                      <li><i class="fas fa-check-circle text-green-500 mr-2"></i> Verify student is active</li>
                                      <li><i class="fas fa-check-circle text-green-500 mr-2"></i> Check if nomination is not already pending</li>
                                  </ul>
                              </div>
                          </div>
                       </div>`,
                showCancelButton: true,
                confirmButtonText: 'Resubmit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3b82f6',
                width: '500px'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Navigate to student list and auto-select this student for nomination
                    window.location.href = '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?resubmit=' + encodeURIComponent(studentId);
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>