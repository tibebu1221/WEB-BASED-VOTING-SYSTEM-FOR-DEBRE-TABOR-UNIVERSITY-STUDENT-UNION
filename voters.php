<?php
session_start();
include("connection.php"); // Ensure this uses MySQLi connection

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as an admin.');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch admin user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Handle voter search
$search = $_GET['search'] ?? '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    // REMOVED 'station' from search as column doesn't exist
    $searchCondition = "WHERE fname LIKE ? OR mname LIKE ? OR lname LIKE ?";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm];
}

// Get total counts - REMOVED 'station' column
$totalStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as voted,
    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as not_voted
    FROM voter" . ($searchCondition ? " $searchCondition" : ""));
if ($searchCondition) {
    $totalStmt->bind_param(str_repeat('s', count($searchParams)), ...$searchParams);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$stats = $totalResult->fetch_assoc();
$totalStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Voters Management</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- ENHANCED FULL SCREEN STYLES --- */
        
        /* 1. Body and Container Setup */
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
        }
        
        /* 2. Header and Navigation */
        .header {
            background: linear-gradient(to right, #2c3e50, #1a2a6c);
            padding: 15px 20px;
            text-align: center;
            border-bottom: 3px solid #ffcc00;
        }
        
        .header img {
            height: 160px;
            border-radius: 8px;
            transition: transform 0.3s;
        }
        
        .header img:hover {
            transform: scale(1.02);
        }
        
        nav {
            background: #1a2a6c;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        nav ul {
            display: flex;
            list-style: none;
            justify-content: center;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 16px 22px;
            display: block;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            font-size: 15px;
        }
        
        nav ul li a:hover {
            background: rgba(255, 255, 255, 0.15);
            border-bottom: 3px solid #ffcc00;
        }
        
        nav ul li a.active {
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 3px solid #ffcc00;
            font-weight: 600;
        }
        
        /* 3. Content Wrapper */
        .content-wrapper {
            display: flex;
            flex-grow: 1;
            min-height: calc(100vh - 300px);
        }
        
        .sidebar {
            width: 280px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px 20px;
            border-right: 1px solid #dee2e6;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar h3 {
            color: #1a2a6c;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1a2a6c;
            text-align: center;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #1a2a6c;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 18px;
        }
        
        .stat-label {
            color: #555;
        }
        
        .total-voters { color: #1a2a6c; }
        .voted { color: #28a745; }
        .not-voted { color: #dc3545; }
        
        .sidebar img {
            width: 100%;
            height: auto;
            max-height: 350px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 20px;
            border: 3px solid #1a2a6c;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            overflow-x: auto;
        }
        
        /* 4. Voter Management Section */
        .voter-section {
            width: 100%;
            max-width: 95%;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
            padding: 30px;
            border: 1px solid #1a2a6c;
        }
        
        .voter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .voter-header h2 {
            color: #1a2a6c;
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #1a2a6c;
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
            outline: none;
        }
        
        .search-box button {
            background: #1a2a6c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-box button:hover {
            background: #0e1a4d;
        }
        
        /* 5. Enhanced Table Styling */
        .voter-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #1a2a6c;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .voter-table thead {
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 100%);
        }
        
        .voter-table th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .voter-table th:last-child {
            border-right: none;
        }
        
        .voter-table tbody tr {
            transition: background-color 0.2s;
        }
        
        .voter-table tbody tr:hover {
            background-color: rgba(26, 42, 108, 0.05);
        }
        
        .voter-table td {
            padding: 16px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #333;
        }
        
        .voter-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 90px;
        }
        
        .status-not-cast {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .status-cast {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        /* 6. No Data Message */
        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .no-data i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .no-data p {
            font-size: 18px;
            margin: 0;
        }
        
        /* 7. Footer */
        footer {
            background: linear-gradient(to right, #1a2a6c, #2c3e50);
            color: white;
            text-align: center;
            padding: 25px;
            font-size: 14px;
            border-top: 3px solid #ffcc00;
        }
        
        /* 8. Responsive Design */
        @media (max-width: 1100px) {
            .content-wrapper {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                justify-content: center;
                align-items: flex-start;
                padding: 20px;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            
            .stats-card {
                flex: 1;
                min-width: 250px;
                margin-bottom: 0;
            }
            
            .sidebar img {
                max-width: 300px;
                margin-top: 0;
            }
            
            .main-content {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .header img {
                height: 120px;
            }
            
            nav ul li a {
                padding: 14px 16px;
                font-size: 14px;
            }
            
            .voter-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .voter-table {
                display: block;
                overflow-x: auto;
            }
            
            .voter-table th,
            .voter-table td {
                padding: 12px 10px;
                font-size: 13px;
            }
        }
        
        @media (max-width: 480px) {
            .header img {
                height: 100px;
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
            }
            
            nav ul li {
                width: 100%;
            }
            
            nav ul li a {
                text-align: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .stats-card {
                min-width: 100%;
            }
        }
        
        /* 9. Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .voter-section {
            animation: fadeIn 0.5s ease-out;
        }
        
        .voter-table tbody tr {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="system_admin.php"><img src="img/logo.jpg" alt="Election Commission Logo"></a>
        </div>
        
        <nav>
            <ul>
                <li><a href="system_admin.php">Home</a></li>
                <li><a href="manage_account.php">Manage Account</a></li>
                <li><a href="a_generate.php">Generate Report</a></li>
                <li><a href="a_candidate.php">Candidates</a></li>
                <li><a class="active" href="voters.php">Voters</a></li>
                <li><a href="adminv_result.php">Result</a></li>
                <li><a href="setDate.php">Set Date</a></li>
                <li><a href="v_comment.php">V_Comment</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <h3><i class="fas fa-chart-bar"></i> Voting Statistics</h3>
                <div class="stats-card">
                    <div class="stat-item">
                        <span class="stat-label">Total Voters</span>
                        <span class="stat-value total-voters"><?php echo $stats['total'] ?? 0; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Votes Cast</span>
                        <span class="stat-value voted"><?php echo $stats['voted'] ?? 0; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Not Voted</span>
                        <span class="stat-value not-voted"><?php echo $stats['not_voted'] ?? 0; ?></span>
                    </div>
                </div>
                <img src="deve/a.JPG" alt="Voter Management Illustration">
            </div>
            
            <div class="main-content">
                <div class="voter-section">
                    <div class="voter-header">
                        <h2><i class="fas fa-users-cog"></i> Registered Voters Management</h2>
                        <form method="GET" action="voters.php" class="search-box">
                            <input type="text" name="search" placeholder="Search by name..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </form>
                    </div>
                    
                    <?php
                    // Build query with search condition - REMOVED 'station' column
                    $query = "SELECT fname, mname, lname, age, sex, status FROM voter";
                    if ($searchCondition) {
                        $query .= " " . $searchCondition;
                    }
                    $query .= " ORDER BY lname, fname";
                    
                    $stmt = $conn->prepare($query);
                    if ($searchCondition) {
                        $stmt->bind_param(str_repeat('s', count($searchParams)), ...$searchParams);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        ?>
                        <div style="overflow-x: auto;">
                            <table class="voter-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <!-- Station column removed as it doesn't exist -->
                                        <th>Voting Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $fullName = htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
                                        $age = htmlspecialchars($row['age']);
                                        $sex = htmlspecialchars($row['sex']);
                                        $status = $row['status'];
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><strong><?php echo $fullName; ?></strong></td>
                                            <td><?php echo $age; ?> years</td>
                                            <td><?php echo $sex; ?></td>
                                            <!-- Station column removed -->
                                            <td>
                                                <span class="status-badge <?php echo $status == 0 ? 'status-not-cast' : 'status-cast'; ?>">
                                                    <?php echo $status == 0 ? 'Not Voted' : 'Voted'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="no-data">
                            <i class="fas fa-user-slash"></i>
                            <p>No voters found<?php echo !empty($search) ? ' matching your search criteria.' : ' in the database.'; ?></p>
                            <?php if (!empty($search)): ?>
                                <a href="voters.php" class="search-box button" style="margin-top: 20px; display: inline-flex; text-decoration: none;">
                                    <i class="fas fa-undo"></i> Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
        
        <footer>
            <p><i class="fas fa-shield-alt"></i> Copyright &copy; <?php echo date("Y"); ?> Election Commission | Secure Online Voting System</p>
            <p style="margin-top: 10px; font-size: 12px; opacity: 0.8;">
                Total Voters: <?php echo $stats['total'] ?? 0; ?> | 
                Voted: <?php echo $stats['voted'] ?? 0; ?> | 
                Not Voted: <?php echo $stats['not_voted'] ?? 0; ?>
            </p>
        </footer>
    </div>
</body>
</html>
<?php
$conn->close();
?>