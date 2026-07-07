<?php
session_start();
include 'koneksi.php';

$error_message = '';

// Tampilkan error dari session jika ada
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Hapus setelah ditampilkan
}

if (isset($_POST['login'])) {
    $nis = $_POST['nis'];
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE nis='$nis' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        
        $_SESSION['nis'] = $data['nis'];
        $_SESSION['nama'] = $data['nama'];
        $_SESSION['kelas'] = $data['kelas'];
        $_SESSION['peran'] = $data['peran'];
        $_SESSION['status_login'] = true;

        $role = strtolower(trim($data['peran']));
        if ($role === '1' || $role === 'admin' || $role === 'guru') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_siswa.php");
        }
    } else {
        $_SESSION['error_message'] = 'NIS atau Password salah! Silakan coba lagi.';
        header("Location: login_absensi.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Absensi</title>
    <link rel="stylesheet" type="text/css" href="absensi.css">
    <script src="theme.js"></script>
</head>
<body>
    <div class="liquid-bg">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,50 Q300,0 600,50 T1200,50 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.1)"></path>
            <path d="M0,60 Q300,20 600,60 T1200,60 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.05)" style="animation: wave 15s linear infinite;"></path>
        </svg>
    </div>
    <form method="post" action="" class="form">
        <div class="form-content">
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <span class="error-icon">⚠</span>
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            <p><img src="cgy.png" alt="Logo Absensi" class="logo"></p>
            <h3>LOGIN ABSENSI</h3>
            <label for="nis">NIS</label>
            <input type="text" name="nis" id="nis" placeholder="Masukkan NIS" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Masukkan password" minlength="8" required>

            <input type="submit" name="login" id="button" value="Masuk">
            <div class="form-footer">
                <button type="button" class="theme-toggle" onclick="window.themeToggle()">Toggle Tema</button>
            </div>
        </div>
    </form>
</body>
</html>