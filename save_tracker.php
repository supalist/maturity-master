<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '/var/www/dat.nz/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Session user_id not set");
}
$user_id = $_SESSION['user_id'];

$tracker_id = isset($_POST['tracker_id']) ? (int)$_POST['tracker_id'] : null;
$tracker_name = trim($_POST['tracker_name'] ?? '');
$start_all_time = $_POST['start_all_time'] ?? null;
$max_spread = isset($_POST['max_spread']) && $_POST['max_spread'] !== '' ? (float)$_POST['max_spread'] : null;
$base_mac = $_POST['base_mac'] ?? null;

// Validate required values
if (empty($tracker_name) || empty($start_all_time) || (!$tracker_id && empty($base_mac))) {
    echo "Tracker name, start time, and base MAC are required.";
    exit;
}

if ($tracker_id) {
    // Update existing tracker
    $stmt = $conn->prepare("UPDATE concrete_trackers SET tracker_name = ?, start_time = ?, max_spread = ? WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Prepare failed (update): " . $conn->error);
    }
    $stmt->bind_param("ssdii", $tracker_name, $start_all_time, $max_spread, $tracker_id, $user_id);
    if (!$stmt->execute()) {
        die("Execute failed (update): " . $stmt->error);
    }
    $stmt->close();
} else {
    // Create new tracker with base_mac
    $stmt = $conn->prepare("INSERT INTO concrete_trackers (user_id, tracker_name, base_mac, start_time, max_spread) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed (insert tracker): " . $conn->error);
    }
    $stmt->bind_param("isssd", $user_id, $tracker_name, $base_mac, $start_all_time, $max_spread);
    if (!$stmt->execute()) {
        die("Execute failed (insert tracker): " . $stmt->error);
    }
    $tracker_id = $stmt->insert_id;
    $stmt->close();

    // Add 8 probes with MACs ending in +0 to +7 from the base MAC
    $macParts = explode(':', strtoupper(trim($base_mac)));

    if (count($macParts) === 6 && ctype_xdigit(implode('', $macParts))) {
        $lastByte = hexdec($macParts[5]);

        $stmt = $conn->prepare("INSERT INTO concrete_probes (tracker_id, mac_address, start_time) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed (probe insert): " . $conn->error);
        }

        for ($i = 0; $i < 8; $i++) {
            $macParts[5] = str_pad(dechex($lastByte + $i), 2, '0', STR_PAD_LEFT);
            $fullMac = strtoupper(implode(':', $macParts));
            $stmt->bind_param("iss", $tracker_id, $fullMac, $start_all_time);
            if (!$stmt->execute()) {
                die("Execute failed (probe $i): " . $stmt->error);
            }
        }

        $stmt->close();
    } else {
        die("Invalid base MAC address format.");
    }
}

// Apply start time to all probes for tracker
$stmt = $conn->prepare("UPDATE concrete_probes SET start_time = ? WHERE tracker_id = ?");
if (!$stmt) {
    die("Prepare failed (update probes): " . $conn->error);
}
$stmt->bind_param("si", $start_all_time, $tracker_id);
if (!$stmt->execute()) {
    die("Execute failed (update probes): " . $stmt->error);
}
$stmt->close();

$conn->close();

header("Location: concrete_edit_probes.php?id=$tracker_id");
exit;
