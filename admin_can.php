<?php
session_start();
include("connection.php");

// Check if the user is logged in and has admin role
if (!isset($_SESSION['u_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ?>
    <script>
        alert('You are not logged in or not authorized! Please login as an admin.');
        window.location = 'login.php';
    </script>
    <?php
    exit();
}

// Fetch admin user data
$user_id = $_SESSION['u_id'];
$stmt = $conn->prepare("SELECT fname, mname FROM user WHERE u_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $FirstName = htmlspecialchars($row['fname']);
    $middleName = htmlspecialchars($row['mname']);
} else {
    echo '<script>alert("Error: User not found in the database."); window.location = "logout.php";</script>';
    exit();
}
$stmt->close();

// Fetch candidate data
$ctrl = $_GET['key'] ?? '';
if (!preg_match('/^[a-zA-Z0-9]+$/', $ctrl)) {
    echo '<script>alert("Invalid candidate ID."); window.location = "a_candidate.php";</script>';
    exit();
}

// Fetch candidate details - REMOVED 'education' as it doesn't exist
$stmt = $conn->prepare("SELECT fname, mname, lname, age, sex, phone, email, experience, candidate_photo FROM candidate WHERE c_id = ?");
$stmt->bind_param("s", $ctrl);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo '<script>alert("Candidate not found."); window.location = "a_candidate.php";</script>';
    $stmt->close();
    exit();
}

$row = $result->fetch_assoc();
$candidate_photo = htmlspecialchars($row['candidate_photo']);
$candidate_name = htmlspecialchars($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
$candidate_age = htmlspecialchars($row['age']);
$candidate_sex = htmlspecialchars($row['sex']);
// $candidate_education removed - column doesn't exist
$candidate_experience = htmlspecialchars($row['experience']);
$candidate_phone = htmlspecialchars($row['phone']);
$candidate_email = htmlspecialchars($row['email']);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Details - Admin Panel</title>
    <link rel="icon" type="image/jpg" href="img/ethio_flag.JPG"/>
    <link rel="stylesheet" href="main.css" type="text/css" media="screen"/>
    <link href="menu.css" rel="stylesheet" type="text/css" media="screen"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Candidate Details Specific Styles */
        .candidate-details-container {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .details-header {
            background: linear-gradient(to right, #1a2a6c, #2c3e50);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .details-header h2 {
            margin: 0;
            font-size: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-btn {
            color: white;
            text-decoration: none;
            font-size: 28px;
            font-weight: bold;
            padding: 0 10px;
            transition: color 0.3s;
        }
        
        .close-btn:hover {
            color: #ffcc00;
        }
        
        .candidate-profile {
            display: flex;
            padding: 30px;
            gap: 30px;
            align-items: flex-start;
        }
        
        .profile-image {
            flex-shrink: 0;
        }
        
        .profile-image img {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            object-fit: cover;
            border: 5px solid #1a2a6c;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .profile-details {
            flex: 1;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 4px solid #1a2a6c;
        }
        
        .detail-item strong {
            color: #1a2a6c;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .detail-item span {
            color: #333;
            font-size: 16px;
        }
        
        .experience-section {
            margin-top: 20px;
            padding: 20px;
            background: #f0f4f8;
            border-radius: 8px;
            border: 1px solid #e0e6ed;
        }
        
        .experience-section h3 {
            color: #1a2a6c;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .experience-content {
            color: #333;
            line-height: 1.6;
            white-space: pre-line;
        }
        
        @media (max-width: 768px) {
            .candidate-profile {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-image img {
                width: 150px;
                height: 150px;
            }
        }
        
        @media (max-width: 480px) {
            .details-header h2 {
                font-size: 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .candidate-profile {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Only the candidate details section - clean and focused -->
    <div class="candidate-details-container">
        <div class="details-header">
            <h2>
                Candidate Profile Details
                <a href="a_candidate.php" class="close-btn" title="Back to Candidates">
                    <i class="fas fa-times"></i>
                </a>
            </h2>
        </div>
        
        <div class="candidate-profile">
            <div class="profile-image">
                <img src="<?php echo file_exists($candidate_photo) ? $candidate_photo : 'img/default_candidate.jpg'; ?>" 
                     alt="Candidate Photo" 
                     onerror="this.src='img/default_candidate.jpg'">
            </div>
            
            <div class="profile-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Full Name</strong>
                        <span><?php echo $candidate_name; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Age</strong>
                        <span><?php echo $candidate_age; ?> years</span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Gender</strong>
                        <span><?php echo $candidate_sex; ?></span>
                    </div>
                    
                    <!-- Education item removed - column doesn't exist -->
                    <!-- <div class="detail-item">
                        <strong>Education Level</strong>
                        <span><?php echo $candidate_education; ?></span>
                    </div> -->
                    
                    <div class="detail-item">
                        <strong>Phone Number</strong>
                        <span><?php echo $candidate_phone; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Email Address</strong>
                        <span><?php echo $candidate_email; ?></span>
                    </div>
                </div>
                
                <div class="experience-section">
                    <h3><i class="fas fa-briefcase"></i> Professional Experience</h3>
                    <div class="experience-content">
                        <?php echo nl2br($candidate_experience); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Optional: Add smooth close functionality
        document.querySelector('.close-btn').addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>