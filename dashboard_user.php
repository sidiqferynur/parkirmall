<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';
$conn = $conn ?? $koneksi ?? null;

// Halaman ini sebelumnya bisa diakses tanpa login sama sekali,
// beda dengan admin.php / owner.php / petugas.php yang semuanya
// mengecek session login. Disamakan di sini.
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$pesan_sukses = "";
$pesan_error = "";

// Proses Simpan Reservasi langsung dari halaman dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_reservasi'])) {
    $nama            = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $plat_nomor      = mysqli_real_escape_string($conn, $_POST['plat_nomor'] ?? '');
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan'] ?? '');
    $warna           = mysqli_real_escape_string($conn, $_POST['warna'] ?? ''); // Menangkap input warna
    $area_parkir     = mysqli_real_escape_string($conn, $_POST['area_parkir'] ?? ''); // Menangkap area/blok parkir
    $tanggal         = mysqli_real_escape_string($conn, $_POST['tanggal_reservasi'] ?? '');
    $jam             = mysqli_real_escape_string($conn, $_POST['jam_reservasi'] ?? '');
    $status          = 'Pending';

    // id_user diambil dari session, bukan dari form, supaya tidak bisa
    // dimanipulasi orang lain untuk membuat reservasi atas nama user lain.
    $id_user_login = (int) ($_SESSION['id_user'] ?? 0);

    if (!empty($nama) && !empty($plat_nomor) && !empty($jenis_kendaraan) && !empty($warna) && !empty($area_parkir) && !empty($tanggal) && !empty($jam)) {
        $query = "INSERT INTO tb_reservasi (id_user, nama, plat_nomor, jenis_kendaraan, warna, area_parkir, tanggal_reservasi, jam_reservasi, status) 
                  VALUES ('$id_user_login', '$nama', '$plat_nomor', '$jenis_kendaraan', '$warna', '$area_parkir', '$tanggal', '$jam', '$status')";
        
        if (mysqli_query($conn, $query)) {
            $pesan_sukses = "Reservasi berhasil dikirim! Silakan cek status Anda pada tabel di bawah.";
        } else {
            $pesan_error = "Gagal menyimpan reservasi: " . mysqli_error($conn);
        }
    } else {
        $pesan_error = "Semua kolom pada form reservasi wajib diisi, termasuk area/blok parkir!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Reservasi Pengguna - E-Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <audio id="logout-sound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <div class="container py-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-speedometer2 text-primary me-2"></i> Dashboard Pengunjung
                </h3>
                <p class="text-muted mb-0 small">Sistem Informasi Parkir Mall - Kelola Reservasi & Cek Ketersediaan Slot</p>
            </div>
            <div>
                <button onclick="konfirmasiLogout()" class="btn btn-outline-danger fw-bold px-3 py-2 rounded-3 shadow-sm border-0 bg-light">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </button>
            </div>
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

        <div class="row g-4 mb-4">
            <?php
            $q_kapasitas = mysqli_query($conn, "SELECT SUM(kapasitas) as total_kapasitas FROM tb_area_parkir");
            $d_kapasitas = mysqli_fetch_assoc($q_kapasitas);
            $total_kapasitas = $d_kapasitas['total_kapasitas'] ?? 45;

            $q_terisi = mysqli_query($conn, "SELECT COUNT(*) as total_terisi FROM tb_transaksi WHERE waktu_keluar IS NULL OR waktu_keluar = ''");
            $d_terisi = mysqli_fetch_assoc($q_terisi);
            $slot_terisi = $d_terisi['total_terisi'] ?? 0;

            $slot_tersedia = max(0, $total_kapasitas - $slot_terisi);
            ?>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-primary border-4 rounded-4 bg-white">
                    <span class="text-muted fw-bold small text-uppercase">Total Kapasitas</span>
                    <h2 class="fw-bold text-primary mt-2 mb-0"><?= $total_kapasitas; ?> <span class="fs-5 text-muted fw-normal">Slot</span></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-success border-4 rounded-4 bg-white">
                    <span class="text-muted fw-bold small text-uppercase">Slot Tersedia</span>
                    <h2 class="fw-bold text-success mt-2 mb-0"><?= $slot_tersedia; ?> <span class="fs-5 text-muted fw-normal">Slot</span></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-warning border-4 rounded-4 bg-white">
                    <span class="text-muted fw-bold small text-uppercase">Slot Terisi</span>
                    <h2 class="fw-bold text-dark mt-2 mb-0"><?= $slot_terisi; ?> <span class="fs-5 text-muted fw-normal">Kendaraan</span></h2>
                </div>
            </div>
        </div>

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
                                    // Mengambil data jenis kendaraan secara dinamis dari tabel tarif admin (tb_tarif)
                                    $query_kendaraan = mysqli_query($conn, "SELECT * FROM tb_tarif"); 
                                    if ($query_kendaraan && mysqli_num_rows($query_kendaraan) > 0) {
                                        while ($k = mysqli_fetch_assoc($query_kendaraan)) {
                                            $nama_kendaraan = $k['jenis_kendaraan'] ?? $k['nama'] ?? $k['kendaraan'] ?? $k['nama_kendaraan'] ?? '';
                                            if (!empty($nama_kendaraan)) {
                                                echo '<option value="' . htmlspecialchars($nama_kendaraan) . '">' . htmlspecialchars($nama_kendaraan) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Warna Kendaraan</label>
                                <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam / Silver / Merah" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Area / Blok Parkir</label>
                                <select name="area_parkir" class="form-select" required>
                                    <option value="" selected disabled>Pilih Area / Blok Parkir</option>
                                    <?php
                                    // Mengambil data area/blok parkir dari tabel yang sama dipakai sisi petugas (tb_area_parkir)
                                    $query_area = mysqli_query($conn, "SELECT * FROM tb_area_parkir");
                                    if ($query_area && mysqli_num_rows($query_area) > 0) {
                                        while ($a = mysqli_fetch_assoc($query_area)) {
                                            $nama_area = $a['nama_area'] ?? '';
                                            if (!empty($nama_area)) {
                                                echo '<option value="' . htmlspecialchars($nama_area) . '">' . htmlspecialchars($nama_area) . '</option>';
                                            }
                                        }
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
                                        <th>Kendaraan & Warna</th>
                                        <th>Area / Blok</th>
                                        <th>Jadwal</th>
                                        <th class="text-center">Status Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    // Hanya ambil reservasi milik user yang sedang login,
                                    // supaya tidak bisa melihat data reservasi user lain.
                                    $id_user_login = (int) ($_SESSION['id_user'] ?? 0);
                                    $query_res = mysqli_query($conn, "SELECT * FROM tb_reservasi WHERE id_user = $id_user_login ORDER BY id DESC");
                                    if ($query_res && mysqli_num_rows($query_res) > 0) {
                                        while ($row = mysqli_fetch_assoc($query_res)) {
                                            $nama_res = $row['nama'] ?? '-';
                                            $plat     = $row['plat_nomor'] ?? '-';
                                            $jenis    = $row['jenis_kendaraan'] ?? '-';
                                            $warna_v  = $row['warna'] ?? '-';
                                            $area_v   = $row['area_parkir'] ?? '-';
                                            $tgl      = $row['tanggal_reservasi'] ?? '-';
                                            $jam      = $row['jam_reservasi'] ?? '-';
                                            $status   = $row['status'] ?? 'Pending';

                                            $badge_bg = 'bg-warning text-dark';
                                            $ket = 'Pending';
                                            if (strtolower($status) == 'approved') {
                                                $badge_bg = 'bg-success';
                                                $ket = 'Disetujui';
                                            } elseif (strtolower($status) == 'rejected') {
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
                                            <small class="text-muted fw-bold"><?= htmlspecialchars($nama_res); ?></small>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-bold"><?= htmlspecialchars($jenis); ?></span><br>
                                            <small class="text-muted">Warna: <?= htmlspecialchars($warna_v); ?></small>
                                        </td>
                                        <td><small class="text-dark fw-bold"><?= htmlspecialchars($area_v); ?></small></td>
                                        <td><small class="text-muted"><?= $tgl; ?><br><?= $jam; ?></small></td>
                                        <td class="text-center">
                                            <span class="badge <?= $badge_bg; ?> px-3 py-2"><?= $ket; ?></span>
                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat reservasi.</td></tr>';
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
    <script>
    // Fungsi konfirmasi keluar (logout) dengan SweetAlert2 dan Efek Suara
    function konfirmasiLogout() {
        const audio = document.getElementById('logout-sound');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(e => console.log("Audio diblokir browser:", e));
        }

        Swal.fire({
            title: 'Apakah Anda yakin ingin keluar?',
            text: "Anda akan keluar dari sesi pengunjung parkir ini.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });
    }
    </script>
</body>
</html>