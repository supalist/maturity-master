<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$key = $_GET['key'] ?? '';
if (!$key) {
    http_response_code(400);
    exit("Missing key.");
}

// Fetch event by key
$stmt = $conn->prepare("SELECT id, event_name FROM maturity_events WHERE share_key = ?");
$stmt->bind_param("s", $key);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    http_response_code(404);
    exit("Event not found.");
}

$event_id = $event['id'];
$event_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['event_name']);

// Get list of probes
$stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
$probes = [];
while ($row = $res->fetch_assoc()) {
    $probes[] = $row['mac_address'];
}
$stmt->close();

if (empty($probes)) {
    exit("No probes associated with this event.");
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $event_name . '_data.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Probe', 'Timestamp', 'Temperature']);

foreach ($probes as $mac) {
    $stmt = $conn->prepare("SELECT corrected_time, temperature FROM RAWDATA WHERE device_mac_address = ? ORDER BY corrected_time ASC");
    $stmt->bind_param("s", $mac);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [$mac, $row['corrected_time'], $row['temperature']]);
    }
    $stmt->close();
}

fclose($output);
exit();
