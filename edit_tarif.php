<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin';$error_msg = '';

// --- LOGIKA TAMBAH TARIF ---
if (isset($_POST['tambah_tarif'])) {$jenis_kendaraan = mysqli_real_escape_string($koneksi,$_POST['jenis_kendaraan']);
    $tarif_per_jam   = (int)$_POST['tarif_per_jam'];

    $q = mysqli_query($koneksi, "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('$jenis_kendaraan', '$tarif_per_jam')");
    if ($q) {
        header("Location: crud_tarif.php");
        exit;
    } else {
        $error_msg = "Gagal menambah data tarif: " . mysqli_error($koneksi);
    }
}

// --- LOGIKA EDIT TARIF ---
if (isset($_POST['edit_tarif'])) {$id_tarif        = (int)$_POST['id_tarif'];$jenis_kendaraan = mysqli_real_escape_string($koneksi,$_POST['jenis_kendaraan']);
    $tarif_per_jam   = (int)$_POST['tarif_per_jam'];

    $q = mysqli_query($koneksi, "UPDATE tb_tarif SET jenis_kendaraan='$jenis_kendaraan', tarif_per_jam='$tarif_per_jam' WHERE id_tarif=$id_tarif");
    if ($q) {
        header("Location: crud_tarif.php");
        exit;
    } else {
        $error_msg = "Gagal mengubah data tarif: " . mysqli_error($koneksi);
    }
}

// --- LOGIKA HAPUS TARIF (DENGAN PENANGANAN ERROR FK) ---
if (isset($_GET['hapus'])) {
    $id_tarif = (int)$_GET['hapus'];
    
    try {
        $q = mysqli_query($koneksi, "DELETE FROM tb_tarif WHERE id_tarif=$id_tarif");
        if ($q) {
            header("Location: crud_tarif.php");
            exit;
        }
    } catch (mysqli_sql_exception $e) {$error_msg = "Data tarif tidak bisa dihapus karena sudah pernah digunakan dalam transaksi parkir!";
    }
}

$query_tarif = mysqli_query($koneksi, "SELECT * FROM tb_tarif ORDER BY id_tarif DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Tarif - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; display: flex; flex-direction: column; }
        .sidebar { width: 260px; min-height: 100vh; background-color: #212529; color: #fff; position: fixed; top: 0; left: 0; z-index: 1000; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.75); padding: 12px 20px; font-weight: 500; border-radius: 8px; margin: 4px 15px; display: flex; align-items: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: #0d6efd; }
        .sidebar .nav-link i { font-size: 1.2rem; margin-right: 12px; }
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; flex: 1; }
        .content-body { padding: 25px; flex: 1; }
        footer { margin-top: auto; }
        @media (max-width: 768px) { .sidebar { margin-left: -260px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <aside class="sidebar d