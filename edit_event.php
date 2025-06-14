<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

$share_key = $_GET['key'] ?? '';
if (!$share_key) exit("Invalid key.");

$stmt = $conn->prepare("SELECT * FROM maturity_events WHERE share_key = ? AND user_id = ?");
$stmt->bind_param("si", $share_key, $_SESSION['user_id']);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) exit("Event not found or not yours.");


// Get probe data including labels
$probes = [];
$stmt = $conn->prepare("SELECT mac_address, location_label FROM maturity_event_probes WHERE event_id = ?");
$stmt->bind_param("i", $event['id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $probes[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['event_name'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $mix = $_POST['mix_type'] ?? 'standard';
    $design_strength = (int) ($_POST['design_strength'] ?? 25);
    $new_probes = $_POST['probes'] ?? [];
    $labels = $_POST['labels'] ?? [];

    // Update the event details
    $stmt = $conn->prepare("UPDATE maturity_events SET event_name = ?, start_time = ?, end_time = ?, mix_type = ?, design_strength = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $name, $start, $end, $mix, $design_strength, $event['id']);
    $stmt->execute();
    $stmt->close();

    // Remove existing probes
    $stmt = $conn->prepare("DELETE FROM maturity_event_probes WHERE event_id = ?");
    $stmt->bind_param("i", $event['id']);
    $stmt->execute();
    $stmt->close();

    // Insert updated probes
    $stmt = $conn->prepare("INSERT INTO maturity_event_probes (event_id, mac_address, location_label) VALUES (?, ?, ?)");
    foreach ($new_probes as $i => $mac) {
        $clean_mac = strtoupper(trim($mac));
        $label = trim($labels[$i] ?? '');
        if (strpos($clean_mac, 'CA:FE:') === 0) {
            $stmt->bind_param("iss", $event['id'], $clean_mac, $label);
            $stmt->execute();
        }
    }
    $stmt->close();

    header("Location: userhome.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Event - Maturity Master</title>
  <link rel="stylesheet" href="mm.css">
  <link rel="stylesheet" href="theme.css">
</head>
<body>
<?php include 'userheader.php'; ?>
<main class="mm-main" style="max-width: 800px; margin: auto;">

  <h2 style="margin-bottom: 1rem;">Edit Project</h2>

<form method="POST" onsubmit="return validateForm();" style="display: flex; flex-direction: column; gap: 1rem; max-width: 400px; margin: auto;">
    <label>Event Name:
      <input type="text" name="event_name" value="<?= htmlspecialchars($event['event_name']) ?>" required>
    </label>

<div style="display: none;">
  <label for="mix_type">Mix Type:</label>
  <select name="mix_type" id="mix_type">
    <option value="standard">Standard (20–45 MPa)</option>
    <option value="flyash">Fly-Ash Blend (slow cure)</option>
    <option value="high_early">High Early Strength</option>
  </select>
</div>

<div style="display: none;">
  <label for="target_strength">Design Strength (MPa):</label>
  <input type="number" name="target_strength" id="target_strength" step="1" min="5" max="100">
</div>

    <label>Start Time:
      <input type="datetime-local" name="start_time" value="<?= date('Y-m-d\TH:i', strtotime($event['start_time'])) ?>" required>
    </label>

    <label>End Time:
      <input type="datetime-local" name="end_time" value="<?= date('Y-m-d\TH:i', strtotime($event['end_time'])) ?>" required>
    </label>

<div>
  <label>Probes:</label>
  <div id="probes" style="display: flex; flex-direction: column; gap: 0.5rem;">
    <?php foreach ($probes as $row): ?>
      <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
        <input type="text" name="probes[]" value="<?= htmlspecialchars($row['mac_address']) ?>" maxlength="17" style="font-family: monospace; flex: 2;" required>
        <input type="text" name="labels[]" value="<?= htmlspecialchars($row['location_label']) ?>" placeholder="e.g. core, top, edge" style="flex: 2;">
        <button type="button" onclick="this.parentElement.remove()" style="flex: 0;">❌</button>
      </div>
    <?php endforeach; ?>

    <?php if (empty($probes)): ?>
      <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
        <input type="text" name="probes[]" placeholder="CA:FE:00:00:00:00" maxlength="17" style="font-family: monospace; flex: 2;" required>
        <input type="text" name="labels[]" placeholder="e.g. core" style="flex: 2;">
        <button type="button" onclick="this.parentElement.remove()" style="flex: 0;">❌</button>
      </div>
    <?php endif; ?>
  </div>
  <button type="button" onclick="addProbe()" style="margin-top: 0.5rem;">➕ Add Another Probe</button>
</div>
    <button type="submit" style="padding: 0.75rem; background: var(--button-bg); color: var(--button-text); border: none; border-radius: 6px; cursor: pointer;">💾 Save Changes</button>
</form>
</main>

<script>
function addProbe(mac = '', label = '') {
  const div = document.createElement('div');
  div.style.display = 'flex';
  div.style.gap = '0.5rem';
  div.style.alignItems = 'flex-end';

  div.innerHTML = `
    <input type="text" name="probes[]" value="${mac}" placeholder="CA:FE:00:00:00:00" maxlength="17" style="font-family: monospace; flex: 2;" required>
    <input type="text" name="labels[]" value="${label}" placeholder="e.g. core" style="flex: 2;">
    <button type="button" onclick="this.parentElement.remove()" style="flex: 0;">❌</button>
  `;

  document.getElementById('probes').appendChild(div);
}

// Optional: Load this on page if probes are empty
window.addEventListener('DOMContentLoaded', () => {
  if (document.querySelectorAll('#probes > div').length === 0) {
    addProbe();
  }
});
</script>
</body>
</html>
