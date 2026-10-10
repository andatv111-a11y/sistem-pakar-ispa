<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: penyakit.php"); exit;
    }
    $kode   = trim($_POST['kode_penyakit']);
    $nama   = trim($_POST['nama_penyakit']);
    $desk   = trim($_POST['deskripsi']);
    $sol    = trim($_POST['solusi']);
    $idEdit = (int)($_POST['id_penyakit'] ?? 0);

    if ($kode === '' || $nama === '') {
        setFlash('error', 'Kode & Nama Penyakit ISPA wajib diisi.');
        header("Location: penyakit.php"); exit;
    }

    if ($idEdit > 0) {
        $stmt = $pdo->prepare("UPDATE penyakit SET kode_penyakit=?, nama_penyakit=?, deskripsi=?, solusi=? WHERE id_penyakit=?");
        $stmt->execute([$kode, $nama, $desk, $sol, $idEdit]);
        setFlash('success', 'Data penyakit ISPA berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO penyakit (kode_penyakit, nama_penyakit, deskripsi, solusi) VALUES (?,?,?,?)");
        $stmt->execute([$kode, $nama, $desk, $sol]);
        setFlash('success', 'Data penyakit ISPA berhasil ditambahkan.');
    }
    header("Location: penyakit.php"); exit;
}

if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM penyakit WHERE id_penyakit = ?")->execute([$id]);
    setFlash('success', 'Data penyakit ISPA berhasil dihapus.');
    header("Location: penyakit.php"); exit;
}

$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM penyakit WHERE id_penyakit = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

$list = $pdo->query("SELECT * FROM penyakit ORDER BY kode_penyakit")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Data Penyakit ISPA pada Balita</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola data jenis penyakit ISPA yang menjadi basis pengetahuan sistem pakar SIPAKAR-ISPA.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? 'Edit Penyakit ISPA' : 'Tambah Penyakit ISPA' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_penyakit" value="<?= $edit['id_penyakit'] ?? 0 ?>">

            <label>Kode Penyakit</label>
            <input type="text" name="kode_penyakit" value="<?= htmlspecialchars($edit['kode_penyakit'] ?? '') ?>"
                   placeholder="Contoh: P01" required>

            <label>Nama Penyakit ISPA</label>
            <input type="text" name="nama_penyakit" value="<?= htmlspecialchars($edit['nama_penyakit'] ?? '') ?>"
                   placeholder="Contoh: ISPA Ringan (Common Cold)" required>

            <label>Deskripsi Klinis</label>
            <textarea name="deskripsi" rows="3"
                      placeholder="Penjelasan medis singkat mengenai penyakit"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>

            <label>Solusi / Penanganan Awal</label>
            <textarea name="solusi" rows="3"
                      placeholder="Rekomendasi penanganan awal di Puskesmas Kedungpring"><?= htmlspecialchars($edit['solusi'] ?? '') ?></textarea>

            <button type="submit" name="simpan" class="btn btn-success">
                <?= $edit ? 'Update Data' : 'Simpan Data' ?>
            </button>
            <?php if ($edit): ?>
                <a href="penyakit.php" class="btn btn-warning">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Penyakit ISPA</h3>
        <table>
            <thead>
                <tr><th>No</th><th>Kode</th><th>Nama Penyakit ISPA</th><th>Deskripsi</th><th>Solusi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada data penyakit ISPA.</td></tr>
                <?php else: foreach ($list as $i => $p): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($p['kode_penyakit']) ?></td>
                        <td><?= htmlspecialchars($p['nama_penyakit']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($p['deskripsi'] ?? '', 0, 60, '...')) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($p['solusi'] ?? '', 0, 60, '...')) ?></td>
                        <td>
                            <a href="?aksi=edit&id=<?= $p['id_penyakit'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?aksi=hapus&id=<?= $p['id_penyakit'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>