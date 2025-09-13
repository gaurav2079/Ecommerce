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

// Check if UserManager class already exists before declaring it
if (!class_exists('LoginHistory')) {
    // LoginHistory class to handle login history operations
    class LoginHistory {
        private $conn;
        private $user_id;
        
        public function __construct($conn, $user_id) {
            $this->conn = $conn;
            $this->user_id = $user_id;
        }
        
        public function getLoginHistory($limit = 20) {
            // For MySQL, we need to use an integer in the LIMIT clause
            $limit = (int)$limit;
            $stmt = $this->conn->prepare("
                SELECT * FROM `login_history` 
                WHERE `user_id` = ? 
                ORDER BY `login_time` DESC 
                LIMIT $limit
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        public function recordLogin($ip_address, $user_agent, $success = true) {
            $stmt = $this->conn->prepare("
                INSERT INTO `login_history` (`user_id`, `ip_address`, `user_agent`, `success`) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$this->user_id, $ip_address, $user_agent, $success]);
        }
        
        public function getTotalLogins() {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total FROM `login_history` 
                WHERE `user_id` = ? AND `success` = 1
            ");
            $stmt->execute([$this->user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        }
    }
}

// Create LoginHistory object
$loginHistory = new LoginHistory($conn, $user_id);

// Get login history
$login_history = $loginHistory->getLoginHistory();

// Get user data - using the existing UserManager from user_header.php
// If UserManager class exists in user_header.php, we can use it
if (class_exists('UserManager')) {
    $userManager = new UserManager($conn, $user_id);
    $user_data = $userManager->getUserData();
} else {
    // Fallback if UserManager is not available
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle account deletion
$message = [];
$incorrect_confirmation = '';
if(isset($_POST['delete_account'])) {
    $confirm_text = trim($_POST['confirm_text']);
    
    // Case-insensitive comparison with trimmed input
    if(strtoupper($confirm_text) === 'DELETE MY ACCOUNT') {
        // Use the existing UserManager if available, otherwise implement deletion here
        if (class_exists('UserManager')) {
            $success = $userManager->deleteAccount();
        } else {
            // Fallback deletion implementation
            try {
                $conn->beginTransaction();
                
                // Delete from related tables
                $tables = ['cart', 'orders', 'messages', 'login_history'];
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
        }
        
        if($success) {
            session_destroy();
            header('Location: home.php?');
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
        .login-history {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .login-item {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }
        .login-item:last-child {
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
        .login-time {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history me-2"></i>Login History</h2>
                <span class="badge bg-primary">Total Logins: <?php echo $total_logins; ?></span>
            </div>
            
            <div class="login-history">
                <?php if(count($login_history) > 0): ?>
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
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No login history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="account-summary mb-4">
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
                            <div><?php echo !empty($user_data['gender']) ? htmlspecialchars($user_data['gender']) : 'Not set'; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-calendar-alt me-2"></i>Member since</div>
                    <div><?php echo date('M d, Y', strtotime($user_data['created_at'])); ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-birthday-cake me-2"></i>Birth Date</div>
                    <div><?php echo ($user_data['birth_date'] != '2000-01-01') ? date('M d, Y', strtotime($user_data['birth_date'])) : 'Not set'; ?></div>
                </div>
                
                <div class="mb-3">
                    <div class="text-primary"><i class="fas fa-clock me-2"></i>Last login</div>
                    <div class="last-login">
                        <?php echo !empty($user_data['last_login']) ? date('M j, Y g:i A', strtotime($user_data['last_login'])) : 'Never'; ?>
                    </div>
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
    
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const confirmText = confirmInput.value.trim().toUpperCase();
            if (confirmText !== 'DELETE MY ACCOUNT') {
                e.preventDefault();
                alert('Please type "DELETE MY ACCOUNT" to confirm deletion.');
                confirmInput.focus();
            }
        });
    }
    
    // Auto-uppercase the input to help users
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>

</body>
</html>