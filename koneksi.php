<?php
// Atur zona waktu PHP agar sesuai dengan Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_parkir";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal! " . mysqli_connect_error());
} else {
    // Sinkronisasi zona waktu MySQL dengan PHP (+07:00 untuk WIB)
    mysqli_query($conn, "SET time_zone = '+07:00'");
}

$koneksi = $conn;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>