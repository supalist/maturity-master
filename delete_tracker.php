<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: userhome.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tracker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tracker_id <= 0) {
    echo "Invalid tracker ID.";
    exit;
}

// Optional: delete probes first if you don’t have ON DELETE CASCADE
$stmt = $conn->prepare("DELETE FROM concrete_probes WHERE tracker_id = ?");
$stmt->bind_param("i", $tracker_id);
$stmt->execute();
$stmt->close();

// Delete tracker (only if owned by current user)
$stmt = $conn->prepare("DELETE FROM concrete_trackers WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $tracker_id, $user_id);
$stmt->execute();
$stmt->close();

$conn->close();

header("Location: userhome.php");
exit;
