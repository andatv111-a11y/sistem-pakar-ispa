<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

// Ambil data info puskesmas
$info = null;
try {
    $info = $pdo->query("SELECT * FROM info_puskesmas LIMIT 1")->fetch();
} catch (Throwable $e) {
    $info = null;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>🏥 Info Puskesmas Kedungpring</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Informasi lengkap tentang Puskesmas Kedungpring — alamat, jam buka, dan layanan
    </p>

    <?php if (!$info): ?>
        <div class="container" style="max-width:100%;">
            <div class="alert alert-error">
                ⚠️ Info Puskesmas belum diisi admin. Hubungi admin untuk melengkapi data.
            </div>
        </div>
    <?php else: ?>

    <!-- Kartu Utama -->
    <div class="container" style="max-width:100%; background:linear-gradient(135deg,#0d6efd,#0a58ca); color:#fff;">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div style="font-size:64px;">🏥</div>
            <div style="flex:1; min-width:200px;">
                <h3 style="color:#fff; margin:0;">
                    <?= htmlspecialchars($info['nama'] ?? 'Puskesmas Kedungpring') ?>
                </h3>
                <p style="font-size:14px; margin-top:8px; opacity:.95;">
                    <?= htmlspecialchars($info['alamat'] ?? '-') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Statistik Cepat -->
    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card" style="border-left-color:#0d6efd;">
            <div style="font-size:32px;">📞</div>
            <p><strong>Telepon</strong><br><?= htmlspecialchars($info['telepon'] ?? '-') ?></p>
        </div>
        <div class="stat-card" style="border-left-color:#198754;">
            <div style="font-size:32px;">📧</div>
            <p><strong>Email</strong><br><?= htmlspecialchars($info['email'] ?? '-') ?></p>
        </div>
        <div class="stat-card" style="border-left-color:#ffc107;">
            <div style="font-size:32px;">🕐</div>
            <p><strong>Jam Buka</strong><br><?= htmlspecialchars($info['jam_senin_jumat'] ?? '07.30 - 14.00') ?></p>
        </div>
    </div>

    <!-- Kontak Lengkap -->
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>📞 Kontak & Alamat</h3>
        <table>
            <tr>
                <td width="180"><strong>Nama Puskesmas</strong></td>
                <td>: <?= htmlspecialchars($info['nama'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td>: <?= htmlspecialchars($info['alamat'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Telepon</strong></td>
                <td>: <?= htmlspecialchars($info['telepon'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td>: <?= htmlspecialchars($info['email'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <!-- Jam Operasional -->
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>🕐 Jam Operasional</h3>
        <table>
            <tr>
                <td width="180"><strong>Senin – Jumat</strong></td>
                <td>: <strong><?= htmlspecialchars($info['jam_senin_jumat'] ?? '-') ?></strong></td>
            </tr>
            <tr>
                <td><strong>Sabtu</strong></td>
                <td>: <strong><?= htmlspecialchars($info['jam_sabtu'] ?? '-') ?></strong></td>
            </tr>
            <tr>
                <td><strong>Minggu</strong></td>
                <td>: <strong><?= htmlspecialchars($info['jam_minggu'] ?? '-') ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Layanan -->
    <?php if (!empty($info['layanan'])): ?>
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>💊 Layanan yang Tersedia</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:10px; margin-top:10px;">
            <?php foreach (explode(',', $info['layanan']) as $lay): ?>
                <div style="background:#e7f1ff; padding:12px; border-radius:8px; font-size:14px; border-left:3px solid #0d6efd;">
                    ✅ <?= htmlspecialchars(trim($lay)) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Peta Lokasi -->
    <?php if (!empty($info['latitude']) && !empty($info['longitude'])): ?>
    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>🗺️ Lokasi Puskesmas</h3>
        <iframe
            width="100%"
            height="350"
            style="border:0; border-radius:8px; display:block;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q=<?= $info['latitude'] ?>,<?= $info['longitude'] ?>&output=embed">
        </iframe>
        <p style="margin-top:12px;">
            <a href="https://www.google.com/maps?q=<?= $info['latitude'] ?>,<?= $info['longitude'] ?>"
               target="_blank"
               class="btn">
                📍 Buka di Google Maps
            </a>
        </p>
    </div>
    <?php endif; ?>

    <!-- Kontak Darurat -->
    <div class="container" style="max-width:100%; margin-top:20px; background:#f8d7da; border:1px solid #f5c2c7;">
        <h3 style="color:#842029;">🚨 Kontak Darurat</h3>
        <p style="font-size:14px; color:#842029;">
            Jika balita Anda dalam kondisi gawat (napas cepat, biru, lemas), <strong>segera hubungi</strong>:
        </p>
        <p style="font-size:20px; font-weight:bold; color:#842029; margin-top:10px;">
            📞 <?= htmlspecialchars($info['telepon'] ?? '-') ?>
        </p>
    </div>

    <!-- Tips Berkunjung -->
    <div class="container" style="max-width:100%; margin-top:20px; background:#fff3cd; border:1px solid #ffd54f;">
        <h3 style="color:#664d03;">💡 Tips Berkunjung ke Puskesmas</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px; color:#664d03;">
            <li>Datang lebih awal (07.30) untuk menghindari antrean panjang</li>
            <li>Bawa <strong>KMS (Kartu Menuju Sehat)</strong> dan buku imunisasi balita</li>
            <li>Bawa <strong>KTP/KK</strong> untuk pendaftaran</li>
            <li>Gunakan <strong>BPJS Kesehatan</strong> jika ada</li>
            <li>Catat keluhan balita sebelum berangkat</li>
        </ul>
    </div>

    <!-- Aksi -->
    <div class="container" style="max-width:100%; margin-top:20px; text-align:center;">
        <a href="diagnosa.php" class="btn btn-success" style="font-size:15px; padding:12px 24px;">
            🩺 Mulai Diagnosa
        </a>
        <a href="konsultasi.php" class="btn" style="background:#0dcaf0; color:#000; font-size:15px; padding:12px 24px;">
            💬 Konsultasi Admin
        </a>
        <a href="dashboard.php" class="btn btn-warning" style="font-size:15px; padding:12px 24px;">
            🏠 Dashboard
        </a>
    </div>

    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>