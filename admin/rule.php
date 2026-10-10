<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: rule.php"); exit;
    }
    $idPenyakit = (int)$_POST['id_penyakit'];
    $idGejala   = (int)$_POST['id_gejala'];
    $prob       = (float)$_POST['probabilitas'];
    $mb         = (float)$_POST['mb'];
    $md         = (float)$_POST['md'];
    $idEdit     = (int)($_POST['id_rule'] ?? 0);

    if ($idPenyakit <= 0 || $idGejala <= 0) {
        setFlash('error', 'Penyakit & Gejala wajib dipilih.');
        header("Location: rule.php"); exit;
    }

    if ($idEdit > 0) {
        $stmt = $pdo->prepare("UPDATE rule_basis SET id_penyakit=?, id_gejala=?, probabilitas=?, mb=?, md=? WHERE id_rule=?");
        $stmt->execute([$idPenyakit, $idGejala, $prob, $mb, $md, $idEdit]);
        setFlash('success', 'Basis rule berhasil diperbarui.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO rule_basis (id_penyakit, id_gejala, probabilitas, mb, md) VALUES (?,?,?,?,?)");
        $stmt->execute([$idPenyakit, $idGejala, $prob, $mb, $md]);
        setFlash('success', 'Basis rule berhasil ditambahkan.');
    }
    header("Location: rule.php"); exit;
}

if ($aksi === 'hapus' && $id > 0) {
    $pdo->prepare("DELETE FROM rule_basis WHERE id_rule = ?")->execute([$id]);
    setFlash('success', 'Basis rule berhasil dihapus.');
    header("Location: rule.php"); exit;
}

$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM rule_basis WHERE id_rule = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

$penyakitList = $pdo->query("SELECT * FROM penyakit ORDER BY kode_penyakit")->fetchAll();
$gejalaList   = $pdo->query("SELECT * FROM gejala ORDER BY kode_gejala")->fetchAll();

$rules = $pdo->query("
    SELECT r.*, p.kode_penyakit, p.nama_penyakit, g.kode_gejala, g.nama_gejala
    FROM rule_basis r
    JOIN penyakit p ON p.id_penyakit = r.id_penyakit
    JOIN gejala g ON g.id_gejala = r.id_gejala
    ORDER BY p.kode_penyakit, g.kode_gejala
")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Basis Rule — Naive Bayes & Certainty Factor</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Rule penghubung antara gejala klinis ISPA dan penyakit, beserta nilai probabilitas (Naive Bayes)
        dan MB/MD (Certainty Factor).
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? 'Edit Rule Basis Pengetahuan' : 'Tambah Rule Basis Pengetahuan' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_rule" value="<?= $edit['id_rule'] ?? 0 ?>">

            <label>Penyakit ISPA</label>
            <select name="id_penyakit" required>
                <option value="">-- Pilih Penyakit ISPA --</option>
                <?php foreach ($penyakitList as $p): ?>
                    <option value="<?= $p['id_penyakit'] ?>" <?= (($edit['id_penyakit'] ?? 0) == $p['id_penyakit']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['kode_penyakit'] . ' - ' . $p['nama_penyakit']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Gejala Klinis</label>
            <select name="id_gejala" required>
                <option value="">-- Pilih Gejala Klinis --</option>
                <?php foreach ($gejalaList as $g): ?>
                    <option value="<?= $g['id_gejala'] ?>" <?= (($edit['id_gejala'] ?? 0) == $g['id_gejala']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['kode_gejala'] . ' - ' . $g['nama_gejala']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Probabilitas (0 – 1) — untuk Naive Bayes</label>
            <input type="number" name="probabilitas" step="0.0001" min="0" max="1"
                   value="<?= $edit['probabilitas'] ?? '0.5' ?>" required>

            <label>MB (Measure of Belief, 0 – 1) — untuk Certainty Factor</label>
            <input type="number" name="mb" step="0.01" min="0" max="1"
                   value="<?= $edit['mb'] ?? '0.5' ?>" required>

            <label>MD (Measure of Disbelief, 0 – 1) — untuk Certainty Factor</label>
            <input type="number" name="md" step="0.01" min="0" max="1"
                   value="<?= $edit['md'] ?? '0.2' ?>" required>

            <button type="submit" name="simpan" class="btn btn-success"><?= $edit ? 'Update Rule' : 'Simpan Rule' ?></button>
            <?php if ($edit): ?><a href="rule.php" class="btn btn-warning">Batal</a><?php endif; ?>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar Basis Rule SIPAKAR-ISPA</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Penyakit ISPA</th><th>Gejala Klinis</th>
                    <th>Probabilitas</th><th>MB</th><th>MD</th><th>CF Pakar</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="8" style="text-align:center;">Belum ada basis rule.</td></tr>
                <?php else: foreach ($rules as $i => $r):
                    $cfPakar = $r['mb'] - $r['md']; ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($r['kode_penyakit'].' - '.$r['nama_penyakit']) ?></td>
                        <td><?= htmlspecialchars($r['kode_gejala'].' - '.$r['nama_gejala']) ?></td>
                        <td><?= $r['probabilitas'] ?></td>
                        <td><?= $r['mb'] ?></td>
                        <td><?= $r['md'] ?></td>
                        <td><strong><?= number_format($cfPakar, 2) ?></strong></td>
                        <td>
                            <a href="?aksi=edit&id=<?= $r['id_rule'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?aksi=hapus&id=<?= $r['id_rule'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>