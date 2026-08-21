<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';
$conn = $conn ?? $koneksi ?? null;

$pesan_sukses = "";
$pesan_error = "";

// Proses Simpan Reservasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_reservasi'])) {
    $nama            = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $plat_nomor      = mysqli_real_escape_string($conn, $_POST['plat_nomor'] ?? '');
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan'] ?? '');
    $tanggal         = mysqli_real_escape_string($conn, $_POST['tanggal_reservasi'] ?? '');
    $jam             = mysqli_real_escape_string($conn, $_POST['jam_reservasi'] ?? '');
    $status          = 'Pending';

    if (!empty($nama) && !empty($plat_nomor) && !empty($jenis_kendaraan) && !empty($tanggal) && !empty($jam)) {
        // Menggunakan kolom 'nama' dan 'jenis_kendaraan' sesuai struktur database Anda
        $query = "INSERT INTO tb_reservasi (nama, plat_nomor, jenis_kendaraan, tanggal_reservasi, jam_reservasi, status) 
                  VALUES ('$nama', '$plat_nomor', '$jenis_kendaraan', '$tanggal', '$jam', '$status')";
        
        if (mysqli_query($conn, $query)) {
            $pesan_sukses = "Reservasi berhasil dikirim! Silakan cek status Anda di tabel sebelah.";
        } else {
            $pesan_error = "Gagal menyimpan reservasi: " . mysqli_error($conn);
        }
    } else {
        $pesan_error = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi & Status Parkir - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <div class="container py-5 flex-grow-1">
        <div class="mb-4">
            <a href="dashboard_user.php" class="btn btn-outline-secondary fw-bold px-3 py-2 rounded-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $pesan_sukses; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-primary text-white py-3 px-4 rounded-top-4 fw-bold">
                        <i class="bi bi-calendar-plus me-2"></i> Buat Reservasi Parkir Baru
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama Anda..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Plat Nomor Kendaraan</label>
                                <input type="text" name="plat_nomor" class="form-control" placeholder="Contoh: B 1234 XYZ" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Jenis Kendaraan</label>
                                <select name="jenis_kendaraan" class="form-select" required>
                                    <option value="" selected disabled>Pilih Jenis Kendaraan</option>
                                    <?php
                                    $query_kendaraan = mysqli_query($conn, "SELECT * FROM tb_tarif");
                                    if (!$query_kendaraan) {
                                        $query_kendaraan = mysqli_query($conn, "SELECT * FROM tarif");
                                    }

                                    if ($query_kendaraan && mysqli_num_rows($query_kendaraan) > 0) {
                                        while ($k = mysqli_fetch_assoc($query_kendaraan)) {
                                            $nama_kendaraan = $k['jenis_kendaraan'] ?? $k['nama'] ?? $k['kendaraan'] ?? $k['nama_kendaraan'] ?? '';
                                            if (!empty($nama_kendaraan)) {
                                                echo '<option value="' . htmlspecialchars($nama_kendaraan) . '">' . htmlspecialchars($nama_kendaraan) . '</option>';
                                            }
                                        }
                                    } else {
                                        echo '<option value="" disabled>Tabel database tidak ditemukan / kosong</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Tanggal Kedatangan</label>
                                <input type="date" name="tanggal_reservasi" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Jam Kedatangan</label>
                                <input type="time" name="jam_reservasi" class="form-control" required>
                            </div>
                            <button type="submit" name="kirim_reservasi" class="btn btn-primary w-100 fw-bold py-2 rounded-3 shadow-sm">
                                <i class="bi bi-send me-1"></i> Pesan Slot Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom rounded-top-4">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clock-history text-primary me-2"></i> Status Reservasi Anda
                        </h5>
                        <small class="text-muted">Cek apakah reservasi Anda disetujui, ditolak, atau masih pending oleh petugas.</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">No</th>
                                        <th>Plat / Nama</th>
                                        <th>Jadwal</th>
                                        <th class="text-center">Status Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $query_res = mysqli_query($conn, "SELECT * FROM tb_reservasi ORDER BY 1 DESC");
                                    if ($query_res && mysqli_num_rows($query_res) > 0) {
                                        while ($row = mysqli_fetch_assoc($query_res)) {
                                            $nama        = $row['nama'] ?? ($row['nama_pemilik'] ?? '-');
                                            $plat        = $row['plat_nomor'] ?? '-';
                                            $tgl         = $row['tanggal_reservasi'] ?? '-';
                                            $jam         = $row['jam_reservasi'] ?? '-';
                                            $status      = $row['status'] ?? 'Pending';

                                            $badge_bg = 'bg-warning text-dark';
                                            $ket = 'Pending';
                                            
                                            if (strtolower($status) == 'approved' || strtolower($status) == 'setujui') {
                                                $badge_bg = 'bg-success';
                                                $ket = 'Disetujui';
                                            } elseif (strtolower($status) == 'rejected' || strtolower($status) == 'tolak') {
                                                $badge_bg = 'bg-danger';
                                                $ket = 'Ditolak';
                                            } elseif (strtolower($status) == 'selesai') {
                                                $badge_bg = 'bg-secondary';
                                                $ket = 'Selesai';
                                            }
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted"><?= $no++; ?></td>
                                        <td>
                                            <span class="badge bg-dark px-2 py-1"><?= htmlspecialchars($plat); ?></span><br>
                                            <small class="text-muted fw-bold"><?= htmlspecialchars($nama); ?></small>
                                        </td>
                                        <td><small class="text-muted"><?= $tgl; ?><br><?= $jam; ?></small></td>
                                        <td class="text-center">
                                            <span class="badge <?= $badge_bg; ?> px-3 py-2"><?= $ket; ?></span>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat reservasi.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white text-center py-4 mt-auto border-top shadow-sm">
        <div class="container">
            <small class="text-muted mb-0">
                &copy; <?= date('Y'); ?> <strong>Parkir Mall</strong> &bull; Dibuat oleh <strong>Sidiq Fery Nur'cahya</strong> &bull; <strong>SMKN 1 SANDEN</strong>. All Rights Reserved.
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>