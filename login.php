<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Set cookie lifetime before session starts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['remember'] ?? '') === '1') {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30, // 30 days
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_start();


require '/var/www/dat.nz/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email and password required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, confirmed FROM MM_users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (!$user['confirmed']) {
            echo json_encode(['success' => false, 'message' => 'Please confirm your email before logging in.']);
            exit;
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
