<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$aksi = $_GET['aksi'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
        header("Location: users.php"); exit;
    }
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role     = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $idEdit   = (int)($_POST['id_user'] ?? 0);

    if ($nama === '' || $username === '') {
        setFlash('error', 'Nama & username wajib diisi.');
        header("Location: users.php"); exit;
    }

    if ($idEdit > 0) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, role=?, password=? WHERE id_user=?");
            $stmt->execute([$nama, $username, $role, $hash, $idEdit]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, role=? WHERE id_user=?");
            $stmt->execute([$nama, $username, $role, $idEdit]);
        }
        setFlash('success', 'User berhasil diperbarui.');
    } else {
        if ($password === '') {
            setFlash('error', 'Password wajib diisi untuk user baru.');
            header("Location: users.php"); exit;
        }
        $cek = $pdo->prepare("SELECT id_user FROM users WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetch()) {
            setFlash('error', 'Username sudah digunakan.');
            header("Location: users.php"); exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)");
        $stmt->execute([$nama, $username, $hash, $role]);
        setFlash('success', 'User berhasil ditambahkan.');
    }
    header("Location: users.php"); exit;
}

if ($aksi === 'hapus' && $id > 0 && $id != $_SESSION['id_user']) {
    $pdo->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
    setFlash('success', 'User berhasil dihapus.');
    header("Location: users.php"); exit;
}

$edit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}

$list = $pdo->query("SELECT * FROM users ORDER BY role, nama")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Manajemen User</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola akun admin &amp; user SIPAKAR-ISPA.
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <div class="container" style="max-width:100%;">
        <h3><?= $edit ? 'Edit User' : 'Tambah User' ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="id_user" value="<?= $edit['id_user'] ?? 0 ?>">

            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" required>

            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($edit['username'] ?? '') ?>" required>

            <label>Password <?= $edit ? '(biarkan kosong jika tidak diubah)' : '' ?></label>
            <input type="password" name="password" <?= $edit ? '' : 'required' ?> minlength="6">

            <label>Role</label>
            <select name="role" required>
                <option value="user" <?= (($edit['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= (($edit['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit" name="simpan" class="btn btn-success"><?= $edit ? 'Update Data' : 'Simpan Data' ?></button>
            <?php if ($edit): ?><a href="users.php" class="btn btn-warning">Batal</a><?php endif; ?>
        </form>
    </div>

    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>Daftar User</h3>
        <table>
            <thead>
                <tr><th>No</th><th>Nama</th><th>Username</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" style="text-align:center;">Belum ada user.</td></tr>
                <?php else: foreach ($list as $i => $u): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($u['nama']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <span class="btn btn-sm <?= $u['role']==='admin' ? 'btn-danger' : 'btn-success' ?>" style="cursor:default;">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="?aksi=edit&id=<?= $u['id_user'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                                <a href="?aksi=hapus&id=<?= $u['id_user'] ?>" class="btn btn-danger btn-sm confirm-delete">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>