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

// Tangkap data otomatis jika ada parameter ?id= dari halaman reservasi yang disetujui
$auto_plat = '';
$auto_pemilik = '';
if (isset($_GET['id']) && $conn) {
    $id_res_masuk = mysqli_real_escape_string($conn, $_GET['id']);
    $q_get_res = mysqli_query($conn, "SELECT * FROM tb_reservasi WHERE id = '$id_res_masuk' LIMIT 1");
    if ($q_get_res && mysqli_num_rows($q_get_res) > 0) {
        $d_res = mysqli_fetch_assoc($q_get_res);
        $auto_plat = $d_res['plat_nomor'] ?? '';
        $auto_pemilik = $d_res['nama'] ?? '';
    }
}

// === 1. PROSES SIMPAN KENDARAAN MASUK ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_masuk']) && $conn) {
    $plat_nomor  = mysqli_real_escape_string($conn, $_POST['id_kendaraan'] ?? '');
    $pemilik     = mysqli_real_escape_string($conn, $_POST['pemilik'] ?? '');
    $warna       = mysqli_real_escape_string($conn, $_POST['warna'] ?? '');
    $id_tarif    = mysqli_real_escape_string($conn, $_POST['tarif_id'] ?? '');
    $id_area     = mysqli_real_escape_string($conn, $_POST['area_id'] ?? '');
    $waktu_masuk = date('Y-m-d H:i:s');
    $status      = 'masuk';
    $id_user     = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 1;

    if (!empty($plat_nomor)) {
        // A0. Ambil nama jenis kendaraan dari tarif yang dipilih
        $jenis_kendaraan = '';
        if (!empty($id_tarif)) {
            $q_jenis = mysqli_query($conn, "SELECT jenis_kendaraan FROM tb_tarif WHERE id_tarif = '$id_tarif' LIMIT 1");
            if ($q_jenis && mysqli_num_rows($q_jenis) > 0) {
                $d_jenis = mysqli_fetch_assoc($q_jenis);
                $jenis_kendaraan = mysqli_real_escape_string($conn, $d_jenis['jenis_kendaraan']);
            }
        }

        // A. Cek apakah plat nomor sudah ada di tb_kendaraan
        $cek_kendaraan = mysqli_query($conn, "SELECT * FROM tb_kendaraan WHERE plat_nomor = '$plat_nomor'");
        
        if (mysqli_num_rows($cek_kendaraan) == 0) {
            mysqli_query($conn, "INSERT INTO tb_kendaraan (plat_nomor, pemilik, warna, jenis_kendaraan) VALUES ('$plat_nomor', '$pemilik', '$warna', '$jenis_kendaraan')");
        } else {
            mysqli_query($conn, "UPDATE tb_kendaraan SET pemilik='$pemilik', warna='$warna', jenis_kendaraan='$jenis_kendaraan' WHERE plat_nomor='$plat_nomor'");
        }

        // B. Ambil ID primary key (id_kendaraan) dari tb_kendaraan
        $q_k = mysqli_query($conn, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat_nomor' LIMIT 1");
        $d_k = mysqli_fetch_assoc($q_k);
        $fk_kendaraan = $d_k['id_kendaraan'] ?? null;

        // C. Simpan transaksi masuk ke tb_transaksi jika fk_kendaraan valid
        if ($fk_kendaraan) {
            $query_in = "INSERT INTO tb_transaksi (id_kendaraan, pemilik, warna, waktu_masuk, id_tarif, status, id_user, id_area) 
                         VALUES ('$fk_kendaraan', '$pemilik', '$warna', '$waktu_masuk', '$id_tarif', '$status', '$id_user', '$id_area')";
            mysqli_query($conn, $query_in);

            // D. Update status reservasi menjadi Selesai jika berasal dari reservasi
            mysqli_query($conn, "UPDATE tb_reservasi SET status = 'Selesai' WHERE plat_nomor = '$plat_nomor'");
        }
    }

    header("Location: transaksi.php");
    exit;
}

// === 2. PROSES KENDARAAN KELUAR ===
if (isset($_GET['aksi']) && $_GET['aksi'] == 'keluar' && isset($_GET['id']) && $conn) {
    $id_parkir    = mysqli_real_escape_string($conn, $_GET['id']);
    $waktu_keluar = date('Y-m-d H:i:s');

    $sql_up = "UPDATE tb_transaksi SET waktu_keluar = '$waktu_keluar', status = 'keluar' WHERE id_parkir = '$id_parkir'";
    mysqli_query($conn, $sql_up);

    header("Location: transaksi.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Parkir - Petugas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; display: flex; flex-direction: column; }
        .main-container { flex: 1; }
    </style>
</head>
<body class="bg-light">

    <div class="main-container container mt-4 mb-5">
        <div class="mb-3 d-flex justify-content-between">
            <a href="petugas.php" class="btn btn-secondary btn-sm fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <a href="reservasi_petugas.php" class="btn btn-warning btn-sm fw-bold text-dark">
                <i class="bi bi-calendar-check me-1"></i> Kelola/Setujui Reservasi Masuk
            </a>
        </div>

        <h2 class="fw-bold mb-4">Menu Transaksi Parkir</h2>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-primary text-white fw-bold py-3 rounded-top-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Input Kendaraan Masuk
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            
                            <div class="mb-3">
                                <label for="id_kendaraan" class="form-label">Plat Nomor / Kendaraan</label>
                                <div class="input-group">
                                    <input type="text" id="id_kendaraan" name="id_kendaraan" value="<?= htmlspecialchars($auto_plat); ?>" class="form-control" placeholder="Ketik atau ambil reservasi..." autocomplete="off" required>
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bookmark-check"></i> Dari Reservasi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" style="max-height: 220px; overflow-y: auto;">
                                        <li><h6 class="dropdown-header">Reservasi Disetujui</h6></li>
                                        <?php
                                        $q_res = $conn ? mysqli_query($conn, "SELECT * FROM tb_reservasi WHERE status = 'Approved'") : null;
                                        if ($q_res && mysqli_num_rows($q_res) > 0) {
                                            while($res = mysqli_fetch_assoc($q_res)) {
                                                $plat_res   = $res['plat_nomor'] ?? '';
                                                $pemilik_res = $res['nama'] ?? '';
                                                
                                                $js_plat = json_encode($plat_res);
                                                $js_pemilik = json_encode($pemilik_res);
                                                
                                                echo "<li><a class='dropdown-item py-2' href='#' onclick=\"document.getElementById('id_kendaraan').value=$js_plat; document.getElementById('pemilik').value=$js_pemilik; return false;\">
                                                        <strong>" . htmlspecialchars($plat_res) . "</strong><br><small class='text-muted'>" . htmlspecialchars($pemilik_res) . "</small>
                                                     </a></li>";
                                            }
                                        } else {
                                            echo '<li><span class="dropdown-item text-muted small">Tidak ada reservasi disetujui</span></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pemilik" class="form-label">Nama Pemilik</label>
                                <input type="text" id="pemilik" name="pemilik" value="<?= htmlspecialchars($auto_pemilik); ?>" class="form-control" placeholder="Masukkan nama pemilik..." required>
                            </div>

                            <div class="mb-3">
                                <label for="warna" class="form-label">Warna Kendaraan</label>
                                <input type="text" id="warna" name="warna" class="form-control" placeholder="Masukkan warna kendaraan..." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kategori & Tarif</label>
                                <select class="form-select" name="tarif_id" required>
                                    <option value="" selected disabled>Pilih Kategori Tarif</option>
                                    <?php
                                    $q_tarif = $conn ? mysqli_query($conn, "SELECT * FROM tb_tarif") : null;
                                    if ($q_tarif && mysqli_num_rows($q_tarif) > 0) {
                                        while($t = mysqli_fetch_assoc($q_tarif)) {
                                            $id_t   = $t['id_tarif'] ?? $t['id'] ?? 1;
                                            $nama_t = $t['jenis_kendaraan'] ?? 'Tarif';
                                            $harga_t = $t['tarif_per_jam'] ?? 0;
                                            
                                            echo "<option value='$id_t'>" . htmlspecialchars($nama_t) . " - Rp " . number_format($harga_t, 0, ',', '.') . "</option>";
                                        }
                                    } else {
                                        echo '<option value="1">Mobil - Rp 20.000</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pilih Area & Sisa Slot Parkir</label>
                                <select class="form-select" name="area_id" required>
                                    <option value="" selected disabled>Pilih Area Parkir</option>
                                    <?php
                                    // Query memperbarui dropdown menggunakan nama_area dari tabel tb_area_parkir
                                    $q_area = $conn ? mysqli_query($conn, "SELECT a.*, 
                                        (a.kapasitas - (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND (t.waktu_keluar IS NULL OR t.waktu_keluar = ''))) AS sisa_slot 
                                        FROM tb_area_parkir a") : null;
                                    
                                    if ($q_area && mysqli_num_rows($q_area) > 0) {
                                        while($a = mysqli_fetch_assoc($q_area)) {
                                            $id_a   = $a['id_area'] ?? $a['id'] ?? 1;
                                            $nama_a = $a['nama_area'] ?? $a['nama'] ?? $a['lokasi'] ?? 'Area';
                                            $sisa   = $a['sisa_slot'] ?? 0;
                                            
                                            $status_slot = ($sisa > 0) ? "Sisa Slot: $sisa" : "Penuh";
                                            $disabled    = ($sisa <= 0) ? "disabled" : "";
                                            
                                            // Menampilkan nama_area (misal: Khusus Motor, Khusus Mobil, dll)
                                            echo "<option value='$id_a' $disabled>" . htmlspecialchars($nama_a) . " ($status_slot)</option>";
                                        }
                                    } else {
                                        echo '<option value="1">Khusus Motor (Sisa Slot: 10)</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <button type="submit" name="simpan_masuk" class="btn btn-success w-100 fw-bold py-2">
                                <i class="bi bi-check-circle me-1"></i> Simpan Masuk
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-dark text-white fw-bold py-3 rounded-top-4">
                        <i class="bi bi-car-front me-2"></i> Daftar Kendaraan Aktif di Area Parkir
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">ID Parkir</th>
                                        <th>Jenis Kendaraan</th>
                                        <th>Plat Nomor</th>
                                        <th>Pemilik</th> 
                                        <th>Warna</th> 
                                        <th>Area Parkir</th>
                                        <th>Waktu Masuk</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_aktif = $conn ? mysqli_query($conn, "SELECT t.*, a.nama_area, k.plat_nomor, tr.jenis_kendaraan FROM tb_transaksi t 
                                                         LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area 
                                                         LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan 
                                                         LEFT JOIN tb_tarif tr ON t.id_tarif = tr.id_tarif 
                                                         WHERE t.waktu_keluar IS NULL OR t.waktu_keluar = ''") : null;
                                    
                                    if ($query_aktif && mysqli_num_rows($query_aktif) > 0) {
                                        while ($row = mysqli_fetch_assoc($query_aktif)) {
                                            $id_parkir   = $row['id_parkir'];
                                            $jenis_kend  = $row['jenis_kendaraan'] ?? 'Kendaraan';
                                            $plat_nomor  = $row['plat_nomor'] ?? $row['id_kendaraan'];
                                            $pemilik     = $row['pemilik'] ?? '-';
                                            $warna       = $row['warna'] ?? '-';
                                            $nama_area   = $row['nama_area'] ?? 'Area';
                                            $w_masuk     = $row['waktu_masuk'];
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold">#<?= htmlspecialchars($id_parkir); ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($jenis_kend); ?></span></td>
                                        <td class="fw-bold"><?= htmlspecialchars($plat_nomor); ?></td>
                                        <td><?= htmlspecialchars($pemilik); ?></td>
                                        <td><?= htmlspecialchars($warna); ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($nama_area); ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($w_masuk); ?></small></td>
                                        <td class="text-center">
                                            <a href="transaksi.php?aksi=keluar&id=<?= $id_parkir; ?>" class="btn btn-danger btn-sm fw-bold" onclick="return confirm('Proses kendaraan keluar?');">
                                                Proses Keluar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada kendaraan aktif saat ini.</td></tr>';
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

    <footer class="bg-white text-center py-4 mt-5 border-top shadow-sm">
        <div class="container">
            <p class="text-muted mb-0 small">
                &copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya | SMKN 1 SANDEN 2026</strong>. All Rights Reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>