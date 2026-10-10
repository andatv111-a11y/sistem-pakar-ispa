<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ==============================
// FILTER TANGGAL & USER
// ==============================
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$idUser = (int)($_GET['id_user'] ?? 0);

$params = [$dari, $sampai];
$whereUser = '';

if ($idUser > 0) {
    $whereUser = " AND h.id_user = ?";
    $params[] = $idUser;
}

// Ambil daftar user untuk dropdown
$users = $pdo->query("SELECT id_user, nama, username FROM users WHERE role='user' ORDER BY nama")->fetchAll();

// Info user terpilih
$userTerpilih = null;
if ($idUser > 0) {
    $stmt = $pdo->prepare("SELECT nama, username FROM users WHERE id_user = ?");
    $stmt->execute([$idUser]);
    $userTerpilih = $stmt->fetch();
}

// ==============================
// STATISTIK UMUM
// ==============================
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM hasil_diagnosa h
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser
");
$stmt->execute($params);
$totalDiagnosa = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT AVG(h.nilai_akhir) FROM hasil_diagnosa h
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser
");
$stmt->execute($params);
$rataKeyakinan = (float) $stmt->fetchColumn();

// User aktif
if ($idUser > 0) {
    $totalUser = 1;
} else {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT h.id_user) FROM hasil_diagnosa h
        WHERE DATE(h.tanggal) BETWEEN ? AND ?
    ");
    $stmt->execute([$dari, $sampai]);
    $totalUser = (int) $stmt->fetchColumn();
}

// ==============================
// DISTRIBUSI PENYAKIT
// ==============================
$stmt = $pdo->prepare("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser
    GROUP BY p.id_penyakit
    ORDER BY jumlah DESC
");
$stmt->execute($params);
$distPenyakit = $stmt->fetchAll();

// ==============================
// DISTRIBUSI STATUS
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN h.nilai_akhir >= 0.7 THEN 1 ELSE 0 END) AS tinggi,
        SUM(CASE WHEN h.nilai_akhir >= 0.5 AND h.nilai_akhir < 0.7 THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN h.nilai_akhir < 0.5 THEN 1 ELSE 0 END) AS rendah
    FROM hasil_diagnosa h
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser
");
$stmt->execute($params);
$distStatus = $stmt->fetch();

// ==============================
// AKTIVITAS HARIAN
// ==============================
$stmt = $pdo->prepare("
    SELECT DATE(h.tanggal) AS tgl, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser
    GROUP BY DATE(h.tanggal) ORDER BY tgl
");
$stmt->execute($params);
$perHari = $stmt->fetchAll();

// ==============================
// DISTRIBUSI USIA BALITA
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN h.usia < 12 THEN 1 ELSE 0 END) AS bayi,
        SUM(CASE WHEN h.usia BETWEEN 12 AND 24 THEN 1 ELSE 0 END) AS batita,
        SUM(CASE WHEN h.usia BETWEEN 25 AND 60 THEN 1 ELSE 0 END) AS balita
    FROM hasil_diagnosa h
    WHERE DATE(h.tanggal) BETWEEN ? AND ? $whereUser AND h.usia IS NOT NULL
");
$stmt->execute($params);
$distUsia = $stmt->fetch();

// ==============================
// EXPORT CSV
// ==============================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $namaFile = 'laporan_ispa_' . $dari . '_sd_' . $sampai . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['LAPORAN DIAGNOSA ISPA — SIPAKAR-ISPA']);
    fputcsv($out, ['Puskesmas Kedungpring']);
    fputcsv($out, ['Periode', $dari, 's/d', $sampai]);
    if ($userTerpilih) {
        fputcsv($out, ['Filter User', $userTerpilih['nama'] . ' (@' . $userTerpilih['username'] . ')']);
    }
    fputcsv($out, []);
    fputcsv($out, ['RINGKASAN']);
    fputcsv($out, ['Total Diagnosa', $totalDiagnosa]);
    fputcsv($out, ['Rata-rata Keyakinan', round($rataKeyakinan * 100, 2) . '%']);
    fputcsv($out, ['User Aktif', $totalUser]);
    fputcsv($out, []);
    fputcsv($out, ['DISTRIBUSI PENYAKIT']);
    fputcsv($out, ['Kode', 'Nama Penyakit', 'Jumlah']);
    foreach ($distPenyakit as $p) {
        fputcsv($out, [$p['kode_penyakit'], $p['nama_penyakit'], $p['jumlah']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['DISTRIBUSI STATUS']);
    fputcsv($out, ['Kategori', 'Jumlah']);
    fputcsv($out, ['Tinggi (>=70%)', (int)($distStatus['tinggi'] ?? 0)]);
    fputcsv($out, ['Sedang (50-69%)', (int)($distStatus['sedang'] ?? 0)]);
    fputcsv($out, ['Rendah (<50%)', (int)($distStatus['rendah'] ?? 0)]);
    fclose($out);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>📊 Laporan &amp; Statistik</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Laporan diagnosa ISPA pada balita — Puskesmas Kedungpring
        <?php if ($userTerpilih): ?>
            <br>Filter: <strong><?= htmlspecialchars($userTerpilih['nama']) ?></strong>
            (@<?= htmlspecialchars($userTerpilih['username']) ?>)
        <?php endif; ?>
    </p>

    <!-- ===================== FILTER ===================== -->
    <div class="container" style="max-width:100%;">
        <h3>🔍 Filter Periode &amp; User</h3>
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">

            <div style="flex:1; min-width:150px;">
                <label>Dari Tanggal</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            </div>

            <div style="flex:1; min-width:150px;">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            </div>

            <div style="flex:1; min-width:220px;">
                <label>Filter User</label>
                <select name="id_user">
                    <option value="0">-- Semua User --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id_user'] ?>" <?= ($idUser == $u['id_user']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nama']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="btn">🔍 Terapkan</button>
                <a href="laporan.php" class="btn btn-warning">🔄 Reset</a>
            </div>
        </form>

        <!-- Tombol Cetak -->
        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <a href="cetak_laporan.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&id_user=<?= $idUser ?>"
               target="_blank"
               class="btn btn-success"
               style="font-weight:bold;">
                🖨️ Cetak Laporan (PDF)
            </a>
            <a href="?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&id_user=<?= $idUser ?>&export=csv"
               class="btn"
               style="background:#198754; color:#fff;">
                📥 Export Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn" style="background:#6c757d;">
                🖨️ Print Halaman
            </button>
        </div>
    </div>

    <!-- ===================== STATISTIK ===================== -->
    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card">
            <h3><?= $totalDiagnosa ?></h3>
            <p>Total Diagnosa</p>
        </div>
        <div class="stat-card" style="border-left-color:#198754;">
            <h3 style="color:#198754;"><?= round($rataKeyakinan * 100, 2) ?>%</h3>
            <p>Rata-rata Keyakinan</p>
        </div>
        <div class="stat-card" style="border-left-color:#ffc107;">
            <h3 style="color:#ffc107;"><?= $totalUser ?></h3>
            <p>User Aktif</p>
        </div>
    </div>

    <!-- ===================== DISTRIBUSI PENYAKIT ===================== -->
    <?php if (!empty($distPenyakit)): ?>
        <?php $maxPenyakit = max(array_column($distPenyakit, 'jumlah')) ?: 1; ?>
        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>🦠 Distribusi Penyakit ISPA</h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Penyakit</th><th>Jumlah</th><th>Persentase</th><th>Visual</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($distPenyakit as $p):
                        $persen = $totalDiagnosa > 0 ? round($p['jumlah'] / $totalDiagnosa * 100, 1) : 0;
                        $bar    = $p['jumlah'] / $maxPenyakit * 100;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($p['kode_penyakit'].' - '.$p['nama_penyakit']) ?></td>
                            <td><strong><?= $p['jumlah'] ?></strong></td>
                            <td><?= $persen ?>%</td>
                            <td>
                                <div class="progress-bar" style="margin:0; height:10px;">
                                    <div class="progress-fill" style="width:<?= $bar ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ===================== DISTRIBUSI STATUS ===================== -->
    <?php
    $t = (int)($distStatus['tinggi'] ?? 0);
    $s = (int)($distStatus['sedang'] ?? 0);
    $r = (int)($distStatus['rendah'] ?? 0);
    $tot = max($t + $s + $r, 1);
    ?>
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>🎯 Distribusi Tingkat Keyakinan</h3>
        <table>
            <thead><tr><th>Kategori</th><th>Jumlah</th><th>Persentase</th></tr></thead>
            <tbody>
                <tr><td>🟢 Tinggi (≥70%)</td><td><?= $t ?></td><td><?= round($t/$tot*100,1) ?>%</td></tr>
                <tr><td>🟡 Sedang (50–69%)</td><td><?= $s ?></td><td><?= round($s/$tot*100,1) ?>%</td></tr>
                <tr><td>🔴 Rendah (&lt;50%)</td><td><?= $r ?></td><td><?= round($r/$tot*100,1) ?>%</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ===================== DISTRIBUSI USIA ===================== -->
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

    <!-- ===================== PER HARI ===================== -->
    <?php if (!empty($perHari)): ?>
        <?php $maxHari = max(array_column($perHari, 'jumlah')) ?: 1; ?>
        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>📅 Aktivitas Diagnosa Harian</h3>
            <table>
                <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Visual</th></tr></thead>
                <tbody>
                    <?php foreach ($perHari as $h):
                        $bar = $h['jumlah'] / $maxHari * 100;
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                            <td><strong><?= $h['jumlah'] ?></strong></td>
                            <td>
                                <div class="progress-bar" style="margin:0; height:10px;">
                                    <div class="progress-fill" style="width:<?= $bar ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ===================== RINGKASAN ===================== -->
    <div class="container" style="max-width:100%; margin-top:20px; background:#e7f1ff;">
        <h3>📝 Ringkasan Laporan</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li>Periode: <strong><?= date('d/m/Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($sampai)) ?></strong></li>
            <?php if ($userTerpilih): ?>
                <li>Filter User: <strong><?= htmlspecialchars($userTerpilih['nama']) ?></strong> (@<?= htmlspecialchars($userTerpilih['username']) ?>)</li>
            <?php else: ?>
                <li>Filter User: <strong>Semua User</strong></li>
            <?php endif; ?>
            <li>Total diagnosa: <strong><?= $totalDiagnosa ?></strong> kali</li>
            <li>Rata-rata keyakinan: <strong><?= round($rataKeyakinan * 100, 2) ?>%</strong></li>
            <li>Penyakit terbanyak: <strong><?= !empty($distPenyakit) ? htmlspecialchars($distPenyakit[0]['nama_penyakit']) : '-' ?></strong></li>
        </ul>
    </div>
</div>

<!-- Print CSS -->
<style media="print">
    .navbar, .sidebar, .footer, .btn, button, form { display: none !important; }
    .main-content { display: block; }
    .content { padding: 0; }
    .container, .stat-card { box-shadow: none; border: 1px solid #ccc; page-break-inside: avoid; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>