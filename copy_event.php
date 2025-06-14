<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$original_key = $_GET['key'] ?? '';
if (!$original_key) exit("No share key provided.");

// Fetch the original event
$stmt = $conn->prepare("SELECT * FROM maturity_events WHERE share_key = ?");
$stmt->bind_param("s", $original_key);
$stmt->execute();
$original_event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$original_event) exit("Original event not found.");

// Prevent the owner from copying their own event
if ($original_event['user_id'] == $_SESSION['user_id']) {
    header("Location: view_event.php?key=" . $original_key);
    exit;
}

// Create new event for current user
$new_share_key = bin2hex(random_bytes(16)); // Generates a unique 32-char key

$stmt = $conn->prepare("INSERT INTO maturity_events (user_id, event_name, start_time, end_time, share_key, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("issss", $_SESSION['user_id'], $original_event['event_name'], $original_event['start_time'], $original_event['end_time'], $new_share_key);
$stmt->execute();
$new_event_id = $stmt->insert_id;
$stmt->close();

// Copy the probes
$stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $original_event['id']);
$stmt->execute();
$result = $stmt->get_result();
$macs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Insert probes for new event
$stmt = $conn->prepare("INSERT INTO maturity_event_probes (event_id, mac_address) VALUES (?, ?)");
foreach ($macs as $row) {
    $stmt->bind_param("is", $new_event_id, $row['mac_address']);
    $stmt->execute();
}
$stmt->close();

// Redirect to new event view
header("Location: view_event.php?key=" . $new_share_key);
exit;
