<?php
// Establish database connection using MySQLi
$conn = mysqli_connect("localhost", "root", "", "onlinevote");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if 'status' is set in the URL
if (isset($_GET['status'])) {
    $status = $_GET['status'];

    // Sanitize input to prevent SQL injection (basic example)
    $status = mysqli_real_escape_string($conn, $status);

    // Query to select user status
    $select_status = mysqli_query($conn, "SELECT * FROM user WHERE u_id='$status'");
    
    if ($select_status) {
        while ($row = mysqli_fetch_object($select_status)) {
            $st = $row->status;

            // Toggle status (0 to 1 or 1 to 0)
            $status2 = ($st == '0') ? 1 : 0;

            // Update the user status
            $update = mysqli_query($conn, "UPDATE user SET status='$status2' WHERE u_id='$status'");
            
            if ($update) {
                // Redirect to manage_account.php
                header("Location: manage_account.php");
                exit();
            } else {
                echo "Error updating record: " . mysqli_error($conn);
            }
        }
    } else {
        echo "Error fetching record: " . mysqli_error($conn);
    }
}

// Close the connection
mysqli_close($conn);
?>