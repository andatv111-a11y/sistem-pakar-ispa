<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

// Tambah reminder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
    } else {
        $judul = trim($_POST['judul'] ?? '');
        $ket   = trim($_POST['keterangan'] ?? '');
        $tgl   = $_POST['tanggal'] ?? '';
        $wkt   = $_POST['waktu'] ?: null;

        if ($judul === '' || $tgl === '') {
            setFlash('error', 'Judul & tanggal wajib diisi.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO reminder (id_user, judul, keterangan, tanggal, waktu) VALUES (?,?,?,?,?)");
            $stmt->execute([$idUser, $judul, $ket, $tgl, $wkt]);
            setFlash('success', 'Reminder ditambahkan.');
        }
    }
    header("Location: reminder.php"); exit;
}

// Update status
if (isset($_GET['selesai'])) {
    $idR = (int)$_GET['selesai'];
    $pdo->prepare("UPDATE reminder SET status='selesai' WHERE id_reminder=? AND id_user=?")->execute([$idR, $idUser]);
    setFlash('success', 'Reminder ditandai selesai.');
    header("Location: reminder.php"); exit;
}

if (isset($_GET['hapus'])) {
    $idR = (int)$_GET['hapus'];
    $pdo->prepare("DELETE FROM reminder WHERE id_reminder=? AND id_user=?")->execute([$idR, $idUser]);
    setFlash('success', 'Reminder dihapus.');
    header("Location: reminder.php"); exit;
}

// Ambil list
$list = $pdo->prepare("SELECT * FROM reminder WHERE id_user = ? ORDER BY tanggal ASC, waktu ASC");
$list->execute([$idUser]);
$list = $list->fetchAll();

// Reminder yang mendekati (7 hari)
$upcoming = array_filter($list, function($r) {
    return $r['status'] === 'pending'
        && strtotime($r['tanggal']) >= strtotime('today')
        && strtotime($r['tanggal']) <= strtotime('+7 days');
});

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>🗓️ Reminder Kontrol & Pengingat</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Buat pengingat untuk kontrol, imunisasi, atau pemberian obat
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <?php if (!empty($upcoming)): ?>
    <div class="container" style="max-width:100%; background:#fff3cd; border:1px solid #ffd54f;">
        <h3 style="color:#664d03;">⏰ Pengingat 7 Hari ke Depan (<?= count($upcoming) ?>)</h3>
        <ul style="line-height:1.9; padding-left:20px; color:#664d03;">
            <?php foreach ($upcoming as $u): ?>
                <li>
                    <strong><?= date('d/m/Y', strtotime($u['tanggal'])) ?></strong>
                    <?= $u['waktu'] ? ' jam ' . date('H:i', strtotime($u['waktu'])) : '' ?>
                    — <?= htmlspecialchars($u['judul']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>➕ Tambah Reminder</h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

            <label>Judul Reminder</label>
            <input type="text" name="judul" required placeholder="Contoh: Kontrol balita ke Puskesmas">

            <label>Keterangan</label>
            <textarea name="keterangan" rows="2" placeholder="Catatan tambahan..."></textarea>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required>
                </div>
                <div>
                    <label>Waktu (opsional)</label>
                    <input type="time" name="waktu">
                </div>
            </div>

            <button type="submit" name="tambah" class="btn btn-success">💾 Simpan Reminder</button>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>📋 Daftar Reminder (<?= count($list) ?>)</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Tanggal</th><th>Judul</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada reminder.</td></tr>
                <?php else: foreach ($list as $i => $r):
                    $lewat = strtotime($r['tanggal']) < strtotime('today') && $r['status'] === 'pending';
                ?>
                    <tr style="<?= $lewat ? 'background:#fff3cd;' : '' ?>">
                        <td><?= $i+1 ?></td>
                        <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?> <?= $r['waktu'] ? date('H:i', strtotime($r['waktu'])) : '' ?></td>
                        <td><strong><?= htmlspecialchars($r['judul']) ?></strong></td>
                        <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                        <td>
                            <?php if ($r['status'] === 'selesai'): ?>
                                <span class="btn btn-sm btn-success">✅ Selesai</span>
                            <?php elseif ($lewat): ?>
                                <span class="btn btn-sm btn-danger">⚠️ Terlewat</span>
                            <?php else: ?>
                                <span class="btn btn-sm btn-warning">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['status'] === 'pending'): ?>
                                <a href="?selesai=<?= $r['id_reminder'] ?>" class="btn btn-sm btn-success">✔️ Selesai</a>
                            <?php endif; ?>
                            <a href="?hapus=<?= $r['id_reminder'] ?>" class="btn btn-sm btn-danger confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>