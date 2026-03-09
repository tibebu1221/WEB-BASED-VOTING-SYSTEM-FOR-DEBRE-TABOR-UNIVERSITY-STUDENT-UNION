<?php
include("connection.php");
session_start();

// 1. Authorization Check (Ensure only officers can access this)
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'officer') {
    die("Unauthorized access.");
}

// 2. Input Validation
if (!isset($_GET['key']) || !isset($_GET['action'])) {
    header('Location: ov_candidate.php');
    exit();
}

$candidate_id = $_GET['key'];
$action = $_GET['action'];

// 3. Determine new status
if ($action === 'activate') {
    $new_status = 1; // Active
} elseif ($action === 'deactivate') {
    $new_status = 0; // Inactive
} else {
    // Invalid action specified
    header('Location: ov_candidate.php');
    exit();
}

// 4. Update the database using a prepared statement
// NOTE: This assumes your candidate table has a column named 'is_active'
$stmt = $conn->prepare("UPDATE candidate SET is_active = ? WHERE c_id = ?");

if ($stmt === false) {
    die("Database error: " . $conn->error);
}

// 'is' stands for integer (for new_status) and string (for candidate_id)
$stmt->bind_param("is", $new_status, $candidate_id);
$stmt->execute();

// 5. Check result and redirect
if ($stmt->affected_rows > 0) {
    $message = ($new_status === 1) ? "Candidate activated successfully." : "Candidate deactivated successfully.";
    ?>
    <script>
        alert('<?php echo $message; ?>');
        window.location.href = 'ov_candidate.php';
    </script>
    <?php
} else {
    ?>
    <script>
        alert('Action failed or candidate status was already set.');
        window.location.href = 'ov_candidate.php';
    </script>
    <?php
}

$stmt->close();
$conn->close();
?>