<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/var/www/dat.nz/vendor/autoload.php'; // Adjust if needed

// Get dynamic user input via URL parameters
$to = $_GET['email'] ?? 'test@example.com';
$username = $_GET['name'] ?? 'User';

$subject = 'Welcome to Maturity Master';
$body = <<<HTML
<p>Hi $username,</p>
<p>Thanks for joining <strong>Maturity Master</strong>!</p>
<p>This is a confirmation that your account is now active. We look forward to helping you stay on top of your goals and compliance needs.</p>
<p>Feel free to log in and explore the features. If you need help, reply to this email or visit our support page.</p>
<p>Cheers,<br>The Maturity Master Team</p>
HTML;

$mail = new PHPMailer(true);

try {
    $mail->isSendmail(); // Use local sendmail/Postfix
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('no-reply@dat.nz', 'Maturity Master');
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->AltBody = strip_tags($body);

    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
