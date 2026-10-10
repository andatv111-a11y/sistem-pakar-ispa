<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../database.php';

function isLogin() { return isset($_SESSION['id_user']); }
function isAdmin() { return isLogin() && $_SESSION['role'] === 'admin'; }
function isUser()  { return isLogin() && $_SESSION['role'] === 'user'; }

function requireLogin() { if (!isLogin()) { header("Location: " . BASE_URL . "login.php"); exit; } }
function requireAdmin() { if (!isAdmin()) { header("Location: " . BASE_URL . "login.php"); exit; } }
function requireUser()  { if (!isUser())  { header("Location: " . BASE_URL . "login.php"); exit; } }

function csrfToken() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function checkCsrf($t) { return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t); }

function setFlash($k,$m){ $_SESSION['flash'][$k]=$m; }
function getFlash($k){
    if(isset($_SESSION['flash'][$k])){ $m=$_SESSION['flash'][$k]; unset($_SESSION['flash'][$k]); return $m; }
    return null;
}