<?php
session_start();
date_default_timezone_set('Africa/Addis_Ababa');

include("connection.php");

// ============ SECURITY & AUTHENTICATION ============
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'discipline_committee') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=unauthorized");
    exit();
}

// Verify user exists and is active
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

$user = $result->fetch_assoc();
$FirstName  = htmlspecialchars($user['fname']);
$MiddleName = htmlspecialchars($user['mname'] ?? '');
$stmt->close();

// ============ AUTO DETECT CANDIDATE PRIMARY KEY COLUMN ============
$candidate_pk = 'candidateID'; // default fallback
$res = $conn->query("SHOW COLUMNS FROM candidate LIKE '%id'");
if ($res && $res->num_rows > 0) {
    $col = $res->fetch_assoc();
    $candidate_pk = $col['Field']; // e.g. c_id, cand_id, id, candidate_id, etc.
}

// ============ FETCH ALL REQUESTS WITH DISCIPLINE STATUS ============
$report_data = [];
$total_requests = $pending_review_count = $cleared_count = $disciplinary_action_count = 0;
$error_message = null;

// Check if discipline_status column exists
$check_column = $conn->query("SHOW COLUMNS FROM request LIKE 'discipline_status'");
$has_discipline_column = ($check_column && $check_column->num_rows > 0);

if (!$has_discipline_column) {
    // Add the discipline_status column if it doesn't exist
    $conn->query("ALTER TABLE request ADD COLUMN discipline_status VARCHAR(50) DEFAULT 'pending'");
    $conn->query("ALTER TABLE request ADD COLUMN review_notes TEXT DEFAULT NULL");
    $conn->query("ALTER TABLE request ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL");
}

// Build query based on discipline status
$sql = "
    SELECT 
        r.requestID,
        r.candidateID,
        r.officeID,
        r.submitted_at,
        r.discipline_status,
        r.review_notes,
        r.reviewed_at,
        c.fname,
        c.mname,
        c.lname,
        c.u_id AS student_id,
        c.cgpa,
        c.year
    FROM request r
    LEFT JOIN candidate c ON r.candidateID = c.`$candidate_pk`
    ORDER BY r.submitted_at DESC";

$result = $conn->query($sql);

if (!$result) {
    $error_message = "Database query failed: " . htmlspecialchars($conn->error);
} elseif ($result->num_rows === 0) {
    $error_message = "No candidate requests found in the system.";
} else {
    while ($row = $result->fetch_assoc()) {
        $full_name = trim(($row['fname'] ?? '') . ' ' . ($row['mname'] ?? '') . ' ' . ($row['lname'] ?? ''));
        $full_name = $full_name !== '' ? htmlspecialchars($full_name) : 'Unknown Candidate';
        
        $discipline_status = $row['discipline_status'] ?? 'not_reviewed';
        $discipline_status_text = 'Not Reviewed';
        $status_color = '#ffc107'; // warning yellow
        $status_bg = '#fff3cd';
        
        switch ($discipline_status) {
            case 'clear':
                $discipline_status_text = 'Cleared ✅';
                $status_color = '#198754';
                $status_bg = '#d1e7dd';
                $cleared_count++;
                break;
            case 'disciplinary_action':
                $discipline_status_text = 'Issues Found ❌';
                $status_color = '#dc3545';
                $status_bg = '#f8d7da';
                $disciplinary_action_count++;
                break;
            case 'pending':
                $discipline_status_text = 'Pending Review';
                $status_color = '#fd7e14';
                $status_bg = '#fff3e0';
                $pending_review_count++;
                break;
            default:
                $discipline_status_text = 'Not Reviewed';
                $pending_review_count++;
                break;
        }
        
        $review_notes = $row['review_notes'] ?? 'No review notes';
        $reviewed_date = $row['reviewed_at'] ? date("M d, Y h:i A", strtotime($row['reviewed_at'])) : 'Not reviewed yet';
        
        $report_data[] = [
            'requestID'           => $row['requestID'],
            'candidateID'         => $row['candidateID'],
            'student_id'          => $row['student_id'] ?? 'N/A',
            'candidateName'       => $full_name,
            'office'              => htmlspecialchars($row['officeID'] ?? 'N/A'),
            'submitted_at'        => date("M d, Y h:i A", strtotime($row['submitted_at'])),
            'reviewed_at'         => $reviewed_date,
            'discipline_status'   => $discipline_status_text,
            'status_color'        => $status_color,
            'status_bg'           => $status_bg,
            'review_notes'        => htmlspecialchars(substr($review_notes, 0, 50)) . (strlen($review_notes) > 50 ? '...' : ''),
            'full_review_notes'   => htmlspecialchars($review_notes),
            'cgpa'                => isset($row['cgpa']) ? number_format($row['cgpa'], 2) : 'N/A',
            'year'                => $row['year'] ?? 'N/A'
        ];

        $total_requests++;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DC - Discipline Status Report</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a2a6c;
            --secondary: #b21f1f;
            --success: #198754;
            --danger: #dc3545;
            --warning: #fd7e14;
            --info: #0dcaf0;
            --dark: #2c3e50;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            padding: 30px 25px;
            position: relative;
        }
        
        .header img { 
            height: 120px; 
            border-radius: 10px;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            margin: 15px 0 10px;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 5px 0;
        }
        
        .generated-date {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        nav {
            background: var(--dark);
        }
        
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        nav li a {
            color: white;
            text-decoration: none;
            padding: 18px 25px;
            display: block;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        nav li a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        
        nav li a.active {
            background: rgba(255,255,255,0.15);
            border-bottom: 3px solid var(--primary);
        }
        
        .content {
            display: flex;
            min-height: 600px;
        }
        
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #f0f4f8 0%, #e3eaf3 100%);
            padding: 30px 20px;
            text-align: center;
            border-right: 1px solid #ddd;
        }
        
        .sidebar img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar strong {
            display: block;
            font-size: 1.2rem;
            color: var(--dark);
            margin: 10px 0 5px;
        }
        
        .sidebar p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .main {
            flex: 1;
            padding: 40px;
        }
        
        h2 {
            color: var(--primary);
            text-align: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-size: 1.8rem;
            position: relative;
        }
        
        h2:after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: var(--secondary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-top: 4px solid var(--primary);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.total {
            border-top-color: var(--primary);
        }
        
        .stat-card.pending {
            border-top-color: var(--warning);
        }
        
        .stat-card.cleared {
            border-top-color: var(--success);
        }
        
        .stat-card.issues {
            border-top-color: var(--danger);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .print-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(26, 42, 108, 0.3);
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.4);
        }
        
        .print-btn:active {
            transform: translateY(-1px);
        }
        
        .export-options {
            display: flex;
            gap: 10px;
        }
        
        .export-btn {
            background: var(--light);
            color: var(--dark);
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .export-btn:hover {
            background: #e9ecef;
            border-color: #ccc;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th {
            background: var(--dark);
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }
        
        td {
            padding: 16px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        tr:nth-child(even) { 
            background: #f9fafb; 
        }
        
        tr:hover { 
            background: #f0f7ff; 
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 120px;
        }
        
        .notes-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #666;
            font-size: 0.9rem;
        }
        
        .notes-preview:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 100;
            max-width: 300px;
        }
        
        .office-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #0d6efd;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .student-info {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .alert {
            padding: 20px;
            margin: 25px 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            border-left: 5px solid var(--danger);
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-info {
            background: #cff4fc;
            color: #055160;
            border-left-color: var(--info);
        }
        
        footer {
            background: var(--dark);
            color: white;
            text-align: center;
            padding: 25px;
            font-size: 0.95rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        footer p {
            margin: 8px 0;
        }
        
        .footer-icon {
            color: var(--primary);
            background: white;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            display: inline-block;
            margin: 0 5px;
        }

        /* Print Styles */
        @media print {
            body { 
                background: white; 
                font-size: 12pt;
            }
            
            .container { 
                box-shadow: none; 
                margin: 0; 
                max-width: 100%;
            }
            
            .sidebar, nav, .print-btn, .export-options { 
                display: none; 
            }
            
            .main { 
                padding: 20px; 
                width: 100%;
            }
            
            table { 
                box-shadow: none;
                font-size: 10pt;
            }
            
            th { 
                background: #f0f0f0 !important; 
                color: #000 !important;
                -webkit-print-color-adjust: exact;
            }
            
            h2 { 
                color: #000; 
                font-size: 16pt;
            }
            
            .stats-grid {
                display: none;
            }
            
            .controls {
                display: none;
            }
            
            .status-badge {
                -webkit-print-color-adjust: exact;
            }
            
            footer {
                background: #f0f0f0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 20px;
                border-right: none;
                border-bottom: 1px solid #ddd;
            }
            
            .sidebar img {
                width: 120px;
                height: 120px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .header img {
                height: 90px;
            }
            
            nav ul {
                flex-direction: column;
            }
            
            nav li a {
                padding: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .export-options {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="img/logo.JPG" alt="Logo">
        <h1>Discipline Committee - Discipline Status Report</h1>
        <p>Comprehensive report of all candidate requests with discipline review status</p>
        <div class="generated-date">
            <i class="fas fa-calendar-alt"></i> <?= date('M d, Y h:i A') ?> EAT
        </div>
    </div>

    <nav>
        <ul>
            <li><a href="discipline_committee.php">Dashboard</a></li>
            <li><a href="dc_manage_requests.php">Manage Requests</a></li>
            <li><a href="dc_check_request.php">Check Validity</a></li>
            <li><a href="dc_generate_report.php" class="active">Reports</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="content">
        <div class="sidebar">
            <img src="deve/A.JPG" alt="Profile">
            <br>
            <strong><?= $FirstName ?> <?= $MiddleName ?></strong>
            <p>Discipline Committee Member</p>
            <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <i class="fas fa-info-circle" style="color: var(--primary);"></i> 
                    This report shows discipline review status for all candidate requests.
                </p>
            </div>
        </div>

        <div class="main">
            <h2>Discipline Review Status Report</h2>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error_message ?>
                </div>
            <?php else: ?>
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-value"><?= $total_requests ?></div>
                        <div class="stat-label">Total Requests</div>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                            All candidate requests in system
                        </p>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-value"><?= $pending_review_count ?></div>
                        <div class="stat-label">Pending Review</div>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                            Awaiting discipline committee review
                        </p>
                    </div>
                    
                    <div class="stat-card cleared">
                        <div class="stat-value"><?= $cleared_count ?></div>
                        <div class="stat-label">Cleared</div>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                            Approved for discipline clearance
                        </p>
                    </div>
                    
                    <div class="stat-card issues">
                        <div class="stat-value"><?= $disciplinary_action_count ?></div>
                        <div class="stat-label">Issues Found</div>
                        <p style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                            Rejected due to discipline issues
                        </p>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <button class="print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print / Save as PDF
                    </button>
                    
                    <div class="export-options">
                        <button class="export-btn" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button class="export-btn" onclick="exportToCSV()">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Report Table -->
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Request ID</th>
                            <th>Candidate Information</th>
                            <th>Office</th>
                            <th>Submitted</th>
                            <th>Reviewed</th>
                            <th>Discipline Status</th>
                            <th>Review Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($report_data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong>#<?= $row['requestID'] ?></strong></td>
                            <td>
                                <div>
                                    <strong><?= $row['candidateName'] ?></strong>
                                    <div class="student-info">
                                        ID: <?= $row['student_id'] ?> | 
                                        CGPA: <?= $row['cgpa'] ?> | 
                                        Year: <?= $row['year'] ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="office-badge"><?= $row['office'] ?></span>
                            </td>
                            <td><?= $row['submitted_at'] ?></td>
                            <td><?= $row['reviewed_at'] ?></td>
                            <td>
                                <span class="status-badge" style="background: <?= $row['status_bg'] ?>; color: <?= $row['status_color'] ?>;">
                                    <?= $row['discipline_status'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="notes-preview" title="<?= $row['full_review_notes'] ?>">
                                    <?= $row['review_notes'] ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Summary Footer -->
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 5px solid var(--primary);">
                    <h3 style="margin-top: 0; color: var(--primary);">Report Summary</h3>
                    <p><strong>Generated By:</strong> <?= $FirstName ?> <?= $MiddleName ?> (Discipline Committee)</p>
                    <p><strong>Generated On:</strong> <?= date('l, F d, Y \a\t h:i A') ?> EAT</p>
                    <p><strong>Data Summary:</strong> This report contains <?= $total_requests ?> candidate requests, 
                        with <?= $pending_review_count ?> pending discipline review, <?= $cleared_count ?> cleared, 
                        and <?= $disciplinary_action_count ?> with issues found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>
            <i class="fas fa-shield-alt footer-icon"></i>
            Copyright &copy; <?= date("Y") ?> | DTUSU Online Voting System
        </p>
        <p><i class="fas fa-info-circle"></i> Discipline Committee Portal • Ensuring Candidate Integrity and Compliance</p>
    </footer>
</div>

<script>
    function exportToExcel() {
        alert('Excel export feature would be implemented here.\nThis would generate an Excel file of the report.');
        // In a real implementation, this would call a PHP script to generate Excel file
        // window.location.href = 'export_excel.php?type=discipline_report';
    }
    
    function exportToCSV() {
        alert('CSV export feature would be implemented here.\nThis would generate a CSV file of the report.');
        // In a real implementation, this would call a PHP script to generate CSV file
        // window.location.href = 'export_csv.php?type=discipline_report';
    }
    
    // Auto-hide print button after printing
    window.addEventListener('afterprint', function() {
        alert('Print operation completed. The report has been sent to your printer.');
    });
    
    // Add tooltip functionality for notes preview
    document.addEventListener('DOMContentLoaded', function() {
        const notes = document.querySelectorAll('.notes-preview');
        notes.forEach(note => {
            note.addEventListener('mouseenter', function(e) {
                const title = this.getAttribute('title');
                if (title && title.length > 50) {
                    this.setAttribute('data-original-title', title);
                    this.removeAttribute('title');
                }
            });
            
            note.addEventListener('mouseleave', function() {
                const original = this.getAttribute('data-original-title');
                if (original) {
                    this.setAttribute('title', original);
                    this.removeAttribute('data-original-title');
                }
            });
        });
    });
</script>

</body>
</html>