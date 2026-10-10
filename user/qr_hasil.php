<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT h.*, p.nama_penyakit, u.nama AS nama_user
    FROM hasil_diagnosa h
    JOIN penyakit p ON p.id_penyakit = h.id_penyakit
    JOIN users u ON u.id_user = h.id_user
    WHERE h.id_hasil = ? AND h.id_user = ?
");
$stmt->execute([$id, $_SESSION['id_user']]);
$hasil = $stmt->fetch();

if (!$hasil) { die('Data tidak ditemukan.'); }

$urlVerifikasi = BASE_URL . 'verifikasi.php?kode=' . md5($id . $hasil['tanggal']);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>📱 QR Code Verifikasi Hasil Diagnosa</h2>

    <div class="container" style="max-width:600px; text-align:center;">
        <p style="color:#666; font-size:14px; margin-bottom:16px;">
            Scan QR code ini untuk memverifikasi keaslian hasil diagnosa.
        </p>

        <div id="qrcode" style="display:inline-block; padding:20px; background:#fff; border:2px solid #0d6efd; border-radius:10px;"></div>

        <p style="margin-top:16px; font-size:13px; color:#666; word-break:break-all;">
            <?= htmlspecialchars($urlVerifikasi) ?>
        </p>

        <p style="margin-top:20px;">
            <a href="hasil.php?id=<?= $id ?>" class="btn">⬅️ Kembali ke Hasil</a>
            <button onclick="window.print()" class="btn btn-success">🖨️ Cetak QR</button>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "<?= $urlVerifikasi ?>",
        width: 250,
        height: 250,
        colorDark: "#0d6efd",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>