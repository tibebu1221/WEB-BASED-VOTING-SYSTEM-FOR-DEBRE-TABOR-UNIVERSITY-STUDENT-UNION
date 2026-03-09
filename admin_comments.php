<?php
include("connection.php"); // Ensure this sets up a MySQLi connection
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_id'])) { // Adjust based on your admin session variable
    ?>
    <script>
        alert('You are not logged in as an admin! Please log in.');
        window.location = 'admin_login.php'; // Adjust to your admin login page
    </script>
    <?php
    exit();
}

// Handle actions: view comment or mark as read
$action = isset($_GET['action']) ? $_GET['action'] : '';
$comment_id = isset($_GET['comment_id']) ? $_GET['comment_id'] : 0;

if ($action == 'mark_read' && $comment_id) {
    // Validate comment_id
    if (!is_numeric($comment_id) || $comment_id <= 0) {
        $error = "Invalid comment ID.";
    } else {
        // Check if the comment exists
        $stmt = $conn->prepare("SELECT comment_id FROM comment WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id); // Assuming comment_id is an integer
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "Invalid comment ID.";
        } else {
            // Update comment status to 'read'
            $stmt = $conn->prepare("UPDATE comment SET status = 'read' WHERE comment_id = ?");
            $stmt->bind_param("i", $comment_id);
            if ($stmt->execute()) {
                $success = "Comment marked as read successfully!";
                echo '<meta content="2;admin_comments.php" http-equiv="refresh"/>';
            } else {
                $error = "Error updating comment status: " . htmlspecialchars($conn->error);
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Admin - Manage Comments</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <style>
        .success { color: green; font-weight: bold; text-align: center; }
        .error { color: red; font-weight: bold; text-align: center; }
        table.comment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.comment-table th, table.comment-table td { border: 1px solid #51a351; padding: 10px; text-align: left; }
        table.comment-table th { background-color: #2f4f4f; color: white; }
        .button_example { background-color: #51a351; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; }
        .comment-details { background-color: #ffffff; padding: 15px; border: 1px solid #51a351; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <table align="center" style="width:900px;border:1px solid gray;background:white url(img/tbg.png) repeat-x left top;border-radius:1px">
        <tr style="height:auto;border-radius:1px;background:white url(img/tbg.png) repeat-x left top;">
            <th colspan="2">
                <a href="system_admin.php"><img src="img/logo.JPG" width="200px" height="180px" align="left" style="margin-left:10px"></a>
                <img src="img/can.png" width="400px" style="margin-left:30px;margin-top:30px" align="center">
            </th>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#2f4f4f" id="Menus" style="height:auto;border-radius:1px;">
                <ul>
                    <li><a href="system_admin.php">Home</a></li>
                    <li><a href="post.php">Post News</a></li>
                    <li><a href="admin_change.php">Change Password</a></li>
                    <li><a href="can_candidate.php">Candidates</a></li>
                    <li class="active"><a href="admin_comments.php">Comments</a></li>
                    <li><a href="can_result.php">Result</a></li>
                    <li><a href="admin_logout.php">Logout</a></li>
                </ul>
            </td>
        </tr>
    </table>
    <table align="center" bgcolor="d3d3d3" style="width:900px;border:1px solid gray;border-radius:1px;" height="40px">
        <tr valign="top">
            <td>
                <div style="clear: both"></div>
                <div id="left">
                    <img src="deve/c.JPG" width="200px" height="400px" border="0">
                </div>
            </td>
            <td>
                <div id="right">
                    <div class="desk">
                        <h1 align="center">Manage Comments</h1>
                        <?php
                        // Display success or error message
                        if (isset($success)) {
                            echo '<p class="success">' . $success . '</p>';
                        }
                        if (isset($error)) {
                            echo '<p class="error">' . $error . '</p>';
                        }

                        // If viewing a specific comment
                        if ($action == 'view' && $comment_id) {
                            if (!is_numeric($comment_id) || $comment_id <= 0) {
                                echo '<p class="error">Invalid comment ID.</p>';
                            } else {
                                $stmt = $conn->prepare("SELECT u_id, name, email, content, date, status FROM comment WHERE comment_id = ?");
                                $stmt->bind_param("i", $comment_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $u_id = htmlspecialchars($row['u_id']);
                                    $name = htmlspecialchars($row['name']);
                                    $email = htmlspecialchars($row['email']);
                                    $content = htmlspecialchars($row['content']);
                                    $date = htmlspecialchars($row['date']);
                                    $status = htmlspecialchars($row['status']);
                                    ?>
                                    <div class="comment-details">
                                        <h2>Comment Details</h2>
                                        <p><strong>User ID:</strong> <?php echo $u_id; ?></p>
                                        <p><strong>Name:</strong> <?php echo $name; ?></p>
                                        <p><strong>Email:</strong> <?php echo $email; ?></p>
                                        <p><strong>Content:</strong> <?php echo $content; ?></p>
                                        <p><strong>Date:</strong> <?php echo $date; ?></p>
                                        <p><strong>Status:</strong> <?php echo $status; ?></p>
                                        <?php if ($status == 'unread') { ?>
                                            <a href="admin_comments.php?action=mark_read&comment_id=<?php echo $comment_id; ?>" class="button_example">OK (Mark as Read)</a>
                                        <?php } else { ?>
                                            <a href="admin_comments.php" class="button_example">OK (Close)</a>
                                        <?php } ?>
                                    </div>
                                    <?php
                                } else {
                                    echo '<p class="error">Invalid comment ID.</p>';
                                }
                                $stmt->close();
                            }
                        } else {
                            // Display list of comments
                            ?>
                            <table class="comment-table">
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Content</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                <?php
                                $result = $conn->query("SELECT comment_id, u_id, name, email, content, date, status FROM comment ORDER BY status = 'unread' DESC, date DESC");
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $comment_id = htmlspecialchars($row['comment_id']);
                                        $u_id = htmlspecialchars($row['u_id']);
                                        $name = htmlspecialchars($row['name']);
                                        $email = htmlspecialchars($row['email']);
                                        $content = htmlspecialchars($row['content']);
                                        $date = htmlspecialchars($row['date']);
                                        $status = htmlspecialchars($row['status']);
                                        ?>
                                        <tr>
                                            <td><?php echo $u_id; ?></td>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $email; ?></td>
                                            <td><?php echo substr($content, 0, 50) . (strlen($content) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $date; ?></td>
                                            <td><?php echo $status; ?></td>
                                            <td>
                                                <a href="admin_comments.php?action=view&comment_id=<?php echo $comment_id; ?>" class="button_example">View</a>
                                                <?php if ($status == 'unread') { ?>
                                                    <a href="admin_comments.php?action=mark_read&comment_id=<?php echo $comment_id; ?>" class="button_example">Mark as Read</a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7">No comments found.</td></tr>';
                                }
                                ?>
                            </table>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" bgcolor="#E6E6FA" align="center">
                <div id="bottom">
                    <p style="text-align:center;padding-right:20px;">Copyright &copy; <?php echo date("Y"); ?> EC.</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
<?php
$conn->close();
?>