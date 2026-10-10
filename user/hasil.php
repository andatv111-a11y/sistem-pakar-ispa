<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'ID hasil diagnosa tidak valid.');
    header("Location: riwayat.php"); exit;
}

$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit, p.deskripsi, p.solusi
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_hasil = ? AND h.id_user = ?
");
$stmt->execute([$id, $_SESSION['id_user']]);
$hasil = $stmt->fetch();

if (!$hasil) {
    setFlash('error', 'Data hasil diagnosa tidak ditemukan.');
    header("Location: riwayat.php"); exit;
}

$stmt = $pdo->prepare("
    SELECT g.id_gejala, g.kode_gejala, g.nama_gejala
    FROM hasil_detail hd
    JOIN gejala g ON g.id_gejala = hd.id_gejala
    WHERE hd.id_hasil = ?
    ORDER BY g.kode_gejala
");
$stmt->execute([$id]);
$gejalaDipilih = $stmt->fetchAll();
$ids = array_column($gejalaDipilih, 'id_gejala');

// Ranking penyakit lain
$rekomendasi = [];
$engineFile  = __DIR__ . '/../proses/naive_bayes.php';
if (file_exists($engineFile) && !empty($ids)) {
    require_once $engineFile;
    if (class_exists('DiagnosaISPA')) {
        try {
            $engine = new DiagnosaISPA($pdo);
            $nb = $engine->hitungNaiveBayes($ids);
            $cf = $engine->hitungCertaintyFactor($ids);
            $rekomendasi = $engine->hitungKombinasi($nb, $cf);
        } catch (Throwable $e) {
            $rekomendasi = [];
        }
    }
}

$infoPenyakit = [];
if (!empty($rekomendasi)) {
    $idsPenyakit = array_keys($rekomendasi);
    $in = implode(',', array_fill(0, count($idsPenyakit), '?'));
    $stmt = $pdo->prepare("SELECT id_penyakit, kode_penyakit, nama_penyakit FROM penyakit WHERE id_penyakit IN ($in)");
    $stmt->execute($idsPenyakit);
    foreach ($stmt->fetchAll() as $row) {
        $infoPenyakit[$row['id_penyakit']] = $row;
    }
}

$nilaiNB    = (float)($hasil['nilai_nb'] ?? 0);
$nilaiCF    = (float)($hasil['nilai_cf'] ?? 0);
$nilaiAkhir = (float)($hasil['nilai_akhir'] ?? 0);

$persen      = round($nilaiAkhir * 100, 2);
$status      = $persen >= 70 ? 'Tinggi' : ($persen >= 50 ? 'Sedang' : 'Rendah');
$warnaStatus = $persen >= 70 ? '#00b894' : ($persen >= 50 ? '#fdcb6e' : '#dc3545');
$emojiStatus = $persen >= 70 ? '🟢' : ($persen >= 50 ? '🟡' : '🔴');
$tanggal     = !empty($hasil['tanggal']) ? date('d/m/Y H:i', strtotime($hasil['tanggal'])) : '-';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.blob-container { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
.blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.3; animation: floatBlob 20s infinite ease-in-out; }
.blob-1 { width: 380px; height: 380px; background: linear-gradient(135deg, #00b894 0%, #00cec9 100%); top: -100px; left: -100px; }
.blob-2 { width: 320px; height: 320px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); bottom: -100px; right: -50px; animation-delay: -7s; }
@keyframes floatBlob { 0%, 100% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-50px) scale(1.1); } }
.content { position: relative; z-index: 1; }

.result-hero { padding: 36px; border-radius: 24px; color: #fff; margin-bottom: 24px; position: relative; overflow: hidden; animation: slideDown 0.7s; box-shadow: 0 25px 50px -15px rgba(0,0,0,0.25); }
@keyframes slideDown { from { opacity: 0; transform: translateY(-25px); } to { opacity: 1; transform: translateY(0); } }
.result-hero::before { content: ''; position: absolute; top: -50%; right: -10%; width: 350px; height: 350px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: pulse 4s infinite; }
@keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 0.4; } }
.result-hero-content { position: relative; z-index: 1; }
.result-hero h1 { font-size: 26px; font-weight: 800; margin: 0 0 8px; color: #fff; }
.result-hero .meta { font-size: 13px; opacity: 0.9; margin-bottom: 20px; }
.result-confidence { font-size: 52px; font-weight: 800; line-height: 1; letter-spacing: -2px; margin: 8px 0 4px; }
.result-status { display: inline-block; padding: 6px 16px; background: rgba(255,255,255,0.25); border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; backdrop-filter: blur(10px); }
.result-progress { margin-top: 20px; height: 12px; background: rgba(255,255,255,0.25); border-radius: 6px; overflow: hidden; }
.result-progress-fill { height: 100%; background: #fff; border-radius: 6px; transition: width 1.5s ease-out; }

.panel-r { background: #fff; border-radius: 20px; padding: 26px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); animation: fadeUp 0.7s backwards; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(25px); } to { opacity: 1; transform: translateY(0); } }
.panel-r h3 { margin: 0 0 18px; font-size: 16px; color: #1a1a2e; font-weight: 700; display: flex; align-items: center; gap: 10px; }

.calc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
.calc-box { background: #f8f9ff; padding: 18px; border-radius: 14px; text-align: center; border: 1px solid #eef0f5; transition: all 0.3s; }
.calc-box:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.calc-box .cb-icon { font-size: 28px; margin-bottom: 8px; }
.calc-box .cb-label { font-size: 11px; font-weight: 700; color: #6c7293; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.calc-box .cb-value { font-size: 24px; font-weight: 800; line-height: 1; letter-spacing: -1px; }
.calc-box .cb-desc { font-size: 11px; color: #6c7293; margin-top: 4px; }

.info-content { font-size: 14px; color: #4a4a6a; line-height: 1.8; }
.info-content p { margin: 8px 0; }

.gejala-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
.gejala-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #e7f1ff; border-radius: 10px; font-size: 13px; }
.gejala-item .gk { font-weight: 800; color: #0d6efd; }

.rank-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.rank-table thead th { background: linear-gradient(135deg, #eef1ff, #e0dcff); color: #667eea; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; }
.rank-table thead th:first-child { border-radius: 10px 0 0 10px; }
.rank-table thead th:last-child { border-radius: 0 10px 10px 0; }
.rank-table tbody tr { transition: background 0.2s; }
.rank-table tbody tr:hover { background: #f8fbff; }
.rank-table tbody td { padding: 12px 16px; border-bottom: 1px solid #f5f5fa; font-size: 13px; color: #333; }
.rank-table tbody tr:last-child td { border-bottom: none; }
.rank-1 td { background: #fff8e1; font-weight: 700; }

.warn-box { background: linear-gradient(135deg, #fff3cd, #ffe69c); padding: 20px; border-radius: 14px; border-left: 4px solid #ffc107; margin-bottom: 20px; }
.warn-box p { font-size: 13px; color: #664d03; line-height: 1.7; margin: 0; }

.action-btns { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-a { padding: 14px 26px; border-radius: 12px; border: none; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-family: inherit; }
.btn-a:hover { transform: translateY(-3px); }
.btn-qr { background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: #fff; box-shadow: 0 8px 20px -6px rgba(108, 92, 231, 0.5); }
.btn-konsul { background: linear-gradient(135deg, #0dcaf0, #6dd5ed); color: #fff; box-shadow: 0 8px 20px -6px rgba(13, 202, 240, 0.5); }
.btn-lagi { background: linear-gradient(135deg, #00b894, #00cec9); color: #fff; box-shadow: 0 8px 20px -6px rgba(0, 184, 148, 0.5); }
.btn-riwayat { background: #f0f0f5; color: #6c7293; }
.btn-dash { background: #f0f0f5; color: #6c7293; }

@media (max-width: 600px) {
    .result-confidence { font-size: 38px; }
    .result-hero { padding: 24px; }
}
</style>

<div class="blob-container">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="content">

    <!-- HERO HASIL -->
    <div class="result-hero" style="background: linear-gradient(135deg, <?= $warnaStatus ?> 0%, <?= $warnaStatus ?>dd 100%);">
        <div class="result-hero-content">
            <div class="meta">📅 Hasil Diagnosa — <?= $tanggal ?></div>
            <h1>🩺 <?= htmlspecialchars($hasil['kode_penyakit'] . ' — ' . $hasil['nama_penyakit']) ?></h1>
            <div class="result-confidence"><?= $persen ?>%</div>
            <span class="result-status"><?= $emojiStatus ?> Keyakinan <?= $status ?></span>
            <div class="result-progress">
                <div class="result-progress-fill" style="width:<?= $persen ?>%"></div>
            </div>
        </div>
    </div>

    <?php if ($m = getFlash('error')): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">❌ <?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <!-- DETAIL PERHITUNGAN -->
    <div class="panel-r">
        <h3>📊 Detail Perhitungan</h3>
        <div class="calc-grid">
            <div class="calc-box">
                <div class="cb-icon">🧮</div>
                <div class="cb-label">Naive Bayes</div>
                <div class="cb-value" style="color:#667eea;"><?= round($nilaiNB * 100, 2) ?>%</div>
                <div class="cb-desc">Probabilitas posterior</div>
            </div>
            <div class="calc-box">
                <div class="cb-icon">⭐</div>
                <div class="cb-label">Certainty Factor</div>
                <div class="cb-value" style="color:#fdcb6e;"><?= round($nilaiCF * 100, 2) ?>%</div>
                <div class="cb-desc">Tingkat keyakinan pakar</div>
            </div>
            <div class="calc-box" style="background:linear-gradient(135deg,#e6f8f3,#d4f3ea); border-color:<?= $warnaStatus ?>;">
                <div class="cb-icon">🎯</div>
                <div class="cb-label">Nilai Akhir</div>
                <div class="cb-value" style="color:<?= $warnaStatus ?>;"><?= $persen ?>%</div>
                <div class="cb-desc">60% NB + 40% CF</div>
            </div>
        </div>
    </div>

    <!-- DESKRIPSI PENYAKIT -->
    <div class="panel-r">
        <h3>📖 Deskripsi Penyakit</h3>
        <div class="info-content">
            <?= nl2br(htmlspecialchars($hasil['deskripsi'] ?? '-')) ?>
        </div>
    </div>

    <!-- SOLUSI -->
    <div class="panel-r" style="background:linear-gradient(135deg,#e6f8f3,#d4f3ea);">
        <h3>💊 Solusi / Penanganan Awal</h3>
        <div class="info-content" style="color:#0f5132;">
            <?= nl2br(htmlspecialchars($hasil['solusi'] ?? '-')) ?>
        </div>
    </div>

    <!-- GEJALA YANG DILAPORKAN -->
    <?php if (!empty($gejalaDipilih)): ?>
    <div class="panel-r">
        <h3>🩺 Gejala Klinis yang Dilaporkan (<?= count($gejalaDipilih) ?>)</h3>
        <div class="gejala-list">
            <?php foreach ($gejalaDipilih as $g): ?>
                <div class="gejala-item">
                    <span class="gk"><?= htmlspecialchars($g['kode_gejala']) ?></span>
                    <span><?= htmlspecialchars($g['nama_gejala']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- RANKING PENYAKIT LAIN -->
    <?php if (!empty($rekomendasi) && count($rekomendasi) > 1): ?>
    <div class="panel-r">
        <h3>📈 Ranking Kemungkinan Penyakit Lain</h3>
        <div style="overflow-x:auto;">
            <table class="rank-table">
                <thead>
                    <tr><th>#</th><th>Penyakit ISPA</th><th>Nilai Akhir</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($rekomendasi as $idP => $val):
                        $info = $infoPenyakit[$idP] ?? null;
                        if (!$info) continue;
                        $kelas = $no === 1 ? 'rank-1' : '';
                    ?>
                        <tr class="<?= $kelas ?>">
                            <td><strong>#<?= $no++ ?></strong></td>
                            <td><?= htmlspecialchars($info['kode_penyakit'] . ' - ' . $info['nama_penyakit']) ?></td>
                            <td><strong><?= round($val * 100, 2) ?>%</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- WARNING -->
    <div class="warn-box">
        <p>
            ⚠️ <strong>Perhatian:</strong> Hasil ini merupakan <em>diagnosa awal</em> berbasis sistem pakar.
            Bila gejala berlanjut atau memburuk, segera bawa balita ke <strong>Puskesmas Kedungpring</strong>
            untuk pemeriksaan lebih lanjut oleh tenaga medis.
        </p>
    </div>

    <!-- AKSI -->
    <div class="panel-r" style="text-align:center;">
        <h3 style="justify-content:center;">🎯 Aksi Selanjutnya</h3>
        <div class="action-btns" style="justify-content:center;">
            <a href="qr_hasil.php?id=<?= $hasil['id_hasil'] ?>" class="btn-a btn-qr">📱 QR Code Verifikasi</a>
            <a href="konsultasi.php" class="btn-a btn-konsul">💬 Konsultasi Admin</a>
            <a href="diagnosa.php" class="btn-a btn-lagi">🩺 Diagnosa Lagi</a>
            <a href="riwayat.php" class="btn-a btn-riwayat">📜 Riwayat</a>
            <a href="dashboard.php" class="btn-a btn-dash">🏠 Dashboard</a>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const bar = document.querySelector('.result-progress-fill');
        if (bar) bar.style.width = '<?= $persen ?>%';
    }, 200);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>