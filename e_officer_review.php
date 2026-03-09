<?php
session_start();
require_once 'connection.php';

// Check if user is logged in as election officer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $request_id = intval($_POST['request_id']);
        $action = isset($_POST['approve']) ? 'approve' : 'reject';
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $sql = "UPDATE election_requests 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $status, $user_id, $notes, $request_id);
        
        if ($stmt->execute()) {
            $success = "Nomination $status successfully!";
        } else {
            $error = "Failed to update nomination: " . $stmt->error;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$dept_filter = $_GET['department'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query for nominations
$sql = "SELECT er.*, d.name as department_name, u.full_name as requester_name
        FROM election_requests er
        LEFT JOIN departments d ON er.department_code = d.code
        LEFT JOIN users u ON er.requested_by = u.id
        WHERE 1=1";
        
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $sql .= " AND er.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($dept_filter !== 'all') {
    $sql .= " AND er.department_code = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (er.student_id LIKE ? OR er.student_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY er.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM election_requests";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get departments for filter
$depts_sql = "SELECT DISTINCT department_code FROM election_requests ORDER BY department_code";
$depts_result = $conn->query($depts_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Officer - Review Nominations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 font-sans" x-data="{ openModal: false, selectedRequest: null }">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="bg-green-600 text-white p-2 rounded-lg">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Election Committee Portal</h1>
                        <p class="text-sm text-gray-600"><?php echo $_SESSION['full_name']; ?></p>
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
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Review Student Nominations</h2>
            <p class="text-gray-600">Approve or reject student nominations from departments</p>
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
                        <p class="text-gray-500 text-sm">Pending Review</p>
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

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Filter Nominations</h3>
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="all" <?php echo $dept_filter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                        <?php while ($dept = $depts_result->fetch_assoc()): ?>
                            <option value="<?php echo $dept['department_code']; ?>" 
                                <?php echo $dept_filter === $dept['department_code'] ? 'selected' : ''; ?>>
                                <?php echo $dept['department_code']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                           placeholder="Student ID or Name">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>

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

        <!-- Nominations Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <?php if ($result->num_rows === 0): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No nominations found</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo $row['student_name']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $row['student_id']; ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $row['department_code']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium <?php echo $row['cgpa'] >= 3.5 ? 'text-green-600' : ($row['cgpa'] >= 2.75 ? 'text-blue-600' : 'text-red-600'); ?>">
                                        <?php echo $row['cgpa']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo $row['requester_name']; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                    <?php elseif ($row['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i> Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i> Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <div class="flex space-x-2">
                                            <button @click="openModal = true; selectedRequest = <?php echo json_encode($row); ?>"
                                                    class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                            <button @click="openModal = true; selectedRequest = <?php echo json_encode($row); ?>"
                                                    class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">
                                                <i class="fas fa-times mr-1"></i> Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">Reviewed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Review Modal -->
    <div x-show="openModal" x-transition.opacity 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" x-cloak>
        <div @click.away="openModal = false" 
             class="bg-white rounded-xl shadow-lg max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4" x-text="'Review Nomination'"></h3>
            
            <div class="mb-6" x-show="selectedRequest">
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h4 class="font-medium text-gray-800 mb-2">Student Details</h4>
                    <p class="text-sm text-gray-600" x-text="selectedRequest.student_name"></p>
                    <p class="text-sm text-gray-600" x-text="'ID: ' + selectedRequest.student_id"></p>
                    <p class="text-sm text-gray-600" x-text="'Department: ' + selectedRequest.department_code"></p>
                    <p class="text-sm text-gray-600" x-text="'CGPA: ' + selectedRequest.cgpa"></p>
                </div>
                
                <form method="POST" action="" @submit="openModal = false">
                    <input type="hidden" name="request_id" :value="selectedRequest.id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Review Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Add notes for approval/rejection..."></textarea>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="submit" name="approve"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition">
                            <i class="fas fa-check mr-2"></i>Approve
                        </button>
                        <button type="submit" name="reject"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition">
                            <i class="fas fa-times mr-2"></i>Reject
                        </button>
                        <button type="button" @click="openModal = false"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-lg transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Alpine.js component
        document.addEventListener('alpine:init', () => {
            Alpine.data('reviewModal', () => ({
                openModal: false,
                selectedRequest: null,
                
                init() {
                    // Initialize if needed
                }
            }));
        });
    </script>
</body>
</html>