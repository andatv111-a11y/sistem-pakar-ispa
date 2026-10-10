<?php
// check_admin.php — periksa hash password admin dan uji kandidat
try {
    $pdo = new PDO('mysql:host=localhost;dbname=db_ispa;charset=utf8mb4', 'root', '');
    $stmt = $pdo->prepare("SELECT username, password FROM users WHERE username = ? LIMIT 1");
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo "User 'admin' tidak ditemukan.\n";
        exit(1);
    }
    echo "User found: " . $user['username'] . "\n";
    $hash = $user['password'];
    echo "Hash: " . $hash . "\n";

    $candidates = ['admin123', 'admin', 'password', '123456', 'admin@123'];
    foreach ($candidates as $p) {
        $ok = password_verify($p, $hash) ? 'MATCH' : 'no';
        echo sprintf("%-12s => %s\n", $p, $ok);
    }
} catch (PDOException $e) {
    echo "Koneksi DB gagal: " . $e->getMessage() . "\n";
    exit(2);
}
