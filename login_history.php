<?php
// login_history.php
include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:home.php');
   exit();
}

// Check if LoginHistory class already exists before declaring it
if (!class_exists('LoginHistory')) {
    // LoginHistory class to handle login history operations
    class LoginHistory {
        private $conn;
        private $user_id;
        
        public function __construct($conn, $user_id) {
            $this->conn = $conn;
            $this->user_id = $user_id;
            $this->createTableIfNotExists();
        }
        
        private function createTableIfNotExists() {
            // Check if table exists
            $table_exists = $this->conn->query("SHOW TABLES LIKE 'login_history'")->rowCount() > 0;
            
            if (!$table_exists) {
                // Create the login_history table
                $sql = "CREATE TABLE `login_history` (
                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT(11) NOT NULL,
                    `ip_address` VARCHAR(45) NOT NULL,
                    `user_agent` TEXT NOT NULL,
                    `success` TINYINT(1) NOT NULL DEFAULT 1,
                    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `user_id_index` (`user_id`),
                    INDEX `login_time_index` (`login_time`)
                )";
                
                try {
                    $this->conn->exec($sql);
                    error_log("Login history table created successfully");
                } catch (PDOException $e) {
                    error_log("Error creating login_history table: " . $e->getMessage());
                }
            }
        }
        
        public function getLoginHistory($limit = 20) {
            try {
                $limit = (int)$limit;
                $stmt = $this->conn->prepare("
                    SELECT * FROM `login_history` 
                    WHERE `user_id` = ? 
                    ORDER BY `login_time` DESC 
                    LIMIT $limit
                ");
                $stmt->execute([$this->user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error fetching login history: " . $e->getMessage());
                return [];
            }
        }
        
        public function recordLogin($ip_address, $user_agent, $success = true) {
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO `login_history` (`user_id`, `ip_address`, `user_agent`, `success`) 
                    VALUES (?, ?, ?, ?)
                ");
                return $stmt->execute([$this->user_id, $ip_address, $user_agent, $success]);
            } catch (PDOException $e) {
                error_log("Error recording login: " . $e->getMessage());
                return false;
            }
        }
        
        public function getTotalLogins() {
            try {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total FROM `login_history` 
                    WHERE `user_id` = ? AND `success` = 1
                ");
                $stmt->execute([$this->user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['total'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error counting logins: " . $e->getMessage());
                return 0;
            }
        }
        
        public function getLastLogin() {
            try {
                $stmt = $this->conn->prepare("
                    SELECT login_time FROM `login_history` 
                    WHERE `user_id` = ? AND `success` = 1
                    ORDER BY `login_time` DESC 
                    LIMIT 1 OFFSET 1
                ");
                $stmt->execute([$this->user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['login_time'] ?? null;
            } catch (PDOException $e) {
                error_log("Error getting last login: " . $e->getMessage());
                return null;
            }
        }
        
        // New method to delete all login history for the user
        public function deleteAllHistory() {
            try {
                $stmt = $this->conn->prepare("
                    DELETE FROM `login_history` 
                    WHERE `user_id` = ?
                ");
                return $stmt->execute([$this->user_id]);
            } catch (PDOException $e) {
                error_log("Error deleting login history: " . $e->getMessage());
                return false;
            }
        }
        
        // New method to get login history count
        public function getHistoryCount() {
            try {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count FROM `login_history` 
                    WHERE `user_id` = ?
                ");
                $stmt->execute([$this->user_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['count'] ?? 0;
            } catch (PDOException $e) {
                error_log("Error counting login history: " . $e->getMessage());
                return 0;
            }
        }
    }
}

// ActivityLog class for tracking profile updates
if (!class_exists('ActivityLog')) {
    class ActivityLog {
        private $conn;
        private $user_id;
        
        public function __construct($conn, $user_id) {
            $this->conn = $conn;
            $this->user_id = $user_id;
            $this->createTableIfNotExists();
        }
        
        private function createTableIfNotExists() {
            // Check if table exists
            $table_exists = $this->conn->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0;
            
            if (!$table_exists) {
                // Create the activity_log table
                $sql = "CREATE TABLE `activity_log` (
                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT(11) NOT NULL,
                    `activity_type` VARCHAR(50) NOT NULL,
                    `activity_details` TEXT,
                    `ip_address` VARCHAR(45),
                    `activity_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `user_id_index` (`user_id`),
                    INDEX `activity_time_index` (`activity_time`),
                    INDEX `activity_type_index` (`activity_type`)
                )";
                
                try {
                    $this->conn->exec($sql);
                    error_log("Activity log table created successfully");
                } catch (PDOException $e) {
                    error_log("Error creating activity_log table: " . $e->getMessage());
                }
            }
        }
        
        public function getRecentActivities($limit = 10) {
            try {
                $limit = (int)$limit;
                $stmt = $this->conn->prepare("
                    SELECT * FROM `activity_log` 
                    WHERE `user_id` = ? 
                    ORDER BY `activity_time` DESC 
                    LIMIT $limit
                ");
                $stmt->execute([$this->user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error fetching activities: " . $e->getMessage());
                return [];
            }
        }
        
        public function recordActivity($activity_type, $activity_details = null, $ip_address = null) {
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO `activity_log` (`user_id`, `activity_type`, `activity_details`, `ip_address`) 
                    VALUES (?, ?, ?, ?)
                ");
                return $stmt->execute([$this->user_id, $activity_type, $activity_details, $ip_address]);
            } catch (PDOException $e) {
                error_log("Error recording activity: " . $e->getMessage());
                return false;
            }
        }
    }
}

// Create LoginHistory object
$loginHistory = new LoginHistory($conn, $user_id);
$activityLog = new ActivityLog($conn, $user_id);

// Handle login history deletion
if(isset($_POST['delete_login_history'])) {
    $success = $loginHistory->deleteAllHistory();
    
    if($success) {
        // Record this activity
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $activityLog->recordActivity('login_history_deletion', 'All login history records deleted', $ip_address);
        
        $message[] = 'Your login history has been successfully deleted.';
        // Refresh the page to show updated history
        header('Location: login_history.php');
        exit();
    } else {
        $message[] = 'Error deleting login history. Please try again.';
    }
}

// Get login history
$login_history = $loginHistory->getLoginHistory();

// Get recent activities
$recent_activities = $activityLog->getRecentActivities();

// Get history count
$history_count = $loginHistory->getHistoryCount();

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $user_data = [];
}

// Handle account deletion
if(isset($_POST['delete_account'])) {
    $confirm_text = trim($_POST['confirm_text']);
    
    // Case-insensitive comparison with trimmed input
    if(strtoupper($confirm_text) === 'DELETE MY ACCOUNT') {
        try {
            $conn->beginTransaction();
            
            // Delete from related tables
            $tables = ['cart', 'orders', 'messages', 'login_history', 'activity_log'];
            foreach ($tables as $table) {
                $table_exists = $conn->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
                if ($table_exists) {
                    $stmt = $conn->prepare("DELETE FROM `$table` WHERE `user_id` = ?");
                    $stmt->execute([$user_id]);
                }
            }
            
            // Delete the user
            $stmt = $conn->prepare("DELETE FROM `users` WHERE `id` = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            $success = true;
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Account deletion error: " . $e->getMessage());
            $success = false;
        }
        
        if($success) {
            session_destroy();
            header('Location: home.php?account_deleted=true');
            exit();
        } else {
            $message[] = 'Error deleting account. Please try again.';
        }
    } else {
        $message[] = 'Confirmation text does not match. Please type "DELETE MY ACCOUNT" to confirm.';
        // Store the incorrect input to show the user what they typed
        $incorrect_confirmation = htmlspecialchars($confirm_text);
    }
}

// Get total successful logins
$total_logins = $loginHistory->getTotalLogins();
// Get last login time (excluding current session)
$last_login = $loginHistory->getLastLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login History & Account Management</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- bootstrap cdn link -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

   <!-- custom css file links -->
   <link rel="stylesheet" href="css/style.css">
   <style>
        .login-history, .activity-history {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .login-item, .activity-item {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }
        .login-item:last-child, .activity-item:last-child {
            border-bottom: none;
        }
        .ip-address {
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            background: #fff;
            margin-top: 20px;
        }
        .device-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: #6c757d;
        }
        .success-badge {
            background-color: #28a745;
        }
        .failed-badge {
            background-color: #dc3545;
        }
        .account-summary {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }
        .last-login {
            font-size: 0.9rem;
        }
        .confirmation-error {
            color: #dc3545;
            font-weight: bold;
            margin-top: 5px;
        }
        .confirmation-example {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
        }
        .login-time, .activity-time {
            font-size: 0.85rem;
        }
        .debug-info {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
        }
        .activity-login {
            background-color: #e3f2fd;
            color: #8bd219ff;
        }
        .activity-profile {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .activity-password {
            background-color: #fff3e0;
            color: #f57c00;
        }
        .activity-delete {
            background-color: #ffebee;
            color: #d32f2f;
        }
        .nav-pills .nav-link.active {
            background-color: #0596f7ff;
        }
        .tab-content {
            padding: 20px 0;
        }
        .history-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .history-count {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .delete-history-btn {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        .delete-history-btn:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #212529;
        }
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        .timeline-icon {
            position: absolute;
            left: -30px;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        .timeline-content {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .activity-type {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .activity-details {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #95a5a6;
        }
        .activity-login .timeline-icon {
            background-color: #6cdb34ff;
        }
        .activity-profile .timeline-icon {
            background-color: #2ecc71;
        }
        .activity-password .timeline-icon {
            background-color: #f39c12;
        }
        .activity-delete .timeline-icon {
            background-color: #e74c3c;
        }
        .no-activities {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        .no-activities i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        .activity-tab-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .activity-tab-header h4 {
            margin: 0;
        }
        .green-icon {
            color: #28a745;
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4"><i class="fas fa-user-shield me-2"></i>Account Security</h2>
            
            <ul class="nav nav-pills mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-tab-pane" type="button" role="tab" aria-controls="login-tab-pane" aria-selected="true">
                        <i class="fas fa-sign-in-alt me-1"></i> Login History
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" style="color:green"id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-tab-pane" type="button" role="tab" aria-controls="activity-tab-pane" aria-selected="false">
                        <i class="fas fa-history me-1 green-icon"></i> Recent Activity
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="login-tab-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
                    <div class="history-actions">
                        <div>
                            <h4><i class="fas fa-history me-2"></i>Login History</h4>
                            <div class="history-count">Total records: <?php echo $history_count; ?></div>
                        </div>
                        <?php if($history_count > 0): ?>
                        <button type="button" style="color:white"class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deleteHistoryModal">
                            <i class="fas fa-trash me-1" style="color:white"></i> Clear History
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(count($login_history) > 0): ?>
                        <div class="login-history">
                            <?php foreach($login_history as $login): ?>
                                <div class="login-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $user_agent = $login['user_agent'];
                                            $device_icon = 'desktop';
                                            $device_type = 'Desktop';
                                            if (preg_match('/mobile|android|iphone|ipad|ipod/i', $user_agent)) {
                                                $device_icon = 'mobile-alt';
                                                $device_type = 'Mobile';
                                            } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
                                                $device_icon = 'tablet-alt';
                                                $device_type = 'Tablet';
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $device_icon; ?> device-icon" title="<?php echo $device_type; ?>"></i>
                                            <div>
                                                <div class="ip-address"><?php echo htmlspecialchars($login['ip_address']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($user_agent); ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?php echo $login['success'] ? 'success-badge' : 'failed-badge'; ?>">
                                                <?php echo $login['success'] ? 'Success' : 'Failed'; ?>
                                            </span>
                                            <div class="login-time text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($login['login_time'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No login history found.</p>
                            <p class="text-muted small">Your login history will appear here after your next login.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="tab-pane fade" id="activity-tab-pane" role="tabpanel" aria-labelledby="activity-tab" tabindex="0">
                    <div class="activity-tab-header">
                        <h4><i class="fas fa-history me-2 green-icon"></i>Recent Activity</h4>
                    </div>
                    
                    <?php if(count($recent_activities) > 0): ?>
                        <div class="activity-timeline">
                            <?php foreach($recent_activities as $activity): ?>
                                <div class="timeline-item <?php 
                                    if ($activity['activity_type'] === 'profile_update') echo 'activity-profile';
                                    elseif ($activity['activity_type'] === 'password_change') echo 'activity-password';
                                    elseif ($activity['activity_type'] === 'login_history_deletion') echo 'activity-delete';
                                    else echo 'activity-login';
                                ?>">
                                    <div class="timeline-icon">
                                        <?php
                                        $activity_icon = 'info-circle';
                                        if ($activity['activity_type'] === 'profile_update') {
                                            $activity_icon = 'user-edit';
                                        } elseif ($activity['activity_type'] === 'password_change') {
                                            $activity_icon = 'key';
                                        } elseif ($activity['activity_type'] === 'login_history_deletion') {
                                            $activity_icon = 'trash';
                                        }
                                        ?>
                                        <i class="fas fa-<?php echo $activity_icon; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="activity-type">
                                            <?php
                                            if ($activity['activity_type'] === 'profile_update') {
                                                echo 'Profile Updated';
                                            } elseif ($activity['activity_type'] === 'password_change') {
                                                echo 'Password Changed';
                                            } elseif ($activity['activity_type'] === 'login_history_deletion') {
                                                echo 'Login History Cleared';
                                            } else {
                                                echo ucfirst(str_replace('_', ' ', $activity['activity_type']));
                                            }
                                            ?>
                                        </div>
                                        <?php if (!empty($activity['activity_details'])): ?>
                                            <div class="activity-details"><?php echo htmlspecialchars($activity['activity_details']); ?></div>
                                        <?php endif; ?>
                                        <div class="activity-meta">
                                            <?php if (!empty($activity['ip_address'])): ?>
                                                <span class="ip-address"><?php echo htmlspecialchars($activity['ip_address']); ?></span>
                                            <?php endif; ?>
                                            <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['activity_time'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-activities">
                            <i class="fas fa-history"></i>
                            <h5>No recent activity found</h5>
                            <p>Your activities will appear here once you perform actions on your account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="account-summary">
                <div class="text-center mb-4">
                    <?php
                    $profile_pic = 'default-avatar.png';
                    if (!empty($user_data['profile_pic']) && file_exists('uploaded_img/' . $user_data['profile_pic'])) {
                        $profile_pic = $user_data['profile_pic'];
                    }
                    ?>
                    <img src="uploaded_img/<?php echo htmlspecialchars($profile_pic); ?>" 
                         class="profile-img rounded-circle" alt="Profile Picture">
                    <h4 class="mt-3"><?php echo htmlspecialchars($user_data['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="text-center">
                            <div class="text-primary"><i class="fas fa-phone fa-lg"></i></div>
                            <small>Phone</small>
                            <div><?php echo !empty($user_data['phone']) ? htmlspecialchars($user_data['phone']) : 'Not set'; ?></div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center">
                            <div class="text-primary"><i class="fas fa-venus-mars fa-lg"></i></div>
                            <small>Gender</small>
                            <div><?php echo !empty($user_data['gender']) ? htmlspecialchars(ucfirst($user_data['gender'])) : 'Not set'; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-calendar-alt me-2"></i>Member since</div>
                    <div><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-birthday-cake me-2"></i>Birth Date</div>
                    <div><?php echo (!empty($user_data['birth_date']) && $user_data['birth_date'] != '0000-00-00') ? date('M d, Y', strtotime($user_data['birth_date'])) : 'Not set'; ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-clock me-2"></i>Last Login</div>
                    <div><?php echo $last_login ? date('M d, Y g:i A', strtotime($last_login)) : 'First login'; ?></div>
                </div>
                
                <?php if(!empty($user_data['address'])): ?>
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-map-marker-alt me-2"></i>Address</div>
                    <div class="small"><?php echo htmlspecialchars($user_data['address']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="danger-zone">
                <h4 class="text-danger mb-4"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h4>
                
                <?php if(!empty($message)): ?>
                    <?php foreach($message as $msg): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($msg); ?>
                            <?php if(!empty($incorrect_confirmation)): ?>
                                <div class="confirmation-error mt-2">
                                    You typed: "<?php echo $incorrect_confirmation; ?>"
                                </div>
                                <div class="confirmation-example mt-2">
                                    Expected: "DELETE MY ACCOUNT"
                                </div>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <p class="text-muted">Once you delete your account, there is no going back. This will permanently delete your account and all associated data.</p>
                
                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Login History Modal -->
<div class="modal fade" id="deleteHistoryModal" tabindex="-1" aria-labelledby="deleteHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning" id="deleteHistoryModalLabel"><i class="fas fa-trash"></i> Clear Login History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">This action cannot be undone</h6>
                        <p class="mb-0">This will permanently delete all your login history records.</p>
                    </div>
                    
                    <p>Are you sure you want to clear all your login history?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_login_history" class="btn btn-warning">Yes, clear my history</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteAccountModalLabel"><i class="fas fa-exclamation-triangle"></i> Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">This action cannot be undone</h6>
                        <p class="mb-0">This will permanently delete your account, orders, cart items, messages, and all associated data.</p>
                    </div>
                    
                    <p>Please type <strong>DELETE MY ACCOUNT</strong> to confirm.</p>
                    
                    <div class="mb-3">
                        <label for="confirmText" class="form-label">Confirmation Text</label>
                        <input type="text" name="confirm_text" class="form-control" id="confirmText" placeholder="Type 'DELETE MY ACCOUNT'" required>
                        <div class="form-text">Type exactly "DELETE MY ACCOUNT" (case-insensitive)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_account" class="btn btn-danger">I understand, delete my account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>

<script>
// Add some client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('form[method="post"]');
    const confirmInput = document.getElementById('confirmText');
    
    if (deleteForm && confirmInput) {
        deleteForm.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'delete_account') {
                const confirmText = confirmInput.value.trim().toUpperCase();
                if (confirmText !== 'DELETE MY ACCOUNT') {
                    e.preventDefault();
                    alert('Please type "DELETE MY ACCOUNT" to confirm deletion.');
                    confirmInput.focus();
                }
            }
        });
    }
    
    // Auto-uppercase the input to help users
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    // Initialize tab functionality
    const triggerTabList = document.querySelectorAll('#myTab button');
    triggerTabList.forEach(triggerEl => {
        new bootstrap.Tab(triggerEl);
    });
});
</script>

</body>
</html>