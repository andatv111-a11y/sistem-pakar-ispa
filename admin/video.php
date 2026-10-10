<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

function extractYoutubeId($input) {
    $input = trim($input);
    if ($input === '') return '';
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) return $input;
    if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $input, $m)) return $m[1];
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $input, $m)) return $m[1];
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/', $input, $m)) return $m[1];
    if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/', $input, $m)) return $m[1];
    return $input;
}

// ==============================
// SIMPAN
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: video.php"); exit;
    }
    $judul        = trim($_POST['judul'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $ytInput      = trim($_POST['youtube_input'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $urutan       = (int)($_POST['urutan'] ?? 0);
    $targetType   = in_array($_POST['target_type'] ?? '', ['semua','user']) ? $_POST['target_type'] : 'semua';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $idEdit       = (int)($_POST['id_video'] ?? 0);

    $ytId = extractYoutubeId($ytInput);

    if ($judul === '' || $ytId === '') {
        setFlash('error', 'Judul & URL/ID YouTube wajib diisi.');
        header("Location: video.php"); exit;
    }

    if ($targetType === 'user' && $targetUserId <= 0) {
        setFlash('error', 'Pilih user target terlebih dahulu.');
        header("Location: video.php"); exit;
    }

    $targetId = ($targetType === 'user') ? $targetUserId : null;

    if ($idEdit > 0) {
        $stmt = $pdo->prepare("UPDATE video_edukasi SET judul=?, deskripsi=?, youtube_id=?, kategori=?, urutan=?, target_type=?, target_user_id=? WHERE id_video=?");
        $stmt->execute([$judul, $deskripsi, $ytId, $kategori, $urutan, $targetType, $targetId, $idEdit]);
        setFlash('success', 'Video berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO video_edukasi (judul, deskripsi, youtube_id, kategori, urutan, target_type, target_user_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$judul, $deskripsi, $ytId, $kategori, $urutan, $targetType, $targetId]);
        setFlash('success', 'Video berhasil ditambahkan.');
    }
    header("Location: video.php"); exit;
}

// ==============================
// HAPUS
// ==============================
if ($aksi === 'hapus' && $id > 0) {
    $stmt = $pdo->prepare("SELECT judul FROM video_edukasi WHERE id_video = ?");
    $stmt->execute([$id]);
    $jd = $stmt->fetchColumn();
    if ($jd) {
        $pdo->prepare("DELETE FROM video_edukasi WHERE id_video = ?")->execute([$id]);
        setFlash('success', 'Video "' . $jd . '" berhasil dihapus.');
    }
    header("Location: video.php"); exit;
}

// ==============================
// EDIT
// ==============================
$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM video_edukasi WHERE id_video = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

// ==============================
// AMBIL DAFTAR USER
// ==============================
$users = $pdo->query("SELECT id_user, nama, username FROM users WHERE role='user' ORDER BY nama")->fetchAll();

// ==============================
// LIST VIDEO
// ==============================
try {
    $list = $pdo->query("
        SELECT v.*, u.nama AS nama_target
        FROM video_edukasi v
        LEFT JOIN users u ON u.id_user = v.target_user_id
        ORDER BY v.urutan ASC, v.id_video ASC
    ")->fetchAll();
} catch (Throwable $e) {
    $list = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
.video-grid-admin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.video-card-admin {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid #e8e8f0;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
}
.video-card-admin:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px -6px rgba(214, 48, 49, 0.25);
    border-color: #d63031;
}
.vc-player { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; cursor: pointer; }
.vc-player iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.vc-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; transition: all 0.3s; z-index: 2; }
.vc-overlay::before { content: '▶️'; font-size: 60px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.5)); animation: pulsePlay 2s infinite; }
@keyframes pulsePlay { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.15); } }
.vc-player.playing .vc-overlay { opacity: 0; pointer-events: none; }
.vc-body-admin { padding: 16px; flex: 1; display: flex; flex-direction: column; }
.vc-judul-admin { font-size: 15px; font-weight: 700; color: #1a1a2e; line-height: 1.4; margin: 0 0 8px; }
.vc-kategori-admin { display: inline-block; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; background: #ffe5e5; color: #d63031; margin-bottom: 10px; letter-spacing: 0.3px; }
.vc-target-badge { display: inline-block; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; margin-left: 6px; letter-spacing: 0.3px; }
.target-semua { background: #d1e7dd; color: #0f5132; }
.target-user { background: #fff3cd; color: #856404; }
.vc-desc-admin { font-size: 13px; color: #6c7293; line-height: 1.55; margin-bottom: 12px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.vc-ytid-admin { font-size: 11px; color: #888; background: #f8f9fa; padding: 6px 10px; border-radius: 6px; margin-bottom: 12px; word-break: break-all; font-family: monospace; }
.vc-actions-admin { display: flex; gap: 6px; padding-top: 12px; border-top: 1px solid #f0f0f5; margin-top: auto; flex-wrap: wrap; }
.btn-vc { flex: 1; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-family: inherit; white-space: nowrap; }
.btn-vc-play { background: linear-gradient(135deg, #ff0000, #cc0000); color: #fff; flex: 2; }
.btn-vc-play:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(255, 0, 0, 0.4); }
.btn-vc-edit { background: #fff3cd; color: #856404; }
.btn-vc-edit:hover { background: #ffc107; color: #fff; }
.btn-vc-del { background: #f8d7da; color: #721c24; }
.btn-vc-del:hover { background: #dc3545; color: #fff; }
.form-row-video { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.info-video { background: #e7f1ff; border-left: 4px solid #0d6efd; padding: 14px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; color: #0d6efd; line-height: 1.7; }
.info-video code { background: #fff; padding: 2px 8px; border-radius: 4px; color: #c2185b; font-weight: 700; }
.target-section { background: linear-gradient(135deg,#f0f7ff,#e7f1ff); padding: 16px; border-radius: 12px; margin: 16px 0; border-left: 4px solid #0d6efd; }
.target-section h4 { margin: 0 0 12px; font-size: 14px; color: #0d6efd; }
.radio-group { display: flex; gap: 20px; margin-bottom: 12px; flex-wrap: wrap; }
.radio-group label { display: flex; align-items: center; gap: 8px; padding: 10px 16px; border: 2px solid #e8e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 600; background: #fff; }
.radio-group label:hover { border-color: #0d6efd; }
.radio-group label:has(input:checked) { background: #e7f1ff; border-color: #0d6efd; color: #0d6efd; }
.radio-group input { margin: 0; }
#userSelector { display: none; }
#userSelector.active { display: block; animation: fadeIn 0.3s; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@media (max-width: 600px) {
    .form-row-video { grid-template-columns: 1fr; }
    .video-grid-admin { grid-template-columns: 1fr; }
    .radio-group { flex-direction: column; }
}
</style>

<div class="content">
    <h2>Manajemen Video Edukasi</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola video edukasi ISPA yang tampil di halaman user. Bisa dikirim ke <strong>semua user</strong> atau <strong>user tertentu</strong>.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <!-- Info -->
    <div class="info-video">
        <strong>💡 Cara Mengambil YouTube ID:</strong><br>
        Buka video di YouTube → <strong>Share</strong> → Copy URL. Contoh: <code>https://www.youtube.com/watch?v=dQw4w9WgXcQ</code><br>
        Yang diambil hanya bagian setelah <code>v=</code> yaitu <code>dQw4w9WgXcQ</code>. Atau paste URL lengkap — sistem otomatis ambil ID-nya.
    </div>

    <!-- FORM -->
    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? '✏️ Edit Video' : '➕ Tambah Video Baru' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_video" value="<?= $edit['id_video'] ?? 0 ?>">

            <label>Judul Video <span style="color:#dc3545;">*</span></label>
            <input type="text" name="judul" required value="<?= htmlspecialchars($edit['judul'] ?? '') ?>" placeholder="Contoh: Cara Mencegah ISPA pada Balita">

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="2" placeholder="Penjelasan singkat..."><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>

            <label>URL YouTube atau Video ID <span style="color:#dc3545;">*</span></label>
            <input type="text" name="youtube_input" required value="<?= htmlspecialchars($edit['youtube_id'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ">

            <div class="form-row-video">
                <div>
                    <label>Kategori</label>
                    <input type="text" name="kategori" value="<?= htmlspecialchars($edit['kategori'] ?? '') ?>" placeholder="Contoh: Dasar">
                </div>
                <div>
                    <label>Urutan</label>
                    <input type="number" name="urutan" value="<?= htmlspecialchars($edit['urutan'] ?? 0) ?>">
                </div>
            </div>

            <!-- TARGET USER -->
            <div class="target-section">
                <h4>🎯 Kirim Video Ke:</h4>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="target_type" value="semua"
                               <?= (($edit['target_type'] ?? 'semua') === 'semua') ? 'checked' : '' ?>
                               onchange="toggleUserSelector()">
                        🌐 Semua User (Broadcast)
                    </label>
                    <label>
                        <input type="radio" name="target_type" value="user"
                               <?= (($edit['target_type'] ?? '') === 'user') ? 'checked' : '' ?>
                               onchange="toggleUserSelector()">
                        👤 User Tertentu
                    </label>
                </div>

                <div id="userSelector" class="<?= (($edit['target_type'] ?? '') === 'user') ? 'active' : '' ?>">
                    <label>Pilih User Target</label>
                    <select name="target_user_id">
                        <option value="">-- Pilih User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id_user'] ?>"
                                    <?= (($edit['target_user_id'] ?? 0) == $u['id_user']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nama']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:12px; color:#666; margin-top:6px;">
                        💡 Video hanya akan muncul di halaman user yang dipilih.
                    </p>
                </div>
            </div>

            <button type="submit" name="simpan" class="btn btn-success">
                <?= $edit ? '💾 Update' : '💾 Simpan' ?>
            </button>
            <?php if ($edit): ?><a href="video.php" class="btn btn-warning">❌ Batal</a><?php endif; ?>
        </form>
    </div>

    <!-- LIST -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>📹 Daftar Video (<?= count($list) ?>)</h3>

        <?php if (empty($list)): ?>
            <p style="text-align:center; color:#888; padding:40px 20px;">Belum ada video.</p>
        <?php else: ?>
            <div class="video-grid-admin">
                <?php foreach ($list as $v):
                    $ytId = htmlspecialchars($v['youtube_id']);
                    $embedUrl = "https://www.youtube.com/embed/{$ytId}?rel=0&modestbranding=1";
                    $watchUrl = "https://www.youtube.com/watch?v={$ytId}";
                    $isSemua = ($v['target_type'] ?? 'semua') === 'semua';
                ?>
                    <div class="video-card-admin">
                        <div class="vc-player" onclick="this.classList.add('playing')">
                            <div class="vc-overlay"></div>
                            <iframe src="<?= $embedUrl ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen loading="lazy"></iframe>
                        </div>
                        <div class="vc-body-admin">
                            <h4 class="vc-judul-admin"><?= htmlspecialchars($v['judul']) ?></h4>

                            <div style="margin-bottom:8px;">
                                <?php if (!empty($v['kategori'])): ?>
                                    <span class="vc-kategori-admin">🏷️ <?= htmlspecialchars($v['kategori']) ?></span>
                                <?php endif; ?>
                                <?php if ($isSemua): ?>
                                    <span class="vc-target-badge target-semua">🌐 Semua User</span>
                                <?php else: ?>
                                    <span class="vc-target-badge target-user">👤 <?= htmlspecialchars($v['nama_target'] ?? 'User') ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($v['deskripsi'])): ?>
                                <p class="vc-desc-admin"><?= htmlspecialchars($v['deskripsi']) ?></p>
                            <?php endif; ?>

                            <div class="vc-ytid-admin">🎬 ID: <strong><?= $ytId ?></strong></div>

                            <div class="vc-actions-admin">
                                <a href="<?= $watchUrl ?>" target="_blank" rel="noopener" class="btn-vc btn-vc-play">
                                    ▶️ Tonton di YouTube
                                </a>
                                <a href="?aksi=edit&id=<?= $v['id_video'] ?>" class="btn-vc btn-vc-edit" title="Edit">✏️</a>
                                <a href="?aksi=hapus&id=<?= $v['id_video'] ?>" class="btn-vc btn-vc-del confirm-delete" title="Hapus"
                                   onclick="return confirm('Yakin hapus video ini?');">🗑️</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleUserSelector() {
    const val = document.querySelector('input[name="target_type"]:checked').value;
    const sel = document.getElementById('userSelector');
    if (val === 'user') {
        sel.classList.add('active');
    } else {
        sel.classList.remove('active');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>