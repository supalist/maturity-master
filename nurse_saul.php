<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$key = $_GET['key'] ?? '';
if (!$key) {
    http_response_code(400);
    exit("Missing key.");
}

$stmt = $conn->prepare("SELECT id, start_time, end_time FROM maturity_events WHERE share_key = ?");
$stmt->bind_param("s", $key);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    http_response_code(404);
    exit("Event not found.");
}

$probes = [];
$stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $probes[] = $row['mac_address'];
}
$stmt->close();

$data = [];
foreach ($probes as $i => $mac) {
    $stmt = $conn->prepare("SELECT corrected_time, temperature FROM RAWDATA WHERE device_mac_address = ? AND corrected_time BETWEEN ? AND ? ORDER BY corrected_time ASC");
    $stmt->bind_param("sss", $mac, $event['start_time'], $event['end_time']);
    $stmt->execute();
    $res = $stmt->get_result();
    $series = [];
    $maturity = 0;
    $last_time = null;

    while ($row = $res->fetch_assoc()) {
        $current_time = strtotime($row['corrected_time']);
        $temp = $row['temperature'];
        if ($last_time !== null) {
            $delta_hr = ($current_time - $last_time) / 3600;
            if ($temp > 0) $maturity += ($temp - 0) * $delta_hr; // Tdatum = 0
            $series[] = ['x' => round($maturity, 2), 'y' => round($temp, 2)];
        }
        $last_time = $current_time;
    }
    $stmt->close();
    if (!empty($series)) {
        $data[] = [
            'label' => "Probe $i",
            'data' => $series
        ];
    }
}
header('Content-Type: application/json');
echo json_encode(['datasets' => $data]);
exit();
