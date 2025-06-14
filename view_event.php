<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: userhome.php");
    exit;
}

$share_key = $_GET['key'] ?? '';
if (!$share_key) {
    echo "Invalid event key.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM maturity_events WHERE share_key = ?");
$stmt->bind_param("s", $share_key);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    echo "Event not found.";
    exit;
}

$stmt = $conn->prepare("SELECT mac_address FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event['id']);
$stmt->execute();
$probes_result = $stmt->get_result();
$probes = [];
while ($row = $probes_result->fetch_assoc()) {
    $probes[] = $row['mac_address'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Event - Maturity Master</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="mm.css">
  <link rel="stylesheet" href="theme.css">
</head>
<body>
<?php include 'userheader.php'; ?>
<main class="mm-main">
  <h2 style="margin-bottom: 0.5rem; color: var(--header-text);">Project: <?= htmlspecialchars($event['event_name']) ?></h2>

  <?php
  $typeName = [
      'standard' => 'Standard Mix',
      'flyash' => 'Fly-Ash Blend',
      'high_early' => 'High Early Strength'
  ][$event['mix_type']] ?? 'Unknown';

  $design = (int) $event['design_strength'];
  $maturity = 0;
  $last_time = null;

  if (!empty($probes)) {
      $stmt = $conn->prepare("SELECT corrected_time, temperature FROM RAWDATA WHERE device_mac_address = ? AND corrected_time BETWEEN ? AND ? ORDER BY corrected_time ASC");
      $stmt->bind_param("sss", $probes[0], $event['start_time'], $event['end_time']);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
          $cur = strtotime($row['corrected_time']);
          if ($last_time !== null) {
              $delta_hr = ($cur - $last_time) / 3600;
              $T = $row['temperature'];
              if ($T > 0) $maturity += ($T - 0) * $delta_hr;
          }
          $last_time = $cur;
      }
      $stmt->close();
  }

  switch ($event['mix_type']) {
      case 'flyash':     $maturity_100 = 3800; $mult = 1.0; break;
      case 'high_early': $maturity_100 = 2500; $mult = 1.25; break;
      default:           $maturity_100 = 2800; $mult = 1.0;
  }

  $predicted = round(min(($design * $maturity / $maturity_100) * $mult, $design), 1);
  $percent = round(($predicted / $design) * 100);
  ?>

  <div class="stat-card-wrapper" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <div class="stat-card" style="flex: 1 1 300px;">
      <div class="label">Mix Type</div>
      <div class="value"><?= htmlspecialchars($typeName) ?></div>
      <div class="sub">Design Strength: <?= $design ?> MPa</div>
      <div class="sub">Estimated Strength: <?= $predicted ?> MPa (<?= $percent ?>%)</div>
    </div>

    <div class="stat-card" style="flex: 1 1 300px;">
      <div style="font-weight: bold; color: var(--accent); margin-bottom: 0.25rem;">Temperature</div>
      <div style="display: grid; grid-template-columns: auto 1fr; row-gap: 0.25rem; font-size: 0.9rem;" id="probe-stats-grid">
        <div style="text-align: right; padding-right: 0.5rem;">Latest:</div>
        <div id="latestTemps"></div>
        <div style="text-align: right; padding-right: 0.5rem;">Min:</div>
        <div id="minTemps"></div>
        <div style="text-align: right; padding-right: 0.5rem;">Max:</div>
        <div id="maxTemps"></div>
        <div style="text-align: right; padding-right: 0.5rem;">Legend:</div>
        <div id="probeLabels" style="font-size: 0.85rem; display: flex; flex-wrap: wrap; gap: 1rem; color: var(--text-light);"></div>
      </div>
    </div>
  </div>

  <div style="width: 90%; height: 300px; margin: auto;" id="graph-container">
    <canvas id="eventChart"></canvas>
  </div>
<div id="nurse-saul-container">
  <canvas id="nurseSaulChart"></canvas>
</div>
<script src="nurse_saul_loader.js" type="module"></script>

<div style="display: flex; flex-wrap: wrap; gap: 2rem; margin-top: 1rem; font-size: 0.95rem;">
  <p style="margin: 0;"><strong>Start:</strong> <?= htmlspecialchars($event['start_time']) ?></p>
  <p style="margin: 0;"><strong>End:</strong> <?= htmlspecialchars($event['end_time']) ?></p>
  <p style="margin: 0;"><strong>Probes:</strong> <?= implode(', ', $probes) ?></p>
</div>

  <div style="margin-top: 1rem;">
    <a href="download_event_csv.php?key=<?= $share_key ?>" class="btn" style="padding: 0.5rem 1rem; background: var(--button-bg); color: var(--button-text); text-decoration: none; border-radius: 4px;">
      📥 Download Temp Data CSV
    </a>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>
<script>
const shareKey = "<?= $share_key ?>";
const isMobile = window.innerWidth < 600;

fetch("fetch_event_graph_data.php?key=" + shareKey + "&mobile=" + (isMobile ? "1" : "0"))
.then(res => res.json())
.then(data => {
  const ctx = document.getElementById('eventChart').getContext('2d');
  const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];

  const startTime = "<?= $event['start_time'] ?>";
  const endTime = "<?= $event['end_time'] ?>";

  if (window.eventChartInstance) window.eventChartInstance.destroy();

  window.eventChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      datasets: data.datasets.map((ds, i) => ({
        label: ds.label,
        data: ds.data,
        borderColor: colors[i % colors.length],
        backgroundColor: 'transparent',
        borderWidth: 2,
        tension: 0.2
      }))
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: { display: true, text: 'Event Temperature Data' },
        legend: { display: true, position: 'bottom' }
      },
      scales: {
        y: {
          title: { display: true, text: 'Temperature (°C)' }
        },
        x: {
          type: 'time',
          min: startTime,
          max: endTime,
          time: {
            tooltipFormat: 'HH:mm dd MMM',
            displayFormats: { minute: 'HH:mm dd MMM', hour: 'HH:mm dd MMM' }
          },
          title: { display: true, text: 'Time' },
          ticks: {
            maxTicksLimit: isMobile ? 4 : 12,
            autoSkip: true,
            maxRotation: 45,
            minRotation: 45
          }
        }
      }
    }
  });

  const mins = [], maxs = [], lasts = [];

  data.datasets.forEach((ds, i) => {
    const temps = ds.data.map(pt => pt.y).filter(v => v !== null);
    if (!temps.length) return;

    const min = Math.min(...temps).toFixed(1);
    const max = Math.max(...temps).toFixed(1);
    const last = temps[temps.length - 1].toFixed(1);
    const color = colors[i % colors.length];

    mins.push(`<span style="color:${color}">${min}°C</span>`);
    maxs.push(`<span style="color:${color}">${max}°C</span>`);
    lasts.push(`<span style="color:${color}">${last}°C</span>`);
  });

  document.getElementById("latestTemps").innerHTML = lasts.join(" ");
  document.getElementById("minTemps").innerHTML = mins.join(" ");
  document.getElementById("maxTemps").innerHTML = maxs.join(" ");
  document.getElementById("probeLabels").innerHTML = data.datasets.map((ds, i) => {
    const color = colors[i % colors.length];
    return `<span style="color:${color}; font-weight:bold">${ds.label}</span>`;
  }).join(" ");
})
.catch(err => console.error("Chart load failed:", err));
</script>
</body>
</html>
