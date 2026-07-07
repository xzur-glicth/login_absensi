<?php
$host     = "localhost";
$username = "root"; 
$password = "";     
$database = "absensi"; // Menggunakan database 'absensi' (arah ke folder data MySQL)

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>