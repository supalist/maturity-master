<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: index.php');
  exit;
}
?>

<link rel="stylesheet" href="mm.css">
<link rel="stylesheet" href="theme.css">

<header class="mm-header">
  <div class="mm-header-inner">
    <a href="/userhome.php" class="mm-logo">Maturity Master</a>
    <nav class="mm-user-controls">
      <input type="checkbox" id="menu-toggle">
      <label for="menu-toggle" class="hamburger">☰</label>
      <ul class="mm-menu">
        <li><a href="create_event.php">➕ Create Event</a></li>
        <li><a href="archived_events.php">📦 Archived Events</a></li>
        <li><a href="index.php" target="_blank">🌐 Home Page</a></li>
      </ul>
      <button onclick="toggleTheme()" title="Toggle Theme" style="background: none; border: none; color: inherit; font-size: 18px; cursor: pointer;">🌓</button>
      <a href="index.php">Logout</a>
    </nav>
  </div>
</header>

<script>
function toggleTheme() {
  const body = document.body;
  const isDark = body.classList.toggle('theme-dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

window.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('theme');
  if (saved === 'dark') {
    document.body.classList.add('theme-dark');
  }
});
</script>
