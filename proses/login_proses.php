<?php
require_once __DIR__ . '/../includes/auth.php';

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Verifikasi CSRF
if (!checkCsrf($_POST['csrf'] ?? '')) {
    setFlash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if ($username === '' || $password === '') {
    setFlash('error', 'Username dan password wajib diisi.');
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Cari user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Verifikasi password
if ($user && password_verify($password, $user['password'])) {
    // Regenerate session ID untuk cegah session fixation
    session_regenerate_id(true);

    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];

    // Redirect sesuai role
    if ($user['role'] === 'admin') {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "user/dashboard.php");
    }
    exit;
}

// Gagal login
setFlash('error', 'Username atau password salah.');
header("Location: " . BASE_URL . "login.php");
exit;