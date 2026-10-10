<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

// ==============================
// STATISTIK UMUM
// ==============================
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total,
           AVG(nilai_akhir) AS avg_conf,
           MAX(nilai_akhir) AS max_conf,
           MIN(nilai_akhir) AS min_conf
    FROM hasil_diagnosa WHERE id_user = ?
");
$stmt->execute([$idUser]);
$s = $stmt->fetch();
$totalDiagnosa   = (int)($s['total'] ?? 0);
$rataKeyakinan   = (float)($s['avg_conf'] ?? 0);
$keyakinanMax    = (float)($s['max_conf'] ?? 0);
$keyakinanMin    = (float)($s['min_conf'] ?? 0);

// ==============================
// DIAGNOSA TERAKHIR
// ==============================
$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ?
    ORDER BY h.tanggal DESC LIMIT 1
");
$stmt->execute([$idUser]);
$last = $stmt->fetch();

// ==============================
// DIAGNOSA PER BULAN (12 BULAN TERAKHIR)
// ==============================
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
           COUNT(*) AS jumlah,
           AVG(nilai_akhir) AS avg_nilai
    FROM hasil_diagnosa
    WHERE id_user = ?
      AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY bulan
    ORDER BY bulan ASC
");
$stmt->execute([$idUser]);
$rawBulan = $stmt->fetchAll();

// Siapkan 12 bulan terakhir (isi 0 kalau kosong)
$bulanLabels = [];
$bulanJumlah = [];
$bulanAvg    = [];
$mapBulan = [];
foreach ($rawBulan as $r) {
    $mapBulan[$r['bulan']] = $r;
}
for ($i = 11; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $bulanLabels[] = date('M Y', strtotime($key . '-01'));
    $bulanJumlah[] = isset($mapBulan[$key]) ? (int)$mapBulan[$key]['jumlah'] : 0;
    $bulanAvg[]    = isset($mapBulan[$key]) ? round((float)$mapBulan[$key]['avg_nilai'] * 100, 2) : 0;
}

// ==============================
// DISTRIBUSI PENYAKIT
// ==============================
$stmt = $pdo->prepare("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(*) AS jumlah
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ?
    GROUP BY p.id_penyakit
    ORDER BY jumlah DESC
");
$stmt->execute([$idUser]);
$distPenyakit = $stmt->fetchAll();

$penyakitLabels = [];
$penyakitData   = [];
foreach ($distPenyakit as $d) {
    $penyakitLabels[] = $d['kode_penyakit'] . ' - ' . $d['nama_penyakit'];
    $penyakitData[]   = (int)$d['jumlah'];
}

// ==============================
// DISTRIBUSI STATUS KEYAKINAN
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN nilai_akhir >= 0.7 THEN 1 ELSE 0 END) AS tinggi,
        SUM(CASE WHEN nilai_akhir >= 0.5 AND nilai_akhir < 0.7 THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN nilai_akhir < 0.5 THEN 1 ELSE 0 END) AS rendah
    FROM hasil_diagnosa WHERE id_user = ?
");
$stmt->execute([$idUser]);
$distStatus = $stmt->fetch();
$statusTinggi = (int)($distStatus['tinggi'] ?? 0);
$statusSedang = (int)($distStatus['sedang'] ?? 0);
$statusRendah = (int)($distStatus['rendah'] ?? 0);

// ==============================
// DISTRIBUSI USIA BALITA
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN usia < 12 THEN 1 ELSE 0 END) AS bayi,
        SUM(CASE WHEN usia BETWEEN 12 AND 24 THEN 1 ELSE 0 END) AS batita,
        SUM(CASE WHEN usia BETWEEN 25 AND 36 THEN 1 ELSE 0 END) AS balita_kecil,
        SUM(CASE WHEN usia BETWEEN 37 AND 60 THEN 1 ELSE 0 END) AS balita
    FROM hasil_diagnosa
    WHERE id_user = ? AND usia IS NOT NULL
");
$stmt->execute([$idUser]);
$distUsia = $stmt->fetch();

// ==============================
// JENIS KELAMIN
// ==============================
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN jenis_kelamin='L' THEN 1 ELSE 0 END) AS laki,
        SUM(CASE WHEN jenis_kelamin='P' THEN 1 ELSE 0 END) AS perempuan
    FROM hasil_diagnosa
    WHERE id_user = ? AND jenis_kelamin IS NOT NULL
");
$stmt->execute([$idUser]);
$distJK = $stmt->fetch();

// ==============================
// 5 RIWAYAT TERBARU
// ==============================
$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ?
    ORDER BY h.tanggal DESC LIMIT 5
");
$stmt->execute([$idUser]);
$recent = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>📊 Statistik Diagnosa Saya</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Ringkasan &amp; visualisasi aktivitas diagnosa Anda di SIPAKAR-ISPA
    </p>

    <!-- ============================== -->
    <!-- KARTU STATISTIK UTAMA -->
    <!-- ============================== -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $totalDiagnosa ?></h3>
            <p>Total Diagnosa</p>
        </div>
        <div class="stat-card" style="border-left-color:#198754;">
            <h3 style="color:#198754;"><?= round($rataKeyakinan * 100, 2) ?>%</h3>
            <p>Rata-rata Keyakinan</p>
        </div>
        <div class="stat-card" style="border-left-color:#0d6efd;">
            <h3 style="color:#0d6efd;"><?= round($keyakinanMax * 100, 2) ?>%</h3>
            <p>Keyakinan Tertinggi</p>
        </div>
        <div class="stat-card" style="border-left-color:#dc3545;">
            <h3 style="color:#dc3545;"><?= round($keyakinanMin * 100, 2) ?>%</h3>
            <p>Keyakinan Terendah</p>
        </div>
    </div>

    <?php if ($totalDiagnosa === 0): ?>
        <div class="container" style="max-width:100%; margin-top:24px; text-align:center; padding:40px;">
            <div style="font-size:64px;">📊</div>
            <h3>Belum Ada Data Diagnosa</h3>
            <p style="color:#666; margin-top:8px;">
                Lakukan diagnosa pertama Anda untuk melihat statistik &amp; grafik.
            </p>
            <a href="diagnosa.php" class="btn btn-success" style="margin-top:16px;">
                🩺 Mulai Diagnosa Sekarang
            </a>
        </div>
    <?php else: ?>

        <!-- ============================== -->
        <!-- GRAFIK 1: DIAGNOSA PER BULAN (Bar + Line) -->
        <!-- ============================== -->
        <div class="container" style="max-width:100%; margin-top:24px;">
            <h3>📅 Aktivitas Diagnosa 12 Bulan Terakhir</h3>
            <div style="height:320px; position:relative;">
                <canvas id="chartBulanan"></canvas>
            </div>
        </div>

        <!-- ============================== -->
        <!-- GRID 2: PIE PENYAKIT + DOUGHNUT STATUS -->
        <!-- ============================== -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:20px; margin-top:24px;">

            <div class="container" style="max-width:100%;">
                <h3>🥧 Distribusi Penyakit ISPA</h3>
                <div style="height:280px; position:relative;">
                    <canvas id="chartPenyakit"></canvas>
                </div>
            </div>

            <div class="container" style="max-width:100%;">
                <h3>🎯 Distribusi Tingkat Keyakinan</h3>
                <div style="height:280px; position:relative;">
                    <canvas id="chartStatus"></canvas>
                </div>
            </div>

        </div>

        <!-- ============================== -->
        <!-- GRAFIK 4: DISTRIBUSI USIA & JENIS KELAMIN -->
        <!-- ============================== -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:20px; margin-top:24px;">

            <div class="container" style="max-width:100%;">
                <h3>👶 Distribusi Usia Balita</h3>
                <div style="height:280px; position:relative;">
                    <canvas id="chartUsia"></canvas>
                </div>
            </div>

            <div class="container" style="max-width:100%;">
                <h3>👥 Distribusi Jenis Kelamin</h3>
                <div style="height:280px; position:relative;">
                    <canvas id="chartJK"></canvas>
                </div>
            </div>

        </div>

        <!-- ============================== -->
        <!-- GRAFIK 6: TREN KEYAKINAN -->
        <!-- ============================== -->
        <div class="container" style="max-width:100%; margin-top:24px;">
            <h3>📈 Tren Rata-Rata Keyakinan per Bulan</h3>
            <div style="height:300px; position:relative;">
                <canvas id="chartTren"></canvas>
            </div>
        </div>

        <!-- ============================== -->
        <!-- TABEL DISTRIBUSI PENYAKIT -->
        <!-- ============================== -->
        <?php if (!empty($distPenyakit)): ?>
        <div class="container" style="max-width:100%; margin-top:24px;">
            <h3>🦠 Tabel Distribusi Penyakit</h3>
            <table>
                <thead>
                    <tr><th>#</th><th>Penyakit</th><th>Jumlah</th><th>Persentase</th><th>Visual</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; $maxP = max($penyakitData ?: [1]);
                    foreach ($distPenyakit as $d):
                        $persen = $totalDiagnosa > 0 ? round($d['jumlah']/$totalDiagnosa*100, 1) : 0;
                        $bar = $d['jumlah']/$maxP*100;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($d['kode_penyakit'].' - '.$d['nama_penyakit']) ?></td>
                            <td><strong><?= $d['jumlah'] ?></strong></td>
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

        <!-- ============================== -->
        <!-- INSIGHT / KESIMPULAN OTOMATIS -->
        <!-- ============================== -->
        <div class="container" style="max-width:100%; margin-top:24px; background:#e7f1ff;">
            <h3>💡 Insight &amp; Kesimpulan</h3>
            <ul style="line-height:2; font-size:14px; padding-left:20px;">
                <li>
                    Anda telah melakukan <strong><?= $totalDiagnosa ?>× diagnosa</strong>.
                </li>
                <?php if (!empty($distPenyakit)): ?>
                <li>
                    Penyakit yang paling sering terdiagnosa:
                    <strong><?= htmlspecialchars($distPenyakit[0]['nama_penyakit']) ?>
                    (<?= $distPenyakit[0]['jumlah'] ?>×)</strong>.
                </li>
                <?php endif; ?>
                <li>
                    Rata-rata tingkat keyakinan sistem:
                    <strong><?= round($rataKeyakinan*100, 2) ?>%</strong>
                    <?php if ($rataKeyakinan >= 0.7): ?>
                        (Sangat Baik ✅)
                    <?php elseif ($rataKeyakinan >= 0.5): ?>
                        (Cukup Baik ⚠️)
                    <?php else: ?>
                        (Perlu Perhatian 🚨)
                    <?php endif; ?>
                </li>
                <?php if ($statusRendah > 0): ?>
                <li>
                    Ada <strong><?= $statusRendah ?> diagnosa</strong> dengan keyakinan rendah (&lt;50%).
                    Disarankan konsultasi lanjutan ke Puskesmas.
                </li>
                <?php endif; ?>
                <?php if (!empty($distUsia) && ($distUsia['bayi'] + $distUsia['batita'] + $distUsia['balita_kecil'] + $distUsia['balita']) > 0): ?>
                <li>
                    Kelompok usia terbanyak yang didiagnosa:
                    <strong>
                    <?php
                    $usiaArr = [
                        'Bayi (<12 bulan)' => (int)($distUsia['bayi'] ?? 0),
                        'Batita (12–24 bulan)' => (int)($distUsia['batita'] ?? 0),
                        'Balita kecil (25–36 bulan)' => (int)($distUsia['balita_kecil'] ?? 0),
                        'Balita (37–60 bulan)' => (int)($distUsia['balita'] ?? 0),
                    ];
                    arsort($usiaArr);
                    echo array_key_first($usiaArr);
                    ?>
                    </strong>.
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- ============================== -->
        <!-- 5 RIWAYAT TERBARU -->
        <!-- ============================== -->
        <?php if (!empty($recent)): ?>
        <div class="container" style="max-width:100%; margin-top:24px;">
            <h3>📋 5 Riwayat Diagnosa Terbaru</h3>
            <table>
                <thead>
                    <tr><th>Tanggal</th><th>Penyakit</th><th>Nilai Akhir</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($r['kode_penyakit'].' - '.$r['nama_penyakit']) ?></td>
                            <td><strong><?= round($r['nilai_akhir']*100, 2) ?>%</strong></td>
                            <td>
                                <a href="hasil.php?id=<?= $r['id_hasil'] ?>" class="btn btn-sm">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:12px;">
                <a href="riwayat.php" class="btn btn-warning">📜 Lihat Semua Riwayat</a>
            </p>
        </div>
        <?php endif; ?>

        <!-- ============================== -->
        <!-- AKSI -->
        <!-- ============================== -->
        <div class="container" style="max-width:100%; margin-top:24px; text-align:center;">
            <a href="diagnosa.php" class="btn btn-success" style="font-size:15px; padding:12px 24px;">
                🩺 Diagnosa Lagi
            </a>
            <a href="dashboard.php" class="btn btn-warning" style="font-size:15px; padding:12px 24px;">
                🏠 Dashboard
            </a>
        </div>

    <?php endif; ?>
</div>

<!-- ============================== -->
<!-- CHART.JS & SCRIPT -->
<!-- ============================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($totalDiagnosa > 0): ?>

    // ============================
    // 1. BAR CHART — DIAGNOSA PER BULAN
    // ============================
    const ctxBulan = document.getElementById('chartBulanan').getContext('2d');
    new Chart(ctxBulan, {
        type: 'bar',
        data: {
            labels: <?= json_encode($bulanLabels) ?>,
            datasets: [{
                label: 'Jumlah Diagnosa',
                data: <?= json_encode($bulanJumlah) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Diagnosa: ' + ctx.parsed.y
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // ============================
    // 2. PIE CHART — DISTRIBUSI PENYAKIT
    // ============================
    const ctxPenyakit = document.getElementById('chartPenyakit').getContext('2d');
    new Chart(ctxPenyakit, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($penyakitLabels) ?>,
            datasets: [{
                data: <?= json_encode($penyakitData) ?>,
                backgroundColor: [
                    '#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d',
                    '#0dcaf0', '#fd7e14', '#6f42c1', '#20c997', '#d63384'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            }
        }
    });

    // ============================
    // 3. DOUGHNUT — STATUS KEYAKINAN
    // ============================
    const ctxStatus = document.getElementById('chartStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Tinggi (≥70%)', 'Sedang (50–69%)', 'Rendah (<50%)'],
            datasets: [{
                data: [<?= $statusTinggi ?>, <?= $statusSedang ?>, <?= $statusRendah ?>],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 12 } }
                }
            }
        }
    });

    // ============================
    // 4. BAR — DISTRIBUSI USIA
    // ============================
    const ctxUsia = document.getElementById('chartUsia').getContext('2d');
    new Chart(ctxUsia, {
        type: 'bar',
        data: {
            labels: ['Bayi (<12bl)', 'Batita (12–24bl)', 'Balita kecil (25–36bl)', 'Balita (37–60bl)'],
            datasets: [{
                label: 'Jumlah',
                data: [
                    <?= (int)($distUsia['bayi'] ?? 0) ?>,
                    <?= (int)($distUsia['batita'] ?? 0) ?>,
                    <?= (int)($distUsia['balita_kecil'] ?? 0) ?>,
                    <?= (int)($distUsia['balita'] ?? 0) ?>
                ],
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // ============================
    // 5. PIE — JENIS KELAMIN
    // ============================
    const ctxJK = document.getElementById('chartJK').getContext('2d');
    new Chart(ctxJK, {
        type: 'pie',
        data: {
            labels: ['Laki-laki', 'Perempuan'],
            datasets: [{
                data: [
                    <?= (int)($distJK['laki'] ?? 0) ?>,
                    <?= (int)($distJK['perempuan'] ?? 0) ?>
                ],
                backgroundColor: ['#0d6efd', '#d63384'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 12 } }
                }
            }
        }
    });

    // ============================
    // 6. LINE — TREN KEYAKINAN
    // ============================
    const ctxTren = document.getElementById('chartTren').getContext('2d');
    new Chart(ctxTren, {
        type: 'line',
        data: {
            labels: <?= json_encode($bulanLabels) ?>,
            datasets: [{
                label: 'Rata-rata Keyakinan (%)',
                data: <?= json_encode($bulanAvg) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.15)',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#198754',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'Keyakinan: ' + ctx.parsed.y + '%'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: v => v + '%' }
                }
            }
        }
    });

    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>