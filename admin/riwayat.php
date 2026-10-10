<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ==============================
// FILTER TANGGAL
// ==============================
$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

$sql = "
    SELECT h.*, u.nama AS nama_user, u.username, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN users u ON u.id_user = h.id_user
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
";
$params = [];
$where  = [];

if ($dari !== '')   { $where[] = "DATE(h.tanggal) >= ?"; $params[] = $dari; }
if ($sampai !== '') { $where[] = "DATE(h.tanggal) <= ?"; $params[] = $sampai; }

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY h.tanggal DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

// ==============================
// STATISTIK RINGKAS
// ==============================
$totalData     = count($list);
$totalAkurat   = 0;
$totalKeyakinan = 0;
foreach ($list as $r) {
    if ($r['nilai_akhir'] >= 0.5) $totalAkurat++;
    $totalKeyakinan += $r['nilai_akhir'];
}
$rataKeyakinan = $totalData > 0 ? ($totalKeyakinan / $totalData) * 100 : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Riwayat Diagnosa ISPA — SIPAKAR-ISPA</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Rekapitulasi seluruh hasil diagnosa awal penyakit ISPA pada balita di Puskesmas Kedungpring
        menggunakan algoritma Naive Bayes &amp; metode Certainty Factor.
    </p>

    <!-- ===================== FILTER TANGGAL ===================== -->
    <div class="container" style="max-width:100%;">
        <h3>Filter Berdasarkan Tanggal</h3>
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div style="flex:1; min-width:180px;">
                <label>Dari Tanggal</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            </div>
            <div style="flex:1; min-width:180px;">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            </div>
            <div>
                <button type="submit" class="btn">Terapkan Filter</button>
                <a href="riwayat.php" class="btn btn-warning">Reset</a>
            </div>
        </form>
    </div>

    <!-- ===================== STATISTIK ===================== -->
    <div class="stats-grid" style="margin-top:24px;">
        <div class="stat-card">
            <h3><?= $totalData ?></h3>
            <p>Total Diagnosa<?= ($dari || $sampai) ? ' (terfilter)' : '' ?></p>
        </div>
        <div class="stat-card">
            <h3><?= $totalAkurat ?></h3>
            <p>Keyakinan ≥ 50%</p>
        </div>
        <div class="stat-card">
            <h3><?= round($rataKeyakinan, 2) ?>%</h3>
            <p>Rata-rata Keyakinan</p>
        </div>
    </div>

    <!-- ===================== TABEL RIWAYAT ===================== -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Riwayat Diagnosa (<?= $totalData ?> data)</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama User</th>
                    <th>Username</th>
                    <th>Hasil Diagnosa ISPA</th>
                    <th>NB</th>
                    <th>CF</th>
                    <th>Nilai Akhir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; color:#888;">
                            Belum ada data riwayat diagnosa.
                        </td>
                    </tr>
                <?php else: foreach ($list as $i => $r):
                    $persen = round($r['nilai_akhir'] * 100, 2);
                    $status = $r['nilai_akhir'] >= 0.5 ? 'Tinggi' : 'Rendah';
                    $warna  = $r['nilai_akhir'] >= 0.5 ? 'btn-success' : 'btn-warning';
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($r['nama_user']) ?></td>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td><?= htmlspecialchars($r['kode_penyakit'] . ' - ' . $r['nama_penyakit']) ?></td>
                        <td><?= round($r['nilai_nb'] * 100, 2) ?>%</td>
                        <td><?= round($r['nilai_cf'] * 100, 2) ?>%</td>
                        <td><strong><?= $persen ?>%</strong></td>
                        <td>
                            <span class="btn btn-sm <?= $warna ?>" style="cursor:default;">
                                <?= $status ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ===================== INFO PERHITUNGAN ===================== -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Keterangan Perhitungan</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li><strong>NB</strong> = nilai probabilitas hasil <em>Naive Bayes</em> yang sudah dinormalisasi.</li>
            <li><strong>CF</strong> = nilai <em>Certainty Factor</em> gabungan dari rule pakar (MB &minus; MD).</li>
            <li><strong>Nilai Akhir</strong> = kombinasi keduanya, dengan bobot <strong>60% Naive Bayes</strong> + <strong>40% Certainty Factor</strong>.</li>
            <li><strong>Status "Tinggi"</strong> = nilai akhir ≥ 50% (keyakinan cukup untuk diagnosa awal).</li>
            <li><strong>Status "Rendah"</strong> = nilai akhir &lt; 50% (disarankan pemeriksaan lanjutan ke tenaga medis).</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>