<?php
require_once __DIR__ . '/../includes/auth.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageInitial = strtoupper(substr($user['username'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ConInf <?= isset($pageTitle) ? '· ' . htmlspecialchars($pageTitle) : '· AppGym' ?></title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%23d9ff4c'/><text x='50%25' y='54%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-weight='900' font-size='18' fill='%23000'>I</text></svg>">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php if (isLoggedIn()): ?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">I</div>
    <div class="sidebar-logo-text">
      <strong>ConInf</strong>
      <small>AppGym</small>
    </div>
  </div>

  <nav>
    <div class="nav-section-label">Principal</div>

    <a href="/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      <span class="nav-label">Dashboard</span>
    </a>

    <a href="/workout.php" class="<?= $currentPage === 'workout' ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
      <span class="nav-label">Entrenar</span>
    </a>

    <div class="nav-section-label">Gestión</div>

    <a href="/routines.php" class="<?= $currentPage === 'routines' ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <span class="nav-label">Rutinas</span>
    </a>

    <a href="/exercises.php" class="<?= $currentPage === 'exercises' ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
      </svg>
      <span class="nav-label">Ejercicios</span>
    </a>

    <div class="nav-section-label">Progreso</div>

    <a href="/history.php" class="<?= $currentPage === 'history' ? 'active' : '' ?>">
      <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="nav-label">Historial</span>
    </a>

  </nav>

  <div class="sidebar-user">
    <div class="sidebar-user-inner">
      <div class="sidebar-avatar"><?= $pageInitial ?></div>
      <div class="sidebar-user-info">
        <div class="uname"><?= htmlspecialchars($user['username'] ?? '') ?></div>
        <a href="/logout.php">Cerrar sesión</a>
      </div>
    </div>
  </div>

</aside>

<!-- Topbar (mobile) -->
<div class="topbar">
  <button class="topbar-toggle" onclick="openSidebar()" aria-label="Abrir menú">
    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>
  <div class="topbar-title">ConInf</div>
  <a href="/workout.php" class="btn btn-primary btn-sm">+ Entrenar</a>
</div>

<?php endif; ?>

<div class="main" style="<?= !isLoggedIn() ? 'margin-left:0;max-width:100vw;' : '' ?>">
<div class="main-inner" style="<?= !isLoggedIn() ? 'padding:0;' : '' ?>">

<script>
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
</script>
