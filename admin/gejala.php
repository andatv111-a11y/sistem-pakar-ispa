<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: gejala.php"); exit;
    }
    $kode   = trim($_POST['kode_gejala']);
    $nama   = trim($_POST['nama_gejala']);
    $idEdit = (int)($_POST['id_gejala'] ?? 0);

    if ($kode === '' || $nama === '') {
        setFlash('error', 'Kode & Nama gejala wajib diisi.');
        header("Location: gejala.php"); exit;
    }

    if ($idEdit > 0) {
        $stmt = $pdo->prepare("UPDATE gejala SET kode_gejala=?, nama_gejala=? WHERE id_gejala=?");
        $stmt->execute([$kode, $nama, $idEdit]);
        setFlash('success', 'Data gejala klinis berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO gejala (kode_gejala, nama_gejala) VALUES (?,?)");
        $stmt->execute([$kode, $nama]);
        setFlash('success', 'Data gejala klinis berhasil ditambahkan.');
    }
    header("Location: gejala.php"); exit;
}

if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM gejala WHERE id_gejala = ?")->execute([$id]);
    setFlash('success', 'Data gejala klinis berhasil dihapus.');
    header("Location: gejala.php"); exit;
}

$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM gejala WHERE id_gejala = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

$list = $pdo->query("SELECT * FROM gejala ORDER BY kode_gejala")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Data Gejala Klinis ISPA</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Daftar gejala klinis yang tampak pada balita penderita ISPA di Puskesmas Kedungpring.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? 'Edit Gejala Klinis' : 'Tambah Gejala Klinis' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_gejala" value="<?= $edit['id_gejala'] ?? 0 ?>">

            <label>Kode Gejala</label>
            <input type="text" name="kode_gejala" value="<?= htmlspecialchars($edit['kode_gejala'] ?? '') ?>"
                   placeholder="Contoh: G01" required>

            <label>Nama Gejala Klinis</label>
            <input type="text" name="nama_gejala" value="<?= htmlspecialchars($edit['nama_gejala'] ?? '') ?>"
                   placeholder="Contoh: Demam" required>

            <button type="submit" name="simpan" class="btn btn-success"><?= $edit ? 'Update Data' : 'Simpan Data' ?></button>
            <?php if ($edit): ?><a href="gejala.php" class="btn btn-warning">Batal</a><?php endif; ?>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Gejala Klinis ISPA</h3>
        <table>
            <thead><tr><th>No</th><th>Kode Gejala</th><th>Nama Gejala Klinis</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="4" style="text-align:center;">Belum ada data gejala.</td></tr>
                <?php else: foreach ($list as $i => $g): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($g['kode_gejala']) ?></td>
                        <td><?= htmlspecialchars($g['nama_gejala']) ?></td>
                        <td>
                            <a href="?aksi=edit&id=<?= $g['id_gejala'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?aksi=hapus&id=<?= $g['id_gejala'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>