<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/lib/auth.php';
require_login();
assert_csrf();

$id_usuario = (int) user_id();
$id_disfraz = (int) ($_POST['id_disfraz'] ?? 0);
if ($id_disfraz <= 0) { header('Location: index.php'); exit; }

$q = "SELECT id FROM votos WHERE id_usuario=$id_usuario AND id_disfraz=$id_disfraz";
$rs = mysqli_query($con, $q);
if ($rs && mysqli_num_rows($rs) > 0) {
  header('Location: index.php'); exit; // ya votó
}

mysqli_query($con, "INSERT INTO votos (id_usuario, id_disfraz) VALUES ($id_usuario, $id_disfraz)");
mysqli_query($con, "UPDATE disfraces SET votos = votos + 1 WHERE id = $id_disfraz");

header('Location: index.php');
