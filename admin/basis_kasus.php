<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: basis_kasus.php"); exit;
    }
    $idPenyakit = (int)($_POST['id_penyakit'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $gejalaIds  = array_map('intval', $_POST['gejala'] ?? []);

    if ($idPenyakit <= 0 || empty($gejalaIds)) {
        setFlash('error', 'Penyakit & minimal 1 gejala klinis wajib dipilih.');
        header("Location: basis_kasus.php"); exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO kasus (id_penyakit, keterangan) VALUES (?,?)");
        $stmt->execute([$idPenyakit, $keterangan]);
        $idKasus = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("INSERT INTO kasus_detail (id_kasus, id_gejala) VALUES (?,?)");
        foreach ($gejalaIds as $g) $stmt2->execute([$idKasus, $g]);

        $pdo->commit();
        setFlash('success', 'Basis kasus balita berhasil ditambahkan.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Gagal: ' . $e->getMessage());
    }
    header("Location: basis_kasus.php"); exit;
}

if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM kasus WHERE id_kasus = ?")->execute([$id]);
    setFlash('success', 'Basis kasus berhasil dihapus.');
    header("Location: basis_kasus.php"); exit;
}

$penyakitList = $pdo->query("SELECT * FROM penyakit ORDER BY kode_penyakit")->fetchAll();
$gejalaList   = $pdo->query("SELECT * FROM gejala ORDER BY kode_gejala")->fetchAll();

$kasusList = $pdo->query("
    SELECT k.*, p.kode_penyakit, p.nama_penyakit,
        (SELECT COUNT(*) FROM kasus_detail kd WHERE kd.id_kasus = k.id_kasus) AS jml_gejala
    FROM kasus k
    JOIN penyakit p ON p.id_penyakit = k.id_penyakit
    ORDER BY k.id_kasus DESC
")->fetchAll();

$statPerPenyakit = $pdo->query("
    SELECT p.kode_penyakit, p.nama_penyakit, COUNT(k.id_kasus) AS jml
    FROM penyakit p
    LEFT JOIN kasus k ON k.id_penyakit = p.id_penyakit
    GROUP BY p.id_penyakit
    ORDER BY p.kode_penyakit
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Basis Kasus — Data Training Naive Bayes</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Data kasus balita penderita ISPA di Puskesmas Kedungpring yang dipakai sebagai data training
        untuk menghitung probabilitas <em>prior</em> pada algoritma Naive Bayes.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3>Tambah Kasus Balita Baru</h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

            <label>Diagnosa Penyakit ISPA</label>
            <select name="id_penyakit" required>
                <option value="">-- Pilih Penyakit ISPA --</option>
                <?php foreach ($penyakitList as $p): ?>
                    <option value="<?= $p['id_penyakit'] ?>">
                        <?= htmlspecialchars($p['kode_penyakit'].' - '.$p['nama_penyakit']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Keterangan Pasien (opsional)</label>
            <input type="text" name="keterangan" placeholder="Contoh: Pasien A, usia 2 tahun, laki-laki">

            <label>Gejala Klinis yang Dialami Balita (min 1)</label>
            <div class="gejala-grid">
                <?php foreach ($gejalaList as $g): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="gejala[]" value="<?= $g['id_gejala'] ?>">
                        <span><?= htmlspecialchars($g['kode_gejala'].' - '.$g['nama_gejala']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="simpan" class="btn btn-success">Simpan Kasus Balita</button>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Statistik Jumlah Kasus per Penyakit ISPA (Prior Naive Bayes)</h3>
        <table>
            <thead><tr><th>Kode</th><th>Penyakit ISPA</th><th>Jumlah Kasus</th></tr></thead>
            <tbody>
                <?php foreach ($statPerPenyakit as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['kode_penyakit']) ?></td>
                        <td><?= htmlspecialchars($s['nama_penyakit']) ?></td>
                        <td><strong><?= $s['jml'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Kasus Balita Penderita ISPA</h3>
        <table>
            <thead>
                <tr><th>No</th><th>Penyakit ISPA</th><th>Keterangan Pasien</th><th>Jml Gejala</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (empty($kasusList)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada data kasus.</td></tr>
                <?php else: foreach ($kasusList as $i => $k): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($k['kode_penyakit'].' - '.$k['nama_penyakit']) ?></td>
                        <td><?= htmlspecialchars($k['keterangan'] ?? '-') ?></td>
                        <td><?= $k['jml_gejala'] ?></td>
                        <td>
                            <a href="?aksi=hapus&id=<?= $k['id_kasus'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>