<?php
require_once 'config.php';
require_once 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $admin_id = 1; // Assuming admin has ID 1
    
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, number, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $_SESSION['name'],
            $_SESSION['email'],
            $_SESSION['number'] ?? '',
            $message
        ]);
    }
}

// Get all messages between user and admin
$messages = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY id ASC");
$messages->execute([$user_id]);
$messages = $messages->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read (you'll need to add an is_read column)
// $conn->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
?>

<div class="messages-container">
    <div class="message-area">
        <h3>Messages with Admin</h3>
        
        <div class="message-history">
            <?php if (empty($messages)): ?>
                <p class="no-messages">No messages yet. Start the conversation!</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['user_id'] == $user_id ? 'sent' : 'received' ?>">
                        <p><?= htmlspecialchars($msg['message']) ?></p>
                        <small>
                            <?= date('M j, g:i a', strtotime($msg['created_at'] ?? 'now')) ?>
                            <?= $msg['user_id'] == $user_id ? ' (You)' : ' (Admin)' ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" class="message-form">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <button type="submit">Send <i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<style>
.messages-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.message-area {
    display: flex;
    flex-direction: column;
    height: 70vh;
}

.message-history {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    margin-bottom: 15px;
    background: #f9f9f9;
}

.message {
    margin-bottom: 15px;
    padding: 10px 15px;
    border-radius: 8px;
    max-width: 70%;
}

.message.sent {
    background: #d4edda;
    margin-left: auto;
}

.message.received {
    background: #f0f0f0;
    margin-right: auto;
}

.message p {
    margin: 0 0 5px 0;
}

.message small {
    color: #666;
    font-size: 0.8em;
}

.no-messages {
    text-align: center;
    color: #666;
    padding: 20px;
}

.message-form {
    display: flex;
    gap: 10px;
}

.message-form textarea {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: none;
    min-height: 60px;
}

.message-form button {
    padding: 0 20px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.message-form button:hover {
    background: #2980b9;
}

.profile-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
}

.message-badge {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}
</style>

<?php require_once 'footer.php'; ?>