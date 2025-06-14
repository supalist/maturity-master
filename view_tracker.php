<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: userhome.php");
  exit;
}

// Tracker ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $tracker_id = (int)$_GET['id'];
  $_SESSION['last_tracker_id'] = $tracker_id;
} elseif (isset($_SESSION['last_tracker_id'])) {
  $tracker_id = (int)$_SESSION['last_tracker_id'];
} else {
  echo "<main style='padding: 2rem; max-width: 600px; margin: auto;'><h2>Invalid tracker ID</h2></main></body></html>";
  exit;
}

// Get tracker
$stmt = $conn->prepare("SELECT * FROM concrete_trackers WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $tracker_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$tracker = $result->fetch_assoc();
$stmt->close();

if (!$tracker) {
  echo "<main style='padding: 2rem; max-width: 600px; margin: auto;'><h2>Tracker not found</h2></main></body></html>";
  exit;
}
?>

<?php include 'userheader.php'; ?>
<main style="max-width: 800px; margin: auto; padding: 2rem;">
  <nav style="padding-bottom: 1rem;">
    <a href="userhome.php">🏠 Dashboard</a> &raquo; Tracker: <?= htmlspecialchars($tracker['tracker_name']) ?>
  </nav>

  <h2><?= htmlspecialchars($tracker['tracker_name']) ?></h2>
  <p><strong>Base MAC:</strong> <?= htmlspecialchars($tracker['base_mac']) ?></p>

  <div style="height: 500px;">
    <canvas id="tempChart"></canvas>
  </div>

  <a href="concrete_edit_probes.php?id=<?= $tracker_id ?>" 
     class="btn" 
     style="padding: 0.5rem 1rem; background: #555; color: #fff; text-decoration: none; margin-top: 1rem; display: inline-block;">
    ✏️ Edit Probes
  </a>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>

<script>
  const trackerId = <?= $tracker_id ?>;
  const fetchDataAndRenderChart = () => {
fetch("fetch_concrete_graph_data.php?id=" + trackerId, { credentials: 'same-origin' })
      .then(res => res.json())
      .then(data => {
        const ctx = document.getElementById('tempChart').getContext('2d');
        const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];

        if (window.tempChartInstance) {
          window.tempChartInstance.destroy();
        }

        window.tempChartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            datasets: data.datasets.map((dataset, i) => ({
              ...dataset,
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
              title: {
                display: true,
                text: 'Temperature Readings (Last 100)'
              },
              legend: {
                display: true,
                position: 'bottom'
              }
            },
            scales: {
              y: {
                min: 0,
                max: 40,
                title: { display: true, text: 'Temperature (°C)' }
              },
              x: {
                type: 'time',
                time: {
                  tooltipFormat: 'HH:mm',
                  displayFormats: { minute: 'HH:mm', hour: 'HH:mm' }
                },
                title: { display: true, text: 'Time' }
              }
            }
          }
        });
      })
      .catch(err => console.error("Chart load failed:", err));
  };

  fetchDataAndRenderChart();
  setInterval(fetchDataAndRenderChart, 60000);
</script>
</body>
</html>
