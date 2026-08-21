<?php
include 'koneksi.php';

// Proteksi: Hanya Owner dan Admin yang bisa melihat rekap ini
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'admin')) {
    header("Location: dashboard.php");
    exit;
}

// Mengambil input tanggal filter, jika kosong default ke tanggal hari ini
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// Query mengambil data parkir berdasarkan rentang waktu yang diinput owner
$query = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
          FROM tb_transaksi t 
          JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
          JOIN tb_area_parkir a ON t.id_area = a.id_area
          WHERE DATE(t.waktu_masuk) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
          ORDER BY t.waktu_masuk DESC";
$res = mysqli_query($conn, $query);

$total_pendapatan = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Rekap Transaksi - Owner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary mb-3">← Kembali ke Dashboard</a>
        <h2>Laporan Pendapatan Uang Parkir</h2>
        
        <!-- Form Sederhana Tanpa Lapisan Grid Tebal -->
<div class="p-3 bg-white rounded shadow-sm mb-4">
    <form method="GET" action="rekap_transaksi.php" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label class="form-label fw-bold">Dari Tanggal</label>
            <input type="date" name="tgl_mulai" class="form-control" value="<?= isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d'); ?>" required>
        </div>
        <div style="flex: 1;">
            <label class="form-label fw-bold">Sampai Tanggal</label>
            <input type="date" name="tgl_selesai" class="form-control" value="<?= isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d'); ?>" required>
        </div>
        <div style="flex: 1;">
            <input type="submit" class="btn btn-success w-100" value="Filter Rekap Data" style="position: relative; z-index: 9999;">
        </div>
    </form>
</div>

        <!-- Tabel Laporan Finansial -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Data Transaksi Terpilih</div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Plat Nomor</th>
                            <th>Kategori</th>
                            <th>Lokasi Slot</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Durasi</th>
                            <th>Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($res) == 0) {
                            echo "<tr><td colspan='7' class='text-center'>Tidak ada data transaksi pada tanggal ini.</td></tr>";
                        }
                        while($row = mysqli_fetch_assoc($res)) { 
                            $total_pendapatan += $row['biaya_total'];
                        ?>
                        <tr>
                            <td><span class="badge bg-dark fs-6"><?= $row['plat_nomor']; ?></span></td>
                            <td><?= ucfirst($row['jenis_kendaraan']); ?></td>
                            <td><?= $row['nama_area']; ?></td>
                            <td><?= $row['waktu_masuk']; ?></td>
                            <td><?= $row['waktu_keluar'] ? $row['waktu_keluar'] : '<span class="text-danger">Sedang Parkir</span>'; ?></td>
                            <td><?= $row['durasi_jam']; ?> Jam</td>
                            <td>Rp. <?= number_format($row['biaya_total']); ?></td>
                        </tr>
                        <?php } ?>
                        <tr class="table-primary fw-bold text-dark fs-5">
                            <td colspan="6" class="text-end">TOTAL PENDAPATAN :</td>
                            <td>Rp. <?= number_format($total_pendapatan); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>