<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

$stmt = $pdo->prepare("SELECT COUNT(*), AVG(nilai_akhir) FROM hasil_diagnosa WHERE id_user = ?");
$stmt->execute([$idUser]);
$s = $stmt->fetch(PDO::FETCH_NUM);
$totalDiagnosa = (int)($s[0] ?? 0);
$rataKeyakinan = (float)($s[1] ?? 0);

$stmt = $pdo->prepare("
    SELECT h.*, p.kode_penyakit, p.nama_penyakit
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    WHERE h.id_user = ?
    ORDER BY h.tanggal DESC LIMIT 1
");
$stmt->execute([$idUser]);
$terakhir = $stmt->fetch();

$totalPenyakit = $pdo->query("SELECT COUNT(*) FROM penyakit")->fetchColumn();
$totalGejala   = $pdo->query("SELECT COUNT(*) FROM gejala")->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.blob-container { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
.blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.3; animation: floatBlob 20s infinite ease-in-out; }
.blob-1 { width: 400px; height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); top: -100px; left: -100px; }
.blob-2 { width: 350px; height: 350px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); bottom: -100px; right: -50px; animation-delay: -7s; }
.blob-3 { width: 300px; height: 300px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); top: 40%; left: 30%; animation-delay: -14s; }
@keyframes floatBlob { 0%, 100% { transform: translate(0,0) scale(1); } 25% { transform: translate(30px,-50px) scale(1.1); } 50% { transform: translate(-40px,30px) scale(0.95); } 75% { transform: translate(20px,-30px) scale(1.05); } }
.content { position: relative; z-index: 1; }

.hero-user { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 36px 40px; border-radius: 24px; color: #fff; margin-bottom: 28px; position: relative; overflow: hidden; box-shadow: 0 25px 50px -15px rgba(102, 126, 234, 0.5); animation: slideDown 0.7s cubic-bezier(0.4, 0, 0.2, 1); }
@keyframes slideDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
.hero-user::before { content: ''; position: absolute; top: -50%; right: -10%; width: 350px; height: 350px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: pulse 4s infinite; }
.hero-user::after { content: ''; position: absolute; bottom: -50%; right: 20%; width: 250px; height: 250px; background: rgba(255,255,255,0.08); border-radius: 50%; animation: pulse 4s infinite 1s; }
@keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.15); opacity: 0.4; } }
.hero-user-content { position: relative; z-index: 1; }
.hero-user h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px; color: #fff; }
.hero-user p { font-size: 14px; opacity: 0.9; margin: 0; }
.wave { display: inline-block; animation: wave 2.5s infinite; transform-origin: 70% 70%; }
@keyframes wave { 0%, 100% { transform: rotate(0deg); } 10%, 30% { transform: rotate(14deg); } 20% { transform: rotate(-8deg); } 40% { transform: rotate(-4deg); } 50% { transform: rotate(10deg); } }

.quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px; animation: fadeUp 0.7s backwards; animation-delay: 0.1s; }
.qa-card { background: #fff; padding: 22px; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; color: inherit; display: flex; align-items: center; gap: 16px; position: relative; overflow: hidden; }
.qa-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--c); transform: scaleX(0); transform-origin: left; transition: transform 0.4s; }
.qa-card:hover::before { transform: scaleX(1); }
.qa-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); }
.qa-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; background: var(--soft); flex-shrink: 0; transition: transform 0.3s; }
.qa-card:hover .qa-icon { transform: scale(1.15) rotate(-6deg); }
.qa-label { font-size: 15px; font-weight: 700; color: #1a1a2e; }
.qa-desc { font-size: 12px; color: #6c7293; margin-top: 2px; }

.stats-grid-u { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; animation: fadeUp 0.7s backwards; animation-delay: 0.2s; }
.stat-u { background: #fff; padding: 22px; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); transition: all 0.35s; cursor: pointer; }
.stat-u:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); }
.stat-u .ic { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: var(--soft); margin-bottom: 12px; }
.stat-u .nm { font-size: 32px; font-weight: 800; color: #1a1a2e; line-height: 1; margin-bottom: 6px; letter-spacing: -1px; }
.stat-u .lb { font-size: 12px; color: #6c7293; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

.section-card { background: #fff; border-radius: 20px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; animation: fadeUp 0.7s backwards; animation-delay: 0.3s; }
.section-card h3 { font-size: 17px; font-weight: 700; color: #1a1a2e; margin: 0 0 18px; display: flex; align-items: center; gap: 10px; }
.section-card h3 span { font-size: 22px; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.last-result { background: linear-gradient(135deg, #e6f8f3, #d4f3ea); padding: 24px; border-radius: 16px; position: relative; overflow: hidden; }
.last-result::before { content: ''; position: absolute; top: -50%; right: -20%; width: 200px; height: 200px; background: rgba(25, 135, 84, 0.08); border-radius: 50%; }
.last-result-content { position: relative; z-index: 1; }
.last-penyakit { font-size: 20px; font-weight: 800; color: #198754; margin: 8px 0; }
.last-confidence { font-size: 24px; font-weight: 800; color: #198754; }

.edu-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
.edu-c { padding: 18px; border-radius: 14px; border-left: 4px solid; transition: all 0.3s; cursor: pointer; }
.edu-c:hover { transform: translateX(6px); }
.edu-c .icon-c { font-size: 28px; margin-bottom: 8px; }
.edu-c h4 { font-size: 14px; font-weight: 700; margin-bottom: 6px; }
.edu-c p { font-size: 12px; color: #6c7293; line-height: 1.5; }

.warn-section { background: linear-gradient(135deg, #fff3cd, #ffe69c); padding: 24px; border-radius: 16px; margin-top: 24px; border-left: 4px solid #ffc107; }
.warn-section h3 { color: #664d03; margin: 0 0 12px; font-size: 15px; }
.warn-section ul { margin: 8px 0 0 20px; padding: 0; line-height: 1.9; font-size: 14px; color: #664d03; }

.btn-hero { display: inline-block; padding: 14px 28px; background: #fff; color: #667eea; border-radius: 12px; font-weight: 700; text-decoration: none; transition: all 0.3s; box-shadow: 0 8px 20px rgba(0,0,0,0.15); font-size: 15px; }
.btn-hero:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0,0,0,0.25); }
</style>

<div class="blob-container">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
</div>

<div class="content">

    <!-- HERO -->
    <div class="hero-user">
        <div class="hero-user-content">
            <h1>Halo, <?= htmlspecialchars($_SESSION['nama']) ?> <span class="wave">👋</span></h1>
            <p>Selamat datang di SIPAKAR-ISPA — Sistem Pakar Diagnosa Awal ISPA pada Balita</p>
            <div style="margin-top:20px;">
                <a href="diagnosa.php" class="btn-hero">🩺 Mulai Diagnosa Sekarang</a>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions">
        <a href="diagnosa.php" class="qa-card" style="--c:#667eea; --soft:linear-gradient(135deg,#eef1ff,#e0dcff);">
            <div class="qa-icon">🩺</div>
            <div>
                <div class="qa-label">Mulai Diagnosa</div>
                <div class="qa-desc">Cek gejala ISPA pada balita</div>
            </div>
        </a>
        <a href="riwayat.php" class="qa-card" style="--c:#00b894; --soft:linear-gradient(135deg,#e6f8f3,#d4f3ea);">
            <div class="qa-icon">📜</div>
            <div>
                <div class="qa-label">Riwayat Saya</div>
                <div class="qa-desc">Lihat diagnosa sebelumnya</div>
            </div>
        </a>
        <a href="edukasi.php" class="qa-card" style="--c:#fdcb6e; --soft:linear-gradient(135deg,#fff8e6,#ffe69c);">
            <div class="qa-icon">📚</div>
            <div>
                <div class="qa-label">Edukasi ISPA</div>
                <div class="qa-desc">Baca panduan kesehatan</div>
            </div>
        </a>
        <a href="statistik.php" class="qa-card" style="--c:#a29bfe; --soft:linear-gradient(135deg,#efeeff,#e0dcff);">
            <div class="qa-icon">📊</div>
            <div>
                <div class="qa-label">Statistik Saya</div>
                <div class="qa-desc">Lihat grafik diagnosa</div>
            </div>
        </a>
    </div>

    <!-- STATS -->
    <div class="stats-grid-u">
        <div class="stat-u">
            <div class="ic" style="background:linear-gradient(135deg,#eef1ff,#e0dcff);">🩺</div>
            <div class="nm" data-count="<?= $totalDiagnosa ?>">0</div>
            <div class="lb">Total Diagnosa</div>
        </div>
        <div class="stat-u">
            <div class="ic" style="background:linear-gradient(135deg,#e6f8f3,#d4f3ea);">🎯</div>
            <div class="nm" data-count="<?= round($rataKeyakinan * 100, 1) ?>" data-suffix="%">0</div>
            <div class="lb">Rata-rata Keyakinan</div>
        </div>
        <div class="stat-u">
            <div class="ic" style="background:linear-gradient(135deg,#fff8e6,#ffe69c);">🦠</div>
            <div class="nm" data-count="<?= $totalPenyakit ?>">0</div>
            <div class="lb">Jenis Penyakit</div>
        </div>
        <div class="stat-u">
            <div class="ic" style="background:linear-gradient(135deg,#ffe5e5,#f5c2c7);">📋</div>
            <div class="nm" data-count="<?= $totalGejala ?>">0</div>
            <div class="lb">Gejala Klinis</div>
        </div>
    </div>

    <!-- DIAGNOSA TERAKHIR -->
    <?php if ($terakhir): ?>
    <div class="section-card">
        <h3><span>📋</span> Diagnosa Terakhir Anda</h3>
        <div class="last-result">
            <div class="last-result-content">
                <div style="font-size:12px; color:#6c7293;">📅 <?= date('d/m/Y H:i', strtotime($terakhir['tanggal'])) ?></div>
                <div class="last-penyakit"><?= htmlspecialchars($terakhir['kode_penyakit'] . ' - ' . $terakhir['nama_penyakit']) ?></div>
                <div style="display:flex; align-items:center; gap:16px; margin:12px 0;">
                    <div class="last-confidence"><?= round($terakhir['nilai_akhir'] * 100, 2) ?>%</div>
                    <div style="flex:1; height:12px; background:rgba(255,255,255,0.6); border-radius:6px; overflow:hidden;">
                        <div style="height:100%; width:<?= round($terakhir['nilai_akhir'] * 100, 2) ?>%; background:linear-gradient(90deg, #00b894, #198754); border-radius:6px;"></div>
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:16px; flex-wrap:wrap;">
                    <a href="hasil.php?id=<?= $terakhir['id_hasil'] ?>" class="btn btn-success">📄 Lihat Detail Hasil</a>
                    <a href="riwayat.php" class="btn btn-warning">📜 Semua Riwayat</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- EDUKASI CEPAT -->
    <div class="section-card">
        <h3><span>📚</span> Edukasi Cepat — Kenali ISPA</h3>
        <div class="edu-cards">
            <div class="edu-c" style="background:#e7f1ff; border-color:#0d6efd;">
                <div class="icon-c">🦠</div>
                <h4 style="color:#0d6efd;">Apa Itu ISPA?</h4>
                <p>Infeksi saluran pernapasan akut — sangat umum pada balita.</p>
            </div>
            <div class="edu-c" style="background:#ffe5e5; border-color:#dc3545;">
                <div class="icon-c">🌡️</div>
                <h4 style="color:#dc3545;">Gejala Umum</h4>
                <p>Demam, batuk, pilek, sesak napas, nafsu makan turun.</p>
            </div>
            <div class="edu-c" style="background:#e6f8f3; border-color:#198754;">
                <div class="icon-c">🛡️</div>
                <h4 style="color:#198754;">Pencegahan</h4>
                <p>ASI eksklusif, imunisasi, jaga kebersihan.</p>
            </div>
            <div class="edu-c" style="background:#fff3cd; border-color:#ffc107;">
                <div class="icon-c">🚨</div>
                <h4 style="color:#d39e00;">Rujuk Segera</h4>
                <p>Napas cepat, demam tinggi, lemas, bibir biru.</p>
            </div>
        </div>
    </div>

    <!-- WARNING -->
    <div class="warn-section">
        <h3>⚠️ Gejala yang Harus Diwaspadai</h3>
        <p style="font-size:14px; color:#664d03;">Segera ke Puskesmas Kedungpring jika balita mengalami:</p>
        <ul>
            <li><strong>Napas cepat</strong> — &gt;50×/menit (2-12 bulan) atau &gt;40×/menit (1-5 tahun)</li>
            <li><strong>Tarikan dinding dada</strong> saat bernapas</li>
            <li><strong>Demam tinggi</strong> &gt;39°C atau &gt;3 hari</li>
            <li><strong>Bibir/kulit membiru</strong> — tanda kurang oksigen</li>
            <li><strong>Lemas / tidak sadar</strong></li>
        </ul>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-count]').forEach(counter => {
        const target = parseFloat(counter.dataset.count) || 0;
        const suffix = counter.dataset.suffix || '';
        const isFloat = target % 1 !== 0;
        const duration = 1200;
        const step = Math.max(isFloat ? 0.1 : 1, target / 40);
        let current = 0;

        const tick = () => {
            current += step;
            if (current >= target) {
                counter.textContent = (isFloat ? target.toFixed(1) : target) + suffix;
                return;
            }
            counter.textContent = (isFloat ? current.toFixed(1) : Math.floor(current)) + suffix;
            requestAnimationFrame(() => setTimeout(tick, 20));
        };
        setTimeout(tick, 200);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>