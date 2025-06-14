<nav class="breadcrumb" style="padding: 1rem 2rem;">
  <a href="userhome.php">🏠 Dashboard</a> &raquo;
  <span>Add Tracker</span>
</nav>

<main style="padding: 0 2rem 2rem 2rem; max-width: 600px; margin: auto;">
  <h2>Add a New Tracker Device</h2>

  <form action="save_tracker.php" method="POST" onsubmit="return formatMACBeforeSubmit();">
    <label>Tracker Name</label><br>
    <input type="text" name="tracker_name" required><br><br>

<label>Base MAC Address</label><br>
<input type="text" id="base_mac" name="base_mac" value="CA:FE:" maxlength="17" placeholder="CA:FE:00:00:00" required style="font-family: monospace; width: 180px;">
<small>Only last digit is forced to <strong>0</strong>.</small><br><br>
<label>Start Time</label><br>
<input type="datetime-local" name="start_all_time" required><br><br>

    <button type="submit">➕ Add Tracker</button>

  </form>
</main>

<script>
const macField = document.getElementById('base_mac');

macField.addEventListener('input', () => {
  let val = macField.value.toUpperCase().replace(/[^0-9A-F]/g, '');

  // Always start with CA:FE:
  if (!val.startsWith('CAFE')) val = 'CAFE' + val.replace(/^CAFE/, '');

  // Build MAC format (6 pairs)
  let formatted = '';
  for (let i = 0; i < 6 && val.length > i * 2; i++) {
    let pair = val.substr(i * 2, 2);
    formatted += pair + (i < 5 ? ':' : '');
  }

  // Fix last digit to 0
  if (formatted.length >= 17) {
    formatted = formatted.substring(0, 16) + '0';
  }

  macField.value = formatted;
});
</script>

</body>
</html>
