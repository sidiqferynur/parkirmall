<?php
// Mengaktifkan error reporting agar jika ada kendala database/query langsung terlihat
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

$nama = $_SESSION['nama'] ?? 'Admin';
$error_msg = '';

// Daftar kategori kendaraan yang didukung
$daftar_kategori = ['Motor', 'Mobil', 'Truk', 'Bis'];

// Mapping kategori -> icon Bootstrap Icons & warna badge
$kategori_style = [
    'Motor' => ['icon' => 'bi-scooter',        'warna' => 'bg-warning text-dark'],
    'Mobil' => ['icon' => 'bi-car-front-fill',  'warna' => 'bg-primary'],
    'Truk'  => ['icon' => 'bi-truck',           'warna' => 'bg-secondary'],
    'Bis'   => ['icon' => 'bi-bus-front-fill',  'warna' => 'bg-dark'],
];

// --- LOGIKA TAMBAH AREA PARKIR ---
if (isset($_POST['tambah_area'])) {
    $kapasitas = (int)$_POST['kapasitas'];
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    // Otomatis membuat nama_area menjadi "Khusus [Kategori]"
    $nama_area = "Khusus " . $kategori;

    if (!in_array($kategori, $daftar_kategori)) {
        $error_msg = "Kategori kendaraan tidak valid.";
    } else {
        $q = mysqli_query($koneksi, "INSERT INTO tb_area_parkir (nama_area, kategori, kapasitas, terisi) VALUES ('$nama_area', '$kategori', '$kapasitas', 0)");
        if ($q) {
            header("Location: crud_area_parkir.php");
            exit;
        } else {
            $error_msg = "Gagal menambah data area parkir: " . mysqli_error($koneksi);
        }
    }
}

// --- LOGIKA HAPUS AREA PARKIR ---
if (isset($_GET['hapus'])) {
    $id_area = (int)$_GET['hapus'];

    mysqli_begin_transaction($koneksi);
    try {
        $q_hapus_transaksi = mysqli_query($koneksi, "DELETE FROM tb_transaksi WHERE id_area=$id_area");
        if ($q_hapus_transaksi === false) {
            throw new Exception(mysqli_error($koneksi));
        }

        $q_hapus_area = mysqli_query($koneksi, "DELETE FROM tb_area_parkir WHERE id_area=$id_area");
        if ($q_hapus_area === false) {
            throw new Exception(mysqli_error($koneksi));
        }

        mysqli_commit($koneksi);
        header("Location: crud_area_parkir.php");
        exit;
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        $error_msg = "Gagal menghapus area parkir beserta riwayat transaksinya: " . $e->getMessage();
    }
}

$subquery_terisi = "(SELECT COUNT(*) FROM tb_transaksi t 
                     WHERE t.id_area = a.id_area 
                     AND (t.waktu_keluar IS NULL OR t.waktu_keluar = ''))";

$filter = $_GET['filter'] ?? '';
if ($filter !== '' && in_array($filter, $daftar_kategori)) {
    $filter_aman = mysqli_real_escape_string($koneksi, $filter);
    $query_area = mysqli_query($koneksi, "SELECT a.*, $subquery_terisi AS terisi_dinamis 
                                         FROM tb_area_parkir a 
                                         WHERE a.kategori='$filter_aman' 
                                         ORDER BY a.id_area DESC");
} else {
    $query_area = mysqli_query($koneksi, "SELECT a.*, $subquery_terisi AS terisi_dinamis 
                                         FROM tb_area_parkir a 
                                         ORDER BY a.kategori ASC, a.id_area DESC");
}

$rekap = [];
foreach ($daftar_kategori as $k) {
    $rekap[$k] = ['kapasitas' => 0, 'terisi' => 0];
}
$query_rekap = mysqli_query($koneksi, "SELECT a.kategori, 
                                        SUM(a.kapasitas) as total_kapasitas, 
                                        SUM($subquery_terisi) as total_terisi 
                                        FROM tb_area_parkir a 
                                        GROUP BY a.kategori");
if ($query_rekap) {
    while ($r = mysqli_fetch_assoc($query_rekap)) {
        if (isset($rekap[$r['kategori']])) {
            $rekap[$r['kategori']]['kapasitas'] = (int)$r['total_kapasitas'];
            $rekap[$r['kategori']]['terisi'] = (int)$r['total_terisi'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Area Parkir - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; }
        .sidebar { width: 260px; min-height: 100vh; background-color: #212529; color: #fff; position: fixed; top: 0; left: 0; z-index: 1000; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.75); padding: 12px 20px; font-weight: 500; border-radius: 8px; margin: 4px 15px; display: flex; align-items: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: #0d6efd; }
        .sidebar .nav-link i { font-size: 1.2rem; margin-right: 12px; }
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
        .content-body { padding: 25px; flex: 1; }
        .kategori-badge { font-size: 0.85rem; padding: 6px 12px; }
        .rekap-card { border-radius: 12px; transition: transform .15s ease; }
        .rekap-card:hover { transform: translateY(-3px); }
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
                    <a href="admin.php" class="nav-link"><i class="bi bi-house-door-fill"></i> Home</a>
                </li>
                <li>
                    <a href="crud_user.php" class="nav-link"><i class="bi bi-people-fill"></i> User</a>
                </li>
                <li>
                    <a href="crud_tarif.php" class="nav-link"><i class="bi bi-cash-coin"></i> Tarif</a>
                </li>
                <li>
                    <a href="crud_area_parkir.php" class="nav-link active"><i class="bi bi-geo-alt-fill"></i> Area Parkir</a>
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
                <strong><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama); ?> (Admin)</strong>
            </div>
            <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-body">
            
            <h2 class="fw-bold mb-3">Kelola Area Parkir</h2>

            <!-- Kata-kata Khusus / Catatan Administrator -->
            <div class="alert alert-primary border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Catatan Khusus Administrator:</strong> Pengelolaan kapasitas slot parkir yang tertib dan akurat adalah kunci utama kelancaran arus kendaraan serta kenyamanan para pengunjung Mall.
                </div>
            </div>

            <?php if (!empty($error_msg)) { ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <!-- REKAP PER KATEGORI -->
            <div class="row g-3 mb-4">
                <?php foreach ($daftar_kategori as $k) {
                    $style = $kategori_style[$k];
                    $total_kap = $rekap[$k]['kapasitas'];
                    $total_isi = $rekap[$k]['terisi'];
                    $sisa = $total_kap - $total_isi;
                ?>
                <div class="col-6 col-md-3">
                    <a href="crud_area_parkir.php?filter=<?php echo $k; ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rekap-card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle <?php echo $style['warna']; ?> d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                    <i class="bi <?php echo $style['icon']; ?> text-white fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-medium"><?php echo $k; ?></div>
                                    <div class="fw-bold text-dark"><?php echo $sisa; ?> / <?php echo $total_kap; ?> Slot</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php } ?>
            </div>

            <?php if ($filter !== '' && in_array($filter, $daftar_kategori)) { ?>
                <div class="alert alert-info d-flex justify-content-between align-items-center rounded-3 shadow-sm mb-4">
                    <span><i class="bi bi-funnel-fill me-2"></i> Menampilkan area kategori: <strong><?php echo htmlspecialchars($filter); ?></strong></span>
                    <a href="crud_area_parkir.php" class="btn btn-sm btn-outline-secondary">Tampilkan Semua</a>
                </div>
            <?php } ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-dark text-white fw-semibold py-3">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Area Parkir
                        </div>
                        <div class="card-body p-4">
                            <form action="crud_area_parkir.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Kategori Kendaraan</label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="" disabled selected>Pilih kategori...</option>
                                        <?php foreach ($daftar_kategori as $k) { ?>
                                            <option value="<?php echo $k; ?>"><?php echo $k; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary fw-medium">Kapasitas Slot</label>
                                    <input type="number" name="kapasitas" class="form-control" placeholder="Contoh: 50" min="1" required>
                                </div>
                                <button type="submit" name="tambah_area" class="btn btn-primary w-100 py-2 fw-semibold rounded-3">
                                    Simpan Area
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                        <div class="card-header bg-primary text-white fw-semibold py-3">
                            <i class="bi bi-p-square me-2"></i> Status Area Saat Ini
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Nama Area</th>
                                            <th>Kategori</th>
                                            <th>Kapasitas Total</th>
                                            <th>Terisi</th>
                                            <th>Sisa Slot</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($query_area && mysqli_num_rows($query_area) > 0) { ?>
                                            <?php while ($a = mysqli_fetch_assoc($query_area)) { 
                                                $terisi = isset($a['terisi_dinamis']) ? (int)$a['terisi_dinamis'] : 0;
                                                $sisa = $a['kapasitas'] - $terisi;
                                                $kat = $a['kategori'] ?? 'Mobil';
                                                $style = $kategori_style[$kat] ?? ['icon' => 'bi-question-circle', 'warna' => 'bg-secondary'];
                                                
                                                // Menampilkan nama area berawalan "Khusus "
                                                $nama_area_tampil = "Khusus " . $kat;
                                            ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($nama_area_tampil); ?></td>
                                                    <td>
                                                        <span class="badge kategori-badge <?php echo $style['warna']; ?>">
                                                            <i class="bi <?php echo $style['icon']; ?> me-1"></i> <?php echo htmlspecialchars($kat); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $a['kapasitas']; ?> Kendaraan</td>
                                                    <td><?php echo $terisi; ?></td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill px-3 py-2">
                                                            <?php echo $sisa; ?> Slot
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="crud_area_parkir.php?hapus=<?php echo $a['id_area']; ?>" class="btn btn-danger btn-sm px-3" onclick="return confirm('PERHATIAN: Menghapus area ini akan MENGHAPUS PERMANEN seluruh riwayat transaksi parkir yang terhubung dengan area ini juga. Tindakan ini tidak bisa dibatalkan. Yakin ingin melanjutkan?')" title="Hapus">
                                                            <i class="bi bi-trash-fill me-1"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Belum ada data area parkir.</td>
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

        <footer class="bg-white border-top py-3 text-center text-muted">
            <div class="container-fluid">
                <small>&copy; <?php echo date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
            </div>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>