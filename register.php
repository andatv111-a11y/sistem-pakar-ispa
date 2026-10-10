<?php
/**
 * =============================================================
 * register.php — Registrasi Akun User Baru
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
<title>Daftar Akun — SIPAKAR-ISPA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="login-page">
<div class="login-box" style="width:420px;">
    <div style="text-align:center; font-size:42px;">📝</div>
    <h2>Daftar Akun Baru</h2>
    <p class="subtitle">SIPAKAR-ISPA — Puskesmas Kedungpring</p>

    <?php if ($m = getFlash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <form method="POST" action="proses/register_proses.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <label>Nama Lengkap</label>
        <input type="text" name="nama" placeholder="Contoh: Ibu Siti"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autofocus>

        <label>Username</label>
        <input type="text" name="username" placeholder="Min. 3 karakter"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required minlength="3" maxlength="50">

        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 karakter" required minlength="6">

        <label>Konfirmasi Password</label>
        <input type="password" name="password2" placeholder="Ulangi password" required minlength="6">

        <button type="submit">📝 Daftar Sekarang</button>
    </form>

    <p class="small">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    <p class="small" style="color:#aaa; font-size:12px; margin-top:8px;">
        Dengan mendaftar, Anda menyetujui bahwa hasil sistem ini bersifat <em>diagnosa awal</em>.
    </p>
</div>
</body>
</html>