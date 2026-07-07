<?php
// Enable detailed PHP errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'koneksi.php';

// Menerima 'Admin' dengan huruf besar dan perbandingan yang lebih fleksibel
$role = isset($_SESSION['peran']) ? trim($_SESSION['peran']) : '';
if (!isset($_SESSION['status_login']) || ($role != '1' && $role != 'admin' && $role != 'Admin' && $role != 'guru'))  {
    header("Location: login_absensi.php");
    exit();
}

// Optional flash message area
$pesan = '';
if (isset($_SESSION['pesan'])) {
    $pesan = $_SESSION['pesan'];
    unset($_SESSION['pesan']);
}

// Ensure izin table exists (in case siswa belum triggered creation)
$create_izin = "CREATE TABLE IF NOT EXISTS izin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    alasan TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    waktu_request DATETIME DEFAULT CURRENT_TIMESTAMP,
    waktu_respon DATETIME DEFAULT NULL,
    admin VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($koneksi, $create_izin);

// Handle approve/reject actions from admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_izin'])) { 
        $id = intval($_POST['approve_izin']);
        $admin = mysqli_real_escape_string($koneksi, $_SESSION['nama']);
        // set approved
        mysqli_query($koneksi, "UPDATE izin SET status='approved', waktu_respon=NOW(), admin='$admin' WHERE id=$id");
        // insert corresponding absen record (jenis_absen=2 for izin)
        $row = mysqli_query($koneksi, "SELECT nis, tanggal FROM izin WHERE id=$id LIMIT 1");
        if ($r = mysqli_fetch_assoc($row)) {
            $nis_ins = mysqli_real_escape_string($koneksi, $r['nis']);
            $tanggal_ins = $r['tanggal'];
            mysqli_query($koneksi, "INSERT INTO absen (nis, tanggal, jenis_absen) VALUES ('$nis_ins', '$tanggal_ins', 2)");
        }
        $_SESSION['pesan'] = 'success:Permintaan izin disetujui.';
        header('Location: dashboard_admin.php'); exit();
    }

    if (isset($_POST['reject_izin'])) {
        $id = intval($_POST['reject_izin']);
        $admin = mysqli_real_escape_string($koneksi, $_SESSION['nama']);
        mysqli_query($koneksi, "UPDATE izin SET status='rejected', waktu_respon=NOW(), admin='$admin' WHERE id=$id");
        $_SESSION['pesan'] = 'success:Permintaan izin ditolak.';
        header('Location: dashboard_admin.php'); exit();
    }
}

// Query untuk rekap absensi masuk hari ini
$tanggal_hari_ini = date('Y-m-d');
$query_rekap = "SELECT COUNT(*) AS total_masuk FROM absen WHERE tanggal='$tanggal_hari_ini' AND jenis_absen=1";
$result_rekap = mysqli_query($koneksi, $query_rekap);
$rekap_data = mysqli_fetch_assoc($result_rekap);
$total_masuk_hari_ini = $rekap_data['total_masuk'] ?? 0;

// Query untuk detail siswa (Dihubungkan ke tabel pengguna agar nama dan kelas muncul)
$query_detail = "SELECT a.nis, p.nama, a.waktu_masuk, p.kelas FROM absen a 
                 LEFT JOIN pengguna p ON a.nis = p.nis 
                 WHERE a.tanggal='$tanggal_hari_ini' AND a.jenis_absen=1 
                 ORDER BY a.waktu_masuk DESC LIMIT 10";
$result_detail = mysqli_query($koneksi, $query_detail);
$detail_absensi = mysqli_fetch_all($result_detail, MYSQLI_ASSOC) ?? [];

$count_detail = count($detail_absensi);

// fetch pending izin (Dihubungkan ke tabel pengguna agar nama muncul)
$pending_izin = [];
$res_izin = mysqli_query($koneksi, "SELECT i.*, p.nama FROM izin i LEFT JOIN pengguna p ON i.nis = p.nis WHERE i.status='pending' ORDER BY i.waktu_request ASC");
if ($res_izin) $pending_izin = mysqli_fetch_all($res_izin, MYSQLI_ASSOC) ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" type="text/css" href="absensi.css">
    <link rel="stylesheet" type="text/css" href="styledash.css">
    <script src="theme.js"></script>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:0;display:block;background:transparent}
        .navbar{background:#2b6ca3;color:#fff;padding:12px 16px}
        .navbar .title{font-size:20px}
        .container{max-width:980px;margin:24px auto;padding:0 16px}
        .card{background:#fff;padding:18px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.08);margin-bottom:16px}
        .grid{display:flex;gap:12px;flex-wrap:wrap}
        .grid .col{flex:1;min-width:220px}
        .btn{display:inline-block;padding:8px 12px;border-radius:4px;text-decoration:none;color:#fff}
        .btn-primary{background:#1f6feb}
        .btn-danger{background:#e05252}
        .message{padding:10px;border-radius:4px;margin-bottom:12px}
        .message-success{background:#e6f7ec;color:#176f2c}
        .message-error{background:#fdecea;color:#a33}
        .stat-card{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:20px;border-radius:8px;margin-bottom:16px}
        .stat-card.success{background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)}
        .stat-number{font-size:32px;font-weight:bold;margin:10px 0}
        .stat-label{font-size:14px;opacity:0.9}
        .absensi-table{width:100%;border-collapse:collapse;margin-top:12px}
        .absensi-table th{background:#f0f0f0;padding:10px;text-align:left;font-weight:bold;border-bottom:2px solid #ddd}
        .absensi-table td{padding:10px;border-bottom:1px solid #eee}
        .absensi-table tr:hover{background:#f9f9f9}
    </style>
</head>
<body>
    <div class="liquid-bg">
        <svg viewBox="0 0 1200 220" preserveAspectRatio="none">
            <path d="M0,120 C300,200 900,40 1200,120 L1200,220 L0,220 Z" fill="rgba(255,255,255,0.04)"></path>
            <path d="M0,140 C300,80 900,220 1200,140 L1200,220 L0,220 Z" fill="rgba(255,255,255,0.02)"></path>
        </svg>
    </div>
    <div class="navbar">
        <div style="display:flex;align-items:center;justify-content:space-between">
            <div class="title">Portal Absensi - Admin</div>
            <button class="theme-toggle" onclick="themeToggle()" id="themeBtn" aria-label="Toggle theme">🌗</button>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></h2>
            <p>Anda masuk sebagai <strong>Admin</strong>.</p>
        </div>

        <?php if (!empty($pesan)): ?>
            <?php $type = strpos($pesan, 'success') === 0 ? 'success' : 'error'; ?>
            <div class="message message-<?php echo $type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars(str_replace($type.':', '', $pesan)); ?>
            </div>
        <?php endif; ?>

        <div class="stat-card success">
            <div class="stat-label">📊 Absensi Masuk Hari Ini</div>
            <div class="stat-number"><?php echo $total_masuk_hari_ini; ?></div>
            <div class="stat-label">Tanggal: <?php echo date('d/m/Y'); ?></div>
        </div>

        <div class="card">
            <h3>📋 Detail Absensi Hari Ini (<?php echo $count_detail > 0 ? $count_detail : 0; ?> Terbaru)</h3>
            <?php if (count($detail_absensi) > 0): ?>
                <table class="absensi-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Waktu Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_absensi as $index => $absen): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($absen['nis']); ?></td>
                                <td><?php echo htmlspecialchars($absen['nama'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($absen['kelas'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($absen['waktu_masuk']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:12px;"><a class="btn btn-primary" href="rekap_absensi.php">Lihat Rekap Lengkap →</a></p>
            <?php else: ?>
                <p style="color:#999;">Belum ada siswa yang absen masuk hari ini.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📨 Permintaan Izin (Pending)</h3>
            <?php if (count($pending_izin) > 0): ?>
                <table class="absensi-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Alasan</th>
                            <th>Waktu Request</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_izin as $i => $iz): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td><?php echo htmlspecialchars($iz['nis']); ?></td>
                                <td><?php echo htmlspecialchars($iz['nama'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($iz['tanggal']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($iz['alasan'])); ?></td>
                                <td><?php echo htmlspecialchars($iz['waktu_request']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <button type="submit" name="approve_izin" value="<?php echo $iz['id']; ?>" class="btn btn-primary">Setujui</button>
                                    </form>
                                    <form method="POST" style="display:inline;margin-left:6px;">
                                        <button type="submit" name="reject_izin" value="<?php echo $iz['id']; ?>" class="btn btn-danger">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#999;">Tidak ada permintaan izin saat ini.</p>
            <?php endif; ?>
        </div>

        <div class="grid">
            <div class="col card">
                <h3>Manajemen Siswa</h3>
                <p>Tambah, edit, atau hapus data siswa.</p>
                <a class="btn btn-primary" href="kelola_siswa.php">Kelola Siswa</a>
            </div>
            <div class="col card">
                <h3>Rekap Absensi</h3>
                <p>Lihat rekap absensi harian atau per kelas.</p>
                <a class="btn btn-primary" href="rekap_absensi.php">Lihat Rekap</a>
            </div>
            <div class="col card">
                <h3>Pengaturan</h3>
                <p>Pengaturan aplikasi dan akun.</p>
                <a class="btn btn-danger" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</body>
<script>
;(function(){
    const btn = document.getElementById('themeBtn');
    if(!btn) return;
    function update(){
        const t = document.documentElement.getAttribute('data-theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light':'dark');
        btn.textContent = t === 'light' ? '🌞' : '🌙';
    }
    update();
    const obs = new MutationObserver(update);
    obs.observe(document.documentElement, {attributes:true,attributeFilter:['data-theme']});
})();

// Auto-refresh dashboard setiap 5 detik untuk melihat absensi terbaru
setInterval(function(){
    location.reload();
}, 5000);
</script>
</html>