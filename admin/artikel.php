<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: artikel.php"); exit;
    }
    $judul    = trim($_POST['judul']);
    $kategori = trim($_POST['kategori']);
    $konten   = trim($_POST['konten']);
    $idEdit   = (int)($_POST['id_artikel'] ?? 0);

    if ($judul === '' || $konten === '') {
        setFlash('error', 'Judul & konten wajib diisi.');
        header("Location: artikel.php"); exit;
    }

    if ($idEdit > 0) {
        $stmt = $pdo->prepare("UPDATE artikel SET judul=?, kategori=?, konten=? WHERE id_artikel=?");
        $stmt->execute([$judul, $kategori, $konten, $idEdit]);
        setFlash('success', 'Artikel berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO artikel (judul, kategori, konten) VALUES (?,?,?)");
        $stmt->execute([$judul, $kategori, $konten]);
        setFlash('success', 'Artikel berhasil ditambahkan.');
    }
    header("Location: artikel.php"); exit;
}

if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM artikel WHERE id_artikel = ?")->execute([$id]);
    setFlash('success', 'Artikel berhasil dihapus.');
    header("Location: artikel.php"); exit;
}

$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id_artikel = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

try {
    $list = $pdo->query("SELECT * FROM artikel ORDER BY tanggal DESC")->fetchAll();
} catch (Throwable $e) {
    $list = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Artikel Edukasi ISPA</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola artikel edukasi tentang ISPA pada balita — tampil di halaman user.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? 'Edit Artikel' : 'Tambah Artikel Baru' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_artikel" value="<?= $edit['id_artikel'] ?? 0 ?>">

            <label>Judul Artikel</label>
            <input type="text" name="judul" required value="<?= htmlspecialchars($edit['judul'] ?? '') ?>">

            <label>Kategori</label>
            <input type="text" name="kategori" value="<?= htmlspecialchars($edit['kategori'] ?? '') ?>" placeholder="Contoh: Pencegahan, Dasar">

            <label>Konten Artikel</label>
            <textarea name="konten" rows="8" required placeholder="Tulis konten lengkap..."><?= htmlspecialchars($edit['konten'] ?? '') ?></textarea>

            <button type="submit" name="simpan" class="btn btn-success"><?= $edit ? 'Update' : 'Simpan' ?></button>
            <?php if ($edit): ?><a href="artikel.php" class="btn btn-warning">Batal</a><?php endif; ?>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Artikel (<?= count($list) ?>)</h3>
        <table>
            <thead><tr><th>No</th><th>Judul</th><th>Kategori</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada artikel.</td></tr>
                <?php else: foreach ($list as $i => $a): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($a['judul']) ?></td>
                        <td><?= htmlspecialchars($a['kategori'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                        <td>
                            <a href="?aksi=edit&id=<?= $a['id_artikel'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?aksi=hapus&id=<?= $a['id_artikel'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>