// Check if user has unread messages
function has_unread_messages($user_id) {
    global $conn;
    // You'll need to add an 'is_read' column to your messages table
    // $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND is_read = 0");
    // For now, we'll just check if there are any messages
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() > 0;
}

// Count unread messages
function count_unread_messages($user_id) {
    global $conn;
    // You'll need to add an 'is_read' column to your messages table
    // $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND is_read = 0");
    // For now, we'll just count all messages
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}