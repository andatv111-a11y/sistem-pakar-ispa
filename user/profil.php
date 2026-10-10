<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];
$error = '';
$success = '';

// ==============================
// AUTO-CREATE KOLOM (kalau belum ada)
// ==============================
try {
    $kolomWajib = [
        'no_telepon'   => "VARCHAR(20) NULL",
        'alamat'       => "TEXT NULL",
        'tanggal_lahir'=> "DATE NULL",
        'jenis_kelamin'=> "ENUM('L','P') NULL",
        'foto'         => "VARCHAR(255) NULL",
        'nama_balita'  => "VARCHAR(100) NULL",
        'usia_balita'  => "INT NULL",
        'jk_balita'    => "ENUM('L','P') NULL",
    ];
    foreach ($kolomWajib as $kol => $tipe) {
        try {
            $cek = $pdo->query("SHOW COLUMNS FROM users LIKE '$kol'")->fetch();
            if (!$cek) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $kol $tipe");
            }
        } catch (Throwable $e) { /* skip kalau gagal */ }
    }
} catch (Throwable $e) { /* skip */ }

// ==============================
// PROSES FORM
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        // ---------- UPDATE PROFIL ----------
        if ($aksi === 'profil') {
            $nama          = trim($_POST['nama'] ?? '');
            $noTelepon     = trim($_POST['no_telepon'] ?? '');
            $alamat        = trim($_POST['alamat'] ?? '');
            $tglLahir      = trim($_POST['tanggal_lahir'] ?? '');
            $jenisKelamin  = in_array($_POST['jenis_kelamin'] ?? '', ['L','P'], true) ? $_POST['jenis_kelamin'] : null;
            $namaBalita    = trim($_POST['nama_balita'] ?? '');
            $usiaBalita    = (int)($_POST['usia_balita'] ?? 0);
            $jkBalita      = in_array($_POST['jk_balita'] ?? '', ['L','P'], true) ? $_POST['jk_balita'] : null;

            if ($nama === '') {
                $error = 'Nama tidak boleh kosong.';
            } else {
                // Upload foto (kalau ada)
                $namaFileFoto = null;
                if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                        $error = 'Format foto harus JPG, PNG, GIF, atau WEBP.';
                    } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                        $error = 'Ukuran foto maksimal 2 MB.';
                    } else {
                        $dirUpload = __DIR__ . '/../assets/img/profil/';
                        if (!is_dir($dirUpload)) mkdir($dirUpload, 0755, true);
                        $namaFileFoto = 'user_' . $idUser . '_' . time() . '.' . $ext;
                        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dirUpload . $namaFileFoto)) {
                            $error = 'Gagal upload foto.';
                        }
                    }
                }

                if ($error === '') {
                    try {
                        if ($namaFileFoto) {
                            // Hapus foto lama
                            $stmt = $pdo->prepare("SELECT foto FROM users WHERE id_user = ?");
                            $stmt->execute([$idUser]);
                            $fotoLama = $stmt->fetchColumn();
                            if ($fotoLama && file_exists(__DIR__ . '/../assets/img/profil/' . $fotoLama)) {
                                @unlink(__DIR__ . '/../assets/img/profil/' . $fotoLama);
                            }

                            $stmt = $pdo->prepare("
                                UPDATE users SET
                                    nama = ?, no_telepon = ?, alamat = ?,
                                    tanggal_lahir = ?, jenis_kelamin = ?, foto = ?,
                                    nama_balita = ?, usia_balita = ?, jk_balita = ?
                                WHERE id_user = ?
                            ");
                            $stmt->execute([
                                $nama, $noTelepon, $alamat,
                                $tglLahir ?: null, $jenisKelamin, $namaFileFoto,
                                $namaBalita ?: null, $usiaBalita ?: null, $jkBalita,
                                $idUser
                            ]);
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE users SET
                                    nama = ?, no_telepon = ?, alamat = ?,
                                    tanggal_lahir = ?, jenis_kelamin = ?,
                                    nama_balita = ?, usia_balita = ?, jk_balita = ?
                                WHERE id_user = ?
                            ");
                            $stmt->execute([
                                $nama, $noTelepon, $alamat,
                                $tglLahir ?: null, $jenisKelamin,
                                $namaBalita ?: null, $usiaBalita ?: null, $jkBalita,
                                $idUser
                            ]);
                        }
                        $_SESSION['nama'] = $nama;
                        $success = 'Profil berhasil diperbarui.';
                    } catch (Throwable $e) {
                        // Fallback: hanya nama
                        $stmt = $pdo->prepare("UPDATE users SET nama = ? WHERE id_user = ?");
                        $stmt->execute([$nama, $idUser]);
                        $_SESSION['nama'] = $nama;
                        $success = 'Nama berhasil diperbarui.';
                    }
                }
            }
        }

        // ---------- GANTI PASSWORD ----------
        elseif ($aksi === 'password') {
            $lama  = $_POST['password_lama'] ?? '';
            $baru  = $_POST['password_baru'] ?? '';
            $ulang = $_POST['password_ulang'] ?? '';

            if ($lama === '' || $baru === '') {
                $error = 'Semua field password wajib diisi.';
            } elseif (strlen($baru) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } elseif ($baru !== $ulang) {
                $error = 'Konfirmasi password baru tidak cocok.';
            } elseif ($baru === $lama) {
                $error = 'Password baru tidak boleh sama dengan password lama.';
            } else {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id_user = ?");
                $stmt->execute([$idUser]);
                $hash = $stmt->fetchColumn();

                if (!password_verify($lama, $hash)) {
                    $error = 'Password lama salah.';
                } else {
                    $newHash = password_hash($baru, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $stmt->execute([$newHash, $idUser]);
                    $success = 'Password berhasil diubah. Silakan ingat password baru Anda.';
                }
            }
        }
    }
}

// ==============================
// AMBIL DATA USER
// ==============================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$idUser]);
$user = $stmt->fetch();

// Statistik
$stmt = $pdo->prepare("SELECT COUNT(*), AVG(nilai_akhir) FROM hasil_diagnosa WHERE id_user = ?");
$stmt->execute([$idUser]);
$stat = $stmt->fetch(PDO::FETCH_NUM);
$totalDiagnosa = (int)($stat[0] ?? 0);
$rataKeyakinan = (float)($stat[1] ?? 0);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>👤 Profil Saya</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola informasi akun, data diri, dan data balita Anda
    </p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- ============================== -->
    <!-- KARTU INFO SINGKAT -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; background:linear-gradient(135deg,#e7f1ff,#f0f7ff);">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <?php if (!empty($user['foto']) && file_exists(__DIR__ . '/../assets/img/profil/' . $user['foto'])): ?>
                <img src="<?= BASE_URL ?>assets/img/profil/<?= htmlspecialchars($user['foto']) ?>"
                     alt="Foto Profil"
                     style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #0d6efd;">
            <?php else: ?>
                <div style="width:90px; height:90px; background:#0d6efd; color:#fff; border-radius:50%; display:flex; justify-content:center; align-items:center; font-size:40px; font-weight:bold; border:3px solid #0d6efd;">
                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                </div>
            <?php endif; ?>

            <div style="flex:1; min-width:200px;">
                <h3 style="margin:0;"><?= htmlspecialchars($user['nama'] ?? '-') ?></h3>
                <p style="font-size:14px; color:#666; margin-top:4px;">
                    @<?= htmlspecialchars($user['username'] ?? '-') ?>
                    &nbsp;|&nbsp;
                    Role: <strong><?= htmlspecialchars($user['role'] ?? 'user') ?></strong>
                </p>
                <p style="font-size:13px; color:#888; margin-top:4px;">
                    📅 Bergabung sejak
                    <?= !empty($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : '-' ?>
                </p>
                <?php if (!empty($user['no_telepon'])): ?>
                <p style="font-size:13px; color:#666; margin-top:2px;">
                    📞 <?= htmlspecialchars($user['no_telepon']) ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($user['alamat'])): ?>
                <p style="font-size:13px; color:#666; margin-top:2px;">
                    📍 <?= htmlspecialchars($user['alamat']) ?>
                </p>
                <?php endif; ?>
            </div>

            <div style="text-align:center; padding:10px 20px; background:#fff; border-radius:10px;">
                <div style="font-size:36px; font-weight:bold; color:#0d6efd;"><?= $totalDiagnosa ?></div>
                <div style="font-size:12px; color:#666;">Total Diagnosa</div>
                <?php if ($totalDiagnosa > 0): ?>
                    <div style="font-size:13px; color:#198754; margin-top:4px;">
                        ⭐ <?= round($rataKeyakinan*100, 1) ?>% avg
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================== -->
    <!-- FORM EDIT PROFIL -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>✏️ Edit Profil</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="aksi" value="profil">

            <!-- SECTION: DATA DIRI -->
            <h4 style="color:#0d6efd; margin-top:16px;">👤 Data Diri</h4>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                <div>
                    <label>Nama Lengkap <span style="color:#dc3545;">*</span></label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                </div>
                <div>
                    <label>Username (tidak bisa diubah)</label>
                    <input type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                           disabled style="background:#f5f5f5; cursor:not-allowed;">
                </div>
                <div>
                    <label>No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telepon"
                           value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>"
                           placeholder="Contoh: 08123456789">
                </div>
                <div>
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir"
                           value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>">
                </div>
                <div>
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= ($user['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($user['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label>Foto Profil (maks 2 MB)</label>
                    <input type="file" name="foto" accept="image/*">
                    <?php if (!empty($user['foto'])): ?>
                        <p style="font-size:12px; color:#666; margin-top:4px;">
                            Foto saat ini: <?= htmlspecialchars($user['foto']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <label>Alamat Lengkap</label>
            <textarea name="alamat" rows="2"
                      placeholder="Contoh: Desa Kedungpring RT 01 RW 02, Kec. Kedungpring, Kab. Lamongan"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>

            <!-- SECTION: DATA BALITA -->
            <h4 style="color:#198754; margin-top:24px;">👶 Data Balita (Opsional)</h4>
            <p style="font-size:13px; color:#666; margin-bottom:12px;">
                Isi data balita Anda agar dapat dipakai otomatis saat diagnosa berikutnya
            </p>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                <div>
                    <label>Nama Balita</label>
                    <input type="text" name="nama_balita"
                           value="<?= htmlspecialchars($user['nama_balita'] ?? '') ?>"
                           placeholder="Contoh: An. Ahmad">
                </div>
                <div>
                    <label>Usia Balita (bulan)</label>
                    <input type="number" name="usia_balita" min="0" max="60"
                           value="<?= htmlspecialchars($user['usia_balita'] ?? '') ?>"
                           placeholder="Contoh: 24">
                </div>
                <div>
                    <label>Jenis Kelamin Balita</label>
                    <select name="jk_balita">
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= ($user['jk_balita'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($user['jk_balita'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-success" style="font-size:15px; padding:12px 24px;">
                    💾 Simpan Perubahan
                </button>
                <a href="profil.php" class="btn btn-warning">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- ============================== -->
    <!-- FORM GANTI PASSWORD -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>🔒 Ganti Password</h3>
        <p style="font-size:13px; color:#666; margin-bottom:12px;">
            Gunakan password yang kuat: minimal 6 karakter, kombinasi huruf + angka
        </p>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="aksi" value="password">

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                <div>
                    <label>Password Lama <span style="color:#dc3545;">*</span></label>
                    <input type="password" name="password_lama" required
                           placeholder="Masukkan password saat ini">
                </div>
                <div>
                    <label>Password Baru <span style="color:#dc3545;">*</span></label>
                    <input type="password" name="password_baru" minlength="6" required
                           placeholder="Min. 6 karakter">
                </div>
                <div>
                    <label>Konfirmasi Password Baru <span style="color:#dc3545;">*</span></label>
                    <input type="password" name="password_ulang" minlength="6" required
                           placeholder="Ulangi password baru">
                </div>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-warning" style="font-size:15px; padding:12px 24px;">
                    🔑 Ganti Password
                </button>
            </div>
        </form>
    </div>

    <!-- ============================== -->
    <!-- INFO AKUN -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>ℹ️ Informasi Akun</h3>
        <table>
            <tr><td width="220">ID User</td><td>: <?= htmlspecialchars($user['id_user'] ?? '-') ?></td></tr>
            <tr><td>Username</td><td>: <?= htmlspecialchars($user['username'] ?? '-') ?></td></tr>
            <tr><td>Role</td><td>: <?= htmlspecialchars($user['role'] ?? '-') ?></td></tr>
            <tr><td>Tanggal Bergabung</td><td>: <?= !empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-' ?></td></tr>
            <tr><td>Terakhir Update</td><td>: <?= !empty($user['updated_at']) ? date('d/m/Y H:i', strtotime($user['updated_at'])) : '-' ?></td></tr>
            <tr><td>Total Diagnosa</td><td>: <strong><?= $totalDiagnosa ?></strong></td></tr>
            <tr><td>Rata-rata Keyakinan</td><td>: <?= $totalDiagnosa > 0 ? round($rataKeyakinan*100, 2).'%' : '-' ?></td></tr>
        </table>
    </div>

    <!-- ============================== -->
    <!-- TIPS KEAMANAN -->
    <!-- ============================== -->
    <div class="container" style="max-width:100%; margin-top:24px; background:#fff3cd; border:1px solid #ffd54f;">
        <h3 style="color:#664d03;">🔐 Tips Keamanan Akun</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px; color:#664d03;">
            <li>Jangan bagikan password ke siapa pun, termasuk admin</li>
            <li>Gunakan password unik yang tidak dipakai di akun lain</li>
            <li>Ganti password secara berkala (minimal 3 bulan sekali)</li>
            <li>Logout setelah selesai menggunakan, terutama di HP/komputer bersama</li>
            <li>Jangan gunakan WiFi publik tanpa VPN untuk login</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>