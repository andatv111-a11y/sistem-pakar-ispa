<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$gejala = $pdo->query("SELECT * FROM gejala ORDER BY kode_gejala")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Form Diagnosa Awal ISPA pada Balita</h2>
    <p style="color:#666; font-size:14px; margin-bottom:8px;">
        Puskesmas Kedungpring — Metode: <strong>Naive Bayes</strong> &amp; <strong>Certainty Factor</strong>
    </p>

    <?php if ($m = getFlash('error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <div class="container" style="max-width:100%;">
        <form id="formDiagnosa" action="<?= BASE_URL ?>proses/diagnosa_proses.php" method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

            <h3>Data Balita (opsional)</h3>
            <label>Nama Balita</label>
            <input type="text" name="nama_balita" placeholder="Contoh: An. Ahmad">

            <label>Usia (bulan)</label>
            <input type="number" name="usia" min="0" max="60" placeholder="Contoh: 24">

            <label>Jenis Kelamin</label>
            <select name="jenis_kelamin">
                <option value="">-- Pilih --</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>

            <h3 style="margin-top:24px;">Pilih Gejala Klinis yang Tampak</h3>
            <p style="font-size:13px; color:#666; margin-bottom:10px;">
                Centang semua gejala yang dialami balita. Minimal pilih 1 gejala.
            </p>

            <div class="gejala-grid">
                <?php foreach ($gejala as $g): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="gejala[]" value="<?= $g['id_gejala'] ?>">
                        <span>
                            <strong><?= htmlspecialchars($g['kode_gejala']) ?></strong>
                            — <?= htmlspecialchars($g['nama_gejala']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-success">🔍 Proses Diagnosa</button>
                <a href="dashboard.php" class="btn btn-warning">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>