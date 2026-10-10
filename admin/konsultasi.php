<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

// ==============================
// KIRIM BALASAN
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['balas'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
    } else {
        $idKonsul = (int)($_POST['id_konsultasi'] ?? 0);
        $balasan  = trim($_POST['balasan'] ?? '');
        if ($balasan === '') {
            setFlash('error', 'Balasan tidak boleh kosong.');
        } else {
            $stmt = $pdo->prepare("UPDATE konsultasi SET balasan=?, id_admin=?, status='dibalas', tanggal_balas=NOW() WHERE id_konsultasi=?");
            $stmt->execute([$balasan, $_SESSION['id_user'], $idKonsul]);
            setFlash('success', 'Balasan berhasil dikirim.');
        }
    }
    header("Location: konsultasi.php"); exit;
}

// ==============================
// HAPUS
// ==============================
if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM konsultasi WHERE id_konsultasi = ?")->execute([$id]);
    setFlash('success', 'Konsultasi berhasil dihapus.');
    header("Location: konsultasi.php"); exit;
}

// ==============================
// DETAIL (form balas)
// ==============================
$edit = null;
if ($aksi === 'balas' && $id > 0) {
    $stmt = $pdo->prepare("
        SELECT k.*, u.nama AS nama_user, u.username, p.nama_penyakit
        FROM konsultasi k
        JOIN users u ON u.id_user = k.id_user
        LEFT JOIN hasil_diagnosa h ON h.id_hasil = k.id_hasil
        LEFT JOIN penyakit p ON p.id_penyakit = h.id_penyakit
        WHERE k.id_konsultasi = ?
    ");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

// ==============================
// STATISTIK
// ==============================
try {
    $totalBaru    = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status='baru'")->fetchColumn();
    $totalDibaca  = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status='dibaca'")->fetchColumn();
    $totalDibalas = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status='dibalas'")->fetchColumn();
    $totalSemua   = (int) $pdo->query("SELECT COUNT(*) FROM konsultasi")->fetchColumn();
} catch (Throwable $e) {
    $totalBaru = $totalDibaca = $totalDibalas = $totalSemua = 0;
}

// ==============================
// LIST
// ==============================
try {
    $list = $pdo->query("
        SELECT k.*, u.nama AS nama_user, p.nama_penyakit
        FROM konsultasi k
        JOIN users u ON u.id_user = k.id_user
        LEFT JOIN hasil_diagnosa h ON h.id_hasil = k.id_hasil
        LEFT JOIN penyakit p ON p.id_penyakit = h.id_penyakit
        ORDER BY CASE WHEN k.status='baru' THEN 1 ELSE 2 END, k.tanggal_kirim DESC
    ")->fetchAll();
} catch (Throwable $e) {
    $list = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Manajemen Konsultasi</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Balas pesan konsultasi dari user — SIPAKAR-ISPA.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <!-- STATISTIK -->
    <div class="stats-grid">
        <div class="stat-card"><h3><?= $totalSemua ?></h3><p>Total Konsultasi</p></div>
        <div class="stat-card"><h3><?= $totalBaru ?></h3><p>Pesan Baru</p></div>
        <div class="stat-card"><h3><?= $totalDibaca ?></h3><p>Sudah Dibaca</p></div>
        <div class="stat-card"><h3><?= $totalDibalas ?></h3><p>Sudah Dibalas</p></div>
    </div>

    <!-- FORM BALAS -->
    <?php if ($edit): ?>
    <div class="container" style="max-width:100%; margin-top:24px; background:#e7f1ff; border-left:4px solid #0d6efd;">
        <h3>Balas Konsultasi</h3>

        <table style="background:#fff; width:100%;">
            <tr><td width="150"><strong>Dari</strong></td><td>: <?= htmlspecialchars($edit['nama_user']) ?> (@<?= htmlspecialchars($edit['username']) ?>)</td></tr>
            <tr><td><strong>Tanggal</strong></td><td>: <?= date('d/m/Y H:i', strtotime($edit['tanggal_kirim'])) ?></td></tr>
            <tr><td><strong>Subjek</strong></td><td>: <strong><?= htmlspecialchars($edit['subjek']) ?></strong></td></tr>
            <?php if (!empty($edit['nama_penyakit'])): ?>
                <tr><td><strong>Diagnosa Terkait</strong></td><td>: <?= htmlspecialchars($edit['nama_penyakit']) ?></td></tr>
            <?php endif; ?>
        </table>

        <div style="margin-top:16px;">
            <strong>Pesan dari User:</strong>
            <div style="background:#fff; padding:14px; border-radius:6px; margin-top:8px; font-size:14px; border-left:3px solid #0d6efd;">
                <?= nl2br(htmlspecialchars($edit['pesan'])) ?>
            </div>
        </div>

        <form method="POST" style="margin-top:20px;">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_konsultasi" value="<?= $edit['id_konsultasi'] ?>">

            <label>Balasan Admin</label>
            <textarea name="balasan" rows="6" required placeholder="Tulis balasan Anda di sini..."><?= htmlspecialchars($edit['balasan'] ?? '') ?></textarea>

            <button type="submit" name="balas" class="btn btn-success">Kirim Balasan</button>
            <a href="konsultasi.php" class="btn btn-warning">Batal</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- DAFTAR -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Konsultasi (<?= count($list) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Subjek</th>
                    <th>Diagnosa</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="7" style="text-align:center;">Belum ada konsultasi dari user.</td></tr>
                <?php else: foreach ($list as $i => $k):
                    if ($k['status'] === 'dibalas') { $warna = 'btn-success'; $label = 'Dibalas'; }
                    elseif ($k['status'] === 'dibaca') { $warna = 'btn-warning'; $label = 'Dibaca'; }
                    else { $warna = 'btn-danger'; $label = 'Baru'; }
                ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($k['tanggal_kirim'])) ?></td>
                        <td><?= htmlspecialchars($k['nama_user']) ?></td>
                        <td><?= htmlspecialchars($k['subjek']) ?></td>
                        <td><?= htmlspecialchars($k['nama_penyakit'] ?? '-') ?></td>
                        <td>
                            <span class="btn btn-sm <?= $warna ?>" style="cursor:default;">
                                <?= $label ?>
                            </span>
                        </td>
                        <td>
                            <a href="?aksi=balas&id=<?= $k['id_konsultasi'] ?>" class="btn btn-sm">Balas</a>
                            <a href="?aksi=hapus&id=<?= $k['id_konsultasi'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PANDUAN -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Panduan Menjawab Konsultasi</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li>Beri balasan yang <strong>sopan, jelas, dan profesional</strong>.</li>
            <li>Jangan memberi <strong>diagnosa pasti</strong> — sistem ini hanya diagnosa awal.</li>
            <li>Anjurkan <strong>kunjungan langsung</strong> ke Puskesmas bila perlu.</li>
            <li>Balas dalam <strong>1×24 jam kerja</strong>.</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>