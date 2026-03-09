<?php
session_start();
include("connection.php");
include("activity_functions.php");

// Check if user is logged in
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'discipline_committee') {
    echo '<div class="no-activities">Please login to view activities</div>';
    exit();
}

// Get recent activities
$recent_activities = getRecentActivities($conn, 5);

if (empty($recent_activities)) {
    echo '<div class="no-activities">
            <i class="fas fa-history"></i>
            <p>No recent activities recorded yet</p>
            <p style="font-size: 0.9rem; margin-top: 10px;">Activities will appear here as you use the system</p>
          </div>';
} else {
    foreach ($recent_activities as $activity) {
        echo '<div class="activity-item real ' . strtolower($activity['action_type']) . '">
                <div class="activity-icon ' . $activity['icon_class'] . '">
                    <i class="' . $activity['icon'] . '"></i>
                </div>
                <div class="activity-content">
                    <h5>
                        <span class="activity-user">' . htmlspecialchars($activity['user_name']) . '</span>
                        ' . ucfirst($activity['action_type']) . '
                    </h5>
                    <p class="activity-details">' . htmlspecialchars($activity['activity_details']) . '</p>
                    <span class="activity-type ' . $activity['action_type'] . '">
                        ' . ucfirst($activity['action_type']) . '
                    </span>
                </div>
                <div class="activity-time">
                    ' . $activity['time_ago'] . '
                </div>
              </div>';
    }
}
?>