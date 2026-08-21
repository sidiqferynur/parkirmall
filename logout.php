<?php
include 'koneksi.php';
include 'log_aktivitas.php';

if (isset($_SESSION['id_user'])) {
    write_log($conn, $_SESSION['id_user'], "User berhasil logout");
}

session_unset();
session_destroy();

header("Location: index.php");
exit;
?>