<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$share_key = $_GET['key'] ?? '';
if (!$share_key) exit("No key given.");

$stmt = $conn->prepare("SELECT id FROM maturity_events WHERE share_key = ? AND user_id = ?");
$stmt->bind_param("si", $share_key, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) exit("Event not found or not yours.");

$event_id = $event['id'];

// ✅ Delete related probes first
$stmt = $conn->prepare("DELETE FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->close();

// ✅ Then delete the event
$stmt = $conn->prepare("DELETE FROM maturity_events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->close();

header("Location: userhome.php");
exit;
