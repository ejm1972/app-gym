<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: /dashboard.php'); exit; }

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if ($mode === 'login') {
        if (login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
            header('Location: /dashboard.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    } else {
        $result = register($_POST['username'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result === true) {
            header('Location: /dashboard.php');
            exit;
        }
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ConInf · AppGym</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0a0a0a; --surface: #111; --border: #222;
    --accent: #e8ff3c; --accent2: #ff4c3c;
    --text: #f0f0f0; --muted: #666; --radius: 6px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: stretch;
  }

  .hero {
    flex: 1;
    background: linear-gradient(135deg, #0a0a0a 0%, #111 50%, #0f1400 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px;
    position: relative;
    overflow: hidden;
  }
  .hero::before {
    content: 'ConInf';;
    position: absolute;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 28vw;
    color: rgba(232,255,60,0.03);
    bottom: -5vw;
    left: -2vw;
    line-height: 1;
    pointer-events: none;
    user-select: none;
  }
  .hero-tag {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--accent);
    margin-bottom: 24px;
  }
  .hero h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(3rem, 6vw, 6rem);
    line-height: 0.9;
    color: var(--text);
    margin-bottom: 24px;
  }
  .hero h1 em { color: var(--accent); font-style: normal; }
  .hero p { color: var(--muted); font-size: 1rem; max-width: 380px; line-height: 1.6; }
  .hero-features { margin-top: 48px; display: flex; flex-direction: column; gap: 12px; }
  .feature-item { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 0.88rem; }
  .feature-item::before { content: ''; width: 6px; height: 6px; background: var(--accent); border-radius: 50%; flex-shrink: 0; }

  .auth-panel {
    width: 420px;
    min-width: 420px;
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 48px;
  }
  .auth-panel h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2rem;
    letter-spacing: 2px;
    margin-bottom: 6px;
  }
  .auth-panel p.sub { color: var(--muted); font-size: 0.85rem; margin-bottom: 32px; }

  .form-group { margin-bottom: 16px; }
  .form-group label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
  .form-control {
    width: 100%; padding: 11px 14px;
    background: #161616;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    transition: border-color 0.15s;
  }
  .form-control:focus { outline: none; border-color: var(--accent); }

  .btn-auth {
    width: 100%; padding: 13px;
    background: var(--accent); color: #000;
    border: none; border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem; font-weight: 700;
    cursor: pointer; margin-top: 8px;
    letter-spacing: 0.5px;
    transition: all 0.15s;
  }
  .btn-auth:hover { background: #d4e838; transform: translateY(-1px); }

  .switch { margin-top: 24px; text-align: center; font-size: 0.85rem; color: var(--muted); }
  .switch a { color: var(--accent); text-decoration: none; font-weight: 600; }

  .alert { padding: 12px 16px; border-radius: var(--radius); font-size: 0.85rem; margin-bottom: 20px; }
  .alert-error { background: rgba(255,76,60,0.1); border: 1px solid rgba(255,76,60,0.3); color: #ff8a7a; }

  @media (max-width: 800px) {
    .hero { display: none; }
    .auth-panel { width: 100%; min-width: unset; border-left: none; }
  }
</style>
</head>
<body>
<div class="hero">
  <div class="hero-tag">💪 AppGym</div>
  <h1>ENTRENÁ.<br>REGISTRÁ.<br><em>PROGRESÁ.</em></h1>
  <p>Tu app personal para administrar rutinas, registrar pesos y ver tu progreso en el tiempo.</p>
  <div class="hero-features">
    <div class="feature-item">Creá y editá tus rutinas de entrenamiento</div>
    <div class="feature-item">Registrá pesos y repeticiones en cada sesión</div>
    <div class="feature-item">Seguí tu historial y evolución</div>
    <div class="feature-item">Gestioná tu biblioteca de ejercicios</div>
  </div>
</div>

<div class="auth-panel">
  <?php if ($mode === 'login'): ?>
    <h2>Bienvenido</h2>
    <p class="sub">Ingresá a tu cuenta para continuar</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="form-group">
        <label>Usuario o Email</label>
        <input type="text" name="username" class="form-control" required autofocus>
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn-auth">Ingresar</button>
    </form>
    <div class="switch">¿No tenés cuenta? <a href="?mode=register">Registrate</a></div>

  <?php else: ?>
    <h2>Crear Cuenta</h2>
    <p class="sub">Empezá gratis hoy mismo</p>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="form-group">
        <label>Usuario</label>
        <input type="text" name="username" class="form-control" required autofocus>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn-auth">Crear Cuenta</button>
    </form>
    <div class="switch">¿Ya tenés cuenta? <a href="?mode=login">Ingresá</a></div>
  <?php endif; ?>
</div>
</body>
</html>
