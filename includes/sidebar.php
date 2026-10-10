<aside class="sidebar">
    <?php if (isAdmin()): ?>
        <h4>Menu Admin</h4>
        <ul>
            <li><a href="<?= BASE_URL ?>admin/dashboard.php">🏠 Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>admin/penyakit.php">🦠 Data Penyakit</a></li>
            <li><a href="<?= BASE_URL ?>admin/gejala.php">📋 Data Gejala</a></li>
            <li><a href="<?= BASE_URL ?>admin/rule.php">⚙️ Basis Rule</a></li>
            <li><a href="<?= BASE_URL ?>admin/basis_kasus.php">📚 Basis Kasus</a></li>
            <li><a href="<?= BASE_URL ?>admin/riwayat.php">📜 Riwayat Diagnosa</a></li>
            <li><a href="<?= BASE_URL ?>admin/users.php">👥 Manajemen User</a></li>
            <li><a href="<?= BASE_URL ?>admin/laporan.php">📊 Laporan</a></li>
            <li><a href="<?= BASE_URL ?>admin/artikel.php">📰 Artikel</a></li>
            <li><a href="<?= BASE_URL ?>admin/video.php">🎥 Video Edukasi</a></li>
            <li><a href="<?= BASE_URL ?>admin/konsultasi.php">💬 Konsultasi</a></li>
            <li><a href="<?= BASE_URL ?>admin/info_puskesmas.php">🏥 Info Puskesmas</a></li>
            <li><a href="<?= BASE_URL ?>admin/backup.php">💾 Backup</a></li>
            <li><a href="<?= BASE_URL ?>admin/pengaturan.php">⚙️ Pengaturan</a></li>
        </ul>
    <?php elseif (isUser()): ?>
        <h4>Menu User</h4>
        <ul>
            <li><a href="<?= BASE_URL ?>user/dashboard.php">🏠 Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>user/diagnosa.php">🩺 Mulai Diagnosa</a></li>
            <li><a href="<?= BASE_URL ?>user/riwayat.php">📜 Riwayat Saya</a></li>
            <li><a href="<?= BASE_URL ?>user/laporan.php">📊 Laporan Saya</a></li>
            <li><a href="<?= BASE_URL ?>user/edukasi.php">📚 Edukasi ISPA</a></li>
            <li><a href="<?= BASE_URL ?>user/video.php">🎥 Video Edukasi</a></li>
            <li><a href="<?= BASE_URL ?>user/statistik.php">📊 Statistik Saya</a></li>
            <li><a href="<?= BASE_URL ?>user/reminder.php">🗓️ Reminder Kontrol</a></li>
            <li><a href="<?= BASE_URL ?>user/konsultasi.php">💬 Konsultasi</a></li>
            <li><a href="<?= BASE_URL ?>user/info_puskesmas.php">🏥 Info Puskesmas</a></li>
            <li><a href="<?= BASE_URL ?>user/profil.php">👤 Profil Saya</a></li>
        </ul>
    <?php endif; ?>
</aside>