<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

// ==============================
// FILTER TANGGAL
// ==============================
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$params = [$idUser, $dari, $sampai];

// ==============================
// STATISTIK USER
// ==============================
$stmt = $pdo->prepare("SELECT COUNT(*) FROM hasil_diagnosa WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$totalDiagnosa = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(nilai_akhir) FROM hasil_diagnosa WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$rataKeyakinan = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT SUM(CASE WHEN nilai_akhir >= 0.7 THEN 1 ELSE 0 END) AS tinggi,
           SUM(CASE WHEN nilai_akhir >= 0.5 AND nilai_akhir < 0.7 THEN 1 ELSE 0 END) AS sedang,
           SUM(CASE WHEN nilai_akhir < 0.5 THEN 1 ELSE 0 END) AS rendah
    FROM hasil_diagnosa
    WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ?
");
$stmt->execute($params);
$distStatus = $stmt->fetch();

// ==============================
// DISTRIBUSI PENYAKIT
// ==============================
$stmt = $pdo->prepare("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ? AND DATE(h.tanggal) BETWEEN ? AND ?
    GROUP BY p.id_penyakit
    ORDER BY jumlah DESC
");
$stmt->execute($params);
$distPenyakit = $stmt->fetchAll();

// ==============================
// DISTRIBUSI USIA BALITA
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN usia < 12 THEN 1 ELSE 0 END) AS bayi,
        SUM(CASE WHEN usia BETWEEN 12 AND 24 THEN 1 ELSE 0 END) AS batita,
        SUM(CASE WHEN usia BETWEEN 25 AND 60 THEN 1 ELSE 0 END) AS balita
    FROM hasil_diagnosa
    WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ? AND usia IS NOT NULL
");
$stmt->execute($params);
$distUsia = $stmt->fetch();

// ==============================
// PER HARI
// ==============================
$stmt = $pdo->prepare("
    SELECT DATE(tanggal) AS tgl, COUNT(*) AS jumlah
    FROM hasil_diagnosa
    WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ?
    GROUP BY DATE(tanggal) ORDER BY tgl
");
$stmt->execute($params);
$perHari = $stmt->fetchAll();

// ==============================
// DETAIL DIAGNOSA
// ==============================
$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ? AND DATE(h.tanggal) BETWEEN ? AND ?
    ORDER BY h.tanggal DESC
");
$stmt->execute($params);
$detail = $stmt->fetchAll();

// Info user
$stmt = $pdo->prepare("SELECT nama, username, no_telepon, alamat FROM users WHERE id_user = ?");
$stmt->execute([$idUser]);
$userInfo = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>📊 Laporan Saya</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Laporan diagnosa ISPA pribadi Anda — <?= htmlspecialchars($userInfo['nama'] ?? '-') ?>
    </p>

    <!-- Filter -->
    <div class="container" style="max-width:100%;">
        <h3>🔍 Filter Periode</h3>
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px;">
                <label>Dari Tanggal</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            </div>
            <div style="flex:1; min-width:160px;">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="btn">🔍 Terapkan</button>
                <a href="laporan.php" class="btn btn-warning">🔄 Reset</a>
                <a href="cetak_laporan.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>"
                   target="_blank" class="btn btn-success">🖨️ Cetak PDF</a>
                <button onclick="window.print()" class="btn" style="background:#6c757d;">🖨️ Print Halaman</button>
            </div>
        </form>
    </div>

    <!-- Statistik -->
    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><h3><?= $totalDiagnosa ?></h3><p>Total Diagnosa</p></div>
        <div class="stat-card" style="border-left-color:#198754;">
            <h3 style="color:#198754;"><?= round($rataKeyakinan * 100, 1) ?>%</h3>
            <p>Rata-rata Keyakinan</p>
        </div>
        <div class="stat-card" style="border-left-color:#0d6efd;">
            <h3 style="color:#0d6efd;"><?= (int)($distStatus['tinggi'] ?? 0) ?></h3>
            <p>Keyakinan Tinggi (≥70%)</p>
        </div>
        <div class="stat-card" style="border-left-color:#dc3545;">
            <h3 style="color:#dc3545;"><?= (int)($distStatus['rendah'] ?? 0) ?></h3>
            <p>Keyakinan Rendah (&lt;50%)</p>
        </div>
    </div>

    <!-- Distribusi Penyakit -->
    <?php if (!empty($distPenyakit)): ?>
        <?php $maxP = max(array_column($distPenyakit, 'jumlah')) ?: 1; ?>
        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>🦠 Distribusi Penyakit</h3>
            <table>
                <thead><tr><th>#</th><th>Penyakit</th><th>Jumlah</th><th>Persentase</th><th>Visual</th></tr></thead>
                <tbody>
                    <?php $no = 1; foreach ($distPenyakit as $p):
                        $persen = $totalDiagnosa > 0 ? round($p['jumlah'] / $totalDiagnosa * 100, 1) : 0;
                        $bar = $p['jumlah'] / $maxP * 100;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($p['kode_penyakit'].' - '.$p['nama_penyakit']) ?></td>
                            <td><strong><?= $p['jumlah'] ?></strong></td>
                            <td><?= $persen ?>%</td>
                            <td><div class="progress-bar" style="margin:0; height:10px;"><div class="progress-fill" style="width:<?= $bar ?>%;"></div></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Distribusi Usia -->
    <?php if ($distUsia && ($distUsia['bayi'] + $distUsia['batita'] + $distUsia['balita']) > 0): ?>
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>👶 Distribusi Usia Balita</h3>
        <table>
            <thead><tr><th>Kelompok Usia</th><th>Jumlah</th></tr></thead>
            <tbody>
                <tr><td>Bayi (&lt; 12 bulan)</td><td><strong><?= (int)$distUsia['bayi'] ?></strong></td></tr>
                <tr><td>Batita (12–24 bulan)</td><td><strong><?= (int)$distUsia['batita'] ?></strong></td></tr>
                <tr><td>Balita (25–60 bulan)</td><td><strong><?= (int)$distUsia['balita'] ?></strong></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Per Hari -->
    <?php if (!empty($perHari)): ?>
        <?php $maxH = max(array_column($perHari, 'jumlah')) ?: 1; ?>
        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>📅 Aktivitas Diagnosa Harian</h3>
            <table>
                <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Visual</th></tr></thead>
                <tbody>
                    <?php foreach ($perHari as $h):
                        $bar = $h['jumlah'] / $maxH * 100;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                            <td><strong><?= $h['jumlah'] ?></strong></td>
                            <td><div class="progress-bar" style="margin:0; height:10px;"><div class="progress-fill" style="width:<?= $bar ?>%;"></div></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Detail Diagnosa -->
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>📋 Detail Riwayat Diagnosa (<?= count($detail) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Balita</th>
                    <th>Usia</th>
                    <th>Hasil Diagnosa</th>
                    <th>Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detail)): ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada data diagnosa.</td></tr>
                <?php else: foreach ($detail as $i => $d): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($d['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($d['nama_balita'] ?? '-') ?></td>
                        <td><?= $d['usia'] ? $d['usia'].' bln' : '-' ?></td>
                        <td><?= htmlspecialchars($d['kode_penyakit'].' - '.$d['nama_penyakit']) ?></td>
                        <td><strong><?= round($d['nilai_akhir']*100, 2) ?>%</strong></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Ringkasan -->
    <div class="container" style="max-width:100%; margin-top:20px; background:#e7f1ff;">
        <h3>📝 Ringkasan Laporan</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li>Nama User: <strong><?= htmlspecialchars($userInfo['nama'] ?? '-') ?></strong></li>
            <li>Username: <strong><?= htmlspecialchars($userInfo['username'] ?? '-') ?></strong></li>
            <li>Periode: <strong><?= date('d/m/Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($sampai)) ?></strong></li>
            <li>Total diagnosa: <strong><?= $totalDiagnosa ?></strong> kali</li>
            <li>Rata-rata keyakinan: <strong><?= round($rataKeyakinan * 100, 2) ?>%</strong></li>
            <li>Penyakit terbanyak: <strong><?= !empty($distPenyakit) ? htmlspecialchars($distPenyakit[0]['nama_penyakit']) : '-' ?></strong></li>
        </ul>
    </div>
</div>

<style media="print">
    .navbar, .sidebar, .footer, .btn, button, form { display: none !important; }
    .main-content { display: block; }
    .content { padding: 0; }
    .container, .stat-card { box-shadow: none; border: 1px solid #ccc; page-break-inside: avoid; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>