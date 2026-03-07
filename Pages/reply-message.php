<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../Login/login.php?error=' . urlencode('Unauthorized access.'));
    exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admindash.php');
    exit;
}

$message_id = (int)($_POST['message_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$reply = trim($_POST['reply_message'] ?? '');

if ($message_id <= 0 || empty($reply)) {
    header('Location: admindash.php?error=' . urlencode('Invalid request'));
    exit;
}

$pdo = db();

try {
    // Get message details
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();

    if (!$message) {
        throw new Exception('Message not found');
    }

    // Update message with reply
    $updateStmt = $pdo->prepare("
        UPDATE contact_messages 
        SET admin_reply = ?, status = 'replied', updated_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$reply, $message_id]);

    // Send email (optional - you can implement this later)
    // $to = $message['email'];
    // $headers = "From: FoundiT <noreply@foundit.com>\r\n";
    // $headers .= "Reply-To: support@foundit.com\r\n";
    // $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // $emailBody = "
    //     <h2>Reply to your message</h2>
    //     <p>Dear {$message['name']},</p>
    //     <p>You recently contacted us with the following message:</p>
    //     <p><em>{$message['message']}</em></p>
    //     <p>Our response:</p>
    //     <p><strong>{$reply}</strong></p>
    //     <p>Thank you for reaching out to us.</p>
    //     <p>Best regards,<br>FoundiT Team</p>
    // ";
    
    // mail($to, $subject, $emailBody, $headers);

    header('Location: admindash.php?message=' . urlencode('Reply sent successfully') . '#messages');
    exit;

} catch (Exception $e) {
    error_log("Reply error: " . $e->getMessage());
    header('Location: admindash.php?error=' . urlencode('Failed to send reply'));
    exit;
}
?>