<?php
require '/var/www/dat.nz/db_connect.php';
require '/var/www/dat.nz/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username FROM MM_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
        exit;
    }

    $token = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

    $stmt = $conn->prepare("UPDATE MM_users SET token = ?, token_expiry = ? WHERE id = ?");
    $stmt->bind_param('ssi', $token, $expiry, $user['id']);
    $stmt->execute();

    // Send the reset link
    $resetLink = "https://www.maturitymaster.com/reset_password.php?token=$token";

    $mail = new PHPMailer(true);
    try {
        $mail->isSendmail();
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('no-reply@dat.nz', 'Maturity Master');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "<p>Hi {$user['username']},</p><p>Click the link below to reset your password:</p><p><a href='$resetLink'>$resetLink</a></p><p>This link will expire in 1 hour.</p>";
        $mail->AltBody = "Reset your password using this link: $resetLink";
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Password reset link sent.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Email error: {$mail->ErrorInfo}"]);
    }
}
?>
