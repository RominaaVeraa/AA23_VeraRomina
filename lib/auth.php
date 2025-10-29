<?php
// lib/auth.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// CSRF
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
function csrf_field() {
  echo '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}
function assert_csrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
      http_response_code(400);
      exit('CSRF token inválido');
    }
  }
}

function is_logged()   { return !empty($_SESSION['user_id']); }
function user_id()     { return $_SESSION['user_id'] ?? null; }
function user_nombre() { return $_SESSION['user_nombre'] ?? null; }

// Admin simple: usuario llamado exactamente "admin"
function is_admin()    { return is_logged() && (user_nombre() === 'admin'); }

function require_login() {
  if (!is_logged()) { header('Location: login.php'); exit; }
}
function require_admin() {
  if (!is_admin()) { http_response_code(403); exit('Solo administrador.'); }
}
