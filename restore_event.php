<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$key = $_GET['key'] ?? '';
if (!$key) exit('Missing key.');

$stmt = $conn->prepare("UPDATE maturity_events SET archived = 0 WHERE share_key = ? AND user_id = ?");
$stmt->bind_param("si", $key, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

header("Location: archived_events.php");
exit;
?>
