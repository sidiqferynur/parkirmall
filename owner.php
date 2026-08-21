<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// KEAMANAN TAMBAHAN: Cek apakah user sudah login dan benar-benar 'owner'
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'owner') {
    header("location: login.php?pesan=belum_login");
    exit();
}

include 'koneksi.php';
$conn = $conn ?? $koneksi ?? null;

// Cek apakah baru saja sukses login dari halaman login.php
$notif_sukses = false;
if (isset($_SESSION['baru_login']) && $_SESSION['baru_login'] === true) {
    $notif_sukses = true;
    unset($_SESSION['baru_login']); // Hapus session agar notif tidak muncul lagi saat refresh
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        html, body { height: 100%; margin: 0; }
        body { 
            background-color: #f4f6f9; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        
        /* Layout Sidebar & Content */
        .wrapper { display: flex; flex: 1; width: 100%; }
        
        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: #212529;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar-brand {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-menu {
            padding: 20px;
            list-style: none;
            margin: 0;
            flex: 1;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #adb5bd;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #0d6efd;
            color: #fff;
        }
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .card-section { 
            background: white; 
            border-radius: 16px; 
            padding: 24px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            margin-bottom: 25px; 
        }

        .footer-custom {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.03);
            color: #6c757d;
            font-size: 14px;
            padding: 15px 25px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<audio id="successAudio" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<div class="wrapper">
    <div class="sidebar">
        <div>
            <div class="sidebar-brand d-flex align-items-center gap-2">
                <span class="bg-primary text-white px-2 py-1 rounded fw-bold fs-5">P</span>
                <h4 class="fw-bold text-white mb-0">Parkir Mall</h4>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="owner.php" class="active">
                        <i class="bi bi-house-door fs-5"></i> Home
                    </a>
                </li>
                <li>
                    <a href="laporan_keuangan.php">
                        <i class="bi bi-wallet2 fs-5"></i> Laporan Keuangan
                    </a>
                </li>
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
            <div class="welcome-card">
                <h2 class="fw-bold text-dark mb-2">Selamat Datang di Sistem Parkir</h2>
                <p class="text-muted mb-0">Anda login sebagai <strong>Owner</strong>. Gunakan menu navigasi di sebelah kiri untuk melihat laporan keuangan dan rekapitulasi data.</p>
            </div>

            <div class="card-section">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Ringkasan Laporan</h5>
                <p class="text-muted mb-0">Gunakan menu navigasi untuk melihat rekapitulasi data pendapatan dan transaksi parkir secara rinci.</p>
            </div>
        </div>

        <footer class="footer-custom d-flex flex-wrap justify-content-between align-items-center">
            <div class="col-md-6 d-flex align-items-center">
                <span class="text-muted">&copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</span>
            </div>
            <ul class="nav col-md-6 justify-content-end list-unstyled d-flex">
                <li class="ms-3"><a class="text-muted text-decoration-none small" href="#">Bantuan</a></li>
                <li class="ms-3"><a class="text-muted text-decoration-none small" href="#">Kontak</a></li>
                <li class="ms-3"><span class="text-muted small">v1.0.0</span></li>
            </ul>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    // Memunculkan pop-up dan suara sukses jika baru saja login
    <?php if ($notif_sukses) { ?>
        window.addEventListener('DOMContentLoaded', (event) => {
            const audio = document.getElementById('successAudio');
            if(audio) {
                audio.play().catch(error => {
                    console.log("Audio diblokir otomatis oleh browser:", error);
                });
            }

            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil!',
                text: 'Selamat datang kembali, Pemilik Mall (Owner).',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true
            });
        });
    <?php } ?>

    // Fungsi konfirmasi saat tombol Keluar diklik
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
                window.location.href = 'logout.php'; // Diarahkan ke script penghancur session logout
            }
        });
    }
</script>

</body>
</html>