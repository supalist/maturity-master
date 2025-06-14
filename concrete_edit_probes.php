<?php
session_start();

require '/var/www/dat.nz/db_connect.php';

// Unified login check
if (!isset($_SESSION['user_id'])) {
    header("Location: userhome.php");
    exit;
}

$tracker_id = $_GET['id'] ?? null;
if (!$tracker_id) {
    echo "Invalid tracker ID.";
    exit;
}

// Fetch full tracker info
$stmt = $conn->prepare("SELECT * FROM concrete_trackers WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $tracker_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$tracker = $result->fetch_assoc();
$stmt->close();

// Fetch probes
$stmt = $conn->prepare("SELECT id, mac_address, name, start_time FROM concrete_probes WHERE tracker_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $tracker_id);
$stmt->execute();
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
$probes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include 'mm_header.php'; ?>
<nav style="padding: 1rem;">
  <a href="userhome.php">🏠 Dashboard</a> &raquo;
  <span>Edit Probes</span>
</nav>

<main style="max-width: 700px; margin: auto; padding: 2rem;">
  <h2>Edit Probes for Tracker #<?= htmlspecialchars($tracker_id) ?></h2>

  <!-- Top Form: Update Tracker -->
  <form action="save_tracker.php" method="POST" style="margin-bottom: 3rem; border: 2px solid #333; padding: 1rem;">
    <input type="hidden" name="tracker_id" value="<?= $tracker_id ?>">

    <label><strong>Tracker Name</strong></label><br>
    <input type="text" name="tracker_name" value="<?= htmlspecialchars($tracker['tracker_name']) ?>" required><br><br>

    <label><strong>Set Start Time for All Probes</strong></label><br>
    <input type="datetime-local" name="start_all_time"
      value="<?= !empty($tracker['start_time']) ? date('Y-m-d\TH:i', strtotime($tracker['start_time'])) : '' ?>"><br>
    <small>If set, this will apply to all probes when saved.</small><br><br>

    <label><strong>Max Temperature Spread (°C)</strong></label><br>
    <input type="number" name="max_spread" step="0.1"
      value="<?= isset($tracker['max_spread']) ? htmlspecialchars($tracker['max_spread']) : '' ?>"
      placeholder="Optional – e.g. 5"><br>
    <small>If set, probes exceeding this difference will be highlighted.</small>

    <button type="submit">💾 Save Tracker & Start Time</button>
  </form>

  <!-- Bottom Form: Update Probe Names -->
  <form action="save_probes.php" method="POST">
    <input type="hidden" name="tracker_id" value="<?= $tracker_id ?>">

<br>
    <small>this feature below not operational yet.</small><br><br>

    <?php foreach ($probes as $probe): ?>
      <div style="margin-bottom: 1rem; border-bottom: 1px dashed #ccc; padding-bottom: 1rem;">
        <strong><?= htmlspecialchars($probe['mac_address']) ?></strong><br>
        Name: <input type="text" name="names[<?= $probe['id'] ?>]" value="<?= htmlspecialchars($probe['name']) ?>">
      </div>
    <?php endforeach; ?>

    <button type="submit">💾 Save Probe Names</button>
  </form>
</main>
</body>
</html>
