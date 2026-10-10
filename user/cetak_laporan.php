<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$params = [$idUser, $dari, $sampai];

// Info User
$stmt = $pdo->prepare("SELECT nama, username, no_telepon, alamat FROM users WHERE id_user = ?");
$stmt->execute([$idUser]);
$userInfo = $stmt->fetch();

// Info Puskesmas
$info = $pdo->query("SELECT * FROM info_puskesmas LIMIT 1")->fetch();
if (!$info) {
    $info = ['nama' => 'Puskesmas Kedungpring', 'alamat' => 'Jl. Raya Kedungpring, Lamongan', 'telepon' => '(0322) 123456'];
}

// Data
$stmt = $pdo->prepare("SELECT COUNT(*), AVG(nilai_akhir) FROM hasil_diagnosa WHERE id_user = ? AND DATE(tanggal) BETWEEN ? AND ?");
$stmt->execute($params);
$stat = $stmt->fetch(PDO::FETCH_NUM);
$totalDiagnosa = (int)($stat[0] ?? 0);
$rataKeyakinan = (float)($stat[1] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ? AND DATE(h.tanggal) BETWEEN ? AND ?
    GROUP BY p.id_penyakit ORDER BY jumlah DESC
");
$stmt->execute($params);
$distPenyakit = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ? AND DATE(h.tanggal) BETWEEN ? AND ?
    ORDER BY h.tanggal DESC
");
$stmt->execute($params);
$detail = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan — <?= htmlspecialchars($userInfo['nama'] ?? 'User') ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', Times, serif; background: #f0f4f8; padding: 20px; color: #000; font-size: 12pt; line-height: 1.5; }
    .page { background: #fff; max-width: 800px; margin: 0 auto; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,.1); }

    .kop { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .kop .logo { width: 80px; height: 80px; border: 2px solid #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; flex-shrink: 0; }
    .kop .info { flex: 1; text-align: center; }
    .kop .info h2 { font-size: 14pt; text-transform: uppercase; }
    .kop .info h1 { font-size: 16pt; text-transform: uppercase; font-weight: bold; }
    .kop .info p { font-size: 10pt; margin-top: 3px; }

    .judul { text-align: center; margin: 20px 0; }
    .judul h3 { font-size: 13pt; text-transform: uppercase; text-decoration: underline; }
    .judul p { font-size: 11pt; margin-top: 5px; }

    .info-user { background: #f0f4f8; padding: 14px; border-radius: 6px; margin-bottom: 20px; }
    .info-user table { width: 100%; border-collapse: collapse; }
    .info-user td { padding: 5px 10px; font-size: 11pt; }
    .info-user td:first-child { width: 140px; font-weight: bold; }

    .section { margin: 20px 0; }
    .section h4 { font-size: 12pt; background: #0d6efd; color: #fff; padding: 8px 12px; margin-bottom: 10px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table th, table td { border: 1px solid #333; padding: 6px 10px; font-size: 11pt; }
    table th { background: #e7f1ff; font-weight: bold; text-align: center; }
    table td.angka { text-align: center; }

    .info-box { display: flex; gap: 20px; margin: 20px 0; }
    .info-box .box { flex: 1; border: 1px solid #333; padding: 14px; text-align: center; }
    .info-box .box .nilai { font-size: 20pt; font-weight: bold; color: #0d6efd; }
    .info-box .box .label { font-size: 10pt; margin-top: 4px; }

    .ttd { margin-top: 40px; display: flex; justify-content: space-between; }
    .ttd .kolom { text-align: center; width: 220px; }
    .ttd .kolom .space { height: 70px; }
    .ttd .kolom .garis { border-top: 1px solid #000; padding-top: 4px; font-weight: bold; }

    .footer-print { text-align: center; font-size: 9pt; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 10px; color: #666; }

    .toolbar { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 999; }
    .toolbar button, .toolbar a { padding: 12px 22px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: bold; text-decoration: none; color: #fff; font-family: inherit; }
    .toolbar .print { background: #198754; }
    .toolbar .back { background: #6c757d; }

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

<!-- Toolbar -->
<div class="toolbar">
    <button class="print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a class="back" href="laporan.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>">⬅️ Kembali</a>
</div>

<div class="page">

    <!-- KOP -->
    <div class="kop">
        <div class="logo">🩺</div>
        <div class="info">
            <h2>Pemerintah Kabupaten Lamongan</h2>
            <h2>Dinas Kesehatan</h2>
            <h1><?= htmlspecialchars($info['nama'] ?? 'Puskesmas Kedungpring') ?></h1>
            <p><?= htmlspecialchars($info['alamat'] ?? '') ?></p>
            <p>Telp: <?= htmlspecialchars($info['telepon'] ?? '-') ?></p>
        </div>
    </div>

    <!-- JUDUL -->
    <div class="judul">
        <h3>Laporan Diagnosa Awal Penyakit ISPA pada Balita</h3>
        <p>SIPAKAR-ISPA — Sistem Pakar Diagnosa ISPA Berbasis Web</p>
        <p>Periode: <strong><?= date('d F Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d F Y', strtotime($sampai)) ?></strong></p>
    </div>

    <!-- INFO USER -->
    <div class="info-user">
        <table>
            <tr><td>Nama User</td><td>: <strong><?= htmlspecialchars($userInfo['nama'] ?? '-') ?></strong></td></tr>
            <tr><td>Username</td><td>: <?= htmlspecialchars($userInfo['username'] ?? '-') ?></td></tr>
            <?php if (!empty($userInfo['no_telepon'])): ?>
                <tr><td>Telepon</td><td>: <?= htmlspecialchars($userInfo['no_telepon']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($userInfo['alamat'])): ?>
                <tr><td>Alamat</td><td>: <?= htmlspecialchars($userInfo['alamat']) ?></td></tr>
            <?php endif; ?>
            <tr><td>Tanggal Cetak</td><td>: <?= date('d F Y H:i') ?> WIB</td></tr>
        </table>
    </div>

    <!-- RINGKASAN -->
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
        </div>
    </div>

    <!-- DISTRIBUSI PENYAKIT -->
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

    <!-- DETAIL RIWAYAT -->
    <?php if (!empty($detail)): ?>
    <div class="section">
        <h4>III. Detail Riwayat Diagnosa</h4>
        <table>
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th width="100">Tanggal</th>
                    <th>Nama Balita</th>
                    <th width="60">Usia</th>
                    <th>Hasil Diagnosa</th>
                    <th width="70">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($detail as $d): ?>
                    <tr>
                        <td class="angka"><?= $no++ ?></td>
                        <td class="angka"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($d['nama_balita'] ?? '-') ?></td>
                        <td class="angka"><?= $d['usia'] ? $d['usia'].' bln' : '-' ?></td>
                        <td><?= htmlspecialchars($d['kode_penyakit'] . ' - ' . $d['nama_penyakit']) ?></td>
                        <td class="angka"><?= round($d['nilai_akhir']*100, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- KESIMPULAN -->
    <div class="section">
        <h4>IV. Kesimpulan</h4>
        <p style="text-align:justify; font-size:11pt;">
            Berdasarkan data diagnosa ISPA periode <strong><?= date('d F Y', strtotime($dari)) ?></strong>
            s/d <strong><?= date('d F Y', strtotime($sampai)) ?></strong>, user
            <strong><?= htmlspecialchars($userInfo['nama'] ?? '-') ?></strong> tercatat melakukan
            <strong><?= $totalDiagnosa ?></strong> diagnosa dengan rata-rata tingkat keyakinan
            <strong><?= round($rataKeyakinan * 100, 2) ?>%</strong>.
            <?php if (!empty($distPenyakit)): ?>
                Penyakit yang paling sering terdiagnosa adalah
                <strong><?= htmlspecialchars($distPenyakit[0]['nama_penyakit']) ?></strong>
                sebanyak <strong><?= $distPenyakit[0]['jumlah'] ?></strong> kali.
            <?php endif; ?>
        </p>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd">
        <div class="kolom">
            <p>Mengetahui,</p>
            <p>Kepala Puskesmas</p>
            <div class="space"></div>
            <div class="garis">(...................................)</div>
        </div>
        <div class="kolom">
            <p>Kedungpring, <?= date('d F Y') ?></p>
            <p>User SIPAKAR-ISPA</p>
            <div class="space"></div>
            <div class="garis">(<?= htmlspecialchars($userInfo['nama'] ?? '........................') ?>)</div>
        </div>
    </div>

    <div class="footer-print">
        Dicetak dari SIPAKAR-ISPA — Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita<br>
        Puskesmas Kedungpring | <?= date('Y') ?>
    </div>

</div>

<script>
    // Auto-print (opsional — buka komentar kalau mau langsung print)
    // window.onload = function() { window.print(); };
</script>

</body>
</html>