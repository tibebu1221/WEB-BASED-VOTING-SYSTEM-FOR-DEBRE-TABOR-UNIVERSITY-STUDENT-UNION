<?php
include("connection.php");
session_start();

// Check if user is logged in and has discipline_committee role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'discipline_committee') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as a discipline committee member.');
        window.location.href = 'login.php';
    </script>
    <?php
    exit();
}

// Set strict SQL mode
$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

// Fetch user details
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ? AND role = 'discipline_committee' AND status = 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=user_not_found");
    exit();
}
$row = $result->fetch_assoc();
$FirstName = htmlspecialchars($row['fname']);
$middleName = htmlspecialchars($row['mname'] ?? '');
$stmt->close();

// Fetch pending requests
$requests = [];
$officeID = 'dtu14r1136'; // Consistent with debug output
$stmt = $conn->prepare("SELECT requestID, candidateID, officeID, status, submitted_at FROM request WHERE discipline_status = 'pending' AND officeID = ? AND requestID IS NOT NULL AND requestID != '' AND requestID REGEXP '^[0-9]+$' AND candidateID IS NOT NULL");
$stmt->bind_param("s", $officeID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!empty($row['requestID']) && !empty($row['candidateID']) && is_numeric($row['requestID'])) {
        $requests[] = $row;
    }
}
// Log requests array for debugging
file_put_contents('debug.log', "Debug - Requests Array: " . print_r($requests, true) . "\n", FILE_APPEND);
$stmt->close();

// Handle discipline check submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_discipline'])) {
    // Log POST data for debugging
    file_put_contents('debug.log', "Debug - POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    $requestID = filter_input(INPUT_POST, 'requestID', FILTER_VALIDATE_INT);
    $candidateID = filter_input(INPUT_POST, 'candidateID', FILTER_SANITIZE_STRING);
    $recordDetails = filter_input(INPUT_POST, 'record_details', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if (empty($_POST['requestID']) || $_POST['requestID'] === '') {
        $error = "Request ID is empty in form submission.";
    } elseif ($requestID === false || $requestID <= 0) {
        $error = "Invalid or missing Request ID.";
    } elseif (empty($candidateID)) {
        $error = "Candidate ID is missing.";
    } elseif (empty($recordDetails)) {
        $error = "Record details are required.";
    } elseif (empty($status)) {
        $error = "Status is required.";
    } else {
        // Check if request exists and is pending
        $stmt = $conn->prepare("SELECT discipline_status FROM request WHERE requestID = ? AND officeID = ?");
        $stmt->bind_param("is", $requestID, $officeID);
        if (!$stmt->execute()) {
            error_log("MySQL Error in SELECT: " . $stmt->error . " | requestID: $requestID", 3, "errors.log");
            echo "<p style='color:red;'>Database error in SELECT: " . htmlspecialchars($stmt->error) . "</p>";
        } else {
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error = "Request ID does not exist.";
            } else {
                $row = $result->fetch_assoc();
                if ($row['discipline_status'] !== 'pending') {
                    $error = "This request has already been processed.";
                } else {
                    // Insert or update discipline record
                    $stmt = $conn->prepare("INSERT INTO discipline_records (candidateID, record_details, status, checked_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE record_details = ?, status = ?, checked_at = NOW()");
                    $stmt->bind_param("sssss", $candidateID, $recordDetails, $status, $recordDetails, $status);
                    if ($stmt->execute()) {
                        // Update request discipline_status
                        $stmt = $conn->prepare("UPDATE request SET discipline_status = ? WHERE requestID = ? AND officeID = ?");
                        $stmt->bind_param("sis", $status, $requestID, $officeID);
                        if ($stmt->execute()) {
                            header("Location: " . $_SERVER["PHP_SELF"]);
                            exit();
                        } else {
                            error_log("MySQL Error in UPDATE: " . $stmt->error . " | requestID: $requestID", 3, "errors.log");
                            echo "<p style='color:red;'>Error updating request: " . htmlspecialchars($stmt->error) . "</p>";
                        }
                    } else {
                        error_log("MySQL Error in INSERT: " . $stmt->error . " | candidateID: $candidateID", 3, "errors.log");
                        echo "<p style='color:red;'>Error saving discipline record: " . htmlspecialchars($stmt->error) . "</p>";
                    }
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting - Check Discipline Requests</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <style>
        body { background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { width: 100%; max-width: 800px; background: rgba(255, 255, 255, 0.95); border-radius: 12px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); padding: 20px; }
        .container h2 { color: #1a2a6c; margin-bottom: 20px; text-align: center; }
        .container table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .container th, .container td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .container th { background-color: #1a2a6c; color: white; }
        .container textarea, .container select { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
        .container button { padding: 10px; background-color: #1a2a6c; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .container button:hover { background-color: #2c3e50; }
        .error { color: red; font-size: 14px; margin-top: 10px; }
    </style>
    <script>
        function validateForm(form) {
            const requestID = form.querySelector('input[name="requestID"]').value;
            const recordDetails = form.querySelector('textarea[name="record_details"]').value;
            const status = form.querySelector('select[name="status"]').value;
            if (!requestID || isNaN(requestID) || requestID <= 0) {
                alert('Invalid or missing Request ID.');
                return false;
            }
            if (!recordDetails || !status) {
                alert('Please fill in all required fields.');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Check Discipline Requests</h2>
        <p>Welcome, <?php echo "$FirstName $middleName"; ?>!</p>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (empty($requests)) { ?>
            <p>No pending discipline requests.</p>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Candidate ID</th>
                    <th>Office ID</th>
                    <th>Submitted At</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($requests as $request) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['requestID'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($request['candidateID'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($request['officeID'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($request['submitted_at'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            if (empty($request['requestID']) || !is_numeric($request['requestID']) || empty($request['candidateID'])) {
                                echo "<p style='color:red;'>Error: Invalid requestID or candidateID for this row.</p>";
                            } else {
                            ?>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm(this)">
                                    <input type="hidden" name="requestID" value="<?php echo htmlspecialchars($request['requestID']); ?>">
                                    <input type="hidden" name="candidateID" value="<?php echo htmlspecialchars($request['candidateID']); ?>">
                                    <textarea name="record_details" placeholder="Enter discipline record details" required></textarea>
                                    <select name="status" required>
                                        <option value="clear">Clear Record</option>
                                        <option value="disciplinary_action">Disciplinary Action</option>
                                    </select>
                                    <button type="submit" name="check_discipline">Submit Check</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
        <a href="dc_requests.php">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>