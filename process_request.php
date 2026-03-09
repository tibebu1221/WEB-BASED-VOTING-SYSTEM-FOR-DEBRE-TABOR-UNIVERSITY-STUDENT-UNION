<?php
// process_request.php - Handles Election Officer's approval/rejection of disciplinary actions
include("connection.php");
session_start();

// Security Check
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'officer') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: e_officer_accept_request.php');
    exit();
}

$request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = strtolower(filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING));

if ($request_id === false || ($action !== 'accept' && $action !== 'reject')) {
    $_SESSION['error_msg'] = "Invalid request ID or action.";
    header('Location: e_officer_accept_request.php');
    exit();
}

$new_status = ($action == 'accept') ? 'Accepted' : 'Rejected';
$redirect_url = 'e_officer_accept_request.php';

// Start Transaction
$conn->begin_transaction();
$success = true;

try {
    // 1. Fetch Request details to determine necessary action
    $stmt = $conn->prepare("SELECT request_type, target_id FROM send_request WHERE request_id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request_data = $result->fetch_assoc();
    $stmt->close();

    if (!$request_data) {
        throw new Exception("Request not found or already processed.");
    }

    $request_type = $request_data['request_type'];
    $target_id = $request_data['target_id'];
    $action_taken = false;

    if ($new_status == 'Accepted') {
        // --- 2. Perform the disciplinary action based on request_type ---
        switch ($request_type) {
            case 'Deactivate Voter':
                // Assuming 'voter' table has a 'status' column (e.g., 'active'/'inactive')
                $stmt = $conn->prepare("UPDATE voter SET status = 'inactive' WHERE voter_id = ?");
                $stmt->bind_param("s", $target_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $action_taken = true;
                $stmt->close();
                break;
            
            case 'Deactivate Candidate':
                // Assuming 'candidate' table has a 'status' column
                $stmt = $conn->prepare("UPDATE candidate SET status = 'inactive' WHERE candidate_id = ?");
                $stmt->bind_param("s", $target_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $action_taken = true;
                $stmt->close();
                break;

            case 'Remove Comment':
                // Assuming 'comments' table uses comment_id as target_id
                $stmt = $conn->prepare("DELETE FROM o_comment WHERE comment_id = ?");
                $stmt->bind_param("s", $target_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $action_taken = true;
                $stmt->close();
                break;

            default:
                // If the type is unknown, just update the status without external action
                $action_taken = true; 
                break;
        }

        if ($action_taken) {
            $_SESSION['success_msg'] = "Request ID $request_id ($request_type for $target_id) successfully **Accepted** and action performed.";
        } else {
            // If action failed (e.g., target ID didn't exist), still update request status, but log the issue.
            $_SESSION['warning_msg'] = "Request Accepted, but target $target_id was not found in the respective table.";
        }
    } else {
        $_SESSION['success_msg'] = "Request ID $request_id successfully **Rejected**.";
        $action_taken = true; // No external action needed for rejection
    }

    // 3. Update the request status in the send_request table
    $stmt_update = $conn->prepare("UPDATE send_request SET status = ? WHERE request_id = ?");
    $stmt_update->bind_param("si", $new_status, $request_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_msg'] = "Error processing request: " . $e->getMessage();
    error_log("Request Processing Error: " . $e->getMessage());
}

$conn->close();
header("Location: $redirect_url");
exit();
?>