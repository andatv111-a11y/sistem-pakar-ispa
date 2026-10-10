<?php
/**
 * =============================================================
 * cetak_laporan.php — Halaman Cetak Laporan SIPAKAR-ISPA
 * Format rapi untuk print / save as PDF
 * =============================================================
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Filter tanggal
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$params = [$dari, $sampai];

// Info Puskesmas
$info = $pdo->query("SELECT * FROM info_puskesmas LIMIT 1")->fetch();
if (!$info) {
    $info = [
        'nama' => 'Puskesmas Kedungpring',
        'alamat' => 'Jl. Raya Kedungpring, Kabupaten Lamongan',
        'telepon' => '(0322) 123456',
        'email' => 'puskesmaskedungpring@email.com',
    ];
}

// ==============================
// AMBIL DATA
// ==============================

// Statistik umum
$stmt = $pdo->prepare("SELECT COUNT(*) FROM hasil_diagnosa WHERE DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$totalDiagnosa = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(nilai_akhir) FROM hasil_diagnosa WHERE DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$rataKeyakinan = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_user) FROM hasil_diagnosa WHERE DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$totalUser = (int) $stmt->fetchColumn();

// Distribusi penyakit
$stmt = $pdo->prepare("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE DATE(h.tanggal) BETWEEN ? AND ?
    GROUP BY p.id_penyakit ORDER BY jumlah DESC
");
$stmt->execute($params);
$distPenyakit = $stmt->fetchAll();

// Distribusi status
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN nilai_akhir >= 0.7 THEN 1 ELSE 0 END) AS tinggi,
        SUM(CASE WHEN nilai_akhir >= 0.5 AND nilai_akhir < 0.7 THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN nilai_akhir < 0.5 THEN 1 ELSE 0 END) AS rendah
    FROM hasil_diagnosa WHERE DATE(tanggal) BETWEEN ? AND ?
");
$stmt->execute($params);
$distStatus = $stmt->fetch();

// Distribusi usia
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN usia < 12 THEN 1 ELSE 0 END) AS bayi,
        SUM(CASE WHEN usia BETWEEN 12 AND 24 THEN 1 ELSE 0 END) AS batita,
        SUM(CASE WHEN usia BETWEEN 25 AND 60 THEN 1 ELSE 0 END) AS balita
    FROM hasil_diagnosa
    WHERE DATE(tanggal) BETWEEN ? AND ? AND usia IS NOT NULL
");
$stmt->execute($params);
$distUsia = $stmt->fetch();

// Distribusi jenis kelamin
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN jenis_kelamin='L' THEN 1 ELSE 0 END) AS laki,
        SUM(CASE WHEN jenis_kelamin='P' THEN 1 ELSE 0 END) AS perempuan
    FROM hasil_diagnosa
    WHERE DATE(tanggal) BETWEEN ? AND ? AND jenis_kelamin IS NOT NULL
");
$stmt->execute($params);
$distJK = $stmt->fetch();

// Detail riwayat diagnosa
$stmt = $pdo->prepare("
    SELECT h.*, u.nama AS nama_user, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN users u ON u.id_user = h.id_user
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE DATE(h.tanggal) BETWEEN ? AND ?
    ORDER BY h.tanggal DESC
");
$stmt->execute($params);
$detail = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan — SIPAKAR-ISPA</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Times New Roman', Times, serif;
        background: #f0f4f8;
        padding: 20px;
        color: #000;
        font-size: 12pt;
        line-height: 1.5;
    }
    .page {
        background: #fff;
        max-width: 800px;
        margin: 0 auto;
        padding: 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,.1);
    }

    /* ============ KOP SURAT ============ */
    .kop {
        border-bottom: 3px double #000;
        padding-bottom: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .kop .logo {
        width: 80px;
        height: 80px;
        border: 2px solid #0d6efd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        flex-shrink: 0;
    }
    .kop .info {
        flex: 1;
        text-align: center;
    }
    .kop .info h2 {
        font-size: 14pt;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .kop .info h1 {
        font-size: 16pt;
        text-transform: uppercase;
        font-weight: bold;
    }
    .kop .info p {
        font-size: 10pt;
        margin-top: 3px;
    }

    /* ============ JUDUL ============ */
    .judul {
        text-align: center;
        margin: 20px 0;
    }
    .judul h3 {
        font-size: 13pt;
        text-transform: uppercase;
        text-decoration: underline;
        margin-bottom: 4px;
    }
    .judul p {
        font-size: 11pt;
    }

    /* ============ SECTION ============ */
    .section {
        margin: 20px 0;
    }
    .section h4 {
        font-size: 12pt;
        background: #0d6efd;
        color: #fff;
        padding: 6px 12px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    /* ============ TABEL ============ */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    table th, table td {
        border: 1px solid #333;
        padding: 6px 10px;
        text-align: left;
        font-size: 11pt;
    }
    table th {
        background: #e7f1ff;
        font-weight: bold;
        text-align: center;
    }
    table td.angka {
        text-align: center;
    }

    /* ============ BOX INFO ============ */
    .info-box {
        display: flex;
        gap: 20px;
        margin: 20px 0;
    }
    .info-box .box {
        flex: 1;
        border: 1px solid #333;
        padding: 12px;
        text-align: center;
    }
    .info-box .box .nilai {
        font-size: 20pt;
        font-weight: bold;
        color: #0d6efd;
    }
    .info-box .box .label {
        font-size: 10pt;
        margin-top: 4px;
    }

    /* ============ TTD ============ */
    .ttd {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }
    .ttd .kolom {
        text-align: center;
        width: 200px;
    }
    .ttd .kolom .space {
        height: 70px;
    }
    .ttd .kolom .garis {
        border-top: 1px solid #000;
        padding-top: 4px;
        font-weight: bold;
    }

    /* ============ TOMBOL (tidak muncul di print) ============ */
    .toolbar {
        position: fixed;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 999;
    }
    .toolbar button, .toolbar a {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: bold;
        text-decoration: none;
        color: #fff;
    }
    .toolbar .print { background: #198754; }
    .toolbar .back { background: #6c757d; }

    /* ============ PRINT ============ */
    @media print {
        body { background: #fff; padding: 0; font-size: 11pt; }
        .page { box-shadow: none; max-width: 100%; padding: 20px; }
        .toolbar { display: none !important; }
        @page { size: A4; margin: 1.5cm; }
        .section { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<!-- TOOLBAR (disembunyikan saat print) -->
<div class="toolbar">
    <button class="print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a class="back" href="laporan.php">⬅️ Kembali</a>
</div>

<div class="page">

    <!-- ============================== -->
    <!-- KOP SURAT -->
    <!-- ============================== -->
    <div class="kop">
        <div class="logo">🩺</div>
        <div class="info">
            <h2>Pemerintah Kabupaten Lamongan</h2>
            <h2>Dinas Kesehatan</h2>
            <h1><?= htmlspecialchars($info['nama'] ?? 'Puskesmas Kedungpring') ?></h1>
            <p><?= htmlspecialchars($info['alamat'] ?? '-') ?></p>
            <p>Telp: <?= htmlspecialchars($info['telepon'] ?? '-') ?> | Email: <?= htmlspecialchars($info['email'] ?? '-') ?></p>
        </div>
    </div>

    <!-- ============================== -->
    <!-- JUDUL LAPORAN -->
    <!-- ============================== -->
    <div class="judul">
        <h3>Laporan Diagnosa Awal Penyakit ISPA pada Balita</h3>
        <p><strong>SIPAKAR-ISPA</strong> — Sistem Pakar Diagnosa ISPA Berbasis Web</p>
        <p>Periode: <strong><?= date('d F Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d F Y', strtotime($sampai)) ?></strong></p>
        <p style="font-size:10pt; margin-top:6px;">
            Dicetak: <?= date('d F Y H:i') ?> WIB
        </p>
    </div>

    <!-- ============================== -->
    <!-- RINGKASAN -->
    <!-- ============================== -->
    <div class="section">
        <h4>I. Ringkasan</h4>
        <div class="info-box">
            <div class="box">
                <div class="nilai"><?= $totalDiagnosa ?></div>
                <div class="label">Total Diagnosa</div>
            </div>
            <div class="box">
                <div class="nilai"><?= round($rataKeyakinan * 100, 1) ?>%</div>
                <div class="label">Rata-rata Keyakinan</div>
            </div>
            <div class="box">
                <div class="nilai"><?= $totalUser ?></div>
                <div class="label">User Aktif</div>
            </div>
        </div>
    </div>

    <!-- ============================== -->
    <!-- DISTRIBUSI PENYAKIT -->
    <!-- ============================== -->
    <?php if (!empty($distPenyakit)): ?>
    <div class="section">
        <h4>II. Distribusi Penyakit ISPA</h4>
        <table>
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Kode</th>
                    <th>Nama Penyakit</th>
                    <th width="80">Jumlah</th>
                    <th width="100">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($distPenyakit as $p):
                    $persen = $totalDiagnosa > 0 ? round($p['jumlah'] / $totalDiagnosa * 100, 1) : 0;
                ?>
                    <tr>
                        <td class="angka"><?= $no++ ?></td>
                        <td class="angka"><?= htmlspecialchars($p['kode_penyakit']) ?></td>
                        <td><?= htmlspecialchars($p['nama_penyakit']) ?></td>
                        <td class="angka"><?= $p['jumlah'] ?></td>
                        <td class="angka"><?= $persen ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- DISTRIBUSI STATUS -->
    <!-- ============================== -->
    <div class="section">
        <h4>III. Distribusi Tingkat Keyakinan</h4>
        <?php
        $t = (int)($distStatus['tinggi'] ?? 0);
        $s = (int)($distStatus['sedang'] ?? 0);
        $r = (int)($distStatus['rendah'] ?? 0);
        $tot = max($t + $s + $r, 1);
        ?>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th width="100">Jumlah</th>
                    <th width="120">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Tinggi (≥ 70%)</td><td class="angka"><?= $t ?></td><td class="angka"><?= round($t/$tot*100,1) ?>%</td></tr>
                <tr><td>Sedang (50 – 69%)</td><td class="angka"><?= $s ?></td><td class="angka"><?= round($s/$tot*100,1) ?>%</td></tr>
                <tr><td>Rendah (&lt; 50%)</td><td class="angka"><?= $r ?></td><td class="angka"><?= round($r/$tot*100,1) ?>%</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ============================== -->
    <!-- DISTRIBUSI USIA -->
    <!-- ============================== -->
    <?php if ($distUsia && ($distUsia['bayi'] + $distUsia['batita'] + $distUsia['balita']) > 0): ?>
    <div class="section">
        <h4>IV. Distribusi Usia Balita</h4>
        <table>
            <thead>
                <tr><th>Kelompok Usia</th><th width="120">Jumlah</th></tr>
            </thead>
            <tbody>
                <tr><td>Bayi (&lt; 12 bulan)</td><td class="angka"><?= (int)$distUsia['bayi'] ?></td></tr>
                <tr><td>Batita (12 – 24 bulan)</td><td class="angka"><?= (int)$distUsia['batita'] ?></td></tr>
                <tr><td>Balita (25 – 60 bulan)</td><td class="angka"><?= (int)$distUsia['balita'] ?></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- DISTRIBUSI JK -->
    <!-- ============================== -->
    <?php if ($distJK && ($distJK['laki'] + $distJK['perempuan']) > 0): ?>
    <div class="section">
        <h4>V. Distribusi Jenis Kelamin</h4>
        <table>
            <thead>
                <tr><th>Jenis Kelamin</th><th width="120">Jumlah</th></tr>
            </thead>
            <tbody>
                <tr><td>Laki-laki</td><td class="angka"><?= (int)$distJK['laki'] ?></td></tr>
                <tr><td>Perempuan</td><td class="angka"><?= (int)$distJK['perempuan'] ?></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- DETAIL RIWAYAT (10 TERAKHIR) -->
    <!-- ============================== -->
    <?php if (!empty($detail)): ?>
    <div class="section">
        <h4>VI. Riwayat Diagnosa (10 Terbaru)</h4>
        <table>
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th width="100">Tanggal</th>
                    <th>User</th>
                    <th>Hasil Diagnosa</th>
                    <th width="70">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach (array_slice($detail, 0, 10) as $d): ?>
                    <tr>
                        <td class="angka"><?= $no++ ?></td>
                        <td class="angka"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($d['nama_user']) ?></td>
                        <td><?= htmlspecialchars($d['kode_penyakit'] . ' - ' . $d['nama_penyakit']) ?></td>
                        <td class="angka"><?= round($d['nilai_akhir']*100, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="font-size:10pt; font-style:italic; margin-top:6px;">
            * Menampilkan 10 dari <?= count($detail) ?> total data
        </p>
    </div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- KESIMPULAN -->
    <!-- ============================== -->
    <div class="section">
        <h4>VII. Kesimpulan</h4>
        <p style="text-align:justify;">
            Berdasarkan data diagnosa ISPA pada balita periode
            <strong><?= date('d F Y', strtotime($dari)) ?></strong> s/d
            <strong><?= date('d F Y', strtotime($sampai)) ?></strong>,
            tercatat sebanyak <strong><?= $totalDiagnosa ?></strong> diagnosa
            dengan rata-rata tingkat keyakinan <strong><?= round($rataKeyakinan * 100, 2) ?>%</strong>.
            <?php if (!empty($distPenyakit)): ?>
                Penyakit yang paling sering terdiagnosa adalah
                <strong><?= htmlspecialchars($distPenyakit[0]['nama_penyakit']) ?></strong>
                dengan jumlah <strong><?= $distPenyakit[0]['jumlah'] ?></strong> kasus
                (<?= round($distPenyakit[0]['jumlah'] / max($totalDiagnosa, 1) * 100, 1) ?>%).
            <?php endif; ?>
        </p>
    </div>

    <!-- ============================== -->
    <!-- TANDA TANGAN -->
    <!-- ============================== -->
    <div class="ttd">
        <div class="kolom">
            <p>Mengetahui,</p>
            <p>Kepala Puskesmas</p>
            <div class="space"></div>
            <div class="garis">(...................................)</div>
        </div>
        <div class="kolom">
            <p>Kedungpring, <?= date('d F Y') ?></p>
            <p>Admin SIPAKAR-ISPA</p>
            <div class="space"></div>
            <div class="garis">(<?= htmlspecialchars($_SESSION['nama']) ?>)</div>
        </div>
    </div>

    <!-- Footer -->
    <p style="text-align:center; font-size:9pt; margin-top:40px; border-top:1px solid #ccc; padding-top:10px; color:#666;">
        Dicetak dari SIPAKAR-ISPA — Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita
        <br>
        Puskesmas Kedungpring | <?= date('Y') ?>
    </p>

</div>

<script>
    // Auto print (opsional — hilangkan komentar kalau mau langsung print)
    // window.onload = function() { window.print(); };
</script>
</body>
</html>