<?php
/**
 * =============================================================
 * index.php — Landing Page SIPAKAR-ISPA
 * Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita
 * Puskesmas Kedungpring
 * =============================================================
 */
require_once __DIR__ . '/includes/auth.php';

// Kalau sudah login → redirect ke dashboard sesuai role
if (isLogin()) {
    if (isAdmin()) {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "user/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIPAKAR-ISPA — Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<style>
    .landing {
        min-height: 100vh;
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        text-align: center;
    }
    .landing .logo { font-size: 72px; margin-bottom: 12px; }
    .landing h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 1px;
    }
    .landing h2 {
        font-size: 16px;
        font-weight: 400;
        opacity: .9;
        margin-bottom: 28px;
        max-width: 700px;
    }
    .landing .info {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 12px;
        padding: 20px 28px;
        max-width: 720px;
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 32px;
        text-align: left;
    }
    .landing .info strong { color: #ffd166; }
    .landing .btn-group { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
    .landing .btn-landing {
        padding: 12px 28px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: .2s;
    }
    .landing .btn-primary-landing { background: #fff; color: #0d6efd; }
    .landing .btn-primary-landing:hover { background: #e7f1ff; transform: translateY(-2px); }
    .landing .btn-outline-landing {
        background: transparent; color: #fff; border: 2px solid #fff;
    }
    .landing .btn-outline-landing:hover { background: rgba(255,255,255,.15); }
    .landing footer {
        margin-top: 40px; font-size: 13px; opacity: .85;
        border-top: 1px solid rgba(255,255,255,.2);
        padding-top: 20px; max-width: 900px; width: 100%;
    }
</style>
</head>
<body>
<div class="landing">
    <div class="logo">🩺</div>
    <h1>SIPAKAR-ISPA</h1>
    <h2>Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita</h2>

    <div class="info">
        <p style="margin-bottom:10px;">
            <strong>Judul Penelitian:</strong><br>
            Rancang Bangun Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita
            Menggunakan Algoritma <em>Naive Bayes</em> dan Metode <em>Certainty Factor</em>
            Berbasis Web di Puskesmas Kedungpring.
        </p>
        <p>
            <strong>Metode:</strong> Naive Bayes + Certainty Factor<br>
            <strong>Lokasi:</strong> Puskesmas Kedungpring<br>
            <strong>Fungsi:</strong> Membantu orang tua &amp; kader posyandu melakukan diagnosa awal ISPA pada balita.
        </p>
    </div>

    <div class="btn-group">
        <a href="login.php" class="btn-landing btn-primary-landing">🔐 Login</a>
        <a href="register.php" class="btn-landing btn-outline-landing">📝 Daftar Akun</a>
    </div>

    <footer>
        &copy; <?= date('Y') ?> SIPAKAR-ISPA — Puskesmas Kedungpring.
        Hasil sistem pakar bersifat diagnosa awal, bukan pengganti pemeriksaan dokter.
    </footer>
</div>
</body>
</html>