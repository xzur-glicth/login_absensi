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
$result = mysqli_query($koneksi, "SELECT id, nis, nama, kelas FROM siswa ORDER BY nama ASC");
if ($result === false) {
    $error = 'Gagal mengambil data siswa: ' . mysqli_error($koneksi);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Siswa</title>
    <link rel="stylesheet" type="text/css" href="absensi.css">
    <script src="theme.js"></script>
    <style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:20px} .card{background:#fff;padding:16px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.08);}</style>
</head>
<body>
    <div style="max-width:1000px;margin:0 auto">
        <div class="card">
            <h2>Kelola Siswa</h2>
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
                            <th>Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) === 0): ?>
                            <tr><td colspan="4">Belum ada data siswa.</td></tr>
                        <?php else: ?>
                            <?php $i = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kelas']); ?></td>
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
