<?php
// Set error reporting for development purposes (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("connection.php");

// 1. Authentication Check and Redirection
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$posted_by_default = htmlspecialchars($_SESSION['username'] ?? 'System Admin');

// 2. Post Handling Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'post_advert')) {
    
    // Sanitize and trim inputs
    $title = trim(filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING));
    $content = trim(filter_var($_POST['content'] ?? '', FILTER_SANITIZE_STRING));
    $posted_by = trim(filter_var($_POST['posted_by'] ?? $posted_by_default, FILTER_SANITIZE_STRING));
    $date = date("Y-m-d H:i:s");

    if (empty($title) || empty($content)) {
        $message = '<div class="message error"><i class="fas fa-exclamation-circle"></i> Error: Announcement Title and Content are required.</div>';
    } else {
        // Prepare Statement for secure INSERT
        $stmt = $conn->prepare("INSERT INTO event (title, content, posted_by, date) VALUES (?, ?, ?, ?)");
        
        if ($stmt === false) {
            error_log("Advert Prepare failed: " . $conn->error);
            $message = '<div class="message error"><i class="fas fa-database"></i> Database error: Failed to prepare statement.</div>';
        } else {
            $stmt->bind_param("ssss", $title, $content, $posted_by, $date);
            
            if ($stmt->execute()) {
                // Success - Store message in session and redirect
                $_SESSION['post_msg'] = '<div class="message success"><i class="fas fa-check-circle"></i> Announcement <strong>' . htmlspecialchars($title) . '</strong> posted successfully! It is now visible on the public homepage.</div>';
                $stmt->close();
                header("Location: a_advert.php"); 
                exit();
            } else {
                error_log("Advert Execute failed: " . $stmt->error);
                $message = '<div class="message error"><i class="fas fa-times-circle"></i> Error posting announcement: ' . htmlspecialchars($stmt->error) . '</div>';
                $stmt->close();
            }
        }
    }
}

// 3. Check for stored success message after redirect
if (isset($_SESSION['post_msg'])) {
    $message = $_SESSION['post_msg'];
    unset($_SESSION['post_msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Post Announcement | Election System</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a2a6c;
            --primary-dark: #0d1e5a;
            --secondary: #b21f1f;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --border-radius: 12px;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #fef2f2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--gray-light);
        }

        .container:hover {
            box-shadow: var(--shadow-hover);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }

        .header-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .header-text h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .header-text p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .message {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideIn 0.3s ease;
            border-left: 4px solid transparent;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left-color: var(--success);
        }

        .message.error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #7f1d1d;
            border-left-color: var(--error);
        }

        .message i {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-label i {
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--light);
            color: var(--dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: var(--gray);
        }

        textarea.form-control {
            min-height: 180px;
            resize: vertical;
            line-height: 1.6;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid var(--gray-light);
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #2d3b8c);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(26, 42, 108, 0.25);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray), #7b8ab8);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563, var(--gray));
            transform: translateY(-2px);
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-hint i {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 25px;
                margin: 20px auto;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 20px;
            }
            
            .header-text h1 {
                font-size: 1.5rem;
            }
        }

        .character-count {
            font-size: 0.85rem;
            color: var(--gray);
            text-align: right;
            margin-top: 5px;
        }

        .character-count.warning {
            color: var(--warning);
        }

        .character-count.error {
            color: var(--error);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <div class="header-text">
                <h1>Post System Announcement</h1>
                <p>Create and publish important announcements for all users</p>
            </div>
        </div>
        
        <?php echo $message; ?>
        
        <form method="POST" action="a_advert.php" id="announcementForm">
            <input type="hidden" name="action" value="post_advert">
            
            <div class="form-group">
                <label class="form-label" for="title">
                    <i class="fas fa-heading"></i> Announcement Title
                </label>
                <input type="text" id="title" name="title" class="form-control" required 
                       placeholder="e.g., Important Election Date Update"
                       maxlength="200">
                <div class="form-hint">
                    <i class="fas fa-lightbulb"></i> Choose a clear, descriptive title that summarizes the announcement
                </div>
                <div class="character-count" id="titleCount">0/200</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="content">
                    <i class="fas fa-align-left"></i> Announcement Content
                </label>
                <textarea id="content" name="content" class="form-control" rows="8" required 
                          placeholder="Enter the full details of your announcement here..."></textarea>
                <div class="form-hint">
                    <i class="fas fa-info-circle"></i> Include all relevant details, dates, and instructions
                </div>
                <div class="character-count" id="contentCount">0/5000</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="posted_by">
                    <i class="fas fa-user-tag"></i> Posted By
                </label>
                <input type="text" id="posted_by" name="posted_by" class="form-control" 
                       value="<?= $posted_by_default ?>" required
                       placeholder="Enter your name or title">
                <div class="form-hint">
                    <i class="fas fa-user-shield"></i> This will be displayed as the announcement author
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Publish Announcement
                </button>
                <a href="system_admin.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>

    <script>
        // Character count functionality
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const titleCount = document.getElementById('titleCount');
        const contentCount = document.getElementById('contentCount');
        
        function updateCharacterCount(element, counter, maxLength) {
            const length = element.value.length;
            counter.textContent = `${length}/${maxLength}`;
            
            // Update color based on usage
            if (length > maxLength * 0.9) {
                counter.className = 'character-count error';
            } else if (length > maxLength * 0.75) {
                counter.className = 'character-count warning';
            } else {
                counter.className = 'character-count';
            }
        }
        
        // Initial counts
        updateCharacterCount(titleInput, titleCount, 200);
        updateCharacterCount(contentInput, contentCount, 5000);
        
        // Update on input
        titleInput.addEventListener('input', () => updateCharacterCount(titleInput, titleCount, 200));
        contentInput.addEventListener('input', () => updateCharacterCount(contentInput, contentCount, 5000));
        
        // Form validation
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            const title = titleInput.value.trim();
            const content = contentInput.value.trim();
            
            if (!title || !content) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Optional: Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
<?php 
// Close the database connection
if (isset($conn)) $conn->close();
?>