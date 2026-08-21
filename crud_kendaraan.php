<?php
// Mengaktifkan error reporting agar pesan kesalahan langsung terlihat jika ada kendala
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$error_msg = '';

// --- LOGIKA TAMBAH KENDARAAN ---
if (isset($_POST['tambah_kendaraan'])) {
    $plat_nomor      = mysqli_real_escape_string($koneksi, $_POST['plat_nomor']);
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $warna           = mysqli_real_escape_string($koneksi, $_POST['warna_kendaraan']); // Mengambil dari form name="warna_kendaraan"
    $pemilik         = mysqli_real_escape_string($koneksi, $_POST['pemilik']);

    // Cek apakah plat nomor sudah ada di database
    $cek_plat = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE plat_nomor = '$plat_nomor'");
    if (mysqli_num_rows($cek_plat) > 0) {
        $error_msg = "Gagal: Plat nomor '$plat_nomor' sudah terdaftar dalam sistem!";
    } else {
        // Query menggunakan nama kolom database 'warna'
        $q = mysqli_query($koneksi, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik) VALUES ('$plat_nomor', '$jenis_kendaraan', '$warna', '$pemilik')");
        if ($q) {
            header("Location: crud_kendaraan.php");
            exit;
        } else {
            $error_msg = "Gagal menambah data kendaraan: " . mysqli_error($koneksi);
        }
    }
}

// --- LOGIKA HAPUS KENDARAAN ---
if (isset($_GET['hapus'])) {
    $id_kendaraan = (int)$_GET['hapus'];
    
    try {
        $q = mysqli_query($koneksi, "DELETE FROM tb_kendaraan WHERE id_kendaraan=$id_kendaraan");
        if ($q) {
            header("Location: crud_kendaraan.php");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        $error_msg = "Data kendaraan tidak bisa dihapus karena masih terikat dengan transaksi parkir!";
    }
}

$query_kendaraan = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan ORDER BY id_kendaraan DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Kendaraan - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            background-color: #f8f9fa;
        }
        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
            padding: 30px;
        }
        footer {
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="content container-fluid px-4">
            
            <div class="mb-3">
                <a href="admin.php" class="btn btn-secondary rounded-3">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>

            <h2 class="fw-bold mb-4">Kelola Data Kendaraan</h2>

            <?php if (!empty($error_msg)) { ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-dark text-white fw-semibold py-3">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Kendaraan Baru
                        </div>
                        <div class="card-body p-4">
                            <form action="crud_kendaraan.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Plat Nomor</label>
                                    <input type="text" name="plat_nomor" class="form-control" placeholder="Contoh: B 1234 ABC" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Jenis Kendaraan</label>
                                    <input type="text" name="jenis_kendaraan" class="form-control" placeholder="Contoh: Mobil / Motor" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Warna</label>
                                    <input type="text" name="warna_kendaraan" class="form-control" placeholder="Contoh: Hitam / Merah" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Nama Pemilik / Pengendara</label>
                                    <input type="text" name="pemilik" class="form-control" placeholder="Contoh: Budi Santoso" required>
                                </div>
                                <button type="submit" name="tambah_kendaraan" class="btn btn-primary w-100 py-2 fw-semibold rounded-3">
                                    Simpan Kendaraan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                        <div class="card-header bg-primary text-white fw-semibold py-3">
                            <i class="bi bi-car-front-fill me-2"></i> Daftar Kendaraan Terdaftar
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Plat Nomor</th>
                                            <th>Jenis Kendaraan</th>
                                            <th>Warna</th>
                                            <th>Pemilik</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($query_kendaraan && mysqli_num_rows($query_kendaraan) > 0) { ?>
                                            <?php $no = 1; while ($k = mysqli_fetch_assoc($query_kendaraan)) { ?>
                                                <tr>
                                                    <td class="ps-4"><?php echo $no++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($k['plat_nomor']); ?></td>
                                                    <td><?php echo htmlspecialchars(!empty($k['jenis_kendaraan']) ? $k['jenis_kendaraan'] : '-'); ?></td>
                                                    <!-- Menampilkan data dari kolom database 'warna' -->
                                                    <td><?php echo htmlspecialchars(!empty($k['warna']) ? $k['warna'] : '-'); ?></td>
                                                    <td><?php echo htmlspecialchars(!empty($k['pemilik']) ? $k['pemilik'] : '-'); ?></td>
                                                    <td class="text-center">
                                                        <a href="crud_kendaraan.php?hapus=<?php echo $k['id_kendaraan']; ?>" class="btn btn-danger btn-sm px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus data kendaraan ini?')" title="Hapus">
                                                            <i class="bi bi-trash-fill me-1"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data kendaraan.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <footer>
            <small>&copy; <?php echo date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>