<?php
include 'koneksi.php';

// Membuat hash password baru yang valid
$password_baru = password_hash('admin123', PASSWORD_BCRYPT);

// Langsung update ke database untuk user admin
$query = "UPDATE tb_user SET password = '$password_baru' WHERE username = 'admin'";

if (mysqli_query($conn, $query)) {
    echo "<h3>Password Admin Berhasil Direset!</h3>";
    echo "Password kamu sekarang adalah: <strong>admin123</strong><br><br>";
    echo "<a href='index.php'>Klik di sini untuk kembali ke halaman Login</a>";
} else {
    echo "Gagal mengupdate database: " . mysqli_error($conn);
}
?>