<?php
require_once __DIR__ . '/../includes/auth.php';

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "register.php");
    exit;
}

// Verifikasi CSRF
if (!checkCsrf($_POST['csrf'] ?? '')) {
    setFlash('error', 'Token keamanan tidak valid.');
    header("Location: " . BASE_URL . "register.php");
    exit;
}

$nama      = trim($_POST['nama'] ?? '');
$username  = trim($_POST['username'] ?? '');
$password  = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

// Validasi
if ($nama === '' || $username === '' || $password === '') {
    setFlash('error', 'Semua field wajib diisi.');
    header("Location: " . BASE_URL . "register.php");
    exit;
}
if (strlen($password) < 6) {
    setFlash('error', 'Password minimal 6 karakter.');
    header("Location: " . BASE_URL . "register.php");
    exit;
}
if ($password !== $password2) {
    setFlash('error', 'Konfirmasi password tidak cocok.');
    header("Location: " . BASE_URL . "register.php");
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
    setFlash('error', 'Username hanya boleh huruf, angka, underscore, titik (3-50 karakter).');
    header("Location: " . BASE_URL . "register.php");
    exit;
}

// Cek username sudah dipakai
$stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    setFlash('error', 'Username sudah digunakan. Silakan pilih yang lain.');
    header("Location: " . BASE_URL . "register.php");
    exit;
}

// Simpan user baru
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'user')");
$stmt->execute([$nama, $username, $hash]);

setFlash('success', 'Registrasi berhasil. Silakan login untuk memulai diagnosa.');
header("Location: " . BASE_URL . "login.php");
exit;