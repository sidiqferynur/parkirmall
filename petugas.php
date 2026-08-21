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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Parkir Mall</title>
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
                    <a href="petugas.php" class="nav-link active"><i class="bi bi-house-door-fill"></i> Home</a>
                </li>
                <li>
                    <a href="transaksi.php" class="nav-link"><i class="bi bi-car-front-fill"></i> Input Parkir</a>
                </li>
                <li>
                    <a href="riwayat_struk.php" class="nav-link"><i class="bi bi-receipt"></i> Riwayat Struk</a>
                </li>
            </ul>
        </div>
        <div>
            <hr class="text-secondary">
            <div class="px-3 mb-3 text-light">
                <small class="d-block text-muted">Login sebagai:</small>
                <strong><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama); ?> (Petugas)</strong>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-body">
            
            <!-- Welcome Card dengan Tombol Logout di Kanan Atas Konten -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h2 class="fw-bold mb-1">Selamat Datang di Sistem Parkir</h2>
                        <p class="text-muted mb-0">Anda login sebagai <strong>Petugas Lapangan</strong>. Gunakan menu navigasi di sebelah kiri.</p>
                    </div>
                    <div>
                        <a href="logout.php" class="btn btn-danger px-3 py-2 d-flex align-items-center" onclick="return confirm('Yakin ingin keluar?')">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Operational Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="mb-3">
                    <h4 class="fw-bold"><i class="bi bi-p-square text-primary me-2"></i>Operasional Parkir</h4>
                    <p class="text-muted">Silahkan lakukan pencatatan kendaraan masuk, keluar, serta mencetak ulang struk transaksi.</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white border-0 rounded-4 p-3 h-100 shadow-sm">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h3 class="fw-bold">Menu Transaksi</h3>
                                    <p class="mb-4">Catat kendaraan masuk & proses kendaraan keluar.</p>
                                </div>
                                <div>
                                    <a href="transaksi.php" class="btn btn-light text-primary fw-bold px-3 py-2">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Buka Transaksi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-dark text-white border-0 rounded-4 p-3 h-100 shadow-sm">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h3 class="fw-bold">Riwayat Struk</h3>
                                    <p class="mb-4">Lihat history transaksi & cetak ulang struk parkir.</p>
                                </div>
                                <div>
                                    <a href="riwayat_struk.php" class="btn btn-light text-dark fw-bold px-3 py-2">
                                        <i class="bi bi-receipt me-1"></i> Buka Riwayat Struk
                                    </a>
                                </div>
                            </div>
                        </div>
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