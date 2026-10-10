<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

// ==============================
// FILTER TANGGAL
// ==============================
$dari   = $_GET['dari']   ?? '';
$sampai = $_GET['sampai'] ?? '';

$sql = "
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ?
";
$params = [$idUser];

if ($dari !== '')   { $sql .= " AND DATE(h.tanggal) >= ?"; $params[] = $dari; }
if ($sampai !== '') { $sql .= " AND DATE(h.tanggal) <= ?"; $params[] = $sampai; }

$sql .= " ORDER BY h.tanggal DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

// ==============================
// STATISTIK
// ==============================
$total = count($list);
$totalAkurat = 0;
$sumKeyakinan = 0;
foreach ($list as $r) {
    if ($r['nilai_akhir'] >= 0.5) $totalAkurat++;
    $sumKeyakinan += $r['nilai_akhir'];
}
$rataKeyakinan = $total > 0 ? round(($sumKeyakinan / $total) * 100, 2) : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
/* ============================================================
   USER RIWAYAT — MODERN DESIGN
   ============================================================ */
.blob-container { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
.blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.3; animation: floatBlob 20s infinite ease-in-out; }
.blob-1 { width: 380px; height: 380px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); top: -100px; left: -100px; }
.blob-2 { width: 320px; height: 320px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); bottom: -100px; right: -50px; animation-delay: -7s; }
@keyframes floatBlob { 0%, 100% { transform: translate(0,0) scale(1); } 50% { transform: translate(40px,-50px) scale(1.1); } }
.content { position: relative; z-index: 1; }

/* HERO */
.page-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px 36px; border-radius: 24px; color: #fff; margin-bottom: 24px; position: relative; overflow: hidden; box-shadow: 0 25px 50px -15px rgba(102, 126, 234, 0.5); animation: slideDown 0.6s; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-25px); } to { opacity: 1; transform: translateY(0); } }
.page-hero::before { content: ''; position: absolute; top: -50%; right: -10%; width: 320px; height: 320px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: pulse 4s infinite; }
@keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 0.4; } }
.page-hero h1 { font-size: 26px; font-weight: 700; margin: 0 0 8px; color: #fff; }
.page-hero p { font-size: 14px; opacity: 0.9; margin: 0; }
.hero-icon-big { width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 38px; backdrop-filter: blur(10px); animation: bob 3s infinite; }
@keyframes bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

@keyframes fadeUp { from { opacity: 0; transform: translateY(25px); } to { opacity: 1; transform: translateY(0); } }

/* FILTER PANEL */
.filter-panel { background: #fff; border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); animation: fadeUp 0.7s backwards; }
.filter-panel h3 { margin: 0 0 14px; font-size: 15px; color: #667eea; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.filter-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.filter-form .fg { flex: 1; min-width: 180px; }
.filter-form label { display: block; font-size: 12px; font-weight: 600; color: #4a4a6a; margin-bottom: 5px; }
.filter-form input[type="date"] { width: 100%; padding: 11px 14px; border: 2px solid #e8e8f0; border-radius: 10px; font-family: inherit; font-size: 13px; background: #fafaff; transition: all 0.2s; }
.filter-form input:focus { outline: none; border-color: #667eea; background: #fff; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
.btn-apply { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 11px 24px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 13px; transition: all 0.3s; box-shadow: 0 8px 20px -6px rgba(102, 126, 234, 0.5); }
.btn-apply:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -6px rgba(102, 126, 234, 0.7); }
.btn-reset-f { background: #fff3cd; color: #856404; padding: 11px 20px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s; }
.btn-reset-f:hover { background: #ffc107; color: #fff; }

/* STATS */
.stats-r { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; animation: fadeUp 0.7s backwards; animation-delay: 0.1s; }
.stat-r { background: #fff; border-radius: 18px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: all 0.35s; position: relative; overflow: hidden; }
.stat-r::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--c); transform: scaleX(0); transform-origin: left; transition: transform 0.4s; }
.stat-r:hover::before { transform: scaleX(1); }
.stat-r:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(102, 126, 234, 0.25); }
.stat-r .ic { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: var(--soft); margin-bottom: 12px; transition: transform 0.3s; }
.stat-r:hover .ic { transform: scale(1.12) rotate(-6deg); }
.stat-r .nm { font-size: 30px; font-weight: 800; color: #1a1a2e; line-height: 1; letter-spacing: -1px; margin-bottom: 6px; }
.stat-r .lb { font-size: 12px; color: #6c7293; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

/* RIWAYAT CARDS */
.riwayat-list { display: flex; flex-direction: column; gap: 14px; animation: fadeUp 0.7s backwards; animation-delay: 0.15s; }
.riwayat-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 18px; transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid #667eea; position: relative; overflow: hidden; }
.riwayat-card::before { content: ''; position: absolute; top: 0; right: 0; width: 150px; height: 100%; background: linear-gradient(90deg, transparent, rgba(102,126,234,0.04)); opacity: 0; transition: opacity 0.3s; }
.riwayat-card:hover::before { opacity: 1; }
.riwayat-card:hover { transform: translateX(6px); box-shadow: 0 12px 28px -6px rgba(102, 126, 234, 0.25); }
.riwayat-card.tinggi { border-left-color: #00b894; }
.riwayat-card.sedang { border-left-color: #fdcb6e; }
.riwayat-card.rendah { border-left-color: #dc3545; }

.rc-date { min-width: 90px; text-align: center; padding: 12px 8px; background: #f8f9ff; border-radius: 12px; }
.rc-date .day { font-size: 28px; font-weight: 800; color: #667eea; line-height: 1; }
.rc-date .month { font-size: 11px; font-weight: 700; color: #6c7293; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
.rc-date .year { font-size: 10px; color: #a0a5c0; margin-top: 1px; }

.rc-content { flex: 1; min-width: 0; }
.rc-penyakit { font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; line-height: 1.3; }
.rc-kode { font-size: 11px; font-weight: 800; background: #eef1ff; color: #667eea; padding: 3px 10px; border-radius: 20px; letter-spacing: 0.5px; margin-right: 8px; vertical-align: middle; }
.rc-meta { display: flex; gap: 14px; font-size: 12px; color: #6c7293; flex-wrap: wrap; margin-top: 6px; }
.rc-meta span { display: inline-flex; align-items: center; gap: 5px; }
.rc-meta strong { color: #1a1a2e; font-weight: 700; }

.rc-confidence { text-align: right; padding: 0 16px; min-width: 100px; }
.rc-confidence .val { font-size: 28px; font-weight: 800; line-height: 1; letter-spacing: -1px; }
.rc-confidence .lbl { font-size: 10px; font-weight: 700; color: #6c7293; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px; }
.rc-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: 0.5px; margin-top: 6px; }
.badge-tinggi { background: #d4edda; color: #155724; }
.badge-sedang { background: #fff3cd; color: #856404; }
.badge-rendah { background: #f8d7da; color: #721c24; }

.rc-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn-mini { padding: 10px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px; font-family: inherit; white-space: nowrap; }
.btn-detail { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; box-shadow: 0 4px 12px -3px rgba(102, 126, 234, 0.5); }
.btn-detail:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -3px rgba(102, 126, 234, 0.7); }
.btn-qr-mini { background: #fff3cd; color: #856404; }
.btn-qr-mini:hover { background: #ffc107; color: #fff; transform: translateY(-2px); }

/* EMPTY */
.empty-r { text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; border: 2px dashed #e0e0ea; animation: fadeUp 0.7s backwards; }
.empty-r .icon { font-size: 72px; margin-bottom: 16px; animation: bob 3s infinite; }
.empty-r h3 { color: #1a1a2e; margin-bottom: 8px; }
.empty-r p { color: #6c7293; font-size: 14px; margin-bottom: 20px; }
.btn-lagi-2 { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 14px 30px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; box-shadow: 0 12px 28px -8px rgba(102, 126, 234, 0.6); }
.btn-lagi-2:hover { transform: translateY(-3px); box-shadow: 0 16px 36px -8px rgba(102, 126, 234, 0.8); }

/* INFO BOX */
.info-box-r { background: linear-gradient(135deg, #e7f1ff, #d4e8ff); padding: 22px; border-radius: 16px; margin-top: 20px; animation: fadeUp 0.7s backwards; animation-delay: 0.2s; }
.info-box-r h3 { color: #667eea; margin: 0 0 14px; font-size: 15px; display: flex; align-items: center; gap: 8px; }
.info-box-r ul { margin: 8px 0 0 20px; padding: 0; line-height: 2; font-size: 13px; color: #4a4a6a; }
.info-box-r code { background: #fff; padding: 2px 8px; border-radius: 5px; color: #c2185b; font-weight: 700; font-size: 12px; }

.tips-box { background: linear-gradient(135deg, #fff8e6, #ffe69c); padding: 22px; border-radius: 16px; margin-top: 16px; border-left: 4px solid #ffc107; animation: fadeUp 0.7s backwards; animation-delay: 0.25s; }
.tips-box h3 { color: #664d03; margin: 0 0 12px; font-size: 15px; display: flex; align-items: center; gap: 8px; }
.tips-box ul { margin: 8px 0 0 20px; padding: 0; line-height: 2; font-size: 13px; color: #664d03; }

@media (max-width: 700px) {
    .riwayat-card { flex-wrap: wrap; }
    .rc-confidence { width: 100%; text-align: left; padding: 10px 0; border-top: 1px solid #f0f0f5; }
    .rc-actions { width: 100%; }
    .btn-mini { flex: 1; justify-content: center; }
}
</style>

<div class="blob-container">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="content">

    <!-- HERO -->
    <div class="page-hero">
        <div style="display:flex; align-items:center; gap:20px; position:relative; z-index:1;">
            <div class="hero-icon-big">📜</div>
            <div>
                <h1>Riwayat Diagnosa ISPA Saya</h1>
                <p>Daftar riwayat diagnosa awal penyakit ISPA pada balita yang Anda lakukan di SIPAKAR-ISPA</p>
            </div>
        </div>
    </div>

    <?php if ($m = getFlash('success')): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ <?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = getFlash('error')): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">❌ <?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <!-- FILTER -->
    <div class="filter-panel">
        <h3>🔍 Filter Berdasarkan Tanggal</h3>
        <form method="GET" class="filter-form">
            <div class="fg">
                <label>Dari Tanggal</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            </div>
            <div class="fg">
                <label>Sampai Tanggal</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn-apply">🔍 Terapkan</button>
                <a href="riwayat.php" class="btn-reset-f">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- STATS -->
    <div class="stats-r">
        <div class="stat-r" style="--c:#667eea; --soft:linear-gradient(135deg,#eef1ff,#e0dcff);">
            <div class="ic">🩺</div>
            <div class="nm"><?= $total ?></div>
            <div class="lb">Total Diagnosa<?= ($dari || $sampai) ? ' (filter)' : '' ?></div>
        </div>
        <div class="stat-r" style="--c:#00b894; --soft:linear-gradient(135deg,#e6f8f3,#d4f3ea);">
            <div class="ic">🎯</div>
            <div class="nm"><?= $totalAkurat ?></div>
            <div class="lb">Keyakinan ≥ 50%</div>
        </div>
        <div class="stat-r" style="--c:#0984e3; --soft:linear-gradient(135deg,#e6f1fd,#d4e8f7);">
            <div class="ic">📊</div>
            <div class="nm"><?= $rataKeyakinan ?>%</div>
            <div class="lb">Rata-rata Keyakinan</div>
        </div>
    </div>

    <!-- RIWAYAT LIST -->
    <?php if (empty($list)): ?>
        <div class="empty-r">
            <div class="icon">📭</div>
            <h3>Belum Ada Riwayat</h3>
            <p>Anda belum melakukan diagnosa apapun. Mulai sekarang untuk cek kondisi balita Anda.</p>
            <a href="diagnosa.php" class="btn-lagi-2">🩺 Mulai Diagnosa Sekarang</a>
        </div>
    <?php else: ?>
        <div class="riwayat-list">
            <?php foreach ($list as $i => $r):
                $persen = round($r['nilai_akhir'] * 100, 2);
                $warna = '#00b894';
                $kelas = 'tinggi';
                $badge = 'badge-tinggi';
                $statusText = '🟢 Tinggi';
                if ($persen < 70 && $persen >= 50) {
                    $warna = '#fdcb6e'; $kelas = 'sedang'; $badge = 'badge-sedang'; $statusText = '🟡 Sedang';
                } elseif ($persen < 50) {
                    $warna = '#dc3545'; $kelas = 'rendah'; $badge = 'badge-rendah'; $statusText = '🔴 Rendah';
                }
                $ts = strtotime($r['tanggal']);
            ?>
                <div class="riwayat-card <?= $kelas ?>">
                    <!-- Tanggal -->
                    <div class="rc-date">
                        <div class="day"><?= date('d', $ts) ?></div>
                        <div class="month"><?= date('M', $ts) ?></div>
                        <div class="year"><?= date('Y', $ts) ?></div>
                    </div>

                    <!-- Content -->
                    <div class="rc-content">
                        <div class="rc-penyakit">
                            <span class="rc-kode"><?= htmlspecialchars($r['kode_penyakit']) ?></span>
                            <?= htmlspecialchars($r['nama_penyakit']) ?>
                        </div>
                        <div class="rc-meta">
                            <span>🕐 <?= date('H:i', $ts) ?> WIB</span>
                            <span>🧮 NB: <strong><?= round($r['nilai_nb'] * 100, 2) ?>%</strong></span>
                            <span>⭐ CF: <strong><?= round($r['nilai_cf'] * 100, 2) ?>%</strong></span>
                        </div>
                    </div>

                    <!-- Confidence -->
                    <div class="rc-confidence">
                        <div class="val" style="color:<?= $warna ?>;"><?= $persen ?>%</div>
                        <div class="lbl">Nilai Akhir</div>
                        <span class="rc-badge <?= $badge ?>"><?= $statusText ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="rc-actions">
                        <a href="hasil.php?id=<?= $r['id_hasil'] ?>" class="btn-mini btn-detail">
                            👁️ Detail
                        </a>
                        <a href="qr_hasil.php?id=<?= $r['id_hasil'] ?>" class="btn-mini btn-qr-mini">
                            📱 QR
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- KETERANGAN -->
    <div class="info-box-r">
        <h3>💡 Keterangan</h3>
        <ul>
            <li><strong>NB</strong> = nilai probabilitas hasil <em>Naive Bayes</em> yang telah dinormalisasi</li>
            <li><strong>CF</strong> = nilai <em>Certainty Factor</em> gabungan dari basis rule pakar</li>
            <li><strong>Nilai Akhir</strong> = kombinasi keduanya (<code>60% NB + 40% CF</code>)</li>
            <li><strong>Status "Tinggi"</strong> = nilai akhir ≥ 70% (keyakinan kuat untuk diagnosa awal)</li>
            <li><strong>Status "Sedang"</strong> = nilai akhir 50–69% (perlu observasi lanjutan)</li>
            <li><strong>Status "Rendah"</strong> = nilai akhir &lt; 50% (disarankan pemeriksaan lanjutan)</li>
            <li><strong>📱 QR</strong> = buat QR Code untuk verifikasi keaslian hasil diagnosa</li>
        </ul>
    </div>

    <!-- TIPS QR -->
    <div class="tips-box">
        <h3>💡 Tips Menggunakan QR Code</h3>
        <ul>
            <li>Klik tombol <strong>📱 QR</strong> untuk buka halaman QR Code</li>
            <li>Cetak atau screenshot QR Code tersebut</li>
            <li>Bawa ke Puskesmas — tenaga medis bisa <strong>scan QR</strong> untuk verifikasi</li>
            <li>QR Code bisa dibuka <strong>tanpa login</strong> — aman & mudah</li>
        </ul>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>