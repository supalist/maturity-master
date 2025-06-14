<?php
session_start();
require '/var/www/dat.nz/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: userhome.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Event</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="mm.css">
  <link rel="stylesheet" href="theme.css">
</head>
<body>
<?php include 'userheader.php'; ?>

<main class="mm-main">

  <h2 style="color: var(--header-text);">Create New Log Event</h2>

<form action="save_event.php" method="POST" onsubmit="return validateForm();" style="color: var(--header-text); display: flex; flex-direction: column; gap: 1rem; max-width: 400px; margin: auto;">
    <label>Event Name:</label>
    <input type="text" name="event_name" required>

    <?php
    $now = date('Y-m-d\TH:i');
    $plus7 = date('Y-m-d\TH:i', strtotime('+7 days'));
    ?>

    <label>Start Time:</label>
    <input type="datetime-local" name="start_time" value="<?= $now ?>" required>

    <label>End Time:</label>
    <input type="datetime-local" name="end_time" value="<?= $plus7 ?>" required>

    <label>Mix Type:</label>
    <select name="mix_type" required>
      <option value="standard">Standard (20–45 MPa)</option>
      <option value="flyash">Fly-Ash Blend (slow cure)</option>
      <option value="high_early">High Early Strength</option>
    </select>

    <label>Target Strength (MPa):</label>
    <input type="number" name="design_strength" min="10" max="100" required>

    <label>Probes (add up to any number you want):</label>
    <div id="probes" class="probe-wrapper">
      <div class="probe-row">
        <input type="text" name="probes[]" placeholder="CA:FE:00:00:00:00" maxlength="17" required>
        <input type="text" name="labels[]" placeholder="e.g. core, surface...">
      </div>
    </div>

    <button type="button" onclick="addProbe()">➕ Add Another Probe</button>
    <button type="submit">✅ Create Event</button>
  </form>
</main>

<script>
function addProbe() {
  const div = document.createElement('div');
  div.className = 'probe-row';
  div.innerHTML = `
    <input type="text" name="probes[]" placeholder="CA:FE:00:00:00:00" maxlength="17" required>
    <input type="text" name="labels[]" placeholder="e.g. edge, middle">`;
  document.getElementById('probes').appendChild(div);
}

function validateForm() {
  const inputs = document.querySelectorAll('#probes input[name="probes[]"]');
  for (let input of inputs) {
    if (!input.value.toUpperCase().startsWith('CA:FE:')) {
      alert('All probe addresses must start with CA:FE:');
      return false;
    }
  }
  return true;
}
</script>
</body>
</html>
