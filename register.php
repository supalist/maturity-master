<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '/var/www/dat.nz/db_connect.php';
require '/var/www/dat.nz/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, confirmed FROM MM_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($row['confirmed']) {
            echo json_encode(['success' => false, 'message' => 'This email is already registered and confirmed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email already registered but not confirmed. Please check your inbox.']);
        }
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $conn->prepare("INSERT INTO MM_users (username, email, password, confirmation_token, token_expiry) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $username, $email, $hashed, $token, $expiry);

    if ($stmt->execute()) {
        // Send confirmation email
        $confirmUrl = "https://www.maturitymaster.com/confirm.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSendmail();
            $mail->setFrom('no-reply@dat.nz', 'Maturity Master');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Please confirm your email';
            $mail->Body = "<p>Hi $username,</p><p>Please confirm your registration by clicking the link below:</p><p><a href='$confirmUrl'>$confirmUrl</a></p>";
            $mail->AltBody = "Confirm your registration: $confirmUrl";
            $mail->send();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Email could not be sent: ' . $mail->ErrorInfo]);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Registration successful. Please check your email to confirm.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
