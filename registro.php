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
    // Verificar si el usuario ya existe
    $nombreSql = mysqli_real_escape_string($con, $nombre);
    $checkUser = mysqli_query($con, "SELECT id FROM usuarios WHERE nombre='$nombreSql'");
    
    if ($checkUser && mysqli_num_rows($checkUser) > 0) {
      $msg = 'El usuario "' . h($nombre) . '" ya existe. Por favor elige otro nombre.';
    } else {
      // Hash de la contraseña
      $hash = password_hash($clave, PASSWORD_DEFAULT);
      $hashSql = mysqli_real_escape_string($con, $hash);
      
      $q = "INSERT INTO usuarios (nombre, clave) VALUES ('$nombreSql', '$hashSql')";
      
      if (mysqli_query($con, $q)) {
        // Registro exitoso, iniciar sesión automáticamente
        $newId = mysqli_insert_id($con);
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_nombre'] = $nombre;
        header('Location: index.php');
        exit;
      } else {
        $msg = 'Error al registrar: ' . mysqli_error($con);
      }
    }
  }
}
?>
<!doctype html>
<html lang="es"><head>
  <meta charset="utf-8">
  <title>🎃 Registro</title>
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
    
    <h2 style="margin-top:0; text-align:center">Crear cuenta</h2>
    
    <div class="row">
      <div>
        <label>Usuario</label>
        <input class="input" name="nombre" maxlength="50" required 
               value="<?= h($_POST['nombre'] ?? '') ?>" 
               placeholder="Elige un nombre de usuario">
      </div>
      
      <div>
        <label>Contraseña</label>
        <input class="input" type="password" name="clave" required 
               placeholder="Escribe una contraseña segura">
      </div>
    </div>
    
    <div style="margin-top:12px">
      <button class="btn" type="submit" style="width:100%">Crear cuenta</button>
    </div>
    
    <p class="tip" style="margin-top:1rem">
      💡 Tip: crea la cuenta <strong>admin</strong> para administrar disfraces.
    </p>
    
    <p style="text-align:center; margin-top:1rem">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
    </p>
  </form>
</main>
</body>
</html>