<?php
require_once __DIR__ . '/../includes/auth.php';
requireUser();

$idUser = (int)$_SESSION['id_user'];

// ==============================
// KIRIM PESAN BARU
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim'])) {
    if (!checkCsrf($_POST['csrf'] ?? '')) {
        setFlash('error', 'Token tidak valid.');
    } else {
        $subjek   = trim($_POST['subjek'] ?? '');
        $pesan    = trim($_POST['pesan'] ?? '');
        $idHasil  = (int)($_POST['id_hasil'] ?? 0);

        if ($subjek === '' || $pesan === '') {
            setFlash('error', 'Subjek & pesan wajib diisi.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO konsultasi (id_user, id_hasil, subjek, pesan)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$idUser, $idHasil ?: null, $subjek, $pesan]);
            setFlash('success', 'Pesan berhasil dikirim. Admin akan membalas segera.');
        }
    }
    header("Location: konsultasi.php"); exit;
}

// ==============================
// AMBIL DAFTAR KONSULTASI USER
// ==============================
try {
    $stmt = $pdo->prepare("
        SELECT k.*, p.nama_penyakit
        FROM konsultasi k
        LEFT JOIN hasil_diagnosa h ON h.id_hasil = k.id_hasil
        LEFT JOIN penyakit p ON p.id_penyakit = h.id_penyakit
        WHERE k.id_user = ?
        ORDER BY k.tanggal_kirim DESC
    ");
    $stmt->execute([$idUser]);
    $list = $stmt->fetchAll();
} catch (Throwable $e) {
    $list = [];
}

// ==============================
// RIWAYAT DIAGNOSA (untuk dropdown)
// ==============================
try {
    $stmt = $pdo->prepare("
        SELECT h.id_hasil, h.tanggal, p.nama_penyakit
        FROM hasil_diagnosa h
        JOIN penyakit p ON p.id_penyakit = h.id_penyakit
        WHERE h.id_user = ?
        ORDER BY h.tanggal DESC
        LIMIT 10
    ");
    $stmt->execute([$idUser]);
    $riwayat = $stmt->fetchAll();
} catch (Throwable $e) {
    $riwayat = [];
}

// Statistik
$totalPesan    = count($list);
$totalDibalas  = 0;
$totalPending  = 0;
foreach ($list as $k) {
    if ($k['status'] === 'dibalas') $totalDibalas++;
    else $totalPending++;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>💬 Konsultasi dengan Admin</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Kirim pertanyaan atau konsultasi seputar hasil diagnosa Anda — Puskesmas Kedungpring
    </p>

    <?php if ($m = getFlash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
    <?php if ($m = getFlash('error')): ?><div class="alert alert-error"><?= htmlspecialchars($m) ?></div><?php endif; ?>

    <!-- Statistik -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $totalPesan ?></h3>
            <p>Total Pesan</p>
        </div>
        <div class="stat-card" style="border-left-color:#198754;">
            <h3 style="color:#198754;"><?= $totalDibalas ?></h3>
            <p>Sudah Dibalas</p>
        </div>
        <div class="stat-card" style="border-left-color:#ffc107;">
            <h3 style="color:#ffc107;"><?= $totalPending ?></h3>
            <p>Menunggu Balasan</p>
        </div>
    </div>

    <!-- Form Kirim Pesan -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>✉️ Kirim Pesan Baru</h3>
        <p style="font-size:13px; color:#666; margin-bottom:12px;">
            Silakan tulis pertanyaan Anda. Admin akan membalas dalam 1×24 jam kerja.
        </p>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

            <label>Subjek <span style="color:#dc3545;">*</span></label>
            <input type="text" name="subjek" required
                   placeholder="Contoh: Pertanyaan tentang hasil diagnosa ISPA"
                   value="<?= htmlspecialchars($_POST['subjek'] ?? '') ?>">

            <label>Kaitkan dengan Hasil Diagnosa (opsional)</label>
            <select name="id_hasil">
                <option value="">-- Tidak dikaitkan --</option>
                <?php foreach ($riwayat as $r): ?>
                    <option value="<?= $r['id_hasil'] ?>">
                        <?= date('d/m/Y', strtotime($r['tanggal'])) ?>
                        — <?= htmlspecialchars($r['nama_penyakit']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Pesan <span style="color:#dc3545;">*</span></label>
            <textarea name="pesan" rows="5" required
                      placeholder="Tulis pertanyaan atau keluhan Anda di sini..."><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>

            <button type="submit" name="kirim" class="btn btn-success" style="font-size:15px; padding:12px 24px;">
                📤 Kirim Pesan
            </button>
        </form>
    </div>

    <!-- Daftar Konsultasi -->
    <div class="container" style="max-width:100%; margin-top:24px;">
        <h3>📬 Riwayat Konsultasi (<?= count($list) ?>)</h3>

        <?php if (empty($list)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <div style="font-size:48px;">💬</div>
                <p>Belum ada konsultasi. Kirim pesan pertama Anda di atas.</p>
            </div>
        <?php else: foreach ($list as $k): ?>
            <div style="border:1px solid #e3e3e3; border-left:4px solid <?= $k['status']==='dibalas'?'#198754':'#ffc107' ?>; padding:16px; border-radius:8px; margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <strong style="color:#0d6efd; font-size:15px;">
                        📌 <?= htmlspecialchars($k['subjek']) ?>
                    </strong>
                    <span class="btn btn-sm <?= $k['status'] === 'dibalas' ? 'btn-success' : 'btn-warning' ?>">
                        <?= $k['status'] === 'dibalas' ? '✅ Dibalas' : '⏳ Menunggu' ?>
                    </span>
                </div>

                <p style="font-size:12px; color:#888; margin-top:6px;">
                    📅 <?= date('d/m/Y H:i', strtotime($k['tanggal_kirim'])) ?>
                    <?php if (!empty($k['nama_penyakit'])): ?>
                        &nbsp;|&nbsp; 🩺 Diagnosa: <strong><?= htmlspecialchars($k['nama_penyakit']) ?></strong>
                    <?php endif; ?>
                </p>

                <!-- Pesan User -->
                <div style="background:#f8f9fa; padding:12px; border-radius:6px; margin-top:12px; font-size:14px;">
                    <strong style="color:#495057;">👤 Anda:</strong><br>
                    <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($k['pesan'])) ?></div>
                </div>

                <!-- Balasan Admin -->
                <?php if (!empty($k['balasan'])): ?>
                    <div style="background:#e7f1ff; padding:12px; border-radius:6px; margin-top:8px; font-size:14px; border-left:3px solid #0d6efd;">
                        <strong style="color:#0d6efd;">👨‍⚕️ Admin:</strong><br>
                        <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($k['balasan'])) ?></div>
                        <?php if (!empty($k['tanggal_balas'])): ?>
                            <p style="font-size:11px; color:#888; margin-top:8px;">
                                Dibalas: <?= date('d/m/Y H:i', strtotime($k['tanggal_balas'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="background:#fff3cd; padding:12px; border-radius:6px; margin-top:8px; font-size:13px; color:#664d03;">
                        ⏳ Menunggu balasan admin...
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>