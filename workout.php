<?php
$pageTitle = 'Entrenar';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

$error = ''; $success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'start_session') {
        $routineId = (int)$_POST['routine_id'] ?: null;
        $name = trim($_POST['session_name'] ?? '');
        if (!$name && !$routineId) $name = 'Sesión libre';
        if (!$name && $routineId) {
            $s = $db->prepare("SELECT name FROM routines WHERE id=?"); $s->execute([$routineId]); $name = $s->fetchColumn();
        }
        $stmt = $db->prepare("INSERT INTO workout_sessions (user_id, routine_id, name) VALUES (?, ?, ?)");
        $stmt->execute([$uid, $routineId, $name]);
        $sid = $db->lastInsertId();
        header("Location: /workout.php?session=$sid");
        exit;

    } elseif ($action === 'log_set') {
        $sid = (int)$_POST['session_id'];
        $eid = (int)$_POST['exercise_id'];
        $setNum = (int)$_POST['set_number'];
        $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
        $reps = (int)$_POST['reps'];
        // Verify session belongs to user
        $sv = $db->prepare("SELECT id FROM workout_sessions WHERE id=? AND user_id=?");
        $sv->execute([$sid, $uid]);
        if ($sv->fetch()) {
            $stmt = $db->prepare("INSERT INTO session_sets (session_id, exercise_id, set_number, weight, reps) VALUES (?,?,?,?,?)");
            $stmt->execute([$sid, $eid, $setNum, $weight, $reps]);
        }
        header("Location: /workout.php?session=$sid&logged=1");
        exit;

    } elseif ($action === 'delete_set') {
        $setId = (int)$_POST['set_id'];
        $sid = (int)$_POST['session_id'];
        $stmt = $db->prepare("DELETE ss FROM session_sets ss JOIN workout_sessions ws ON ws.id=ss.session_id WHERE ss.id=? AND ws.user_id=?");
        $stmt->execute([$setId, $uid]);
        header("Location: /workout.php?session=$sid");
        exit;

    } elseif ($action === 'finish_session') {
        $sid = (int)$_POST['session_id'];
        $stmt = $db->prepare("UPDATE workout_sessions SET finished_at=NOW(), notes=? WHERE id=? AND user_id=?");
        $stmt->execute([trim($_POST['notes'] ?? ''), $sid, $uid]);
        header("Location: /history.php?session=$sid");
        exit;
    }
}

$sessionId = (int)($_GET['session'] ?? 0);
$session = null;
$sessionSets = [];
$routineExercises = [];

if ($sessionId) {
    $sv = $db->prepare("SELECT ws.*, r.name AS routine_name FROM workout_sessions ws LEFT JOIN routines r ON r.id=ws.routine_id WHERE ws.id=? AND ws.user_id=?");
    $sv->execute([$sessionId, $uid]);
    $session = $sv->fetch();

    if ($session && $session['finished_at']) {
        header("Location: /history.php?session=$sessionId");
        exit;
    }

    if ($session) {
        // Logged sets grouped by exercise
        $sets = $db->prepare("SELECT ss.*, e.name AS ex_name FROM session_sets ss JOIN exercises e ON e.id=ss.exercise_id WHERE ss.session_id=? ORDER BY ss.exercise_id, ss.set_number");
        $sets->execute([$sessionId]);
        $allSets = $sets->fetchAll();
        foreach ($allSets as $s) {
            $sessionSets[$s['exercise_id']]['name'] = $s['ex_name'];
            $sessionSets[$s['exercise_id']]['sets'][] = $s;
        }

        // Routine exercises if any
        if ($session['routine_id']) {
            $rex = $db->prepare("SELECT re.*, e.name AS ex_name, e.muscle_group FROM routine_exercises re JOIN exercises e ON e.id=re.exercise_id WHERE re.routine_id=? ORDER BY re.order_index");
            $rex->execute([$session['routine_id']]);
            $routineExercises = $rex->fetchAll();
        }
    }
}

// User exercises
$allExercises = $db->prepare("SELECT * FROM exercises WHERE user_id=? ORDER BY name");
$allExercises->execute([$uid]);
$myExercises = $allExercises->fetchAll();

// User routines
$myRoutines = $db->prepare("SELECT * FROM routines WHERE user_id=? ORDER BY name");
$myRoutines->execute([$uid]);
$routinesList = $myRoutines->fetchAll();

// Previous best for an exercise
function getPreviousBest(PDO $db, int $uid, int $exId): ?array {
    $stmt = $db->prepare("SELECT ss.weight, ss.reps FROM session_sets ss JOIN workout_sessions ws ON ws.id=ss.session_id WHERE ws.user_id=? AND ss.exercise_id=? AND ss.weight IS NOT NULL ORDER BY ss.weight DESC LIMIT 1");
    $stmt->execute([$uid, $exId]);
    return $stmt->fetch() ?: null;
}
?>

<?php if (!$session): ?>
<!-- Start session -->
<div class="page-header">
  <h1>ENTRE<span>NAR</span></h1>
</div>
<div style="max-width:520px;">
  <div class="card">
    <div class="card-title">Iniciar Sesión</div>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="start_session">
      <div class="form-group">
        <label>Basado en una Rutina (opcional)</label>
        <select name="routine_id" class="form-control">
          <option value="">-- Sesión libre --</option>
          <?php foreach ($routinesList as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($_GET['routine'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Nombre de la sesión (opcional)</label>
        <input type="text" name="session_name" class="form-control" placeholder="Ej: Pecho y tríceps">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:1rem;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Empezar
      </button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- Active session -->
<div class="page-header">
  <div>
    <h1>SES<span>IÓN</span></h1>
    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px;">
      <?= htmlspecialchars($session['name']) ?> · Iniciada <?= date('H:i', strtotime($session['started_at'])) ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;">
    <button class="btn btn-ghost" onclick="document.getElementById('finish-form').style.display='block'">Finalizar Sesión</button>
  </div>
</div>

<?php if (isset($_GET['logged'])): ?>
<div class="alert alert-success">✓ Serie registrada</div>
<?php endif; ?>

<!-- Finish form (hidden) -->
<div class="card" id="finish-form" style="display:none;margin-bottom:24px;border-color:var(--accent);">
  <div class="card-title">Finalizar Entrenamiento</div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="finish_session">
    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
    <div class="form-group">
      <label>Notas finales (opcional)</label>
      <textarea name="notes" class="form-control" placeholder="¿Cómo fue el entrenamiento?"></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">Guardar y Finalizar</button>
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('finish-form').style.display='none'">Cancelar</button>
    </div>
  </form>
</div>

<div class="grid grid-2" style="align-items:start;">

  <!-- Log a set -->
  <div class="card">
    <div class="card-title">Registrar Serie</div>
    <form method="POST" id="log-form">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="log_set">
      <input type="hidden" name="session_id" value="<?= $sessionId ?>">
      <div class="form-group">
        <label>Ejercicio</label>
        <select name="exercise_id" class="form-control" id="ex-select" onchange="updateBest(this)" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach ($routineExercises as $re): ?>
          <optgroup label="📋 Rutina">
            <option value="<?= $re['exercise_id'] ?>"><?= htmlspecialchars($re['ex_name']) ?> (<?= $re['sets'] ?>×<?= $re['reps'] ?>)</option>
          </optgroup>
          <?php break; // Just show the label once - we'll list all below ?>
          <?php endforeach; ?>
          <?php if (!empty($routineExercises)): ?>
          <?php foreach ($routineExercises as $re): ?>
          <?php endforeach; ?>
          <?php endif; ?>
          <?php
          // Rebuild properly
          if (!empty($routineExercises)): ?>
          <optgroup label="— Rutina: <?= htmlspecialchars($session['routine_name'] ?? '') ?> —">
            <?php foreach ($routineExercises as $re): ?>
            <option value="<?= $re['exercise_id'] ?>" data-sets="<?= $re['sets'] ?>" data-reps="<?= $re['reps'] ?>"><?= htmlspecialchars($re['ex_name']) ?></option>
            <?php endforeach; ?>
          </optgroup>
          <?php endif; ?>
          <optgroup label="— Todos mis ejercicios —">
            <?php foreach ($myExercises as $ex): ?>
            <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?><?= $ex['muscle_group'] ? ' ('.$ex['muscle_group'].')' : '' ?></option>
            <?php endforeach; ?>
          </optgroup>
        </select>
      </div>
      <div id="best-display" style="display:none;padding:8px 12px;background:rgba(232,255,60,0.07);border-radius:var(--radius);border:1px solid rgba(232,255,60,0.15);font-size:0.82rem;color:var(--accent);margin-bottom:12px;">
        🏆 Mejor marca: <span id="best-val"></span>
      </div>
      <div style="display:flex;gap:10px;">
        <div class="form-group" style="flex:1;">
          <label>Serie #</label>
          <input type="number" name="set_number" class="form-control" value="1" min="1" max="20" required>
        </div>
        <div class="form-group" style="flex:1.5;">
          <label>Peso (kg)</label>
          <input type="number" name="weight" class="form-control" step="0.5" min="0" placeholder="0 = sin peso">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Reps</label>
          <input type="number" name="reps" class="form-control" value="10" min="1" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">✓ Registrar Serie</button>
    </form>
  </div>

  <!-- Sets logged so far -->
  <div class="card">
    <div class="card-title">Series de Esta Sesión</div>
    <?php if (empty($sessionSets)): ?>
      <p style="color:var(--muted);font-size:0.88rem;">Aún no registraste ninguna serie. ¡Empezá!</p>
    <?php else: ?>
    <?php foreach ($sessionSets as $exId => $exData): ?>
    <div style="margin-bottom:20px;">
      <div style="font-weight:700;font-size:0.9rem;margin-bottom:8px;color:var(--accent);"><?= htmlspecialchars($exData['name']) ?></div>
      <table style="width:100%;font-size:0.85rem;">
        <thead><tr><th style="padding:5px 8px;">Serie</th><th style="padding:5px 8px;">Peso</th><th style="padding:5px 8px;">Reps</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($exData['sets'] as $s): ?>
          <tr>
            <td style="padding:5px 8px;color:var(--muted);">#<?= $s['set_number'] ?></td>
            <td style="padding:5px 8px;font-weight:600;"><?= $s['weight'] !== null ? $s['weight'].'kg' : '—' ?></td>
            <td style="padding:5px 8px;"><?= $s['reps'] ?> reps</td>
            <td style="padding:5px 8px;">
              <form method="POST" style="display:inline;" onsubmit="return confirm('¿Borrar esta serie?');">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="delete_set">
                <input type="hidden" name="set_id" value="<?= $s['id'] ?>">
                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                <button class="btn btn-danger btn-sm" style="padding:3px 8px;">×</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<script>
// Best marks data from PHP
const bests = {
  <?php foreach ($myExercises as $ex):
    $best = getPreviousBest($db, $uid, $ex['id']);
    if ($best): ?>
  <?= $ex['id'] ?>: '<?= $best['weight'] ?>kg × <?= $best['reps'] ?> reps',
  <?php endif; endforeach; ?>
};

function updateBest(sel) {
  const id = sel.value;
  const display = document.getElementById('best-display');
  const val = document.getElementById('best-val');
  if (bests[id]) {
    val.textContent = bests[id];
    display.style.display = 'block';
  } else {
    display.style.display = 'none';
  }
  // Auto increment set number
  const setInput = document.querySelector('input[name="set_number"]');
  const logged = document.querySelectorAll('tbody tr').length;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
