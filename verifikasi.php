<?php
require_once __DIR__ . '/includes/auth.php';

$kode = $_GET['kode'] ?? '';
$hasil = null;
$valid = false;

if ($kode !== '') {
    // Cari hasil diagnosa yang md5(id + tanggal) = kode
    $stmt = $pdo->query("
        SELECT h.*, p.nama_penyakit, u.nama AS nama_user
        FROM hasil_diagnosa h
        JOIN penyakit p ON p.id_penyakit = h.id_penyakit
        JOIN users u ON u.id_user = h.id_user
    ");
    foreach ($stmt->fetchAll() as $r) {
        if (md5($r['id_hasil'] . $r['tanggal']) === $kode) {
            $hasil = $r;
            $valid = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Verifikasi Hasil Diagnosa — SIPAKAR-ISPA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body style="background:#f0f4f8; padding:40px 20px;">
<div style="max-width:600px; margin:0 auto;">
    <div style="background:#fff; padding:32px; border-radius:12px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,.1);">
        <div style="font-size:48px;">🩺</div>
        <h1 style="color:#0d6efd; margin:8px 0;">SIPAKAR-ISPA</h1>
        <p style="color:#666; font-size:14px;">Verifikasi Hasil Diagnosa</p>

        <?php if ($valid && $hasil): ?>
            <div style="background:#d1e7dd; color:#0f5132; padding:16px; border-radius:8px; margin-top:20px;">
                ✅ <strong>DOKUMEN VALID</strong>
            </div>

            <div style="text-align:left; margin-top:20px;">
                <table style="width:100%;">
                    <tr><td width="150">Nama User</td><td>: <?= htmlspecialchars($hasil['nama_user']) ?></td></tr>
                    <tr><td>Tanggal Diagnosa</td><td>: <?= date('d/m/Y H:i', strtotime($hasil['tanggal'])) ?></td></tr>
                    <tr><td>Hasil Diagnosa</td><td>: <strong><?= htmlspecialchars($hasil['nama_penyakit']) ?></strong></td></tr>
                    <tr><td>Nilai Keyakinan</td><td>: <strong><?= round($hasil['nilai_akhir']*100, 2) ?>%</strong></td></tr>
                </table>
            </div>

            <p style="margin-top:20px; font-size:12px; color:#888;">
                Dokumen ini diterbitkan oleh SIPAKAR-ISPA — Puskesmas Kedungpring
            </p>
        <?php else: ?>
            <div style="background:#f8d7da; color:#842029; padding:16px; border-radius:8px; margin-top:20px;">
                ❌ <strong>DOKUMEN TIDAK VALID</strong>
                <p style="font-size:13px; margin-top:8px;">QR code tidak dikenali atau sudah kedaluwarsa.</p>
            </div>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <a href="<?= BASE_URL ?>" class="btn">🏠 Beranda</a>
        </p>
    </div>
</div>
</body>
</html>