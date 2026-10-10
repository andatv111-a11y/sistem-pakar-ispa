<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

// ==============================
// AMBIL DAFTAR VIDEO
// ==============================
try {
    $videos = $pdo->query("
        SELECT * FROM video_edukasi
        ORDER BY urutan ASC, id_video ASC
    ")->fetchAll();
} catch (Throwable $e) {
    $videos = [];
}

// Kelompokkan per kategori
$perKategori = [];
foreach ($videos as $v) {
    $kat = $v['kategori'] ?: 'Umum';
    $perKategori[$kat][] = $v;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>🎥 Video Edukasi ISPA</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kumpulan video edukasi tentang ISPA pada balita — Puskesmas Kedungpring
    </p>

    <?php if (empty($videos)): ?>
        <div class="container" style="max-width:100%; text-align:center; padding:40px;">
            <div style="font-size:64px;">🎥</div>
            <h3>Belum Ada Video Edukasi</h3>
            <p style="color:#666; margin-top:8px;">
                Admin belum menambahkan video edukasi.
                Silakan cek kembali nanti atau hubungi admin.
            </p>
            <a href="edukasi.php" class="btn" style="margin-top:16px;">
                📚 Baca Artikel Edukasi
            </a>
        </div>
    <?php else: ?>

        <!-- Info Cards -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color:#dc3545;">
                <div style="font-size:32px;">🎥</div>
                <p><strong>Video Berkualitas</strong><br>Pilih dari sumber terpercaya</p>
            </div>
            <div class="stat-card" style="border-left-color:#0d6efd;">
                <div style="font-size:32px;">📚</div>
                <p><strong><?= count($videos) ?> Video</strong><br>Tersedia dalam <?= count($perKategori) ?> kategori</p>
            </div>
            <div class="stat-card" style="border-left-color:#198754;">
                <div style="font-size:32px;">👶</div>
                <p><strong>Fokus Balita</strong><br>Khusus untuk orang tua balita</p>
            </div>
        </div>

        <!-- Daftar Video per Kategori -->
        <?php foreach ($perKategori as $kategori => $vids): ?>
            <div class="container" style="max-width:100%; margin-top:24px;">
                <h3>📁 Kategori: <?= htmlspecialchars($kategori) ?> (<?= count($vids) ?>)</h3>

                <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:20px; margin-top:16px;">
                    <?php foreach ($vids as $v): ?>
                        <div style="background:#fff; border:1px solid #e3e3e3; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,.05); transition:.2s;"
                             onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,.1)';"
                             onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 6px rgba(0,0,0,.05)';">

                            <!-- Video Embed -->
                            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden;">
                                <iframe
                                    src="https://www.youtube.com/embed/<?= htmlspecialchars($v['youtube_id']) ?>"
                                    style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>

                            <!-- Info Video -->
                            <div style="padding:16px;">
                                <h4 style="color:#0d6efd; font-size:15px; line-height:1.4;">
                                    <?= htmlspecialchars($v['judul']) ?>
                                </h4>

                                <?php if (!empty($v['kategori'])): ?>
                                    <span style="display:inline-block; background:#e7f1ff; color:#0d6efd; padding:3px 10px; border-radius:12px; font-size:11px; margin-top:6px;">
                                        🏷️ <?= htmlspecialchars($v['kategori']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($v['deskripsi'])): ?>
                                    <p style="font-size:13px; color:#666; margin-top:10px; line-height:1.6;">
                                        <?= htmlspecialchars($v['deskripsi']) ?>
                                    </p>
                                <?php endif; ?>

                                <p style="margin-top:12px;">
                                    <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($v['youtube_id']) ?>"
                                       target="_blank"
                                       class="btn btn-sm"
                                       style="font-size:12px;">
                                        ▶️ Buka di YouTube
                                    </a>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Call to Action -->
        <div class="container" style="max-width:100%; margin-top:24px; background:linear-gradient(135deg,#0d6efd,#0a58ca); color:#fff; text-align:center;">
            <h3 style="color:#fff;">Butuh Informasi Lebih Lengkap?</h3>
            <p style="font-size:14px; margin-bottom:16px; opacity:.95;">
                Baca juga artikel edukasi & lakukan diagnosa awal ISPA pada balita Anda
            </p>
            <a href="edukasi.php" class="btn" style="background:#fff; color:#0d6efd; font-weight:bold; margin-right:8px;">
                📚 Baca Edukasi
            </a>
            <a href="diagnosa.php" class="btn btn-success">
                🩺 Mulai Diagnosa
            </a>
        </div>

    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>