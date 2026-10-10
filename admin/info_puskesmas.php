<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pesan = '';
$tipe  = '';

function bersihkanKoordinat($input) {
    $input = str_replace(',', '.', trim($input));
    $input = preg_replace('/[^0-9.\-]/', '', $input);
    if (preg_match('/-?\d+(\.\d+)?/', $input, $m)) return $m[0];
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        $pesan = 'Token tidak valid.';
        $tipe  = 'error';
    } else {
        $latitude  = bersihkanKoordinat($_POST['latitude'] ?? '');
        $longitude = bersihkanKoordinat($_POST['longitude'] ?? '');

        if ($latitude !== '' && $longitude !== '') {
            $latVal = (float)$latitude;
            $lonVal = (float)$longitude;
            if (abs($latVal) > 90 && abs($lonVal) <= 90) {
                $temp = $latitude;
                $latitude = $longitude;
                $longitude = $temp;
                $pesan = 'Latitude & Longitude terdeteksi kebalik — sudah dikoreksi. ';
            }
        }

        $latFinal = $latitude  !== '' ? (float)$latitude  : null;
        $lonFinal = $longitude !== '' ? (float)$longitude : null;

        if ($latFinal !== null && ($latFinal < -90 || $latFinal > 90)) {
            $pesan .= 'Latitude harus antara -90 s/d 90. ';
            $tipe = 'error';
        }
        if ($lonFinal !== null && ($lonFinal < -180 || $lonFinal > 180)) {
            $pesan .= 'Longitude harus antara -180 s/d 180. ';
            $tipe = 'error';
        }

        if ($tipe !== 'error') {
            try {
                $cek = $pdo->query("SELECT COUNT(*) FROM info_puskesmas")->fetchColumn();
                if ((int)$cek > 0) {
                    $stmt = $pdo->prepare("UPDATE info_puskesmas SET nama=?, alamat=?, telepon=?, email=?, jam_senin_jumat=?, jam_sabtu=?, jam_minggu=?, latitude=?, longitude=?, layanan=? WHERE id=1");
                    $stmt->execute([
                        $_POST['nama'] ?? '',
                        $_POST['alamat'] ?? '',
                        $_POST['telepon'] ?? '',
                        $_POST['email'] ?? '',
                        $_POST['jam_senin_jumat'] ?? '',
                        $_POST['jam_sabtu'] ?? '',
                        $_POST['jam_minggu'] ?? '',
                        $latFinal,
                        $lonFinal,
                        $_POST['layanan'] ?? ''
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO info_puskesmas (id, nama, alamat, telepon, email, jam_senin_jumat, jam_sabtu, jam_minggu, latitude, longitude, layanan) VALUES (1,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([
                        $_POST['nama'] ?? '',
                        $_POST['alamat'] ?? '',
                        $_POST['telepon'] ?? '',
                        $_POST['email'] ?? '',
                        $_POST['jam_senin_jumat'] ?? '',
                        $_POST['jam_sabtu'] ?? '',
                        $_POST['jam_minggu'] ?? '',
                        $latFinal,
                        $lonFinal,
                        $_POST['layanan'] ?? ''
                    ]);
                }
                $pesan .= 'Info Puskesmas berhasil disimpan.';
                $tipe = 'success';
            } catch (Throwable $e) {
                $pesan = 'Gagal simpan: ' . $e->getMessage();
                $tipe  = 'error';
            }
        }
    }
}

try {
    $info = $pdo->query("SELECT * FROM info_puskesmas LIMIT 1")->fetch();
    if (!$info) {
        $info = [
            'nama' => 'Puskesmas Kedungpring',
            'alamat' => '',
            'telepon' => '',
            'email' => '',
            'jam_senin_jumat' => '07.30 - 14.00 WIB',
            'jam_sabtu' => '07.30 - 12.00 WIB',
            'jam_minggu' => 'Tutup',
            'latitude' => '',
            'longitude' => '',
            'layanan' => 'Pemeriksaan Umum, KIA, Imunisasi, Gizi, Kesehatan Anak'
        ];
    }
} catch (Throwable $e) {
    $info = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Edit Info Puskesmas</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kelola informasi Puskesmas yang tampil di halaman user.
    </p>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe === 'error' ? 'error' : 'success' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <div class="container" style="max-width:100%;">
            <h3>Data Utama</h3>

            <label>Nama Puskesmas</label>
            <input type="text" name="nama" required value="<?= htmlspecialchars($info['nama'] ?? '') ?>">

            <label>Alamat Lengkap</label>
            <textarea name="alamat" rows="2"><?= htmlspecialchars($info['alamat'] ?? '') ?></textarea>

            <label>Telepon</label>
            <input type="text" name="telepon" value="<?= htmlspecialchars($info['telepon'] ?? '') ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($info['email'] ?? '') ?>">
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>Jam Operasional</h3>

            <label>Senin – Jumat</label>
            <input type="text" name="jam_senin_jumat" value="<?= htmlspecialchars($info['jam_senin_jumat'] ?? '') ?>">

            <label>Sabtu</label>
            <input type="text" name="jam_sabtu" value="<?= htmlspecialchars($info['jam_sabtu'] ?? '') ?>">

            <label>Minggu</label>
            <input type="text" name="jam_minggu" value="<?= htmlspecialchars($info['jam_minggu'] ?? '') ?>">
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>Lokasi Google Maps</h3>
            <p style="font-size:13px; color:#664d03; background:#fff3cd; padding:10px; border-radius:6px;">
                <strong>Catatan:</strong> Latitude = angka negatif (contoh -7.1809), Longitude = angka positif (contoh 112.2765).
            </p>

            <label>Latitude (Lintang)</label>
            <input type="text" name="latitude" value="<?= htmlspecialchars($info['latitude'] ?? '') ?>" placeholder="-7.1809">

            <label>Longitude (Bujur)</label>
            <input type="text" name="longitude" value="<?= htmlspecialchars($info['longitude'] ?? '') ?>" placeholder="112.2765">

            <?php if (!empty($info['latitude']) && !empty($info['longitude'])): ?>
                <div style="margin-top:12px;">
                    <iframe
                        width="100%" height="280"
                        style="border:0; border-radius:8px;"
                        loading="lazy"
                        src="https://www.google.com/maps?q=<?= htmlspecialchars($info['latitude']) ?>,<?= htmlspecialchars($info['longitude']) ?>&output=embed">
                    </iframe>
                </div>
            <?php endif; ?>
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <h3>Layanan yang Tersedia</h3>

            <label>Daftar Layanan (pisahkan dengan koma)</label>
            <textarea name="layanan" rows="3"><?= htmlspecialchars($info['layanan'] ?? '') ?></textarea>
        </div>

        <div class="container" style="max-width:100%; margin-top:20px;">
            <button type="submit" class="btn btn-success">Simpan Info Puskesmas</button>
            <a href="info_puskesmas.php" class="btn btn-warning">Reset</a>
            <a href="<?= BASE_URL ?>user/info_puskesmas.php" target="_blank" class="btn">Lihat Halaman User</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>