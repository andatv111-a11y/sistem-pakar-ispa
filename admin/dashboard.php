<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$totalPenyakit = $pdo->query("SELECT COUNT(*) FROM penyakit")->fetchColumn();
$totalGejala   = $pdo->query("SELECT COUNT(*) FROM gejala")->fetchColumn();
$totalRule     = $pdo->query("SELECT COUNT(*) FROM rule_basis")->fetchColumn();
$totalKasus    = $pdo->query("SELECT COUNT(*) FROM kasus")->fetchColumn();
$totalDiagnosa = $pdo->query("SELECT COUNT(*) FROM hasil_diagnosa")->fetchColumn();
$totalUser     = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

$recent = $pdo->query("
    SELECT h.id_hasil, h.tanggal, h.nilai_akhir, u.nama AS nama_user, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN users u ON u.id_user = h.id_user
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    ORDER BY h.tanggal DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Dashboard Admin — SIPAKAR-ISPA</h2>
    <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> 👋</p>
    <p style="color:#666; font-size:14px;">
        Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita — Puskesmas Kedungpring
    </p>

    <div class="stats-grid">
        <div class="stat-card"><h3><?= $totalPenyakit ?></h3><p>Data Penyakit ISPA</p></div>
        <div class="stat-card"><h3><?= $totalGejala ?></h3><p>Data Gejala Klinis</p></div>
        <div class="stat-card"><h3><?= $totalRule ?></h3><p>Basis Rule (NB + CF)</p></div>
        <div class="stat-card"><h3><?= $totalKasus ?></h3><p>Basis Kasus</p></div>
        <div class="stat-card"><h3><?= $totalDiagnosa ?></h3><p>Total Diagnosa</p></div>
        <div class="stat-card"><h3><?= $totalUser ?></h3><p>User Terdaftar</p></div>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>5 Riwayat Diagnosa Terbaru</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Tanggal</th><th>Nama User</th>
                    <th>Hasil Diagnosa ISPA</th><th>Nilai Keyakinan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#888;">Belum ada data diagnosa.</td></tr>
                <?php else: foreach ($recent as $i => $r): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($r['nama_user']) ?></td>
                        <td><?= htmlspecialchars($r['nama_penyakit']) ?></td>
                        <td><strong><?= round($r['nilai_akhir']*100, 2) ?>%</strong></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>