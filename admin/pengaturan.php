<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pesan = '';
$tipe  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        $pesan = 'Token tidak valid.';
        $tipe  = 'error';
    } else {
        $fields = ['nama_puskesmas','alamat_puskesmas','telepon_puskesmas','bobot_nb','bobot_cf','footer_text'];
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
            ");
            foreach ($fields as $f) {
                if (isset($_POST[$f])) $stmt->execute([$f, trim($_POST[$f])]);
            }
            $pesan = 'Pengaturan berhasil disimpan.';
            $tipe = 'success';
        } catch (Throwable $e) {
            $pesan = 'Gagal simpan: ' . $e->getMessage();
            $tipe = 'error';
        }
    }
}

$config = [];
try {
    $rows = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll();
    foreach ($rows as $r) $config[$r['kunci']] = $r['nilai'];
} catch (Throwable $e) {
    $pesan = $pesan ?: 'Tabel pengaturan belum ada. Jalankan SQL: CREATE TABLE pengaturan (kunci VARCHAR(50) PRIMARY KEY, nilai TEXT);';
    $tipe = $tipe ?: 'error';
}

$bobotNB = (float)($config['bobot_nb'] ?? 0.6);
$bobotCF = (float)($config['bobot_cf'] ?? 0.4);
$sumBobot = $bobotNB + $bobotCF;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Pengaturan Sistem</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Konfigurasi umum SIPAKAR-ISPA.
    </p>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe === 'error' ? 'error' : 'success' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <div class="container" style="max-width:100%;">
            <h3>Informasi Puskesmas</h3>

            <label>Nama Puskesmas</label>
            <input type="text" name="nama_puskesmas" value="<?= htmlspecialchars($config['nama_puskesmas'] ?? 'Puskesmas Kedungpring') ?>">

            <label>Alamat</label>
            <textarea name="alamat_puskesmas" rows="2"><?= htmlspecialchars($config['alamat_puskesmas'] ?? '') ?></textarea>

            <label>Telepon</label>
            <input type="text" name="telepon_puskesmas" value="<?= htmlspecialchars($config['telepon_puskesmas'] ?? '') ?>">
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>Bobot Algoritma</h3>
            <p style="font-size:13px; color:#666; margin-bottom:12px;">
                Atur bobot kombinasi Naive Bayes &amp; Certainty Factor. Total harus = 1.0.
            </p>

            <label>Bobot Naive Bayes</label>
            <input type="number" name="bobot_nb" step="0.01" min="0" max="1"
                   value="<?= htmlspecialchars($config['bobot_nb'] ?? '0.6') ?>">

            <label>Bobot Certainty Factor</label>
            <input type="number" name="bobot_cf" step="0.01" min="0" max="1"
                   value="<?= htmlspecialchars($config['bobot_cf'] ?? '0.4') ?>">

            <p style="font-size:13px; color:<?= abs($sumBobot - 1.0) < 0.01 ? '#198754' : '#dc3545' ?>;">
                Total bobot: <strong><?= number_format($sumBobot, 2) ?></strong>
                <?= abs($sumBobot - 1.0) < 0.01 ? 'Valid' : 'Harus = 1.00' ?>
            </p>
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>Teks Footer</h3>

            <label>Teks Footer</label>
            <input type="text" name="footer_text" value="<?= htmlspecialchars($config['footer_text'] ?? 'SIPAKAR-ISPA — Puskesmas Kedungpring') ?>">
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <button type="submit" class="btn btn-success">Simpan Pengaturan</button>
            <a href="pengaturan.php" class="btn btn-warning">Reset</a>
        </div>
    </form>

    <div class="container" style="max-width:100%; margin-top:20px; background:#e7f1ff;">
        <h3>Informasi Sistem</h3>
        <table>
            <tr><td width="200">Nama Sistem</td><td>: SIPAKAR-ISPA</td></tr>
            <tr><td>Judul Skripsi</td><td>: Rancang Bangun Sistem Pakar Diagnosa Awal Penyakit ISPA pada Balita</td></tr>
            <tr><td>Metode</td><td>: Naive Bayes + Certainty Factor</td></tr>
            <tr><td>Lokasi</td><td>: Puskesmas Kedungpring</td></tr>
            <tr><td>Versi PHP</td><td>: <?= phpversion() ?></td></tr>
            <tr><td>Versi MySQL</td><td>: <?= $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?></td></tr>
            <tr><td>Tanggal Server</td><td>: <?= date('d/m/Y H:i:s') ?></td></tr>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>