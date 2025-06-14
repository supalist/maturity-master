<?php include '/var/www/maturitymaster.com/userheader.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Archived Events - Maturity Master</title>
  <link rel="stylesheet" href="mm.css">
  <link rel="stylesheet" href="theme.css">
</head>
<body>
<main class="mm-main" style="width: 100%; max-width: 800px; margin: auto; padding: 1rem;">

<?php
require '/var/www/dat.nz/db_connect.php';

function getEventStats($conn, $event_id, $start_time, $end_time) {
    $probes = [];
    $stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $probes[] = $row['mac_address'];
    }
    $stmt->close();

    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];
    $minLine = 'Min: ';
    $maxLine = 'Max: ';
    $lastLine = 'Last: ';

    foreach ($probes as $i => $mac) {
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

            $minLine .= "<span style='color:$color'>" . round($min, 1) . "°C</span> ";
            $maxLine .= "<span style='color:$color'>" . round($max, 1) . "°C</span> ";
            $lastLine .= "<span style='color:$color'>" . round($last, 1) . "°C</span> ";
        }
    }

    return $minLine . "<br>" . $maxLine . "<br>" . $lastLine;
}

$stmt = $conn->prepare("SELECT id, event_name, start_time, end_time, share_key FROM maturity_events WHERE user_id = ? AND archived = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h2 style="text-align: center; font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--header-text);">Archived Events</h2>
<div class="event-list">
<?php if (empty($events)): ?>
    <p style="color: var(--header-text);">No archived projects. <a href="userhome.php" style="color: var(--link-color);">Back to Projects 🏠</a></p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
    <div class="event-card" style="background: var(--card-bg); padding: 1rem; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; color: var(--header-text);">
        <p style="margin: 0 0 10px; font-weight: bold;">
            Event: <span style="font-weight: normal;"><?= htmlspecialchars($event['event_name']) ?></span>
        </p>

        <div style="font-size: 0.9rem; margin-bottom: 10px;">
            <?= getEventStats($conn, $event['id'], $event['start_time'], $event['end_time']) ?>
        </div>

        <div style="margin-bottom: 10px;">
            <small>Link:</small>
            <input type="text" value="https://www.maturitymaster.com/view_event.php?key=<?= $event['share_key'] ?>" style="width:100%; font-size: small;" readonly onclick="this.select();">
        </div>

        <div>
            <a href="view_event.php?key=<?= $event['share_key'] ?>" style="padding: 0.5rem 1rem; background: var(--action-green); color: var(--button-text); text-decoration: none; border-radius: 4px;">📊 View</a>
            <a href="restore_event.php?key=<?= $event['share_key'] ?>" onclick="return confirm('Restore this event?');" style="padding: 0.5rem 1rem; background: var(--button-bg); color: var(--button-text); text-decoration: none; border-radius: 4px;">♻️ Restore</a>
            <a href="delete_event.php?key=<?= $event['share_key'] ?>" onclick="return confirm('Delete this archived event permanently?');" style="padding: 0.5rem 1rem; background: var(--action-red); color: var(--button-text); text-decoration: none; border-radius: 4px;">🗑️ Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<div style="margin-top: 2rem;">
    <a href="userhome.php" class="btn" style="padding: 0.75rem 1.5rem; background: var(--button-bg); color: var(--button-text); text-decoration: none; border-radius: 6px;">⬅️ Back to Live Projects</a>
</div>

</main>
</body>
</html>
