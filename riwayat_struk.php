<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$conn = $conn ?? $koneksi ?? null;

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$nama = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas Parkir';

// ===== Handle aksi CETAK atau KELUAR =====
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $aksi = $_GET['aksi'];
    $id_raw = $_GET['id']; // format: T26 atau R18
    $sumber_id = strtoupper(substr($id_raw, 0, 1)); // 'T' atau 'R'
    $id_asli = intval(substr($id_raw, 1));

    if ($sumber_id === 'R') {
        // ---- Reservasi ----
        $cek = mysqli_query($conn, "SELECT waktu_keluar_aktual FROM tb_reservasi WHERE id = $id_asli");
        if ($cek && $row_c = mysqli_fetch_assoc($cek)) {
            if (empty($row_c['waktu_keluar_aktual'])) {
                // Selain mencatat waktu keluar, status juga diubah jadi 'Selesai'
                // supaya halaman Kelola Reservasi tidak nyangkut di 'Approved'
                // walau kendaraan sudah benar-benar keluar.
                mysqli_query($conn, "UPDATE tb_reservasi SET waktu_keluar_aktual = NOW(), status = 'Selesai' WHERE id = $id_asli");
            }
        }
        if ($aksi === 'cetak') {
            header("Location: cetak_struk.php?id=" . $id_asli . "&sumber=reservasi");
            exit;
        } else {
            // aksi=keluar saja -> refresh halaman ini
            header("Location: riwayat_struk.php");
            exit;
        }
    } else {
        // ---- Transaksi biasa ----
        $cek_waktu = mysqli_query($conn, "SELECT waktu_keluar FROM tb_transaksi WHERE id_parkir = $id_asli");
        if ($cek_waktu && $row_c = mysqli_fetch_assoc($cek_waktu)) {
            if (empty($row_c['waktu_keluar']) || $row_c['waktu_keluar'] == '0000-00-00 00:00:00') {
                mysqli_query($conn, "UPDATE tb_transaksi SET waktu_keluar = NOW(), status = 'keluar' WHERE id_parkir = $id_asli");
            }
        }
        header("Location: cetak_struk.php?id=" . $id_asli . "&sumber=transaksi");
        exit;
    }
}

// ===== Ambil data =====
$query = "SELECT CONCAT('T', t.id_parkir) AS id_parkir, 
                 COALESCE(k.plat_nomor, '-') AS plat_nomor, 
                 COALESCE(k.jenis_kendaraan, '-') AS jenis_kendaraan, 
                 COALESCE(a.nama_area, 'Area Regular') AS nama_area,
                 CAST(t.waktu_masuk AS DATETIME) AS waktu_masuk,
                 CAST(t.waktu_keluar AS DATETIME) AS waktu_keluar,
                 t.biaya_total,
                 'transaksi' AS sumber
          FROM tb_transaksi t
          LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
          LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
          WHERE t.status = 'keluar' OR t.waktu_keluar IS NOT NULL

          UNION

          SELECT CONCAT('R', r.id) AS id_parkir,
                 r.plat_nomor,
                 r.jenis_kendaraan,
                 COALESCE(NULLIF(r.area_parkir, ''), 'Area Reservasi Mall') AS nama_area,
                 STR_TO_DATE(CONCAT(r.tanggal_reservasi, ' ', r.jam_reservasi), '%Y-%m-%d %H:%i:%s') AS waktu_masuk,
                 CAST(r.waktu_keluar_aktual AS DATETIME) AS waktu_keluar,
                 0 AS biaya_total,
                 'reservasi' AS sumber
          FROM tb_reservasi r
          WHERE LOWER(r.status) IN ('approved', 'setujui', 'selesai')

          ORDER BY waktu_masuk ASC";
         
$result = mysqli_query($conn, $query);

$semua_baris = [];
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $semua_baris[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Struk - Parkir Mall</title>
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

    <aside class="sidebar d-flex flex-column justify-content-between p-3">
        <div>
            <div class="d-flex align-items-center mb-4 px-3 pt-2">
                <i class="bi bi-p-square-fill fs-2 text-primary me-2"></i>
                <span class="fs-4 fw-bold">Parkir Mall</span>
            </div>
            <hr class="text-secondary">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="petugas.php" class="nav-link"><i class="bi bi-house-door-fill"></i> Home</a>
                </li>
                <li>
                    <a href="transaksi.php" class="nav-link"><i class="bi bi-car-front-fill"></i> Input Parkir</a>
                </li>
                <li>
                    <a href="riwayat_struk.php" class="nav-link active"><i class="bi bi-receipt"></i> Riwayat Struk</a>
                </li>
            </ul>
        </div>
        <div>
            <hr class="text-secondary">
            <div class="px-3 mb-3 text-light">
                <small class="d-block text-muted">Login sebagai:</small>
                <strong><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama); ?> (Petugas)</strong>
            </div>
            <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center" onclick="return confirm('Yakin ingin keluar?')">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-body">
            
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="fw-bold mb-1">Riwayat Struk Transaksi</h2>
                <p class="text-muted mb-0">Daftar seluruh transaksi dan reservasi parkir yang telah tercatat di sistem.</p>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#ID</th>
                                    <th>Plat Nomor</th>
                                    <th>Jenis Kendaraan</th>
                                    <th>Area</th>
                                    <th>Waktu Masuk</th>
                                    <th>Waktu Keluar</th>
                                    <th>Total Biaya</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($semua_baris)): ?>
                                    <?php foreach ($semua_baris as $row): 
                                        $plat = $row['plat_nomor'] ?? '-';
                                        $jenis_kendaraan = $row['jenis_kendaraan'] ?? '-';
                                        $area = $row['nama_area'] ?? '-';
                                        $waktu_masuk = $row['waktu_masuk'];
                                        $sudah_keluar = !empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00';

                                        $waktu_keluar = $sudah_keluar ? $row['waktu_keluar'] : date('Y-m-d H:i:s');

                                        $masuk = strtotime($waktu_masuk);
                                        $keluar = strtotime($waktu_keluar);
                                        $durasi_jam = ($keluar > $masuk) ? ceil(($keluar - $masuk) / 3600) : 1;
                                        if ($durasi_jam < 1) $durasi_jam = 1;

                                        $jk = mysqli_real_escape_string($conn, $jenis_kendaraan);
                                        $q_t = mysqli_query($conn, "SELECT tarif_per_jam FROM tb_tarif WHERE LOWER(TRIM(jenis_kendaraan)) = LOWER(TRIM('$jk')) LIMIT 1");
                                        $tarif = ($q_t && mysqli_num_rows($q_t) > 0) ? intval(mysqli_fetch_assoc($q_t)['tarif_per_jam']) : 2000;
                                        
                                        $biaya = intval($row['biaya_total']);
                                        if ($biaya <= 0 && $sudah_keluar) {
                                            $biaya = $durasi_jam * $tarif;
                                        }

                                        $is_reservasi = $row['sumber'] === 'reservasi';
                                    ?>
                                        <tr>
                                            <td class="ps-3 fw-bold">#<?= $row['id_parkir']; ?></td>
                                            <td class="fw-bold"><span class="badge bg-dark px-2 py-1"><?= htmlspecialchars($plat); ?></span></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($jenis_kendaraan); ?></span></td>
                                            <td><?= htmlspecialchars($area); ?></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($waktu_masuk); ?></small></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= $sudah_keluar ? htmlspecialchars($row['waktu_keluar']) : '<span class="text-danger">Belum Keluar</span>'; ?>
                                                </small>
                                            </td>
                                            <td class="fw-semibold">Rp <?= number_format($biaya, 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <?php if ($is_reservasi && !$sudah_keluar): ?>
                                                    <a href="riwayat_struk.php?aksi=keluar&id=<?= $row['id_parkir']; ?>" class="btn btn-warning btn-sm fw-bold mb-1" onclick="return confirm('Catat kendaraan ini keluar sekarang?')">
                                                        <i class="bi bi-box-arrow-right me-1"></i> Keluar
                                                    </a>
                                                <?php endif; ?>
                                                <a href="riwayat_struk.php?aksi=cetak&id=<?= $row['id_parkir']; ?>" target="_blank" class="btn btn-dark btn-sm fw-bold mb-1">
                                                    <i class="bi bi-printer me-1"></i> Cetak Struk
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data transaksi tersimpan di database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <footer class="bg-white border-top py-3 text-center text-muted shadow-sm">
            <div class="container-fluid">
                <small>&copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
            </div>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>