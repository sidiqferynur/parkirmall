<?php
session_start();
include 'koneksi.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $nama     = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    // Cek apakah username sudah pernah terdaftar
    $cek_user = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE username = '$username'");
    
    if (mysqli_num_rows($cek_user) > 0) {
        $_SESSION['error'] = "Username sudah digunakan! Cari username lain.";
        header("Location: register.php");
        exit;
    } else {
        // Gunakan hashing yang sesuai dengan proses_login.php kamu
        // Contoh jika proses_login.php menggunakan md5:
        $password_hashed = md5($password);

        $query = "INSERT INTO tb_user (username, nama, password, role) VALUES ('$username', '$nama', '$password_hashed', '$role')";
        
        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success'] = "Pendaftaran berhasil! Silakan login.";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error'] = "Gagal mendaftar: " . mysqli_error($koneksi);
            header("Location: register.php");
            exit;
        }
    }
} else {
    header("Location: register.php");
    exit;
}