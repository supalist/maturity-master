<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - Maturity Master</title>
  <link rel="stylesheet" href="mm.css">
  <link rel="stylesheet" href="theme.css">
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0077cc">
</head>
<body>
<?php include '/var/www/maturitymaster.com/userheader.php'; ?>
<main class="mm-main" style="width: 100%; max-width: 800px; margin: auto; padding: 1rem;">
<?php
require '/var/www/dat.nz/db_connect.php';

function getEventStats($conn, $event_id, $start_time, $end_time, $design_strength) {
    $probes = [];
    $stmt = $conn->prepare("SELECT mac_address, location_label FROM maturity_event_probes WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $probes[] = [
            'mac' => $row['mac_address'],
            'label' => $row['location_label'] ?? ''
        ];
    }
    $stmt->close();

    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];
    $labelsLine = '';
    $minLine = '';
    $maxLine = '';
    $lastLine = '';

    foreach ($probes as $i => $probe) {
        $mac = $probe['mac'];
        $label = $probe['label'] ?: ('P' . ($i + 1));

        $stmt = $conn->prepare("SELECT temperature FROM RAWDATA WHERE device_mac_address = ? AND corrected_time BETWEEN ? AND ? ORDER BY corrected_time ASC");
        $stmt->bind_param("sss", $mac, $start_time, $end_time);
        $stmt->execute();
        $res = $stmt->get_result();

        $temps = [];
        while ($r = $res->fetch_assoc()) {
            $temps[] = $r['temperature'];
        }
        $stmt->close();

        if (!empty($temps)) {
            $min = min($temps);
            $max = max($temps);
            $last = end($temps);
            $color = $colors[$i % count($colors)];

            $labelsLine .= "<span class='stat-reading' style='color:$color; font-weight:bold'>" . htmlspecialchars($label) . "</span> ";
            $minLine    .= "<span class='stat-reading' style='color:$color'>" . number_format($min, 1) . "°C</span> ";
            $maxLine    .= "<span class='stat-reading' style='color:$color'>" . number_format($max, 1) . "°C</span> ";
            $lastLine   .= "<span class='stat-reading' style='color:$color'>" . number_format($last, 1) . "°C</span> ";
        }
    }

    $maturity = 0;
    $last_time = null;
    if (!empty($probes)) {
        $stmt = $conn->prepare("SELECT corrected_time, temperature FROM RAWDATA WHERE device_mac_address = ? AND corrected_time BETWEEN ? AND ? ORDER BY corrected_time ASC");
        $stmt->bind_param("sss", $probes[0]['mac'], $start_time, $end_time);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $current_time = strtotime($row['corrected_time']);
            if ($last_time !== null) {
                $delta_hours = ($current_time - $last_time) / 3600;
                $Tavg = $row['temperature'];
                if ($Tavg > 0) {
                    $maturity += ($Tavg - 0) * $delta_hours;
                }
            }
            $last_time = $current_time;
        }
        $stmt->close();
    }

    $target = (int) $design_strength;
    $predicted = round(min($target, ($target * $maturity / 2800)), 1);
    $percent = round(($predicted / $target) * 100);

    return [
        'predicted' => $predicted,
        'percent' => $percent,
        'target' => $target,
        'labelsLine' => $labelsLine,
        'minLine' => $minLine,
        'maxLine' => $maxLine,
        'lastLine' => $lastLine
    ];
}

$stmt = $conn->prepare("SELECT id, event_name, start_time, end_time, share_key, design_strength, mix_type FROM maturity_events WHERE user_id = ? AND archived = 0 ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h2 style="text-align: center; font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--header-text);">Your Project/s</h2>
<div class="event-list">
<?php if (empty($events)): ?>
    <p style="color: var(--header-text);">No projects yet. <a href="create_event.php" style="color: var(--link-color);">Create your first project ➕</a></p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
    <?php $stats = getEventStats($conn, $event['id'], $event['start_time'], $event['end_time'], $event['design_strength']); ?>
    <div class="event-card" style="background: var(--card-bg); padding: 1rem; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; color: var(--header-text);">
        <p style="margin: 0 0 10px; font-weight: bold;">
            Project: <span style="font-weight: normal;"><?= htmlspecialchars($event['event_name']) ?></span>
        </p>

        <div class="stat-card-wrapper" style="display: flex; flex-wrap: wrap; gap: 1rem;">
          <div class="stat-card" style="flex: 1 1 300px; border-radius: 10px; padding: 1rem; background: rgba(255,255,255,0.05); box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
            <div class="label" style="font-weight: bold; margin-bottom: 0.25rem; color: var(--accent);">Estimated Strength</div>
            <div class="value" style="font-size: 1.5rem;"><?= $stats['predicted'] ?> MPa</div>
            <div class="sub" style="font-size: 0.9rem; color: var(--text-light);"><?= $stats['percent'] ?>% of <?= $stats['target'] ?> MPa</div>
          </div>

          <div class="stat-card" style="flex: 1 1 300px; border-radius: 10px; padding: 1rem; background: rgba(255,255,255,0.05); box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
            <div style="font-weight: bold; color: var(--accent); margin-bottom: 0.25rem;">Temperature</div>
<div style="display: grid; grid-template-columns: auto 1fr; row-gap: 0.25rem; font-size: 0.9rem;">
  <div style="text-align: right; padding-right: 0.5rem;">Latest:</div>
  <div><?= $stats['lastLine'] ?></div>
  <div style="text-align: right; padding-right: 0.5rem;">Min:</div>
  <div><?= $stats['minLine'] ?></div>
  <div style="text-align: right; padding-right: 0.5rem;">Max:</div>
  <div><?= $stats['maxLine'] ?></div>
  <div style="text-align: right; padding-right: 0.5rem;">Legend:</div>
  <div style="font-size: 0.85rem; display: flex; flex-wrap: wrap; gap: 1rem; color: var(--text-light);">
    <?= $stats['labelsLine'] ?>
  </div>
</div>
          </div>
        </div>

        <div style="margin-top: 10px; margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 0.5rem;">
          <button onclick="copyLink('link-<?= $event['id'] ?>')" style="padding: 0.5rem 1rem; background: var(--button-bg); color: var(--button-text); border: none; border-radius: 4px; cursor: pointer;">
            📋 Copy Link
          </button>
          <a href="view_event.php?key=<?= $event['share_key'] ?>" style="padding: 0.5rem 1rem; background: var(--action-green); color: var(--button-text); text-decoration: none; border-radius: 4px;">📊 View</a>
          <a href="edit_event.php?key=<?= $event['share_key'] ?>" style="padding: 0.5rem 1rem; background: var(--action-gray); color: var(--button-text); text-decoration: none; border-radius: 4px;">✏️ Edit</a>
          <a href="archive_event.php?key=<?= $event['share_key'] ?>" onclick="return confirm('Archive this project? It can be retrieved later');" style="padding: 0.5rem 1rem; background: #555; color: white; text-decoration: none; border-radius: 4px;">📦 Archive</a>
        </div>
        <input type="text" id="link-<?= $event['id'] ?>" value="https://www.maturitymaster.com/view_event.php?key=<?= $event['share_key'] ?>" readonly style="position:absolute; left:-9999px;">
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div style="margin-top: 1rem;">
    <a href="create_event.php" class="btn" style="padding: 0.75rem 1.5rem; background: var(--button-bg); color: var(--button-text); text-decoration: none; border-radius: 6px;">
        ➕ New Project
    </a>
</div>

</main>
<script>
function copyLink(id) {
  const input = document.getElementById(id);
  input.select();
  document.execCommand('copy');
  alert("🔗 Link copied to clipboard!");
}
</script>

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js')
    .then(() => console.log('✅ Service Worker registered'))
    .catch(err => console.error('Service Worker error:', err));
}
</script>
</body>
</html>
