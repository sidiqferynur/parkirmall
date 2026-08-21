<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'owner') {
    header("Location: login.php?pesan=belum_login");
    exit;
}

include "koneksi.php";

$conn = $conn ?? $koneksi ?? null;

$today = date('Y-m-d');
$dari_tanggal = isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : '';
$sampai_tanggal = isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : '';
$filter_mode = isset($_GET['mode']) ? $_GET['mode'] : '';

if ($filter_mode == 'hari_ini') {
    $dari_tanggal = $today; $sampai_tanggal = $today;
} elseif ($filter_mode == 'kemarin') {
    $dari_tanggal = date('Y-m-d', strtotime('-1 day')); $sampai_tanggal = date('Y-m-d', strtotime('-1 day'));
} elseif ($filter_mode == 'bulan_ini') {
    $dari_tanggal = date('Y-m-01'); $sampai_tanggal = date('Y-m-t');
} elseif ($filter_mode == 'bulan_lalu') {
    $dari_tanggal = date('Y-m-01', strtotime('first day of last month')); $sampai_tanggal = date('Y-m-t', strtotime('last day of last month'));
} elseif ($filter_mode == 'semua') {
    $dari_tanggal = ''; $sampai_tanggal = '';
}

$filter_transaksi = "";
$filter_reservasi = "";
if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
    $filter_transaksi = " AND DATE(t.waktu_masuk) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
    $filter_reservasi = " AND DATE(STR_TO_DATE(CONCAT(r.tanggal_reservasi, ' ', r.jam_reservasi), '%Y-%m-%d %H:%i:%s')) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'";
}

$query_gabungan = "SELECT CONCAT('T', t.id_parkir) AS id_parkir, 
                          COALESCE(k.plat_nomor, '-') AS plat_nomor, 
                          COALESCE(k.jenis_kendaraan, '-') AS jenis_kendaraan, 
                          CAST(t.waktu_masuk AS DATETIME) AS waktu_masuk,
                          CAST(t.waktu_keluar AS DATETIME) AS waktu_keluar,
                          t.biaya_total,
                          'transaksi' AS sumber
                   FROM tb_transaksi t
                   LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                   WHERE (t.status = 'keluar' OR t.waktu_keluar IS NOT NULL) $filter_transaksi

                   UNION

                   SELECT CONCAT('R', r.id) AS id_parkir,
                          r.plat_nomor,
                          r.jenis_kendaraan,
                          STR_TO_DATE(CONCAT(r.tanggal_reservasi, ' ', r.jam_reservasi), '%Y-%m-%d %H:%i:%s') AS waktu_masuk,
                          CAST(r.waktu_keluar_aktual AS DATETIME) AS waktu_keluar,
                          0 AS biaya_total,
                          'reservasi' AS sumber
                   FROM tb_reservasi r
                   WHERE LOWER(r.status) IN ('approved', 'setujui', 'selesai') $filter_reservasi

                   ORDER BY waktu_masuk DESC";

$result_tabel = mysqli_query($conn, $query_gabungan);

$total_kendaraan = 0;
$total_pendapatan = 0;
$semua_baris = [];

if ($result_tabel) {
    while ($row = mysqli_fetch_assoc($result_tabel)) {
        $waktu_masuk = $row['waktu_masuk'];
        $sudah_keluar = !empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00';
        $waktu_keluar = $sudah_keluar ? $row['waktu_keluar'] : date('Y-m-d H:i:s');

        $masuk = strtotime($waktu_masuk);
        $keluar = strtotime($waktu_keluar);
        $durasi_jam = ($keluar > $masuk) ? ceil(($keluar - $masuk) / 3600) : 1;
        if ($durasi_jam < 1) $durasi_jam = 1;

        $jk = mysqli_real_escape_string($conn, $row['jenis_kendaraan'] ?? 'Motor');
        $q_t = mysqli_query($conn, "SELECT tarif_per_jam FROM tb_tarif WHERE LOWER(TRIM(jenis_kendaraan)) = LOWER(TRIM('$jk')) LIMIT 1");
        $tarif = ($q_t && mysqli_num_rows($q_t) > 0) ? intval(mysqli_fetch_assoc($q_t)['tarif_per_jam']) : 2000;

        $biaya = intval($row['biaya_total']);
        if ($biaya <= 0 && $sudah_keluar) {
            $biaya = $durasi_jam * $tarif;
        }

        $row['biaya_total'] = $biaya;
        $semua_baris[] = $row;

        $total_kendaraan++;
        $total_pendapatan += $biaya;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Parkir - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        html, body { height: 100%; margin: 0; }
        body { background-color: #f4f6f9; display: flex; flex-direction: column; min-height: 100vh; }
        .wrapper { display: flex; flex: 1; width: 100%; }
        .sidebar { width: 260px; background-color: #212529; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar-brand { padding: 0 20px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { padding: 20px; list-style: none; margin: 0; flex: 1; }
        .sidebar-menu li { margin-bottom: 10px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #adb5bd; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #0d6efd; color: #fff; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; justify-content: space-between; }
        .card-section { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 25px; }
        .footer-custom { background-color: #ffffff; border-radius: 16px; box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.03); color: #6c757d; font-size: 14px; padding: 15px 25px; margin-top: 30px; }
        @media print {
            .sidebar, .btn, form, footer, .nav, .print-hide { display: none !important; }
            body { background-color: white !important; }
            .main-content { padding: 0 !important; }
            .card-section, .card { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <span class="bg-primary text-white px-2 py-1 rounded fw-bold fs-5">P</span>
                <h4 class="fw-bold text-white mb-0">Parkir Mall</h4>
            </div>
            <ul class="sidebar-menu">
                <li><a href="owner.php"><i class="bi bi-house-door fs-5"></i> Home</a></li>
                <li><a href="laporan_keuangan.php" class="active"><i class="bi bi-wallet2 fs-5"></i> Laporan Keuangan</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-3 mb-3 px-2">
                <i class="bi bi-person-circle fs-3 text-muted"></i>
                <div class="overflow-hidden">
                    <h6 class="mb-0 text-white text-truncate fw-bold">Pemilik Mall</h6>
                    <small class="text-muted text-truncate d-block" style="font-size: 11px;">(Owner)</small>
                </div>
            </div>
            <button onclick="konfirmasiLogout()" class="btn btn-danger w-100 fw-bold py-2 rounded-3 d-flex align-items-center justify-content-center gap-2 border-0">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </button>
        </div>
    </div>

    <div class="main-content">
        <div>
            <div class="print-header">
                <h2 class="fw-bold">LAPORAN KEUANGAN PARKIR MALL</h2>
                <p class="text-muted small">Dicetak pada: <?= date('d-m-Y H:i:s'); ?></p>
                <hr>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark mb-0"><i class="bi bi-wallet2 text-primary me-2"></i>Laporan Keuangan Parkir</h2>
                <button onclick="window.print()" class="btn btn-success fw-semibold px-4 shadow-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>

            <div class="mb-4 print-hide">
                <span class="fw-bold me-2 text-secondary">Pintasan Cepat:</span>
                <a href="laporan_keuangan.php?mode=hari_ini" class="btn btn-sm btn-outline-primary mb-1 fw-semibold">Hari Ini</a>
                <a href="laporan_keuangan.php?mode=kemarin" class="btn btn-sm btn-outline-secondary mb-1 fw-semibold">Kemarin</a>
                <a href="laporan_keuangan.php?mode=bulan_ini" class="btn btn-sm btn-outline-success mb-1 fw-semibold">Bulan Ini</a>
                <a href="laporan_keuangan.php?mode=bulan_lalu" class="btn btn-sm btn-outline-warning mb-1 fw-semibold">Bulan Lalu</a>
                <a href="laporan_keuangan.php?mode=semua" class="btn btn-sm btn-outline-dark mb-1 fw-semibold">Semua Data</a>
            </div>

            <div class="card-section print-hide">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="dari_tanggal" class="form-label fw-semibold">Dari Tanggal:</label>
                        <input type="date" class="form-control" id="dari_tanggal" name="dari_tanggal" value="<?php echo htmlspecialchars($dari_tanggal); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="sampai_tanggal" class="form-label fw-semibold">Sampai Tanggal:</label>
                        <input type="date" class="form-control" id="sampai_tanggal" name="sampai_tanggal" value="<?php echo htmlspecialchars($sampai_tanggal); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success fw-semibold px-4"><i class="bi bi-filter me-1"></i> Filter</button>
                        <a href="laporan_keuangan.php" class="btn btn-secondary fw-semibold">Reset</a>
                    </div>
                </form>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card p-4 bg-primary text-white shadow-sm border-0 rounded-4">
                        <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Transaksi Selesai</h6>
                        <h3 class="fw-bold mb-0"><?php echo number_format($total_kendaraan); ?> Kendaraan</h3>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4 bg-success text-white shadow-sm border-0 rounded-4">
                        <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Pendapatan Parkir</h6>
                        <h3 class="fw-bold mb-0">Rp. <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>

            <div class="card-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="py-3">ID Transaksi</th>
                                <th class="py-3">Plat Nomor</th>
                                <th class="py-3">Jenis Kendaraan</th>
                                <th class="py-3">Masuk</th>
                                <th class="py-3">Keluar</th>
                                <th class="py-3">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($semua_baris)): ?>
                                <?php foreach ($semua_baris as $row): 
                                    $sudah_keluar = !empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00';
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $row['id_parkir']; ?></strong></td>
                                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($row['plat_nomor']); ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['jenis_kendaraan']); ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $row['waktu_masuk'] ?? '-'; ?></span></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo $sudah_keluar ? $row['waktu_keluar'] : 'Belum Keluar'; ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-success">Rp. <?php echo number_format($row['biaya_total'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data transaksi untuk rentang waktu ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="footer-custom d-flex flex-wrap justify-content-between align-items-center">
            <div class="col-md-6 d-flex align-items-center">
                <span class="text-muted">&copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</span>
            </div>
            <ul class="nav col-md-6 justify-content-end list-unstyled d-flex">
                <li class="ms-3"><span class="text-muted small">v1.0.0</span></li>
            </ul>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    function konfirmasiLogout() {
        Swal.fire({
            title: 'Apakah Anda yakin ingin keluar?',
            text: "Anda akan keluar dari sesi sistem parkir ini.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }
        });
    }
</script>

</body>
</html>