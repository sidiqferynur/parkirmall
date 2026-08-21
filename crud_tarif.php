<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin';
$error_msg = '';

// --- LOGIKA TAMBAH TARIF ---
if (isset($_POST['tambah_tarif'])) {
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    // Hapus semua karakter selain angka (jaga-jaga jika titik ribuan lolos terkirim)
    $tarif_per_jam = (int) preg_replace('/\D/', '', $_POST['tarif_per_jam']);

    $q = mysqli_query($koneksi, "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('$jenis_kendaraan', '$tarif_per_jam')");
    if ($q) {
        header("Location: crud_tarif.php");
        exit;
    } else {
        $error_msg = "Gagal menambah data tarif: " . mysqli_error($koneksi);
    }
}

// --- LOGIKA EDIT TARIF ---
if (isset($_POST['edit_tarif'])) {
    $id_tarif        = (int)$_POST['id_tarif'];
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    // Hapus semua karakter selain angka (jaga-jaga jika titik ribuan lolos terkirim)
    $tarif_per_jam = (int) preg_replace('/\D/', '', $_POST['tarif_per_jam']);

    $q = mysqli_query($koneksi, "UPDATE tb_tarif SET jenis_kendaraan='$jenis_kendaraan', tarif_per_jam='$tarif_per_jam' WHERE id_tarif=$id_tarif");
    if ($q) {
        header("Location: crud_tarif.php");
        exit;
    } else {
        $error_msg = "Gagal mengubah data tarif: " . mysqli_error($koneksi);
    }
}

// --- LOGIKA HAPUS TARIF (PENANGANAN ERROR FK) ---
if (isset($_GET['hapus'])) {
    $id_tarif = (int)$_GET['hapus'];
    
    try {
        $q = mysqli_query($koneksi, "DELETE FROM tb_tarif WHERE id_tarif=$id_tarif");
        if ($q) {
            header("Location: crud_tarif.php");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        $error_msg = "Data tarif tidak bisa dihapus karena sudah pernah digunakan dalam transaksi parkir!";
    }
}

$query_tarif = mysqli_query($koneksi, "SELECT * FROM tb_tarif ORDER BY id_tarif DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarif - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: #212529;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 12px 20px;
            font-weight: 500;
            border-radius: 8px;
            margin: 4px 15px;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }
        .sidebar .nav-link i {
            font-size: 1.2rem;
            margin-right: 12px;
        }
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-body {
            padding: 25px;
            flex: 1 0 auto;
        }
        .footer-site {
            background-color: #ffffff;
            border-top: 1px solid #dee2e6;
            padding: 15px 0;
            text-align: center;
            color: #6c757d;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .sidebar { margin-left: -260px; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>

    <aside class="sidebar d-flex flex-column justify-content-between p-3">
        <div>
            <div class="d-flex align-items-center mb-4 px-3 pt-2">
                <i class="bi bi-p-square-fill fs-2 text-primary me-2"></i>
                <span class="fs-4 fw-bold">Parkir Mall</span>
            </div>
            <hr class="text-secondary">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="admin.php" class="nav-link"><i class="bi bi-house-door-fill"></i> Home</a>
                </li>
                <li>
                    <a href="crud_user.php" class="nav-link"><i class="bi bi-people-fill"></i> User</a>
                </li>
                <li>
                    <a href="crud_tarif.php" class="nav-link active"><i class="bi bi-cash-coin"></i> Tarif</a>
                </li>
                <li>
                    <a href="crud_area_parkir.php" class="nav-link"><i class="bi bi-geo-alt-fill"></i> Area Parkir</a>
                </li>
                <li>
                    <a href="crud_kendaraan.php" class="nav-link"><i class="bi bi-car-front-fill"></i> Kendaraan</a>
                </li>
                <li>
                    <a href="log_aktivitas.php" class="nav-link"><i class="bi bi-journal-text"></i> Log Aktivitas</a>
                </li>
            </ul>
        </div>
        <div>
            <hr class="text-secondary">
            <div class="px-3 mb-3 text-light">
                <small class="d-block text-muted">Login sebagai:</small>
                <strong><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($nama); ?> (Admin)</strong>
            </div>
            <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <div class="content-body">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Kelola Data Tarif Parkir</h3>
                    <p class="text-muted mb-0">Tambah, ubah, atau hapus tarif parkir berdasarkan jenis kendaraan.</p>
                </div>
            </div>

            <?php if (!empty($error_msg)) { ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Tambah Tarif Baru</h5>
                            <form action="crud_tarif.php" method="POST" onsubmit="return stripRibuan(this)">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Jenis Kendaraan</label>
                                    <input type="text" name="jenis_kendaraan" class="form-control" placeholder="Contoh: Mobil, Motor, Truk" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tarif Per Jam (Rp)</label>
                                    <input type="text" inputmode="numeric" name="tarif_per_jam" class="form-control input-rupiah" placeholder="Contoh: 3.000" oninput="formatRibuan(this)" required>
                                </div>
                                <button type="submit" name="tambah_tarif" class="btn btn-primary w-100 rounded-3">
                                    <i class="bi bi-plus-circle me-1"></i> Simpan Tarif
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3 px-4">
                            <h5 class="fw-bold mb-0">Daftar Tarif Parkir</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">No</th>
                                            <th>Jenis Kendaraan</th>
                                            <th>Tarif / Jam</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($query_tarif && mysqli_num_rows($query_tarif) > 0) { ?>
                                            <?php $no = 1; while ($t = mysqli_fetch_assoc($query_tarif)) { ?>
                                                <tr>
                                                    <td class="ps-4"><?php echo $no++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($t['jenis_kendaraan']); ?></td>
                                                    <td>Rp. <?php echo number_format($t['tarif_per_jam'], 0, ',', '.'); ?></td>
                                                    <td class="text-end pe-4">
                                                        <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditTarif<?php echo $t['id_tarif']; ?>" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <a href="crud_tarif.php?hapus=<?php echo $t['id_tarif']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus tarif ini?')" title="Hapus">
                                                            <i class="bi bi-trash-fill"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="modalEditTarif<?php echo $t['id_tarif']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content rounded-4 border-0">
                                                            <form action="crud_tarif.php" method="POST" onsubmit="return stripRibuan(this)">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold">Edit Tarif Parkir</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id_tarif" value="<?php echo $t['id_tarif']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Jenis Kendaraan</label>
                                                                        <input type="text" name="jenis_kendaraan" class="form-control" value="<?php echo htmlspecialchars($t['jenis_kendaraan']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Tarif Per Jam (Rp)</label>
                                                                        <input type="text" inputmode="numeric" name="tarif_per_jam" class="form-control input-rupiah" value="<?php echo number_format($t['tarif_per_jam'], 0, ',', '.'); ?>" oninput="formatRibuan(this)" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="edit_tarif" class="btn btn-primary">Simpan Perubahan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">Belum ada data tarif.</td>
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

        <footer class="footer-site">
            <small>&copy; <?php echo date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format tampilan input tarif jadi ada titik ribuan saat mengetik
        function formatRibuan(el) {
            let raw = el.value.replace(/\D/g, ''); // buang semua selain digit
            el.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
        }

        // Sebelum form dikirim, buang titik ribuan supaya server terima angka murni
        function stripRibuan(form) {
            form.querySelectorAll('.input-rupiah').forEach(function (el) {
                el.value = el.value.replace(/\D/g, '');
            });
            return true;
        }
    </script>
</body>
</html>