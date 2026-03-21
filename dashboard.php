<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

// Stats
$totalRoutines = $db->prepare("SELECT COUNT(*) FROM routines WHERE user_id = ?");
$totalRoutines->execute([$uid]);
$statRoutines = $totalRoutines->fetchColumn();

$totalExercises = $db->prepare("SELECT COUNT(*) FROM exercises WHERE user_id = ?");
$totalExercises->execute([$uid]);
$statExercises = $totalExercises->fetchColumn();

$totalSessions = $db->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id = ?");
$totalSessions->execute([$uid]);
$statSessions = $totalSessions->fetchColumn();

$thisWeek = $db->prepare("SELECT COUNT(*) FROM workout_sessions WHERE user_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$thisWeek->execute([$uid]);
$statWeek = $thisWeek->fetchColumn();

// Recent sessions
$recentSessions = $db->prepare("
    SELECT ws.*, r.name AS routine_name,
    (SELECT COUNT(DISTINCT exercise_id) FROM session_sets WHERE session_id = ws.id) AS exercise_count
    FROM workout_sessions ws
    LEFT JOIN routines r ON r.id = ws.routine_id
    WHERE ws.user_id = ?
    ORDER BY ws.started_at DESC
    LIMIT 5
");
$recentSessions->execute([$uid]);
$sessions = $recentSessions->fetchAll();

// Routines preview
$routines = $db->prepare("SELECT r.*, COUNT(re.id) AS exercise_count FROM routines r LEFT JOIN routine_exercises re ON re.routine_id = r.id WHERE r.user_id = ? GROUP BY r.id ORDER BY r.updated_at DESC LIMIT 4");
$routines->execute([$uid]);
$myRoutines = $routines->fetchAll();
?>

<div class="page-header">
  <h1>DASH<span>BOARD</span></h1>
  <a href="/workout.php" class="btn btn-primary">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    Iniciar Entrenamiento
  </a>
</div>

<!-- Stats -->
<div class="grid grid-3" style="margin-bottom:24px;">
  <div class="stat-box">
    <div class="label">Entrenamientos esta semana</div>
    <div class="value"><?= $statWeek ?></div>
    <div class="sub"><?= $statSessions ?> totales</div>
  </div>
  <div class="stat-box">
    <div class="label">Rutinas creadas</div>
    <div class="value"><?= $statRoutines ?></div>
    <div class="sub"><a href="/routines.php" style="color:var(--accent);text-decoration:none;">Ver todas →</a></div>
  </div>
  <div class="stat-box">
    <div class="label">Ejercicios en biblioteca</div>
    <div class="value"><?= $statExercises ?></div>
    <div class="sub"><a href="/exercises.php" style="color:var(--accent);text-decoration:none;">Gestionar →</a></div>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">

  <!-- Recent sessions -->
  <div class="card">
    <div class="card-title">Últimas Sesiones</div>
    <?php if (empty($sessions)): ?>
      <p style="color:var(--muted);font-size:0.88rem;">Aún no hay sesiones registradas. <a href="/workout.php" style="color:var(--accent);">¡Empezá a entrenar!</a></p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Fecha</th><th>Rutina</th><th>Ejercicios</th></tr></thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
          <tr>
            <td>
              <a href="/history.php?session=<?= $s['id'] ?>" style="color:var(--text);text-decoration:none;font-weight:500;">
                <?= date('d/m/y H:i', strtotime($s['started_at'])) ?>
              </a>
            </td>
            <td><span class="badge badge-yellow"><?= htmlspecialchars($s['routine_name'] ?? $s['name'] ?? 'Libre') ?></span></td>
            <td><?= $s['exercise_count'] ?> ejerc.</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:14px;"><a href="/history.php" class="btn btn-ghost btn-sm">Ver historial completo</a></div>
    <?php endif; ?>
  </div>

  <!-- My routines -->
  <div class="card">
    <div class="card-title">Mis Rutinas</div>
    <?php if (empty($myRoutines)): ?>
      <p style="color:var(--muted);font-size:0.88rem;">No tenés rutinas. <a href="/routines.php" style="color:var(--accent);">Crear una</a></p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <?php foreach ($myRoutines as $r): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:var(--surface2);border-radius:var(--radius);">
        <div>
          <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($r['name']) ?></div>
          <div style="font-size:0.78rem;color:var(--muted);margin-top:2px;"><?= $r['exercise_count'] ?> ejercicios</div>
        </div>
        <div style="display:flex;gap:8px;">
          <a href="/workout.php?routine=<?= $r['id'] ?>" class="btn btn-primary btn-sm">Entrenar</a>
          <a href="/routines.php?edit=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">Editar</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:14px;"><a href="/routines.php" class="btn btn-ghost btn-sm">Ver todas las rutinas</a></div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
