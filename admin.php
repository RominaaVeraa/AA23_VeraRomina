<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/lib/auth.php';
require_admin();

$action = $_GET['action'] ?? '';
$msg = '';

// Crear/Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  assert_csrf();
  $id = (int)($_POST['id'] ?? 0);
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $eliminado = isset($_POST['eliminado']) ? 1 : 0;

  if ($nombre === '' || $descripcion === '') {
    $msg = 'Nombre y descripción son obligatorios';
  } else {
    $nombreSql = mysqli_real_escape_string($con, $nombre);
    $descSql   = mysqli_real_escape_string($con, $descripcion);

    // Subida de imagen
    $setFoto = '';
    $fotoBlobSql = "''"; // por defecto vacío

    if (!empty($_FILES['foto']['name'])) {                 // $_FILES['foto']['name']
      $archivo = $_FILES['foto']['name'];
      $parts = explode('.', $archivo);                     // explode(".",$archivo)
      $ext = strtolower(end($parts));                      // end($extension)

      if (is_uploaded_file($_FILES['foto']['tmp_name'])) { // is_uploaded_file()
        $qu = time();                                      // time()
        $dest = "fotos/{$qu}.{$ext}";
        if (copy($_FILES['foto']['tmp_name'], $dest)) {    // copy(...)
          $setFoto = ", foto='" . mysqli_real_escape_string($con, basename($dest)) . "'";
          $bin = file_get_contents($dest);
          $fotoBlobSql = "'" . mysqli_real_escape_string($con, $bin) . "'";

          if (!empty($_POST['foto_actual'])) {
            @unlink('fotos/' . $_POST['foto_actual']);     // unlink('fotos/' . $_POST['foto_actual'])
          }
        } else {
          $msg = 'No se pudo mover la imagen';
        }
      } else {
        $msg = 'Subida inválida';
      }
    }

    if ($id > 0) {
      $q = "UPDATE disfraces
            SET nombre='$nombreSql', descripcion='$descSql', eliminado=$eliminado, foto_blob=$fotoBlobSql $setFoto
            WHERE id=$id";
      if (!mysqli_query($con, $q)) { $msg = 'Error: ' . mysqli_error($con); }
      else { $msg = 'Disfraz actualizado'; }
    } else {
      $q = "INSERT INTO disfraces (nombre, descripcion, votos, foto, foto_blob, eliminado)
            VALUES ('$nombreSql', '$descSql', 0, '', $fotoBlobSql, $eliminado)";
      if (!mysqli_query($con, $q)) { $msg = 'Error: ' . mysqli_error($con); }
      else {
        $newId = mysqli_insert_id($con);                   // mysqli_insert_id()
        if ($setFoto !== '') {
          mysqli_query($con, "UPDATE disfraces SET " . substr($setFoto, 2) . " WHERE id = $newId");
        }
        $msg = 'Disfraz creado';
      }
    }
  }
}

// Borrar físico (opcional)
if ($action === 'del' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $rs = mysqli_query($con, "SELECT foto FROM disfraces WHERE id=$id");
  $r = $rs ? mysqli_fetch_assoc($rs) : null;
  mysqli_query($con, "DELETE FROM disfraces WHERE id=$id");
  if ($r && !empty($r['foto'])) { @unlink('fotos/' . $r['foto']); }
  header('Location: admin.php'); exit;
}

// Edición: traer fila si corresponde
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $rs = mysqli_query($con, "SELECT * FROM disfraces WHERE id=$id");
  $edit = $rs ? mysqli_fetch_assoc($rs) : null;
}
?>
<!doctype html>
<html lang="es"><head>
  <meta charset="utf-8"><title>Admin – Disfraces</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/halloween.css">
</head><body>
<header class="header">
  <div class="brand">🧙‍♀️ Admin Disfraces</div>
  <nav class="nav small"><a href="index.php">Inicio</a> <a href="logout.php">Salir</a></nav>
</header>
<main class="container">
  <?php if ($msg) echo '<p class="alert">'.h($msg).'</p>'; ?>

  <h2><?= $edit ? 'Editar' : 'Crear' ?> disfraz</h2>
  <form class="form" method="post" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <input type="hidden" name="foto_actual" value="<?= h($edit['foto'] ?? '') ?>">
    <div class="row">
      <label>Nombre</label>
      <input class="input" name="nombre" maxlength="50" required value="<?= h($edit['nombre'] ?? '') ?>">
      <label>Descripción</label>
      <textarea name="descripcion" rows="4" required><?= h($edit['descripcion'] ?? '') ?></textarea>
    </div>
    <div class="row2">
      <div>
        <label>Imagen (jpg/png/gif/webp)</label>
        <input type="file" name="foto" accept="image/*">
      </div>
      <div style="align-self:end">
        <label><input type="checkbox" name="eliminado" <?= !empty($edit['eliminado']) ? 'checked' : '' ?>> Marcado eliminado</label>
      </div>
    </div>
    <div style="margin-top:12px"><button class="btn" type="submit">Guardar</button></div>
  </form>

  <h2 style="margin-top:28px">Listado (vista rápida)</h2>
  <?php
    $rs = mysqli_query($con, "SELECT id, nombre, votos, foto, eliminado FROM disfraces ORDER BY id DESC");
    if ($rs) {
      $nf = mysqli_num_fields($rs); // mysqli_num_fields()
      echo '<table class="table"><thead><tr>';
      for ($i=0; $i<$nf; $i++) {
        $finfo = mysqli_fetch_field_direct($rs, $i);
        echo '<th>' . h($finfo->name) . '</th>';
      }
      echo '<th>Acciones</th></tr></thead><tbody>';
      while ($row = mysqli_fetch_assoc($rs)) {
        echo '<tr>';
        foreach ($row as $v) echo '<td>' . h((string)$v) . '</td>';
        echo '<td><a class="btn secondary" href="admin.php?action=edit&id='.(int)$row['id'].'">Editar</a> ';
        echo '<a class="btn" href="admin.php?action=del&id='.(int)$row['id'].'" onclick="return confirm(\'¿Eliminar?\')">Eliminar</a></td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p class="alert">'.h(mysqli_error($con)).'</p>'; // mysqli_error($con)
    }
  ?>
</main>
</body></html>
