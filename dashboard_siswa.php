<?php
// 1. AKTIFKAN PELACAK ERROR
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

// 2. SET ZONA WAKTU
date_default_timezone_set('Asia/Jakarta');

// 3. PROTEKSI HALAMAN
$peran_user = isset($_SESSION['peran']) ? strtolower(trim($_SESSION['peran'])) : '';
if (!isset($_SESSION['status_login']) || ($peran_user != 'siswa' && $peran_user != '2')) {
    header("Location: login_absensi.php");
    exit();
}

$nis = $_SESSION['nis'];
$nama = $_SESSION['nama'];
$tanggal = date('Y-m-d');
$waktu = date('H:i:s');

$pesan = '';

// 4. PROSES ABSEN MASUK
if (isset($_POST['absen_masuk'])) {
    $cek_masuk = mysqli_query($koneksi, "SELECT * FROM absen WHERE nis='$nis' AND tanggal='$tanggal' AND jenis_absen=1");
    if (mysqli_num_rows($cek_masuk) > 0) {
        $pesan = "<div class='message message-error'>⚠️ Anda sudah absen masuk hari ini!</div>";
    } else {
        $query_masuk = mysqli_query($koneksi, "INSERT INTO absen (nis, tanggal, waktu_masuk, jenis_absen) VALUES ('$nis', '$tanggal', '$waktu', 1)");
        if ($query_masuk) {
            $pesan = "<div class='message message-success'>✅ Absen masuk berhasil pada pukul $waktu</div>";
        } else {
            $pesan = "<div class='message message-error'>❌ Gagal absen masuk: " . mysqli_error($koneksi) . "</div>";
        }
    }
}

// 5. PROSES ABSEN KELUAR
if (isset($_POST['absen_keluar'])) {
    $cek_masuk = mysqli_query($koneksi, "SELECT * FROM absen WHERE nis='$nis' AND tanggal='$tanggal' AND jenis_absen=1");
    if (mysqli_num_rows($cek_masuk) == 0) {
        $pesan = "<div class='message message-error'>⚠️ Anda belum absen masuk hari ini!</div>";
    } else {
        $data_absen = mysqli_fetch_assoc($cek_masuk);
        if (!empty($data_absen['waktu_keluar']) && $data_absen['waktu_keluar'] != '00:00:00') {
            $pesan = "<div class='message message-error'>⚠️ Anda sudah absen keluar hari ini!</div>";
        } else {
            $query_keluar = mysqli_query($koneksi, "UPDATE absen SET waktu_keluar='$waktu' WHERE nis='$nis' AND tanggal='$tanggal' AND jenis_absen=1");
            if ($query_keluar) {
                $pesan = "<div class='message message-success'>✅ Absen keluar berhasil pada pukul $waktu</div>";
            } else {
                $pesan = "<div class='message message-error'>❌ Gagal absen keluar: " . mysqli_error($koneksi) . "</div>";
            }
        }
    }
}

// 6. PROSES PENGAJUAN IZIN
if (isset($_POST['kirim_izin'])) {
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);
    if (empty($alasan)) {
        $pesan = "<div class='message message-error'>⚠️ Alasan izin tidak boleh kosong!</div>";
    } else {
        $query_izin = mysqli_query($koneksi, "INSERT INTO izin (nis, tanggal, alasan, status) VALUES ('$nis', '$tanggal', '$alasan', 'pending')");
        if ($query_izin) {
            $pesan = "<div class='message message-success'>✅ Permintaan izin berhasil dikirim! Menunggu konfirmasi admin.</div>";
        } else {
            $pesan = "<div class='message message-error'>❌ Gagal mengirim izin: " . mysqli_error($koneksi) . "</div>";
        }
    }
}

$query_status = mysqli_query($koneksi, "SELECT * FROM absen WHERE nis='$nis' AND tanggal='$tanggal' AND jenis_absen=1");
$status_hari_ini = mysqli_fetch_assoc($query_status);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Absensi</title>
    <link rel="stylesheet" type="text/css" href="styledash_siswa.css?v=999">
</head>
<body>

    <div class="header-banner">
        <h2>Halo, <?php echo htmlspecialchars($nama); ?>!</h2>
        <p>Portal Absensi Digital SMK Negeri 1 Bojong</p>

        <svg class="liquid-waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z" />
            </defs>
            <g class="wave-keyframes">
                <use href="#gentle-wave" x="48" y="0" class="wave-layer wave-layer1" />
                <use href="#gentle-wave" x="48" y="3" class="wave-layer wave-layer2" />
                <use href="#gentle-wave" x="48" y="7" class="wave-layer wave-layer3" />
            </g>
        </svg>
    </div>

    <div class="container">
        
        <div class="card">
            <div class="card-info-grid">
                <div class="info-box">
                    NIS Siswa
                    <span><?php echo htmlspecialchars($nis); ?></span>
                </div>
                <div class="info-box">
                    Jam Saat Ini
                    <span><?php echo date('H:i'); ?> WIB</span>
                </div>
            </div>
        </div>

        <?php echo $pesan; ?>

        <div class="card">
            <h3 style="font-size: 16px; margin-bottom: 15px; text-align: center; color: #475569;">Pilih Aksi Absensi Hari Ini</h3>
            <div class="btn-group">
                <form method="POST" style="flex: 1;">
                    <button type="submit" name="absen_masuk" class="btn btn-primary" <?php echo ($status_hari_ini) ? 'disabled' : ''; ?>>
                        Absen Masuk
                    </button>
                </form>

                <form method="POST" style="flex: 1;">
                    <button type="submit" name="absen_keluar" class="btn btn-danger" <?php echo (!$status_hari_ini || (!empty($status_hari_ini['waktu_keluar']) && $status_hari_ini['waktu_keluar'] != '00:00:00')) ? 'disabled' : ''; ?>>
                        Absen Keluar
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #475569;">Formulir Pengajuan Izin</h3>
            <form method="POST">
                <div style="margin-bottom: 15px; text-align: left;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 500; display: block; margin-bottom: 6px;">Alasan Tidak Hadir (Sakit / Keperluan):</label>
                    <textarea name="alasan" rows="3" placeholder="Tulis alasan detail di sini..." class="input-alasan" required></textarea>
                </div>
                <button type="submit" name="kirim_izin" class="btn btn-primary" style="width: 100%; display: block;">
                    Kirim Permintaan Izin
                </button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; margin-bottom: 10px; color: #475569;">Riwayat Hari Ini (<?php echo date('d/m/Y'); ?>)</h3>
            <div class="status-table">
                <div class="status-row">
                    <div class="status-label">Jam Absen Masuk</div>
                    <div class="status-value">
                        <?php echo (!empty($status_hari_ini['waktu_masuk'])) ? '🕒 ' . $status_hari_ini['waktu_masuk'] : '—'; ?>
                    </div>
                </div>
                <div class="status-row">
                    <div class="status-label">Jam Absen Keluar</div>
                    <div class="status-value">
                        <?php echo (!empty($status_hari_ini['waktu_keluar']) && $status_hari_ini['waktu_keluar'] != '00:00:00') ? '🕒 ' . $status_hari_ini['waktu_keluar'] : '—'; ?>
                    </div>
                </div>
            </div>
        </div>

        <a href="logout.php" class="logout-link">Keluar dari Aplikasi (Logout)</a>
    </div>

</body>
</html>