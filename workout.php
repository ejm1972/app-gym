<?php
$pageTitle = 'Entrenar';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

$error = ''; $success = '';

// Manejo de peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // --- 1. INICIAR SESIÓN DE ENTRENAMIENTO ---
    if ($action === 'start_session') {
        $routineId = (int)$_POST['routine_id'] ?: null;
        $routineDayId = (int)$_POST['routine_day_id'] ?: null;

        $stmt = $db->prepare("
            INSERT INTO workout_sessions (user_id, routine_id, routine_day_id, started_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$uid, $routineId, $routineDayId]);
        $sid = $db->lastInsertId();

        header("Location: /workout.php?session=$sid");
        exit;

    // --- 2. REGISTRAR UNA SERIE ---
    } elseif ($action === 'log_set') {
        $sid = (int)$_POST['session_id'];
        $eid = (int)$_POST['exercise_id'];
        $setNum = (int)$_POST['set_number'];
        $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
        $reps = (int)$_POST['reps'];
        $reid = !empty($_POST['routine_exercise_id']) ? (int)$_POST['routine_exercise_id'] : null;
        $rpe = $_POST['rpe'] !== '' ? (float)$_POST['rpe'] : null;

        // Calcular Personal Record (PR) basado en volumen (Peso * Reps)
        $best = getPreviousBest($db, $uid, $eid);
        $isPR = false;

        if ($best && $weight !== null) {
            $prevVolume = $best['weight'] * $best['reps'];
            $currentVolume = $weight * $reps;
            if ($currentVolume > $prevVolume) {
                $isPR = true;
            }
        } elseif (!$best && $weight !== null && $weight > 0) {
            $isPR = true;
        }

        // Verificar pertenencia de la sesión
        $sv = $db->prepare("SELECT id FROM workout_sessions WHERE id=? AND user_id=?");
        $sv->execute([$sid, $uid]);

        if ($sv->fetch()) {
            $stmt = $db->prepare("
                INSERT INTO session_sets
                (session_id, exercise_id, routine_exercise_id, set_number, weight, reps, rpe, is_pr)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sid, $eid, $reid, $setNum, $weight, $reps, $rpe, $isPR ? 1 : 0]);
        }

        header("Location: /workout.php?session=$sid&logged=1");
        exit;

    // --- 3. ELIMINAR SERIE REGISTRADA ---
    } elseif ($action === 'delete_set') {
        $setId = (int)$_POST['set_id'];
        $sid = (int)$_POST['session_id'];

        $stmt = $db->prepare("
            DELETE ss FROM session_sets ss 
            JOIN workout_sessions ws ON ws.id=ss.session_id 
            WHERE ss.id=? AND ws.user_id=?
        ");
        $stmt->execute([$setId, $uid]);

        header("Location: /workout.php?session=$sid");
        exit;

    // --- 4. FINALIZAR SESIÓN ---
    } elseif ($action === 'finish_session') {
        $sid = (int)$_POST['session_id'];

        $stmt = $db->prepare("
            UPDATE workout_sessions 
            SET ended_at=NOW(), notes=? 
            WHERE id=? AND user_id=?
        ");
        $stmt->execute([trim($_POST['notes'] ?? ''), $sid, $uid]);

        header("Location: /history.php?session=$sid");
        exit;
    }
}

// Cargar datos de la sesión activa
$sessionId = (int)($_GET['session'] ?? 0);
$session = null;
$sessionSets = [];
$routineTree = []; // Jerarquía Bloques -> Ejercicios

if ($sessionId) {
    $sv = $db->prepare("
        SELECT ws.*, r.name AS routine_name, rd.name AS day_name 
        FROM workout_sessions ws 
        LEFT JOIN routines r ON r.id=ws.routine_id 
        LEFT JOIN routine_days rd ON rd.id=ws.routine_day_id 
        WHERE ws.id=? AND ws.user_id=?
    ");
    $sv->execute([$sessionId, $uid]);
    $session = $sv->fetch();

    if ($session && $session['ended_at']) {
        header("Location: /history.php?session=$sessionId");
        exit;
    }

    if ($session) {
        // Cargar series ya registradas en la sesión
        $sets = $db->prepare("
            SELECT ss.*, e.name AS ex_name 
            FROM session_sets ss 
            JOIN exercises e ON e.id=ss.exercise_id 
            WHERE ss.session_id=? 
            ORDER BY ss.id ASC
        ");
        $sets->execute([$sessionId]);
        $allSets = $sets->fetchAll();

        foreach ($allSets as $s) {
            $sessionSets[$s['exercise_id']]['name'] = $s['ex_name'];
            $sessionSets[$s['exercise_id']]['sets'][] = $s;
        }

        // Cargar estructura completa de la rutina (Día -> Bloques -> Ejercicios)
        if ($session['routine_day_id']) {
            $blocksQuery = $db->prepare("
                SELECT * FROM routine_blocks 
                WHERE routine_day_id=? 
                ORDER BY order_index
            ");
            $blocksQuery->execute([$session['routine_day_id']]);
            $routineBlocks = $blocksQuery->fetchAll();

            foreach ($routineBlocks as $block) {
                $exQuery = $db->prepare("
                    SELECT re.*, e.name AS ex_name, e.muscle_group 
                    FROM routine_exercises re 
                    JOIN exercises e ON e.id=re.exercise_id 
                    WHERE re.routine_block_id=? 
                    ORDER BY re.order_index
                ");
                $exQuery->execute([$block['id']]);
                $block['exercises'] = $exQuery->fetchAll();
                $routineTree[] = $block;
            }
        }
    }
}

// Catálogo general de ejercicios
$allExercises = $db->prepare("SELECT * FROM exercises WHERE user_id=? ORDER BY name");
$allExercises->execute([$uid]);
$myExercises = $allExercises->fetchAll();

// Rutinas y Días disponibles para iniciar sesión
$myRoutines = $db->prepare("SELECT * FROM routines WHERE user_id=? ORDER BY name");
$myRoutines->execute([$uid]);
$routinesList = $myRoutines->fetchAll();

$routineDaysList = [];
foreach ($routinesList as $r) {
    $rdStmt = $db->prepare("SELECT id, name, routine_id FROM routine_days WHERE routine_id=? ORDER BY order_index");
    $rdStmt->execute([$r['id']]);
    $routineDaysList[$r['id']] = $rdStmt->fetchAll();
}

// Función auxiliar para obtener la mejor marca del usuario
function getPreviousBest(PDO $db, int $uid, int $exId): ?array {
    $stmt = $db->prepare("
        SELECT ss.weight, ss.reps 
        FROM session_sets ss 
        JOIN workout_sessions ws ON ws.id=ss.session_id 
        WHERE ws.user_id=? AND ss.exercise_id=? AND ss.weight IS NOT NULL 
        ORDER BY (ss.weight * ss.reps) DESC 
        LIMIT 1
    ");
    $stmt->execute([$uid, $exId]);
    return $stmt->fetch() ?: null;
}
?>

<?php if (!$session): ?>
<!-- PANTALLA: INICIAR SESIÓN -->
<div class="page-header">
  <h1>ENTRE<span>NAR</span></h1>
</div>
<div style="max-width:520px;">
  <div class="card">
    <div class="card-title">Iniciar Sesión de Entrenamiento</div>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="start_session">

      <div class="form-group">
        <label>Seleccionar Rutina</label>
        <select name="routine_id" id="routine_select" class="form-control" onchange="updateDayOptions(this.value)">
          <option value="">-- Sesión Libre --</option>
          <?php foreach ($routinesList as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($_GET['routine'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" id="day_select_group" style="display:none;">
        <label>Seleccionar Día de la Rutina</label>
        <select name="routine_day_id" id="routine_day_id" class="form-control">
            <!-- Dinámico por JavaScript -->
        </select>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:1rem;margin-top:10px;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Empezar Entrenamiento
      </button>
    </form>
  </div>
</div>

<script>
const daysMap = <?= json_encode($routineDaysList) ?>;

function updateDayOptions(routineId) {
    const dayGroup = document.getElementById('day_select_group');
    const daySelect = document.getElementById('routine_day_id');
    daySelect.innerHTML = '';

    if (routineId && daysMap[routineId] && daysMap[routineId].length > 0) {
        daysMap[routineId].forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            daySelect.appendChild(opt);
        });
        dayGroup.style.display = 'block';
    } else {
        dayGroup.style.display = 'none';
    }
}

// Disparar cambio si viene una rutina seleccionada desde la URL
if (document.getElementById('routine_select').value) {
    updateDayOptions(document.getElementById('routine_select').value);
}
</script>

<?php else: ?>
<!-- PANTALLA: SESIÓN ACTIVA -->
<div class="page-header">
  <div>
    <h1>SES<span>IÓN</span></h1>
    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px;">
      <?= htmlspecialchars($session['routine_name'] ?? 'Entrenamiento Libre') ?> 
      <?= $session['day_name'] ? ' — ' . htmlspecialchars($session['day_name']) : '' ?> 
      · Iniciada <?= date('H:i', strtotime($session['started_at'])) ?>
    </div>
  </div>
  <div>
    <button class="btn btn-ghost" onclick="document.getElementById('finish-form').style.display='block'">Finalizar Sesión</button>
  </div>
</div>

<?php if (isset($_GET['logged'])): ?>
<div class="alert alert-success">✓ Serie registrada correctamente</div>
<?php endif; ?>

<!-- Formulario oculto para finalizar entrenamiento -->
<div class="card" id="finish-form" style="display:none;margin-bottom:24px;border-color:var(--accent);">
  <div class="card-title">Finalizar Entrenamiento</div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="finish_session">
    <input type="hidden" name="session_id" value="<?= $sessionId ?>">
    <div class="form-group">
      <label>Notas finales (opcional)</label>
      <textarea name="notes" class="form-control" placeholder="¿Cómo te sentiste hoy?"></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary">Guardar y Finalizar</button>
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('finish-form').style.display='none'">Cancelar</button>
    </div>
  </form>
</div>

<div class="grid grid-2" style="align-items:start;">

  <!-- FORMULARIO: REGISTRAR SERIE -->
  <div class="card">
    <div class="card-title">Registrar Serie</div>
    <form method="POST" id="log-form">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="action" value="log_set">
      <input type="hidden" name="session_id" value="<?= $sessionId ?>">
      <input type="hidden" name="routine_exercise_id" id="routine_exercise_id" value="">

      <div class="form-group">
        <label>Ejercicio</label>
        <select name="exercise_id" class="form-control" id="ex-select" onchange="onExerciseChange(this)" required>
          <option value="">-- Seleccionar Ejercicio --</option>
          
          <?php if (!empty($routineTree)): ?>
            <?php foreach ($routineTree as $block): ?>
              <optgroup label="📦 <?= htmlspecialchars($block['name']) ?> (<?= $block['block_type'] ?>)">
                <?php foreach ($block['exercises'] as $re): ?>
                  <option 
                    value="<?= $re['exercise_id'] ?>"
                    data-reid="<?= $re['id'] ?>"
                    data-sets="<?= $re['sets'] ?>"
                    data-reps-min="<?= $re['target_reps_min'] ?>"
                    data-reps-max="<?= $re['target_reps_max'] ?>"
                    data-work-sec="<?= $re['work_seconds'] ?>"
                    data-weight="<?= $re['target_weight'] ?>"
                    data-label="<?= htmlspecialchars($re['subgroup_label'] ?? '') ?>"
                  >
                    <?= $re['subgroup_label'] ? '['.$re['subgroup_label'].'] ' : '' ?><?= htmlspecialchars($re['ex_name']) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          <?php endif; ?>

          <optgroup label="— Todos los ejercicios de la biblioteca —">
            <?php foreach ($myExercises as $ex): ?>
            <option value="<?= $ex['id'] ?>"><?= htmlspecialchars($ex['name']) ?></option>
            <?php endforeach; ?>
          </optgroup>
        </select>
      </div>

      <!-- Tarjeta informativa de Mejor Marca -->
      <div id="best-display" style="display:none;padding:8px 12px;background:rgba(232,255,60,0.07);border-radius:var(--radius);border:1px solid rgba(232,255,60,0.15);font-size:0.82rem;color:var(--accent);margin-bottom:12px;">
        🏆 Mejor marca histórica: <span id="best-val"></span>
      </div>

      <!-- Tarjeta informativa de Objetivo Prescrito -->
      <div id="target-display" style="display:none;padding:8px 12px;background:rgba(255,255,255,0.05);border-radius:var(--radius);font-size:0.82rem;color:#aaa;margin-bottom:12px;">
        🎯 Objetivo prescrito: <span id="target-val" style="color:#fff;font-weight:600;"></span>
      </div>

      <div style="display:flex;gap:10px;">
        <div class="form-group" style="flex:1;">
          <label>Serie #</label>
          <input type="number" name="set_number" id="set_number_input" class="form-control" value="1" min="1" max="20" required>
        </div>
        <div class="form-group" style="flex:1.5;">
          <label>Peso (kg)</label>
          <input type="number" name="weight" id="weight_input" class="form-control" step="0.5" min="0" placeholder="0 = sin peso">
        </div>
        <div class="form-group" style="flex:1;">
          <label>Reps / Segs</label>
          <input type="number" name="reps" id="reps_input" class="form-control" value="10" min="1" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">✓ Registrar Serie</button>
    </form>
  </div>

  <!-- LISTADO DE SERIES REGISTRADAS -->
  <div class="card">
    <div class="card-title">Series de Esta Sesión</div>
    <?php if (empty($sessionSets)): ?>
      <p style="color:var(--muted);font-size:0.88rem;">Aún no registraste ninguna serie en este entrenamiento.</p>
    <?php else: ?>
      <?php foreach ($sessionSets as $exId => $exData): ?>
      <div style="margin-bottom:20px;">
        <div style="font-weight:700;font-size:0.9rem;margin-bottom:8px;color:var(--accent);"><?= htmlspecialchars($exData['name']) ?></div>
        <table style="width:100%;font-size:0.85rem;">
          <thead>
            <tr>
              <th style="padding:5px 8px;">Serie</th>
              <th style="padding:5px 8px;">Peso</th>
              <th style="padding:5px 8px;">Cantidad</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($exData['sets'] as $s): ?>
            <tr>
              <td style="padding:5px 8px;color:var(--muted);">#<?= $s['set_number'] ?></td>
              <td style="padding:5px 8px;font-weight:600;"><?= $s['weight'] !== null ? $s['weight'].' kg' : '—' ?></td>
              <td style="padding:5px 8px;">
                <?= $s['reps'] ?> reps/segs
                <?php if (!empty($s['is_pr'])): ?> 🔥 <?php endif; ?>
              </td>
              <td style="padding:5px 8px; text-align:right;">
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
// Datos de mejores marcas pasados desde PHP
const bests = {
  <?php foreach ($myExercises as $ex):
    $best = getPreviousBest($db, $uid, $ex['id']);
    if ($best): ?>
  <?= $ex['id'] ?>: '<?= $best['weight'] ?>kg × <?= $best['reps'] ?> reps',
  <?php endif; endforeach; ?>
};

function onExerciseChange(sel) {
  const id = sel.value;
  const opt = sel.options[sel.selectedIndex];

  // 1. Asignar Routine Exercise ID oculto
  document.getElementById('routine_exercise_id').value = opt.dataset.reid || '';

  // 2. Mostrar Mejor Marca
  const bestDisplay = document.getElementById('best-display');
  const bestVal = document.getElementById('best-val');
  if (bests[id]) {
    bestVal.textContent = bests[id];
    bestDisplay.style.display = 'block';
  } else {
    bestDisplay.style.display = 'none';
  }

  // 3. Mostrar Objetivos Prescritos
  const targetDisplay = document.getElementById('target-display');
  const targetVal = document.getElementById('target-val');

  let targetText = '';
  if (opt.dataset.workSec) {
    targetText = opt.dataset.workSec + ' segs de trabajo';
    document.getElementById('reps_input').value = opt.dataset.workSec;
  } else if (opt.dataset.repsMin) {
    targetText = opt.dataset.repsMin + (opt.dataset.repsMax ? ' - ' + opt.dataset.repsMax : '') + ' reps';
    document.getElementById('reps_input').value = opt.dataset.repsMin;
  }

  if (opt.dataset.weight) {
    targetText += ' @ ' + opt.dataset.weight + ' kg';
    document.getElementById('weight_input').value = opt.dataset.weight;
  } else if (bests[id]) {
    // Si no hay peso en la rutina pero sí récord previo, autocompletar con el récord
    const match = bests[id].match(/(\d+(\.\d+)?)kg/);
    if (match) {
      document.getElementById('weight_input').value = match[1];
    }
  }

  if (targetText) {
    targetVal.textContent = targetText;
    targetDisplay.style.display = 'block';
  } else {
    targetDisplay.style.display = 'none';
  }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>