<?php
$pageTitle = 'Ejercicios';
require_once __DIR__ . '/includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

$error = ''; $success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $muscle = $_POST['muscle_group'] ?? '';
        $equipment = $_POST['equipment'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        if (!$name) { $error = 'El nombre es obligatorio.'; }
        else {
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO exercises (user_id, name, muscle_group, equipment, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$uid, $name, $muscle, $equipment, $notes]);
                $success = 'Ejercicio creado.';
            } else {
                $eid = (int)$_POST['id'];
                $stmt = $db->prepare("UPDATE exercises SET name=?, muscle_group=?, equipment=?, notes=? WHERE id=? AND user_id=?");
                $stmt->execute([$name, $muscle, $equipment, $notes, $eid, $uid]);
                $success = 'Ejercicio actualizado.';
            }
        }
    } elseif ($action === 'delete') {
        $eid = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM exercises WHERE id=? AND user_id=?");
        $stmt->execute([$eid, $uid]);
        $success = 'Ejercicio eliminado.';
    }
}

// Fetch exercises
$filter = $_GET['muscle'] ?? '';
if ($filter) {
    $stmt = $db->prepare("SELECT * FROM exercises WHERE user_id=? AND muscle_group=? ORDER BY name");
    $stmt->execute([$uid, $filter]);
} else {
    $stmt = $db->prepare("SELECT * FROM exercises WHERE user_id=? ORDER BY name");
    $stmt->execute([$uid]);
}
$exercises = $stmt->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$editEx = null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM exercises WHERE id=? AND user_id=?");
    $s->execute([$editId, $uid]);
    $editEx = $s->fetch();
}

$muscleGroups = ['Pecho','Espalda','Hombros','Bíceps','Tríceps','Piernas','Glúteos','Core','Cardio','Otro'];
$equipmentList = ['Barra','Mancuernas','Máquina','Cable','Polea','Peso corporal','Banda','Kettlebell','Otro'];

// Distinct muscle groups used by this user
$usedMuscles = $db->prepare("SELECT DISTINCT muscle_group FROM exercises WHERE user_id=? AND muscle_group != '' ORDER BY muscle_group");
$usedMuscles->execute([$uid]);
$muscleFilters = $usedMuscles->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
  <h1>EJERCI<span>CIOS</span></h1>
  <button class="btn btn-primary" onclick="toggleForm()">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nuevo Ejercicio
  </button>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Form -->
<div class="card" id="ex-form" style="margin-bottom:24px;display:<?= ($editEx || $error) ? 'block' : 'none' ?>;">
  <div class="card-title"><?= $editEx ? 'Editar Ejercicio' : 'Nuevo Ejercicio' ?></div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input type="hidden" name="action" value="<?= $editEx ? 'edit' : 'create' ?>">
    <?php if ($editEx): ?><input type="hidden" name="id" value="<?= $editEx['id'] ?>"><?php endif; ?>
    <div class="grid grid-2">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editEx['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Grupo Muscular</label>
        <select name="muscle_group" class="form-control">
          <option value="">-- Sin clasificar --</option>
          <?php foreach ($muscleGroups as $mg): ?>
          <option value="<?= $mg ?>" <?= ($editEx['muscle_group'] ?? '') === $mg ? 'selected' : '' ?>><?= $mg ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Equipamiento</label>
        <select name="equipment" class="form-control">
          <option value="">-- Sin especificar --</option>
          <?php foreach ($equipmentList as $eq): ?>
          <option value="<?= $eq ?>" <?= ($editEx['equipment'] ?? '') === $eq ? 'selected' : '' ?>><?= $eq ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($editEx['notes'] ?? '') ?>" placeholder="Opcional">
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editEx ? 'Guardar Cambios' : 'Crear Ejercicio' ?></button>
      <button type="button" class="btn btn-ghost" onclick="toggleForm()">Cancelar</button>
    </div>
  </form>
</div>

<!-- Muscle filter tabs -->
<?php if (!empty($muscleFilters)): ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="/exercises.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-ghost' ?>">Todos</a>
  <?php foreach ($muscleFilters as $mf): ?>
  <a href="/exercises.php?muscle=<?= urlencode($mf) ?>" class="btn btn-sm <?= $filter === $mf ? 'btn-primary' : 'btn-ghost' ?>"><?= htmlspecialchars($mf) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Exercise list -->
<div class="card">
  <?php if (empty($exercises)): ?>
    <p style="color:var(--muted);font-size:0.9rem;text-align:center;padding:40px 0;">
      No tenés ejercicios aún. ¡Creá tu primero!
    </p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Nombre</th><th>Músculo</th><th>Equipamiento</th><th>Notas</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($exercises as $ex): ?>
        <tr>
          <td style="font-weight:600;"><?= htmlspecialchars($ex['name']) ?></td>
          <td><?php if ($ex['muscle_group']): ?><span class="badge badge-yellow"><?= htmlspecialchars($ex['muscle_group']) ?></span><?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?></td>
          <td><?php if ($ex['equipment']): ?><span class="badge badge-gray"><?= htmlspecialchars($ex['equipment']) ?></span><?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?></td>
          <td style="color:var(--muted);font-size:0.83rem;"><?= htmlspecialchars($ex['notes'] ?: '—') ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="/exercises.php?edit=<?= $ex['id'] ?>" class="btn btn-ghost btn-sm">Editar</a>
              <form method="POST" onsubmit="return confirm('¿Eliminar este ejercicio?');" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                <button class="btn btn-danger btn-sm">Borrar</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleForm() {
  const f = document.getElementById('ex-form');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
<?php if ($editEx): ?>document.getElementById('ex-form').style.display = 'block';<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
