<?php
include("connection.php");
session_start();

// 1. Authorization Check (Ensure only officers can access this)
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'officer') {
    die("Unauthorized access.");
}

// 2. Input Validation
if (!isset($_GET['key']) || !isset($_GET['action'])) {
    // Redirect back if parameters are missing
    header('Location: reg_voter.php');
    exit();
}

$voter_id = $_GET['key'];
$action = $_GET['action'];

// 3. Determine new status
if ($action === 'activate') {
    $new_status = 1; // Active
} elseif ($action === 'deactivate') {
    $new_status = 0; // Inactive
} else {
    // Invalid action specified
    header('Location: reg_voter.php');
    exit();
}

// 4. Update the database using a prepared statement
$stmt = $conn->prepare("UPDATE voter SET is_active = ? WHERE vid = ?");

// Check if the statement was prepared successfully
if ($stmt === false) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("is", $new_status, $voter_id);
$stmt->execute();

// 5. Check result and redirect
if ($stmt->affected_rows > 0) {
    // Success
    $message = ($new_status === 1) ? "Account activated successfully." : "Account deactivated successfully.";
    ?>
    <script>
        alert('<?php echo $message; ?>');
        window.location.href = 'reg_voter.php';
    </script>
    <?php
} else {
    // Failure (e.g., voter ID not found)
    ?>
    <script>
        alert('Action failed or account status was already set.');
        window.location.href = 'reg_voter.php';
    </script>
    <?php
}

$stmt->close();
$conn->close();
?>