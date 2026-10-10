<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistem Pakar ISPA - Puskesmas Kedungpring</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="brand">🩺 Sistem Pakar ISPA</div>
    <div class="menu">
        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>admin/penyakit.php">Penyakit</a>
            <a href="<?= BASE_URL ?>admin/gejala.php">Gejala</a>
            <a href="<?= BASE_URL ?>admin/rule.php">Rule</a>
            <a href="<?= BASE_URL ?>admin/basis_kasus.php">Basis Kasus</a>
            <a href="<?= BASE_URL ?>admin/riwayat.php">Riwayat</a>
            <a href="<?= BASE_URL ?>admin/users.php">Users</a>
        <?php elseif (isUser()): ?>
            <a href="<?= BASE_URL ?>user/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>user/diagnosa.php">Diagnosa</a>
            <a href="<?= BASE_URL ?>user/riwayat.php">Riwayat</a>
        <?php endif; ?>
        <?php if (isLogin()): ?>
            <a href="<?= BASE_URL ?>logout.php" class="logout">Logout (<?= htmlspecialchars($_SESSION['nama']) ?>)</a>
        <?php endif; ?>
    </div>
</nav>
<main class="main-content">