<?php
session_start();
include("connection.php");

// Check if user is logged in as department
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'department') {
    header("Location: login.php");
    exit();
}

$department_code = $_SESSION['department_code'];
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_nominees'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $cgpa = floatval($_POST['cgpa']);
    
    // Validate CGPA
    if ($cgpa < 2.75) {
        $error = "Student CGPA must be at least 2.75 for nomination";
    } else {
        // Check if student is already nominated
        $check_sql = "SELECT id FROM election_requests WHERE student_id = ? AND department_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $student_id, $department_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "This student has already been nominated";
        } else {
            // Insert nomination
            $sql = "INSERT INTO election_requests (student_id, student_name, department_code, cgpa, requested_by, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdi", $student_id, $student_name, $department_code, $cgpa, $user_id);
            
            if ($stmt->execute()) {
                $success = "Student nominated successfully!";
                // Clear form
                $_POST = array();
            } else {
                $error = "Failed to nominate student: " . $stmt->error;
            }
        }
    }
}

// Get department's pending nominations
$pending_sql = "SELECT * FROM election_requests 
                WHERE department_code = ? AND requested_by = ? 
                ORDER BY created_at DESC";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("si", $department_code, $user_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM election_requests 
              WHERE department_code = ? AND requested_by = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("si", $department_code, $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Portal - Send Nominations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="bg-indigo-600 text-white p-2 rounded-lg">
                        <i class="fas fa-university"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Department Portal</h1>
                        <p class="text-sm text-gray-600"><?php echo $_SESSION['full_name']; ?> (<?php echo $department_code; ?>)</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="logout.php" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Student Nomination System</h2>
            <p class="text-gray-600">Nominate students from your department for the upcoming elections</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Nominations</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-users text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo $stats['pending']; ?></p>
                    </div>
                    <div class="bg-amber-100 p-3 rounded-full">
                        <i class="fas fa-clock text-amber-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Approved</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Rejected</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Nomination Form -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Nominate a Student</h3>
                
                <?php if (isset($success)): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700"><?php echo $success; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700"><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                            <input type="text" name="student_id" required
                                   value="<?php echo $_POST['student_id'] ?? ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="e.g. CS-2024-001">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="student_name" required
                                   value="<?php echo $_POST['student_name'] ?? ''; ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="e.g. John Doe">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                CGPA (Minimum: 2.75)
                                <span class="text-xs text-gray-500">(0.00 - 4.00)</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="cgpa" required step="0.01" min="0" max="4"
                                       value="<?php echo $_POST['cgpa'] ?? ''; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent pr-12"
                                       placeholder="e.g. 3.45">
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                                    / 4.00
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Students with CGPA below 2.75 cannot be nominated</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" value="<?php echo $department_code; ?>" 
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg" readonly>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" name="submit_nominees"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Nomination
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Pending Nominations -->
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Pending Nominations</h3>
                
                <?php if ($pending_result->num_rows === 0): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No pending nominations</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php while ($row = $pending_result->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-800"><?php echo $row['student_name']; ?></h4>
                                    <p class="text-sm text-gray-600">ID: <?php echo $row['student_id']; ?></p>
                                    <div class="flex items-center mt-2 space-x-4">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-graduation-cap mr-1"></i> CGPA: <?php echo $row['cgpa']; ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    <i class="fas fa-clock mr-1"></i> Pending
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
                
                <!-- View All Button -->
                <?php if ($pending_result->num_rows > 0): ?>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="dep_nominations.php" 
                           class="w-full block text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-list mr-2"></i>View All Nominations
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="mt-8 bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Nomination Guidelines</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800">Eligibility</h4>
                        <p class="text-sm text-gray-600">Minimum CGPA of 2.75 required</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-clock text-blue-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800">Review Process</h4>
                        <p class="text-sm text-gray-600">Election committee reviews within 48 hours</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-purple-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-user-check text-purple-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800">Status Updates</h4>
                        <p class="text-sm text-gray-600">Check back for approval/rejection status</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>