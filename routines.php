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

    // --- ACCIONES DE RUTINA ---
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
    }

    // --- ACCIONES DE DÍAS ---
    elseif ($action === 'add_day') {
        $rid = (int)$_POST['routine_id'];
        $dayName = trim($_POST['day_name'] ?? '');
        if ($dayName) {
            $ord = $db->prepare("SELECT COALESCE(MAX(order_index),0)+1 FROM routine_days WHERE routine_id=?");
            $ord->execute([$rid]);
            $nextOrd = $ord->fetchColumn();

            $stmt = $db->prepare("INSERT INTO routine_days (routine_id, name, order_index) VALUES (?, ?, ?)");
            $stmt->execute([$rid, $dayName, $nextOrd]);
            $success = 'Día agregado.';
        }
    } elseif ($action === 'delete_day') {
        $dayId = (int)$_POST['day_id'];
        $stmt = $db->prepare("DELETE rd FROM routine_days rd JOIN routines r ON r.id = rd.routine_id WHERE rd.id=? AND r.user_id=?");
        $stmt->execute([$dayId, $uid]);
        $success = 'Día eliminado.';
    }

    // --- ACCIONES DE BLOQUES ---
    elseif ($action === 'add_block') {
        $dayId = (int)$_POST['day_id'];
        $blockName = trim($_POST['block_name'] ?? '');
        $blockType = $_POST['block_type'] ?? 'TRADITIONAL';
        $workSec = !empty($_POST['work_seconds']) ? (int)$_POST['work_seconds'] : null;
        $restSec = !empty($_POST['rest_between_exercises']) ? (int)$_POST['rest_between_exercises'] : null;

        if ($blockName) {
            $ord = $db->prepare("SELECT COALESCE(MAX(order_index),0)+1 FROM routine_blocks WHERE routine_day_id=?");
            $ord->execute([$dayId]);
            $nextOrd = $ord->fetchColumn();

            $stmt = $db->prepare("INSERT INTO routine_blocks (routine_day_id, name, block_type, work_seconds, rest_between_exercises, order_index) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dayId, $blockName, $blockType, $workSec, $restSec, $nextOrd]);
            $success = 'Bloque agregado al día.';
        }
    } elseif ($action === 'delete_block') {
        $blockId = (int)$_POST['block_id'];
        $stmt = $db->prepare("DELETE rb FROM routine_blocks rb JOIN routine_days rd ON rd.id=rb.routine_day_id JOIN routines r ON r.id=rd.routine_id WHERE rb.id=? AND r.user_id=?");
        $stmt->execute([$blockId, $uid]);
        $success = 'Bloque eliminado.';
    }

    // --- ACCIONES DE EJERCICIOS DENTRO DEL BLOQUE ---
    elseif ($action === 'add_exercise') {
        $blockId = (int)$_POST['block_id'];
        $eid = (int)$_POST['exercise_id'];
        $sets = (int)$_POST['sets'];
        $repsMin = !empty($_POST['reps_min']) ? (int)$_POST['reps_min'] : null;
        $repsMax = !empty($_POST['reps_max']) ? (int)$_POST['reps_max'] : null;
        $workSec = !empty($_POST['work_seconds']) ? (int)$_POST['work_seconds'] : null;
        $weight = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
        $rest = (int)$_POST['rest_seconds'];
        $subgroup = trim($_POST['subgroup_label'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $ord = $db->prepare("SELECT COALESCE(MAX(order_index),0)+1 FROM routine_exercises WHERE routine_block_id=?");
        $ord->execute([$blockId]);
        $nextOrd = $ord->fetchColumn();

        $stmt = $db->prepare("INSERT INTO routine_exercises (routine_block_id, exercise_id, sets, target_reps_min, target_reps_max, work_seconds, target_weight, rest_seconds, subgroup_label, notes, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$blockId, $eid, $sets, $repsMin, $repsMax, $workSec, $weight, $rest, $subgroup, $notes, $nextOrd]);
        $success = 'Ejercicio agregado al bloque.';
    } elseif ($action === 'remove_exercise') {
        $reid = (int)$_POST['re_id'];
        $stmt = $db->prepare("DELETE re FROM routine_exercises re JOIN routine_blocks rb ON rb.id=re.routine_block_id JOIN routine_days rd ON rd.id=rb.routine_day_id JOIN routines r ON r.id=rd.routine_id WHERE re.id=? AND r.user_id=?");
        $stmt->execute([$reid, $uid]);
        $success = 'Ejercicio quitado del bloque.';
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRoutine = null;
$routineDays = [];

if ($editId) {
    $s = $db->prepare("SELECT * FROM routines WHERE id=? AND user_id=?");
    $s->execute([$editId, $uid]);
    $editRoutine = $s->fetch();

    if ($editRoutine) {
        // Cargar Días
        $sd = $db->prepare("SELECT * FROM routine_days WHERE routine_id=? ORDER BY order_index");
        $sd->execute([$editId]);
        $routineDays = $sd->fetchAll();

        // Cargar Bloques y Ejercicios para cada Día
        foreach ($routineDays as &$day) {
            $sb = $db->prepare("SELECT * FROM routine_blocks WHERE routine_day_id=? ORDER BY order_index");
            $sb->execute([$day['id']]);
            $day['blocks'] = $sb->fetchAll();

            foreach ($day['blocks'] as &$block) {
                $se = $db->prepare("SELECT re.*, e.name AS ex_name, e.muscle_group FROM routine_exercises re JOIN exercises e ON e.id=re.exercise_id WHERE re.routine_block_id=? ORDER BY re.order_index");
                $se->execute([$block['id']]);
                $block['exercises'] = $se->fetchAll();
            }
        }
    }
}

// Cargar todas las rutinas con su conteo de ejercicios (Vía JOIN multinivel)
$allRoutines = $db->prepare("
    SELECT r.*, COUNT(re.id) AS ex_count 
    FROM routines r 
    LEFT JOIN routine_days rd ON rd.routine_id = r.id 
    LEFT JOIN routine_blocks rb ON rb.routine_day_id = rd.id 
    LEFT JOIN routine_exercises re ON re.routine_block_id = rb.id 
    WHERE r.user_id = ? 
    GROUP BY r.id 
    ORDER BY r.updated_at DESC
");
$allRoutines->execute([$uid]);
$routines = $allRoutines->fetchAll();

// Ejercicios del usuario para los selectores
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

<!-- Formulario para Crear Rutina -->
<div class="card" id="create-form" style="margin-bottom:24px;display:none;">
  <div class="card-title">Nueva Rutina</div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="create_routine">
    <div class="grid grid-2">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="name" class="form-control" placeholder="Ej: Rutina Pre Operatoria" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" name="description" class="form-control" placeholder="Opcional">
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">Crear y Diseñar</button>
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('create-form').style.display='none'">Cancelar</button>
    </div>
  </form>
</div>

<?php if ($editRoutine): ?>
<!-- MODO EDICIÓN DE RUTINA -->
<div class="card" style="margin-bottom:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div class="card-title" style="margin-bottom:0;">Editando: <?= htmlspecialchars($editRoutine['name']) ?></div>
    <a href="/routines.php" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

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
    <button type="submit" class="btn btn-ghost btn-sm">Actualizar Info Básica</button>
  </form>

  <!-- JERARQUÍA DE DÍAS Y BLOQUES -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h3>Días de la Rutina</h3>
    <button class="btn btn-secondary btn-sm" onclick="document.getElementById('add-day-form').style.display='block'">+ Agregar Día</button>
  </div>

  <!-- Formulario Agregar Día -->
  <div class="card" id="add-day-form" style="display:none; margin-bottom: 20px; background: var(--bg-surface-2);">
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="add_day">
      <input type="hidden" name="routine_id" value="<?= $editRoutine['id'] ?>">
      <div class="form-group">
        <label>Nombre del Día (ej: Día 1, Pecho/Espalda)</label>
        <input type="text" name="day_name" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Guardar Día</button>
      <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('add-day-form').style.display='none'">Cancelar</button>
    </form>
  </div>

  <?php if (empty($routineDays)): ?>
    <p style="color:var(--muted);font-size:0.88rem;">No hay días agregados en esta rutina.</p>
  <?php else: ?>
    <?php foreach ($routineDays as $day): ?>
      <div style="border:1px solid var(--border); padding:16px; border-radius:8px; margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <h4 style="margin:0; font-size:1.2rem; color:var(--accent);"><?= htmlspecialchars($day['name']) ?></h4>
          <div>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('add-block-<?= $day['id'] ?>').style.display='block'">+ Agregar Bloque</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este día y todo su contenido?');">
              <input type="hidden" name="csrf" value="<?= csrf() ?>">
              <input type="hidden" name="action" value="delete_day">
              <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
              <button class="btn btn-danger btn-sm">Eliminar Día</button>
            </form>
          </div>
        </div>

        <!-- Formulario Nuevo Bloque -->
        <div class="card" id="add-block-<?= $day['id'] ?>" style="display:none; margin-bottom:15px; background:var(--bg-surface-3);">
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="add_block">
                <input type="hidden" name="day_id" value="<?= $day['id'] ?>">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Nombre del Bloque</label>
                        <input type="text" name="block_name" class="form-control" placeholder="ej: Bloque Movilidad, Zona Media, Fuerza" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Bloque</label>
                        <select name="block_type" class="form-control">
                            <option value="TRADITIONAL">Tradicional</option>
                            <option value="CIRCUIT">Circuito (Tiempo)</option>
                            <option value="SUPERSET">Super Serie</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Guardar Bloque</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('add-block-<?= $day['id'] ?>').style.display='none'">Cancelar</button>
            </form>
        </div>

        <!-- LISTADO DE BLOQUES DEL DÍA -->
        <?php foreach ($day['blocks'] as $block): ?>
            <div style="background:var(--bg-surface); padding:12px; margin-bottom:12px; border-radius:6px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong><?= htmlspecialchars($block['name']) ?></strong>
                    <div>
                        <span class="badge badge-yellow"><?= $block['block_type'] ?></span>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este bloque?');">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="action" value="delete_block">
                            <input type="hidden" name="block_id" value="<?= $block['id'] ?>">
                            <button class="btn btn-danger btn-sm">×</button>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Ejercicios del Bloque -->
                <div class="table-wrap" style="margin:10px 0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Ord</th>
                                <th>Ejercicio</th>
                                <th>Series</th>
                                <th>Reps / Tiempo</th>
                                <th>Peso Target</th>
                                <th>Descanso</th>
                                <th>Notas</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($block['exercises'] as $ex): ?>
                            <tr>
                                <td><?= htmlspecialchars($ex['subgroup_label'] ?: $ex['order_index']) ?></td>
                                <td><b><?= htmlspecialchars($ex['ex_name']) ?></b></td>
                                <td><?= $ex['sets'] ?></td>
                                <td>
                                    <?php 
                                        if ($ex['work_seconds']) echo $ex['work_seconds'] . ' segs';
                                        elseif ($ex['target_reps_min']) echo $ex['target_reps_min'] . ($ex['target_reps_max'] ? '-'.$ex['target_reps_max'] : '');
                                        else echo '-';
                                    ?>
                                </td>
                                <td><?= $ex['target_weight'] ? $ex['target_weight'].' kg' : '-' ?></td>
                                <td><?= $ex['rest_seconds'] ? $ex['rest_seconds'].'s' : '-' ?></td>
                                <td><?= htmlspecialchars($ex['notes'] ?? '') ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Quitar ejercicio?');">
                                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                        <input type="hidden" name="action" value="remove_exercise">
                                        <input type="hidden" name="re_id" value="<?= $ex['id'] ?>">
                                        <button class="btn btn-danger btn-sm">×</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulario Agregar Ejercicio al Bloque -->
                <form method="POST" style="margin-top:10px;">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="add_exercise">
                    <input type="hidden" name="block_id" value="<?= $block['id'] ?>">
                    
                    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:flex-end;">
                        <div style="width:50px;">
                            <label style="font-size:0.7rem;">Etik.</label>
                            <input type="text" name="subgroup_label" placeholder="A1" class="form-control">
                        </div>
                        <div style="flex:2; min-width:140px;">
                            <label style="font-size:0.7rem;">Ejercicio</label>
                            <select name="exercise_id" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($myExercises as $ex): ?>
                                    <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="width:50px;">
                            <label style="font-size:0.7rem;">Series</label>
                            <input type="number" name="sets" value="3" class="form-control">
                        </div>
                        <div style="width:50px;">
                            <label style="font-size:0.7rem;">Reps</label>
                            <input type="number" name="reps_min" placeholder="10" class="form-control">
                        </div>
                        <div style="width:55px;">
                            <label style="font-size:0.7rem;">Tiempo(s)</label>
                            <input type="number" name="work_seconds" placeholder="20" class="form-control">
                        </div>
                        <div style="width:60px;">
                            <label style="font-size:0.7rem;">Peso(kg)</label>
                            <input type="number" step="0.5" name="target_weight" placeholder="10" class="form-control">
                        </div>
                        <div style="width:55px;">
                            <label style="font-size:0.7rem;">Desc(s)</label>
                            <input type="number" name="rest_seconds" placeholder="40" class="form-control">
                        </div>
                        <div style="flex:1; min-width:100px;">
                            <label style="font-size:0.7rem;">Nota</label>
                            <input type="text" name="notes" placeholder="ej: 1 mancuerna" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">+</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- LISTA DE RUTINAS GENERAL -->
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
      <span class="badge badge-yellow"><?= $r['ex_count'] ?> ejercicios totales</span>
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