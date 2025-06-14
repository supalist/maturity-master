<?php
require '/var/www/dat.nz/db_connect.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    exit('Invalid confirmation link.');
}

$stmt = $conn->prepare("SELECT id FROM MM_users WHERE confirmation_token = ? AND confirmed = 0");
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    exit('This link is invalid, expired, or already used.');
}

$stmt = $conn->prepare("UPDATE MM_users SET confirmed = 1, confirmation_token = NULL WHERE id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header('Location: /index.php#authModal');
    exit;
} else {
    echo 'Confirmation failed. Please try again.';
}
?>
