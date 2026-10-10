<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pesan = '';

// Proses download
if (isset($_GET['download'])) {
    $tables = [];
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $pesan = 'Gagal ambil tabel: ' . $e->getMessage();
    }

    if (empty($pesan)) {
        $namaFile = 'backup_db_ispa_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Pragma: no-cache');

        echo "-- =============================================\n";
        echo "-- Backup Database db_ispa\n";
        echo "-- Sistem: SIPAKAR-ISPA\n";
        echo "-- Tanggal: " . date('Y-m-d H:i:s') . "\n";
        echo "-- =============================================\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch();
            echo "-- ----------------------------\n";
            echo "-- Table: $t\n";
            echo "-- ----------------------------\n";
            echo "DROP TABLE IF EXISTS `$t`;\n";
            echo $create['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                echo "INSERT INTO `$t` VALUES\n";
                $inserts = [];
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, $row);
                    $inserts[] = '(' . implode(', ', $vals) . ')';
                }
                echo implode(",\n", $inserts) . ";\n\n";
            }
        }
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }
}

$tables = [];
$totalBaris = 0;
$detailTabel = [];
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $jml = (int) $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $totalBaris += $jml;
        $detailTabel[$t] = $jml;
    }
} catch (Throwable $e) {
    $pesan = 'Gagal cek database: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Backup Database</h2>
    <p style="color:#666; font-size:14px; margin-bottom:16px;">
        Backup seluruh data SIPAKAR-ISPA ke file .sql.
    </p>

    <?php if ($pesan): ?>
        <div class="alert alert-error"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="container" style="max-width:100%; background:#fff3cd; border-left:4px solid #ffc107;">
        <h3 style="color:#664d03;">Penting Dibaca Sebelum Backup</h3>
        <ul style="line-height:1.9; font-size:14px; padding-left:20px; color:#664d03;">
            <li>Backup berisi seluruh struktur &amp; data tabel</li>
            <li>Simpan file backup di tempat aman (Google Drive, flashdisk)</li>
            <li>Lakukan backup rutin minimal 1× seminggu</li>
            <li>File backup bisa dipakai untuk restore lewat phpMyAdmin / HeidiSQL</li>
        </ul>
    </div>

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><h3><?= count($tables) ?></h3><p>Jumlah Tabel</p></div>
        <div class="stat-card"><h3><?= number_format($totalBaris) ?></h3><p>Total Baris Data</p></div>
    </div>

    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>Ringkasan Tabel</h3>
        <table>
            <thead><tr><th>#</th><th>Nama Tabel</th><th>Jumlah Baris</th></tr></thead>
            <tbody>
                <?php $no = 1; foreach ($detailTabel as $t => $jml): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($t) ?></strong></td>
                        <td><?= number_format($jml) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="container" style="max-width:100%; margin-top:20px; text-align:center;">
        <h3>Download Backup</h3>
        <p style="font-size:14px; color:#666; margin-bottom:16px;">
            Klik tombol di bawah untuk mengunduh file <code>.sql</code> berisi seluruh database.
        </p>
        <a href="backup.php?download=1" class="btn btn-success" style="font-size:16px; padding:14px 28px;">
            Download Backup (.sql)
        </a>
    </div>

    <div class="container" style="max-width:100%; margin-top:20px;">
        <h3>Cara Restore Database</h3>
        <ol style="line-height:1.9; font-size:14px; padding-left:20px;">
            <li>Buka <strong>phpMyAdmin</strong> atau <strong>HeidiSQL</strong></li>
            <li>Pilih database <code>db_ispa</code></li>
            <li>Klik <strong>Import</strong> → pilih file <code>.sql</code> hasil backup</li>
            <li>Klik <strong>Go / Run</strong></li>
        </ol>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>