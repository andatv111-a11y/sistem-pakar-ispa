<?php
/**
 * =============================================================
 * login.php — Halaman Login SIPAKAR-ISPA
 * =============================================================
 */
require_once __DIR__ . '/includes/auth.php';

if (isLogin()) {
    header("Location: " . BASE_URL . (isAdmin() ? "admin/dashboard.php" : "user/dashboard.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — SIPAKAR-ISPA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="login-page">
<div class="login-box">
    <div style="text-align:center; font-size:48px;">🩺</div>
    <h2>SIPAKAR-ISPA</h2>
    <p class="subtitle">Sistem Pakar Diagnosa Awal ISPA pada Balita</p>

    <?php if ($m = getFlash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = getFlash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <form method="POST" action="proses/login_proses.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>

        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>

        <button type="submit">🔐 Masuk</button>
    </form>

    <p class="small">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    <p class="small" style="color:#aaa; font-size:12px; margin-top:8px;">
        &copy; <?= date('Y') ?> SIPAKAR-ISPA — Puskesmas Kedungpring
    </p>
</div>
</body>
</html>