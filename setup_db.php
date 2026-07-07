<?php
// Simple setup script to create required tables. Run once via browser.
include 'koneksi.php';

try {
    $sql = file_get_contents(__DIR__ . '/create_tables.sql');
    if ($sql === false) throw new Exception('create_tables.sql not found');
    // Split statements naively on semicolon
    $stmts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($stmts as $stmt) {
        if (strlen($stmt) === 0) continue;
        mysqli_query($koneksi, $stmt);
    }
    echo "Selesai menjalankan create_tables.sql. Periksa database untuk tabel 'siswa' dan 'absen'.";
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}

?>
