<?php
require '/var/www/dat.nz/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';
    if (!$token) {
        exit('Invalid token.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['password'] ?? '';

    if (!$token || !$newPassword) {
        exit('Missing data.');
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE MM_users SET password = ?, token = NULL WHERE token = ?");
    $stmt->bind_param('ss', $hash, $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo '<!DOCTYPE html><html><head><link rel="stylesheet" href="mm.css">
        <script>
        alert("Password updated successfully.");
        setTimeout(() => window.location.href = "index.php", 2000);
        </script>
        </head><body><main class="mm-main"><h2>Password updated. Redirecting...</h2></main></body></html>';
        exit;
    } else {
        exit('Invalid or expired token.');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="mm.css">
</head>
<body>
  <main class="mm-main">
    <h1>Reset Your Password</h1>
    <form method="post">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
      <input type="password" name="password" placeholder="New Password" required class="form-control mb-2">
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </main>
</body>
</html>
