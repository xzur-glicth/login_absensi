<?php
// Enable detailed PHP errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

$role = isset($_SESSION['peran']) ? strtolower(trim($_SESSION['peran'])) : '';
if (!isset($_SESSION['status_login']) || ($role !== '1' && $role !== 'admin')) {
    header("Location: login_absensi.php");
    exit();
}

$error = '';
$query = "SELECT a.nis, IFNULL(s.nama, '-') AS nama, a.tanggal, a.waktu_masuk, a.waktu_keluar, a.jenis_absen FROM absen a LEFT JOIN siswa s ON a.nis = s.nis ORDER BY a.tanggal DESC, a.waktu_masuk DESC LIMIT 200";

// Cek apakah tabel 'siswa' dan 'absen' ada sebelum menjalankan query
$checkSiswa = mysqli_query($koneksi, "SHOW TABLES LIKE 'siswa'");
$checkAbsen = mysqli_query($koneksi, "SHOW TABLES LIKE 'absen'");
if ($checkSiswa === false || $checkAbsen === false) {
    $error = 'Gagal memeriksa struktur database: ' . mysqli_error($koneksi);
} elseif (mysqli_num_rows($checkSiswa) === 0 || mysqli_num_rows($checkAbsen) === 0) {
    $error = "Tabel 'siswa' atau 'absen' tidak ditemukan. Jalankan `create_tables.sql` atau gunakan `setup_db.php` untuk membuat tabel yang diperlukan.";
    $result = false;
} else {
    $result = mysqli_query($koneksi, $query);
    if ($result === false) {
        $error = 'Gagal mengambil data rekap: ' . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Absensi</title>
    <link rel="stylesheet" type="text/css" href="absensi.css">
    <script src="theme.js"></script>
    <style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:20px} .card{background:#fff;padding:16px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.08);}</style>
</head>
<body>
    <div style="max-width:1100px;margin:0 auto">
        <div class="card">
            <h2>Rekap Absensi</h2>
            <p><a href="dashboard_admin.php">← Kembali ke Dashboard</a></p>
            <?php if ($error): ?>
                <div style="padding:10px;background:#fdecea;color:#a33;border-radius:4px;margin-bottom:12px"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <table border="1" cellpadding="8" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Waktu Masuk</th>
                            <th>Waktu Keluar</th>
                            <th>Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) === 0): ?>
                            <tr><td colspan="7">Tidak ada data absensi.</td></tr>
                        <?php else: ?>
                            <?php $i = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                    <td><?php echo htmlspecialchars($row['waktu_masuk']); ?></td>
                                    <td><?php echo htmlspecialchars($row['waktu_keluar']); ?></td>
                                    <td><?php echo $row['jenis_absen'] == 1 ? 'Masuk' : ($row['jenis_absen'] == 2 ? 'Keluar' : 'Lainnya'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
