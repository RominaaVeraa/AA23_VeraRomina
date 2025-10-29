<?php
// config/db.php
$DB_HOST = '127.0.0.1';
$DB_PORT = 3308; // cambia a 3308 si tu MySQL está en 3308
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'halloween';

$con = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT); // mysqli_connect()
if (!$con) {
  die('Error de conexión: ' . mysqli_connect_error());
}
mysqli_set_charset($con, 'utf8mb4');
