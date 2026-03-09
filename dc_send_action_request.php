<?php
// dc_send_action_request.php - Discipline Committee sends action request to Election Officer
include("connection.php");
session_start();

// Security Check: Assuming a DC role (or officer temporarily handling it)
if (!isset($_SESSION['u_id']) || !in_array($_SESSION['role'], ['officer', 'discipline_committee'])) {
    // Redirect if not authorized
    header('Location: login.php');
    exit();
}

$FirstName = 'Discipline';
$middleName = 'Committee'; 

$error = null;
$success = null;
$request_type = '';
$target_id = '';
$details = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    
    // Sanitize and validate inputs
    $request_type = trim($_POST['request_type'] ?? '');
    $target_id = trim($_POST['target_id'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $sender_name = "Discipline Committee";

    if (empty($request_type) || empty($target_id) || empty($details)) {
        $error = "❌ Please fill in all request fields.";
    } else {
        // Prepare the SQL to insert into the target table: send_request
        $stmt_insert = $conn->prepare("INSERT INTO send_request (sender_name, request_type, target_id, details) VALUES (?, ?, ?, ?)");
        
        if ($stmt_insert === false) {
            $error = "❌ Database error during preparation: " . $conn->error;
        } else {
            $stmt_insert->bind_param("ssss", $sender_name, $request_type, $target_id, $details);
            
            if ($stmt_insert->execute()) {
                $success = "✅ Disciplinary action request sent successfully to the Election Officer for approval (ID: " . $stmt_insert->insert_id . ").";
                $request_type = $target_id = $details = ''; 
            } else {
                $error = "❌ Error sending request: " . $conn->error;
            }
            $stmt_insert->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DC - Send Disciplinary Request</title>
    </head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc;">
        <h2>Discipline Committee - Send Disciplinary Action</h2>
        <p>This request is sent to the Election Officer for final approval.</p>

        <?php
        if (isset($error)) echo "<div style='color:red;'>$error</div>";
        if (isset($success)) echo "<div style='color:green;'>$success</div>";
        ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            
            <label for="request_type">Request Type:</label>
            <select id="request_type" name="request_type" required>
                <option value="">-- Select Action --</option>
                <option value="Deactivate Voter" <?php if ($request_type == 'Deactivate Voter') echo 'selected'; ?>>Deactivate Voter</option>
                <option value="Deactivate Candidate" <?php if ($request_type == 'Deactivate Candidate') echo 'selected'; ?>>Deactivate Candidate</option>
                <option value="Remove Comment" <?php if ($request_type == 'Remove Comment') echo 'selected'; ?>>Remove Comment</option>
            </select><br><br>

            <label for="target_id">Target ID (Voter/Candidate/Comment ID):</label>
            <input type="text" id="target_id" name="target_id" placeholder="Enter Target ID" required value="<?php echo htmlspecialchars($target_id); ?>"><br><br>

            <label for="details">Reason/Details:</label>
            <textarea id="details" name="details" placeholder="Explain the reason for the action." required><?php echo htmlspecialchars($details); ?></textarea><br><br>

            <button type="submit" name="submit_request">Submit Request</button>
        </form>
        <p style="margin-top: 20px;"><a href="e_officer.php">Back to Dashboard</a></p>
        
    </div>
</body>
</html>
<?php $conn->close(); ?>