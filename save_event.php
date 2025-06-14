<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: userhome.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$event_name = trim($_POST['event_name'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$probes = $_POST['probes'] ?? [];

// ✅ New fields
$mix_type = $_POST['mix_type'] ?? 'standard';
$design_strength = (int) ($_POST['design_strength'] ?? 25);

// Validate
if (empty($event_name) || empty($start_time) || empty($end_time) || empty($probes)) {
    echo "All fields are required.";
    exit;
}

// Validate probes all start with CA:FE:
foreach ($probes as $probe) {
    if (stripos($probe, 'CA:FE:') !== 0) {
        echo "All probe MAC addresses must start with CA:FE:";
        exit;
    }
}

// Generate share key
$share_key = bin2hex(random_bytes(16));
// ✅ Save event with new fields
$stmt = $conn->prepare("INSERT INTO maturity_events (user_id, event_name, start_time, end_time, share_key, mix_type, design_strength) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("isssssi", $user_id, $event_name, $start_time, $end_time, $share_key, $mix_type, $design_strength);
$stmt->execute();
$event_id = $stmt->insert_id;
$stmt->close();

// ✅ Save probes with location labels
$labels = $_POST['labels'] ?? [];

$stmt = $conn->prepare("INSERT INTO maturity_event_probes (event_id, mac_address, location_label) VALUES (?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

foreach ($probes as $i => $mac) {
    $mac_clean = strtoupper(trim($mac));
    $label = trim($labels[$i] ?? '');
    if (stripos($mac_clean, 'CA:FE:') === 0) {
        $stmt->bind_param("iss", $event_id, $mac_clean, $label);
        $stmt->execute();
    }
}

$stmt->close();
$conn->close();

header("Location: userhome.php");
exit;
?>

