<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// And then your login check:
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$tracker_id = (int)$_GET['id'];

// Get tracker
$stmt = $conn->prepare("SELECT * FROM concrete_trackers WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $tracker_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$tracker = $result->fetch_assoc();
if (!$tracker) {
  echo json_encode(['error' => 'Invalid tracker']);
  exit;
}

// Fetch probe list
$probes = [];
$stmt = $conn->prepare("SELECT mac_address, start_time, name FROM concrete_probes WHERE tracker_id = ?");
$stmt->bind_param("i", $tracker_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $probes[] = [
        'mac' => $row['mac_address'],
        'start_time' => $row['start_time'],
        'name' => $row['name'] ?: $row['mac_address']
    ];
}
error_log("Total probes loaded: " . count($probes));

// Build dataset
$timestamps = [];
$datasets = [];

foreach ($probes as $probe) {
    $mac = $probe['mac'];
    $start_time = $probe['start_time'];
    $label = $probe['name'];

    error_log("Fetching RAWDATA for: $mac since $start_time");

    $sql = "SELECT temperature, corrected_time FROM RAWDATA 
            WHERE device_mac_address = ? 
            AND temperature < 4999 
            AND corrected_time >= ?
            ORDER BY corrected_time ASC LIMIT 100";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $mac, $start_time);
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("Found " . $result->num_rows . " rows for $mac");

    $data = [];
    while ($row = $result->fetch_assoc()) {
$t = date('Y-m-d\TH:i:s', strtotime($row['corrected_time']));
        $temp = floatval($row['temperature']);
        $data[] = ['x' => $t, 'y' => $temp];
        $timestamps[] = $t;
    }

    $datasets[] = [
        'label' => $label,
        'data' => $data
    ];
}

echo json_encode([
  'timestamps' => array_values(array_unique($timestamps)),
  'datasets' => $datasets
]);
