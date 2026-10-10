<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/naive_bayes.php';
requireUser();

// Hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

// CSRF
if (!checkCsrf($_POST['csrf'] ?? '')) {
    setFlash('error', 'Token keamanan tidak valid.');
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

// Ambil gejala terpilih
$gejalaDipilih = array_map('intval', $_POST['gejala'] ?? []);
$gejalaDipilih = array_values(array_unique(array_filter($gejalaDipilih, fn($v) => $v > 0)));

if (empty($gejalaDipilih)) {
    setFlash('error', 'Silakan pilih minimal 1 gejala klinis terlebih dahulu.');
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

// Validasi: gejala harus ada di database
$in = implode(',', array_fill(0, count($gejalaDipilih), '?'));
$stmt = $pdo->prepare("SELECT COUNT(*) FROM gejala WHERE id_gejala IN ($in)");
$stmt->execute($gejalaDipilih);
if ((int) $stmt->fetchColumn() !== count($gejalaDipilih)) {
    setFlash('error', 'Terdapat gejala yang tidak valid.');
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

// Data balita (opsional)
$namaBalita    = trim($_POST['nama_balita'] ?? '');
$usia          = (int) ($_POST['usia'] ?? 0);
$jenisKelamin  = in_array($_POST['jenis_kelamin'] ?? '', ['L','P'], true) ? $_POST['jenis_kelamin'] : null;

// Jalankan perhitungan Naive Bayes + Certainty Factor
$engine = new DiagnosaISPA($pdo);
$hasilEngine = $engine->diagnosa($gejalaDipilih);

if (empty($hasilEngine['id_penyakit'])) {
    setFlash('error', 'Sistem tidak dapat menentukan diagnosa. Coba pilih gejala lain.');
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

$idPenyakit  = (int) $hasilEngine['id_penyakit'];
$nilaiNB     = (float) $hasilEngine['nilai_nb'];
$nilaiCF     = (float) $hasilEngine['nilai_cf'];
$nilaiAkhir  = (float) $hasilEngine['nilai_akhir'];

// Simpan ke database
$pdo->beginTransaction();
try {
    // Catatan: kolom nama_balita, usia, jenis_kelamin opsional.
    // Jika tabel hasil_diagnosa kamu belum punya, ubah query di bawah.
    $stmt = $pdo->prepare("
        INSERT INTO hasil_diagnosa
            (id_user, id_penyakit, nilai_nb, nilai_cf, nilai_akhir)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['id_user'], $idPenyakit, $nilaiNB, $nilaiCF, $nilaiAkhir]);
    $idHasil = (int) $pdo->lastInsertId();

    // Simpan detail gejala
    $stmt2 = $pdo->prepare("INSERT INTO hasil_detail (id_hasil, id_gejala) VALUES (?, ?)");
    foreach ($gejalaDipilih as $idG) {
        $stmt2->execute([$idHasil, $idG]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    setFlash('error', 'Gagal menyimpan hasil diagnosa: ' . $e->getMessage());
    header("Location: " . BASE_URL . "user/diagnosa.php");
    exit;
}

// Redirect ke halaman hasil
header("Location: " . BASE_URL . "user/hasil.php?id=" . $idHasil);
exit;