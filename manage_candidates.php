<?php
session_start();
include("connection.php");

// Check role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'department') {
    header("Location: login.php");
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Action Handlers ---

// 1. Add New Candidate
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'add' &&
    isset($_POST['token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['token'])
) {
    // Basic sanitization
    $u_id = htmlspecialchars(trim($_POST['u_id'])); // Student ID
    $fname = htmlspecialchars(trim($_POST['fname']));
    $lname = htmlspecialchars(trim($_POST['lname']));
    $mname = htmlspecialchars(trim($_POST['mname']));
    $cgpa = floatval($_POST['cgpa']);
    $status = isset($_POST['status']) ? 1 : 0; // Activated or Deactivated

    try {
        $stmt = $conn->prepare("INSERT INTO candidate (u_id, fname, lname, mname, cgpa, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdi", $u_id, $fname, $lname, $mname, $cgpa, $status);
        $stmt->execute();
        $_SESSION['msg'] = "Candidate **{$fname}** added successfully!";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // 1062 is for duplicate entry (assuming u_id is unique)
            $_SESSION['msg'] = "<span style='color:red;'>Error: Student ID **{$u_id}** already exists.</span>";
        } else {
             $_SESSION['msg'] = "<span style='color:red;'>Database Error: " . $e->getMessage() . "</span>";
        }
    }
    header("Location: manage_candidates.php");
    exit();
}

// 2. Edit Candidate
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'edit' &&
    isset($_POST['token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['token'])
) {
    $c_id = (int)$_POST['c_id'];
    $fname = htmlspecialchars(trim($_POST['fname']));
    $lname = htmlspecialchars(trim($_POST['lname']));
    $mname = htmlspecialchars(trim($_POST['mname']));
    $cgpa = floatval($_POST['cgpa']);

    $stmt = $conn->prepare("UPDATE candidate SET fname = ?, lname = ?, mname = ?, cgpa = ? WHERE c_id = ?");
    $stmt->bind_param("sssdi", $fname, $lname, $mname, $cgpa, $c_id);
    $stmt->execute();

    $_SESSION['msg'] = "Candidate **{$fname}** updated successfully!";
    header("Location: manage_candidates.php");
    exit();
}

// 3. Activate/Deactivate Toggle
if (
    isset($_POST['action']) &&
    $_POST['action'] === 'toggle_status' &&
    isset($_POST['token']) &&
    hash_equals($_SESSION['csrf_token'], $_POST['token'])
) {
    $c_id = (int)$_POST['c_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status === 1 ? 0 : 1;
    $status_text = $new_status === 1 ? 'Activated' : 'Deactivated';

    $stmt = $conn->prepare("UPDATE candidate SET status = ? WHERE c_id = ?");
    $stmt->bind_param("ii", $new_status, $c_id);
    $stmt->execute();

    $_SESSION['msg'] = "Candidate status changed to **{$status_text}**!";
    header("Location: manage_candidates.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Candidates</title>
    <style>
        body { font-family: Arial; background: #f0f8ff; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #1a2a6c; color: white; }
        .btn { padding: 8px 15px; background: #0066CC; color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 5px;}
        .btn-red { background: #dc3545; }
        .btn-green { background: #28a745; }
        .btn-gray { background: #6c757d; }
        .form-popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .form-content { background: white; padding: 20px; border-radius: 8px; width: 400px; }
        .form-content input[type="text"], .form-content input[type="number"] { width: 95%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
        .form-content button { margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <a href="dep.php" class="btn btn-gray" style="float:right;">Back to Dashboard</a>
    <h2>Manage Candidate Accounts</h2>

    <button class="btn btn-green" onclick="openAddForm()">+ Add New Candidate</button>

    <?php
    if (isset($_SESSION['msg'])) {
        echo "<p style='font-weight:bold; margin-top: 15px;'>" . $_SESSION['msg'] . "</p>";
        unset($_SESSION['msg']);
    }
    ?>

    <table>
        <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>CGPA</th>
            <th>Account Status</th>
            <th>Actions</th>
        </tr>

        <?php
        $res = $conn->query("SELECT * FROM candidate ORDER BY status DESC, u_id ASC");

        while ($r = $res->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($r['u_id']); ?></td>
            <td><?= htmlspecialchars($r['fname'] . ' ' . $r['mname'] . ' ' . $r['lname']); ?></td>
            <td><?= $r['cgpa']; ?></td>

            <td>
                <?php if ($r['status'] == 1): ?>
                    <span style='color:green; font-weight:bold;'>Activated</span>
                <?php else: ?>
                    <span style='color:red;'>Deactivated</span>
                <?php endif; ?>
            </td>

            <td>
                <button class="btn" onclick="openEditForm(<?= $r['c_id']; ?>, '<?= htmlspecialchars($r['fname']); ?>', '<?= htmlspecialchars($r['mname']); ?>', '<?= htmlspecialchars($r['lname']); ?>', '<?= $r['cgpa']; ?>')">Edit</button>

                <form method="post" style="display:inline;">
                    <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="c_id" value="<?= $r['c_id']; ?>">
                    <input type="hidden" name="current_status" value="<?= $r['status']; ?>">
                    <button type="submit" class="btn <?= $r['status'] == 1 ? 'btn-red' : 'btn-green'; ?>">
                        <?= $r['status'] == 1 ? 'Deactivate' : 'Activate'; ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div id="addForm" class="form-popup">
        <div class="form-content">
            <h3>Add New Candidate</h3>
            <form method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">
                Student ID: <input type="text" name="u_id" required pattern="[a-zA-Z0-9]+" title="Only letters and numbers allowed."><br>
                First Name: <input type="text" name="fname" required><br>
                Middle Name: <input type="text" name="mname"><br>
                Last Name: <input type="text" name="lname" required><br>
                CGPA: <input type="number" name="cgpa" step="0.01" min="0" max="4.0" required><br>
                Account Status: <input type="checkbox" name="status" value="1" checked> Activated<br>
                <button type="submit" class="btn btn-green">Add Candidate</button>
                <button type="button" class="btn btn-gray" onclick="closeAddForm()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="editForm" class="form-popup">
        <div class="form-content">
            <h3>Edit Candidate</h3>
            <form method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="c_id" id="edit_c_id">
                First Name: <input type="text" name="fname" id="edit_fname" required><br>
                Middle Name: <input type="text" name="mname" id="edit_mname"><br>
                Last Name: <input type="text" name="lname" id="edit_lname" required><br>
                CGPA: <input type="number" name="cgpa" id="edit_cgpa" step="0.01" min="0" max="4.0" required><br>
                <button type="submit" class="btn">Update Candidate</button>
                <button type="button" class="btn btn-gray" onclick="closeEditForm()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openAddForm() { document.getElementById('addForm').style.display = 'flex'; }
        function closeAddForm() { document.getElementById('addForm').style.display = 'none'; }

        function openEditForm(c_id, fname, mname, lname, cgpa) {
            document.getElementById('edit_c_id').value = c_id;
            document.getElementById('edit_fname').value = fname;
            document.getElementById('edit_mname').value = mname;
            document.getElementById('edit_lname').value = lname;
            document.getElementById('edit_cgpa').value = cgpa;
            document.getElementById('editForm').style.display = 'flex';
        }
        function closeEditForm() { document.getElementById('editForm').style.display = 'none'; }
    </script>
</div>
</body>
</html>