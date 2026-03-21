<?php
$pageTitle = 'Rutinas';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_routine') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) { $error = 'El nombre es obligatorio.'; }
        else {
            $stmt = $db->prepare("INSERT INTO routines (user_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$uid, $name, $desc]);
            $newId = $db->lastInsertId();
            header("Location: /routines.php?edit=$newId");
            exit;
        }
    } elseif ($action === 'update_routine') {
        $rid = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE routines SET name=?, description=? WHERE id=? AND user_id=?");
        $stmt->execute([trim($_POST['name']), trim($_POST['description'] ?? ''), $rid, $uid]);
        $success = 'Rutina actualizada.';
    } elseif ($action === 'delete_routine') {
        $rid = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM routines WHERE id=? AND user_id=?");
        $stmt->execute([$rid, $uid]);
        header('Location: /routines.php');
        exit;
    } elseif ($action === 'add_exercise') {
        $rid = (int)$_POST['routine_id'];
        $eid = (int)$_POST['exercise_id'];
        $sets = (int)$_POST['sets'];
        $reps = trim($_POST['reps'] ?? '10');
        $rest = (int)$_POST['rest_seconds'];
        // Get next order
        $ord = $db->prepare("SELECT COALESCE(MAX(order_index),0)+1 FROM routine_exercises WHERE routine_id=?");
        $ord->execute([$rid]);
        $nextOrder = $ord->fetchColumn();
        $stmt = $db->prepare("INSERT INTO routine_exercises (routine_id, exercise_id, sets, reps, rest_seconds, order_index) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$rid, $eid, $sets, $reps, $rest, $nextOrder]);
        $success = 'Ejercicio agregado.';
    } elseif ($action === 'remove_exercise') {
        $reid = (int)$_POST['re_id'];
        $stmt = $db->prepare("DELETE FROM routine_exercises WHERE id=? AND routine_id IN (SELECT id FROM routines WHERE user_id=?)");
        $stmt->execute([$reid, $uid]);
        $success = 'Ejercicio eliminado de la rutina.';
    } elseif ($action === 'update_exercise') {
        $reid = (int)$_POST['re_id'];
        $stmt = $db->prepare("UPDATE routine_exercises SET sets=?, reps=?, rest_seconds=? WHERE id=? AND routine_id IN (SELECT id FROM routines WHERE user_id=?)");
        $stmt->execute([(int)$_POST['sets'], trim($_POST['reps']), (int)$_POST['rest_seconds'], $reid, $uid]);
        $success = 'Ejercicio actualizado.';
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRoutine = null;
$routineExercises = [];

if ($editId) {
    $s = $db->prepare("SELECT * FROM routines WHERE id=? AND user_id=?");
    $s->execute([$editId, $uid]);
    $editRoutine = $s->fetch();
    if ($editRoutine) {
        $rex = $db->prepare("SELECT re.*, e.name AS ex_name, e.muscle_group FROM routine_exercises re JOIN exercises e ON e.id=re.exercise_id WHERE re.routine_id=? ORDER BY re.order_index");
        $rex->execute([$editId]);
        $routineExercises = $rex->fetchAll();
    }
}

// All routines
$allRoutines = $db->prepare("SELECT r.*, COUNT(re.id) AS ex_count FROM routines r LEFT JOIN routine_exercises re ON re.routine_id=r.id WHERE r.user_id=? GROUP BY r.id ORDER BY r.updated_at DESC");
$allRoutines->execute([$uid]);
$routines = $allRoutines->fetchAll();

// User exercises for dropdown
$userExercises = $db->prepare("SELECT * FROM exercises WHERE user_id=? ORDER BY name");
$userExercises->execute([$uid]);
$myExercises = $userExercises->fetchAll();
?>

<div class="page-header">
  <h1>RU<span>TINAS</span></h1>
  <button class="btn btn-primary" onclick="document.getElementById('create-form').style.display='block'">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nueva Rutina
  </button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Create form -->
<div class="card" id="create-form" style="margin-bottom:24px;display:none;">
  <div class="card-title">Nueva Rutina</div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="create_routine">
    <div class="grid grid-2">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="name" class="form-control" placeholder="Ej: Push Day A" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" name="description" class="form-control" placeholder="Opcional">
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">Crear y Editar</button>
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('create-form').style.display='none'">Cancelar</button>
    </div>
  </form>
</div>

<?php if ($editRoutine): ?>
<!-- Edit Routine -->
<div class="card" style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="card-title" style="margin-bottom:0;">Editando: <?= htmlspecialchars($editRoutine['name']) ?></div>
    <a href="/routines.php" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <!-- Update name/desc -->
  <form method="POST" style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--border);">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="update_routine">
    <input type="hidden" name="id" value="<?= $editRoutine['id'] ?>">
    <div class="grid grid-2">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editRoutine['name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editRoutine['description'] ?? '') ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-ghost btn-sm">Actualizar Info</button>
  </form>

  <!-- Exercises in routine -->
  <div class="card-title">Ejercicios de la Rutina</div>
  <?php if (empty($routineExercises)): ?>
    <p style="color:var(--muted);font-size:0.88rem;margin-bottom:16px;">Aún no hay ejercicios. Agregá uno abajo.</p>
  <?php else: ?>
  <div class="table-wrap" style="margin-bottom:20px;">
    <table>
      <thead><tr><th>#</th><th>Ejercicio</th><th>Músculo</th><th>Series</th><th>Reps</th><th>Descanso</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($routineExercises as $i => $re): ?>
        <tr>
          <td style="color:var(--muted);"><?= $i+1 ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($re['ex_name']) ?></td>
          <td><?php if ($re['muscle_group']): ?><span class="badge badge-yellow"><?= htmlspecialchars($re['muscle_group']) ?></span><?php endif; ?></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <input type="hidden" name="action" value="update_exercise">
              <input type="hidden" name="re_id" value="<?= $re['id'] ?>">
              <input type="number" name="sets" value="<?= $re['sets'] ?>" min="1" max="20" style="width:55px;" class="form-control">
          </td>
          <td>
              <input type="text" name="reps" value="<?= htmlspecialchars($re['reps']) ?>" style="width:65px;" class="form-control">
          </td>
          <td>
              <input type="number" name="rest_seconds" value="<?= $re['rest_seconds'] ?>" min="0" style="width:70px;" class="form-control">s
          </td>
          <td>
              <button type="submit" class="btn btn-ghost btn-sm">✓</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Quitar este ejercicio?');">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <input type="hidden" name="action" value="remove_exercise">
              <input type="hidden" name="re_id" value="<?= $re['id'] ?>">
              <button class="btn btn-danger btn-sm">×</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Add exercise to routine -->
  <div style="font-size:0.8rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Agregar Ejercicio</div>
  <?php if (empty($myExercises)): ?>
    <p style="color:var(--muted);font-size:0.88rem;">No tenés ejercicios en tu biblioteca. <a href="/exercises.php" style="color:var(--accent);">Crear ejercicios primero</a></p>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="add_exercise">
    <input type="hidden" name="routine_id" value="<?= $editRoutine['id'] ?>">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="flex:2;min-width:160px;margin-bottom:0;">
        <label>Ejercicio</label>
        <select name="exercise_id" class="form-control" required>
          <option value="">-- Seleccionar --</option>
          <?php foreach ($myExercises as $ex): ?>
          <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?><?= $ex['muscle_group'] ? ' ('.$ex['muscle_group'].')' : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="width:70px;margin-bottom:0;">
        <label>Series</label>
        <input type="number" name="sets" value="3" min="1" max="20" class="form-control">
      </div>
      <div class="form-group" style="width:80px;margin-bottom:0;">
        <label>Reps</label>
        <input type="text" name="reps" value="10" class="form-control" placeholder="8-12">
      </div>
      <div class="form-group" style="width:90px;margin-bottom:0;">
        <label>Descanso(s)</label>
        <input type="number" name="rest_seconds" value="60" min="0" class="form-control">
      </div>
      <div style="margin-bottom:0;">
        <button type="submit" class="btn btn-primary">Agregar</button>
      </div>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- List all routines -->
<?php if (empty($routines)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <p style="color:var(--muted);margin-bottom:16px;">No tenés rutinas. ¡Creá tu primera!</p>
  <button class="btn btn-primary" onclick="document.getElementById('create-form').style.display='block'">Crear Rutina</button>
</div>
<?php else: ?>
<div class="grid grid-2">
  <?php foreach ($routines as $r): ?>
  <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
    <div>
      <div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;margin-bottom:4px;"><?= htmlspecialchars($r['name']) ?></div>
      <?php if ($r['description']): ?><p style="color:var(--muted);font-size:0.85rem;margin-bottom:10px;"><?= htmlspecialchars($r['description']) ?></p><?php endif; ?>
      <span class="badge badge-yellow"><?= $r['ex_count'] ?> ejercicios</span>
      <span style="color:var(--muted);font-size:0.78rem;margin-left:10px;">Actualizado: <?= date('d/m/y', strtotime($r['updated_at'])) ?></span>
    </div>
    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap;">
      <a href="/workout.php?routine=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Entrenar
      </a>
      <a href="/routines.php?edit=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">Editar</a>
      <form method="POST" onsubmit="return confirm('¿Eliminar esta rutina?');" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="action" value="delete_routine">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-danger btn-sm">Eliminar</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
