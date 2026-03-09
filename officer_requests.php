<?php
// officer_requests.php (Page for Department Officer to view and approve/reject candidate requests)
session_start();
include("connection.php"); // Ensure this uses MySQLi connection

// Check if the user is logged in and has officer role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'department') {
    header("Location: login.php");
    exit();
}

// Assume officeID for department/registrar is 1 (you can adjust based on DB)
// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    $action = $_GET['action']; // 'approve' or 'reject'
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE request SET status = ? WHERE requestID = ? AND officeID = 1"); // officeID=1 for registrar
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to requests page
    header("Location: officer_requests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Candidate Requests (Department Officer)</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Similar styles as before */
        .request-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #2f4f4f;
            box-shadow: 0 0 18px rgba(0, 0, 0, 0.4);
        }
        .request-table th, .request-table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .request-table th {
            background-color: #2f4f4f;
            color: white;
        }
        .action-link {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
        }
        .approve {
            background: green;
        }
        .reject {
            background: red;
        }
    </style>
    <script>
        function confirmAction(action, name) {
            return confirm(`Are you sure you want to ${action} the request for ${name}?`);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="e_officer.php"><img src="img/logo.JPG" alt="Election Logo"></a>
        </div>
        
        <nav>
            <ul>
                <li><a href="e_officer.php">Home</a></li>
                <li><a class="active" href="officer_requests.php">View Candidate Requests</a></li>
                <li><a href="officer_generate_report.php">Generate Report</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <div class="content-wrapper">
            <div class="sidebar">
                <img src="deve/A.JPG" width="200px" height="400px" alt="Sidebar Image">
            </div>
            <div class="main-content">
                <h2>Pending Candidate Requests for Department Approval</h2>
                <table class="request-table">
                    <tr>
                        <th>Request ID</th>
                        <th>Candidate ID</th>
                        <th>Candidate Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    // Fetch pending requests for registrar office (officeID=1, status='pending')
                    $stmt = $conn->prepare("SELECT r.requestID, r.candidateID, c.fname, c.mname, c.lname, r.status 
                                            FROM request r 
                                            JOIN candidate c ON r.candidateID = c.c_id 
                                            WHERE r.officeID = 1 AND r.status = 'pending'");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $full_name = htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
                            echo "<tr>
                                    <td>" . htmlspecialchars($row['requestID']) . "</td>
                                    <td>" . htmlspecialchars($row['candidateID']) . "</td>
                                    <td>" . $full_name . "</td>
                                    <td>" . htmlspecialchars($row['status']) . "</td>
                                    <td>
                                        <a href='officer_requests.php?action=approve&request_id=" . $row['requestID'] . "' class='action-link approve' onclick='return confirmAction(\"approve\", \"" . $full_name . "\");'>Approve</a>
                                        <a href='officer_requests.php?action=reject&request_id=" . $row['requestID'] . "' class='action-link reject' onclick='return confirmAction(\"reject\", \"" . $full_name . "\");'>Reject</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No pending requests.</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </table>
            </div>
        </div>
        
        <footer>
            <p>Copyright &copy; <?php echo date("Y"); ?> EC. | Secure Online Voting System for DTUSU</p>
        </footer>
    </div>
</body>
</html>
<?php
$conn->close();
?>