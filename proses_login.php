<?php
session_start();
include 'koneksi.php';

$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = $_POST['password'];

$query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username'");
$cek = mysqli_num_rows($query);

if ($cek > 0) {
    $data = mysqli_fetch_assoc($query);
    
    if ($password == $data['password']) {
        
        $_SESSION['username'] = $data['username'];
        $_SESSION['level'] = $data['level'];
        $_SESSION['status'] = "login";

        $level = strtolower(trim($data['level']));
        $halaman_tujuan = "index.php";

        if ($level == "owner") {
            $halaman_tujuan = "owner.php";
        } elseif ($level == "admin") {
            $halaman_tujuan = "admin.php";
        } elseif ($level == "petugas") {
            $halaman_tujuan = "petugas.php";
        }

        echo "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <title>Login Berhasil</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Login Berhasil!',
                    text: 'Selamat datang kembali, " . $data['username'] . "!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(function() {
                    window.location.href = '$halaman_tujuan';
                });
            </script>
        </body>
        </html>
        ";
        exit();

    } else {
        echo "<script>alert('Password salah!'); window.location.href='login.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Username tidak ditemukan!'); window.location.href='login.php';</script>";
    exit();
}
?>