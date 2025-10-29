<?php
require __DIR__ . '/lib/auth.php';
$_SESSION = [];
session_destroy();
header('Location: index.php');
