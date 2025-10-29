<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/lib/auth.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  assert_csrf();
  
  $nombre = trim($_POST['nombre'] ?? '');
  $clave = trim($_POST['clave'] ?? '');

  if ($nombre === '' || $clave === '') {
    $msg = 'Usuario y contraseña son obligatorios';
  } else {
    $nombreSql = mysqli_real_escape_string($con, $nombre);
    $rs = mysqli_query($con, "SELECT id, nombre, clave FROM usuarios WHERE nombre='$nombreSql'");
    
    if ($rs && mysqli_num_rows($rs) > 0) {
      $user = mysqli_fetch_assoc($rs);
      
      // Verificar la contraseña con password_verify
      if (password_verify($clave, $user['clave'])) {
        // Login exitoso
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        header('Location: index.php');
        exit;
      } else {
        $msg = 'Contraseña incorrecta';
      }
    } else {
      $msg = 'Usuario no encontrado';
    }
  }
}
?>
<!doctype html>
<html lang="es"><head>
  <meta charset="utf-8">
  <title>👻 Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/halloween.css">
</head>
<body>
<header class="header">
  <div class="brand">🎃 Halloween Votes</div>
  <nav class="nav small">
    <a href="index.php">Inicio</a>
  </nav>
</header>

<main class="container">
  <?php if ($msg): ?>
    <p class="alert"><?= h($msg) ?></p>
  <?php endif; ?>

  <form class="form" method="post">
    <?php csrf_field(); ?>
    
    <h2 style="margin-top:0; text-align:center">Iniciar sesión</h2>
    
    <div class="row">
      <div>
        <label>Usuario</label>
        <input class="input" name="nombre" maxlength="50" required 
               value="<?= h($_POST['nombre'] ?? '') ?>" 
               placeholder="Tu nombre de usuario">
      </div>
      
      <div>
        <label>Contraseña</label>
        <input class="input" type="password" name="clave" required 
               placeholder="Tu contraseña">
      </div>
    </div>
    
    <div style="margin-top:12px">
      <button class="btn" type="submit" style="width:100%">Entrar</button>
    </div>
    
    <p style="text-align:center; margin-top:1rem">
      ¿No tienes cuenta? <a href="registro.php" style="color:var(--accent); text-decoration:none; font-weight:600">Regístrate aquí</a>
    </p>
  </form>
</main>
</body>
</html>