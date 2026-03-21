<?php
$pageTitle = 'Historial';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

$sessionId = (int)($_GET['session'] ?? 0);

if ($sessionId) {
    // Session detail view
    $sv = $db->prepare("SELECT ws.*, r.name AS routine_name FROM workout_sessions ws LEFT JOIN routines r ON r.id=ws.routine_id WHERE ws.id=? AND ws.user_id=?");
    $sv->execute([$sessionId, $uid]);
    $session = $sv->fetch();

    if (!$session) {
        header('Location: /history.php');
        exit;
    }

    $sets = $db->prepare("SELECT ss.*, e.name AS ex_name, e.muscle_group FROM session_sets ss JOIN exercises e ON e.id=ss.exercise_id WHERE ss.session_id=? ORDER BY ss.exercise_id, ss.set_number");
    $sets->execute([$sessionId]);
    $allSets = $sets->fetchAll();

    $byExercise = [];
    foreach ($allSets as $s) {
        $byExercise[$s['exercise_id']]['name'] = $s['ex_name'];
        $byExercise[$s['exercise_id']]['muscle'] = $s['muscle_group'];
        $byExercise[$s['exercise_id']]['sets'][] = $s;
    }
?>
<div class="page-header">
  <div>
    <h1>DETA<span>LLE</span></h1>
    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px;"><?= htmlspecialchars($session['name']) ?></div>
  </div>
  <a href="/history.php" class="btn btn-ghost">← Volver al historial</a>
</div>

<div class="grid grid-2" style="align-items:start;margin-bottom:24px;">
  <div class="stat-box">
    <div class="label">Fecha</div>
    <div style="font-size:1.3rem;font-weight:600;color:var(--text);margin-top:4px;"><?= date('d/m/Y H:i', strtotime($session['started_at'])) ?></div>
  </div>
  <div class="stat-box">
    <div class="label">Duración</div>
    <?php
    $dur = '—';
    if ($session['finished_at']) {
        $diff = strtotime($session['finished_at']) - strtotime($session['started_at']);
        $dur = floor($diff/60) . ' min';
    }
    ?>
    <div style="font-size:1.3rem;font-weight:600;color:var(--text);margin-top:4px;"><?= $dur ?></div>
  </div>
</div>

<?php if ($session['notes']): ?>
<div class="card" style="margin-bottom:20px;border-left:3px solid var(--accent);">
  <div style="font-size:0.78rem;color:var(--muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.5px;">Notas</div>
  <p style="font-size:0.9rem;"><?= nl2br(htmlspecialchars($session['notes'])) ?></p>
</div>
<?php endif; ?>

<?php if (empty($byExercise)): ?>
<div class="card"><p style="color:var(--muted);">No se registraron series en esta sesión.</p></div>
<?php else: ?>
<div class="grid grid-2">
  <?php foreach ($byExercise as $exId => $exData):
    $totalVol = 0;
    $bestWeight = 0;
    foreach ($exData['sets'] as $s) {
        if ($s['weight']) { $totalVol += $s['weight'] * $s['reps']; $bestWeight = max($bestWeight, $s['weight']); }
    }
  ?>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
      <div>
        <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:1px;"><?= htmlspecialchars($exData['name']) ?></div>
        <?php if ($exData['muscle']): ?><span class="badge badge-yellow"><?= htmlspecialchars($exData['muscle']) ?></span><?php endif; ?>
      </div>
      <div style="text-align:right;">
        <div style="font-size:0.75rem;color:var(--muted);">Volumen total</div>
        <div style="font-size:1.1rem;font-weight:700;color:var(--accent);"><?= number_format($totalVol, 1) ?>kg</div>
      </div>
    </div>
    <table style="width:100%;font-size:0.85rem;">
      <thead><tr><th style="padding:5px 8px;">Serie</th><th style="padding:5px 8px;">Peso</th><th style="padding:5px 8px;">Reps</th><th style="padding:5px 8px;">Volumen</th></tr></thead>
      <tbody>
        <?php foreach ($exData['sets'] as $s): ?>
        <tr>
          <td style="padding:6px 8px;color:var(--muted);">#<?= $s['set_number'] ?></td>
          <td style="padding:6px 8px;font-weight:600;"><?= $s['weight'] !== null ? $s['weight'].'kg' : '—' ?></td>
          <td style="padding:6px 8px;"><?= $s['reps'] ?> reps</td>
          <td style="padding:6px 8px;color:var(--muted);"><?= $s['weight'] ? number_format($s['weight'] * $s['reps'], 1).'kg' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($bestWeight): ?>
    <div style="margin-top:10px;font-size:0.78rem;color:var(--muted);">🏆 Mayor peso: <strong style="color:var(--text);"><?= $bestWeight ?>kg</strong></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php } else { // History list ?>

<div class="page-header">
  <h1>HISTO<span>RIAL</span></h1>
</div>

<?php
// Filters
$month = $_GET['month'] ?? '';
$filterRoutine = (int)($_GET['routine'] ?? 0);

$query = "SELECT ws.*, r.name AS routine_name,
    COUNT(DISTINCT ss.exercise_id) AS ex_count,
    COUNT(ss.id) AS set_count,
    COALESCE(SUM(ss.weight * ss.reps), 0) AS total_volume,
    TIMESTAMPDIFF(MINUTE, ws.started_at, ws.finished_at) AS duration_min
    FROM workout_sessions ws
    LEFT JOIN routines r ON r.id=ws.routine_id
    LEFT JOIN session_sets ss ON ss.session_id=ws.id
    WHERE ws.user_id=?";
$params = [$uid];

if ($month) {
    $query .= " AND DATE_FORMAT(ws.started_at, '%Y-%m') = ?";
    $params[] = $month;
}
if ($filterRoutine) {
    $query .= " AND ws.routine_id = ?";
    $params[] = $filterRoutine;
}
$query .= " GROUP BY ws.id ORDER BY ws.started_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Routines for filter
$routines = $db->prepare("SELECT id, name FROM routines WHERE user_id=? ORDER BY name");
$routines->execute([$uid]);
$routineList = $routines->fetchAll();

// Month filter options
$months = $db->prepare("SELECT DISTINCT DATE_FORMAT(started_at, '%Y-%m') AS m, DATE_FORMAT(started_at, '%B %Y') AS label FROM workout_sessions WHERE user_id=? ORDER BY m DESC");
$months->execute([$uid]);
$monthList = $months->fetchAll();
?>

<!-- Filters -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
  <select class="form-control" style="width:auto;" onchange="applyFilter('month', this.value)">
    <option value="">Todos los meses</option>
    <?php foreach ($monthList as $m): ?>
    <option value="<?= $m['m'] ?>" <?= $month === $m['m'] ? 'selected' : '' ?>><?= $m['label'] ?></option>
    <?php endforeach; ?>
  </select>
  <select class="form-control" style="width:auto;" onchange="applyFilter('routine', this.value)">
    <option value="">Todas las rutinas</option>
    <?php foreach ($routineList as $r): ?>
    <option value="<?= $r['id'] ?>" <?= $filterRoutine === $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($month || $filterRoutine): ?>
  <a href="/history.php" class="btn btn-ghost">Limpiar filtros</a>
  <?php endif; ?>
</div>

<?php if (empty($sessions)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <p style="color:var(--muted);">No hay entrenamientos registrados<?= $month || $filterRoutine ? ' con estos filtros' : ' aún' ?>.</p>
  <?php if (!$month && !$filterRoutine): ?>
  <a href="/workout.php" class="btn btn-primary" style="margin-top:16px;">Empezar a entrenar</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Fecha</th><th>Sesión</th><th>Ejercicios</th><th>Series</th><th>Volumen</th><th>Duración</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td style="white-space:nowrap;color:var(--muted);font-size:0.82rem;"><?= date('d/m/y H:i', strtotime($s['started_at'])) ?></td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
            <?php if ($s['routine_name']): ?><span class="badge badge-yellow"><?= htmlspecialchars($s['routine_name']) ?></span><?php endif; ?>
          </td>
          <td><?= $s['ex_count'] ?></td>
          <td><?= $s['set_count'] ?></td>
          <td style="font-weight:600;color:var(--accent);"><?= $s['total_volume'] ? number_format($s['total_volume']).'kg' : '—' ?></td>
          <td style="color:var(--muted);"><?= $s['duration_min'] !== null ? $s['duration_min'].'min' : '—' ?></td>
          <td>
            <a href="/history.php?session=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">Ver detalle</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Summary stats -->
<?php
$totalVol = array_sum(array_column($sessions, 'total_volume'));
$totalSets = array_sum(array_column($sessions, 'set_count'));
?>
<div class="grid grid-3" style="margin-top:20px;">
  <div class="stat-box"><div class="label">Sesiones</div><div class="value"><?= count($sessions) ?></div></div>
  <div class="stat-box"><div class="label">Series totales</div><div class="value"><?= $totalSets ?></div></div>
  <div class="stat-box"><div class="label">Volumen total</div><div class="value" style="font-size:1.8rem;"><?= number_format($totalVol/1000, 1) ?>t</div></div>
</div>
<?php endif; ?>

<script>
function applyFilter(key, val) {
  const url = new URL(window.location);
  if (val) url.searchParams.set(key, val);
  else url.searchParams.delete(key);
  window.location = url.toString();
}
</script>

<?php } ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
