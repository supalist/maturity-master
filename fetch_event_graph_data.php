<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Check session and event key
if (!isset($_SESSION['user_id']) || !isset($_GET['key'])) {
    echo json_encode(['datasets' => []]);
    exit;
}

$share_key = $_GET['key'];

// Get event + probes
$stmt = $conn->prepare("SELECT id, start_time, end_time FROM maturity_events WHERE share_key = ?");
$stmt->bind_param("s", $share_key);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    echo json_encode(['datasets' => []]);
    exit;
}

$event_id = $event['id'];
$start_time = $event['start_time'];
$end_time = $event['end_time'];

// Get probes for event
$stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$probes_result = $stmt->get_result();

$datasets = [];

while ($probe = $probes_result->fetch_assoc()) {
    $mac = $probe['mac_address'];
    // Get last 100 records for this probe in time range
    $data_stmt = $conn->prepare("
        SELECT corrected_time, temperature 
        FROM RAWDATA 
        WHERE device_mac_address = ? 
          AND corrected_time BETWEEN ? AND ?
        ORDER BY corrected_time DESC 
        LIMIT 100
    ");
    $data_stmt->bind_param("sss", $mac, $start_time, $end_time);
$is_mobile = ($_GET['mobile'] ?? '0') === '1';

    $data_stmt->execute();
    $data_result = $data_stmt->get_result();

$points = [];
$index = 0;
while ($row = $data_result->fetch_assoc()) {
    if (!$is_mobile || $index % 3 === 0) {
        $points[] = [
            'x' => date('c', strtotime($row['corrected_time'])),
            'y' => $row['temperature']
        ];
    }
    $index++;
}

    $datasets[] = [
        'label' => $mac,
        'data' => array_reverse($points) // show oldest first
    ];

    $data_stmt->close();
}

$stmt->close();
$conn->close();

echo json_encode(['datasets' => $datasets]);
