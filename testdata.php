<?php
session_start();
date_default_timezone_set('Pacific/Auckland');
require '/var/www/dat.nz/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$baseMacs = [
    'CA:FE:00:00:00:90',
    'CA:FE:00:00:00:91',
    'CA:FE:00:00:00:92'
];

// --- Handle delete ---
if (isset($_GET['delete']) && $_GET['delete'] === 'true') {
    $placeholders = implode(',', array_fill(0, count($baseMacs), '?'));
    $types = str_repeat('s', count($baseMacs));

    $stmt = $conn->prepare("DELETE FROM RAWDATA WHERE device_mac_address IN ($placeholders)");
    $stmt->bind_param($types, ...$baseMacs);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    echo "<h3>✅ Deleted $deleted test records from RAWDATA.</h3>";
    echo '<a href="testdata.php">Back to Add Test Data</a>';
    exit;
}

// --- Get MySQL server time ---
$result = $conn->query("SELECT UNIX_TIMESTAMP()");
$row = $result->fetch_row();
$mysqlNow = $row[0];

echo "<h2>Generating test data into RAWDATA...</h2>";

$startTime = $mysqlNow - (3 * 24 * 60 * 60); // 3 days ago
$endTime = floor($mysqlNow / 1800) * 1800;  // current time snapped to last full 30 min slot
$interval = 60 * 30; // 30 minutes

$peakTemp = 35;
$baseTemp = 15;

foreach ($baseMacs as $index => $mac) {
    $timeShiftSeconds = 0;
    $tempShift = 0;

    if ($index === 1) {
        $timeShiftSeconds = 3600;  // 1 hour behind
        $tempShift = +3;
    } elseif ($index === 2) {
        $timeShiftSeconds = 7200;  // 2 hours behind
        $tempShift = -5;
    }

    $insertCount = 0;
    for ($t = $startTime; $t <= $endTime; $t += $interval) {
        $shiftedTime = $t - $timeShiftSeconds; // ✅ probes behind baseline

        $progress = ($t - $startTime) / ($endTime - $startTime);
        $temp = $baseTemp + ($peakTemp - $baseTemp) * sin($progress * pi());
        $temp += $tempShift;
        $temp += mt_rand(-2, 2) * 0.05;

        $corrected_time = date('Y-m-d H:i:s', $shiftedTime);
        $temperature = round($temp, 1);

        $stmt = $conn->prepare("INSERT INTO RAWDATA (device_mac_address, corrected_time, temperature) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $mac, $corrected_time, $temperature);
        $stmt->execute();
        $stmt->close();

        $insertCount++;
    }

    echo "Inserted $insertCount readings for $mac<br>";
}

echo "<h3>✅ Test data generation complete with delays + perfect variance + no drift.</h3>";

// --- Add delete button ---
echo '<br><a href="?delete=true" style="padding: 0.5rem 1rem; background: #a00; color: #fff; text-decoration: none; border-radius: 4px;">🗑️ Delete All Test Data</a>';
?>
