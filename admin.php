<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Admin';

// Inisialisasi data default untuk jam 08:00 sampai 20:00 dengan nilai 0
$chart_data_map = [
    '08:00' => 0,
    '10:00' => 0,
    '12:00' => 0,
    '14:00' => 0,
    '16:00' => 0,
    '18:00' => 0,
    '20:00' => 0
];

// Query untuk mengelompokkan data berdasarkan jam masuk dari tb_transaksi
$query = "SELECT 
            CASE 
                WHEN HOUR(waktu_masuk) >= 8 AND HOUR(waktu_masuk) < 10 THEN '08:00'
                WHEN HOUR(waktu_masuk) >= 10 AND HOUR(waktu_masuk) < 12 THEN '10:00'
                WHEN HOUR(waktu_masuk) >= 12 AND HOUR(waktu_masuk) < 14 THEN '12:00'
                WHEN HOUR(waktu_masuk) >= 14 AND HOUR(waktu_masuk) < 16 THEN '14:00'
                WHEN HOUR(waktu_masuk) >= 16 AND HOUR(waktu_masuk) < 18 THEN '16:00'
                WHEN HOUR(waktu_masuk) >= 18 AND HOUR(waktu_masuk) < 20 THEN '18:00'
                ELSE '20:00'
            END AS kelompok_jam, 
            COUNT(*) AS total 
          FROM tb_transaksi 
          WHERE HOUR(waktu_masuk) >= 8 AND HOUR(waktu_masuk) <= 20
          GROUP BY kelompok_jam";

$res = mysqli_query($koneksi, $query);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $jam_key = $row['kelompok_jam'];
        if (array_key_exists($jam_key, $chart_data_map)) {
            $chart_data_map[$jam_key] = (int)$row['total'];
        }
    }
}

// Pisahkan kembali ke array labels dan data untuk Chart.js
$labels = array_keys($chart_data_map);
$data_jumlah = array_values($chart_data_map);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Parkir Mall</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; }
        .sidebar { width: 260px; min-height: 100vh; background-color: #212529; color: #fff; position: fixed; top: 0; left: 0; z-index: 1000; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.75); padding: 12px 20px; font-weight: 500; border-radius: 8px; margin: 4px 15px; display: flex; align-items: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: #0d6efd; }
        .sidebar .nav-link i { font-size: 1.2rem; margin-right: 12px; }
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
        .content-body { padding: 25px; flex: 1; }
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
                    <a href="admin.php" class="nav-link active"><i class="bi bi-house-door-fill"></i> Home</a>
                </li>
                <li>
                    <a href="crud_user.php" class="nav-link"><i class="bi bi-people-fill"></i> User</a>
                </li>
                <li>
                    <a href="crud_tarif.php" class="nav-link"><i class="bi bi-cash-coin"></i> Tarif</a>
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
                <strong><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nama); ?> (Admin)</strong>
            </div>
            <button onclick="konfirmasiLogout()" class="btn btn-danger w-100 fw-bold py-2 rounded-3 d-flex align-items-center justify-content-center gap-2 border-0">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-body">
            <div class="p-4 bg-white rounded-4 shadow-sm border mb-4">
                <h1 class="display-6 fw-bold">Selamat Datang di Sistem Parkir</h1>
                <p class="text-muted mb-0">Anda login sebagai <strong>Admin</strong>. Gunakan menu navigasi di sebelah kiri untuk mengakses fitur yang sesuai dengan hak akses Anda.</p>
            </div>

            <div class="card shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Tren Kedatangan Kendaraan</h5>
                </div>
                <div style="position: relative; height: 350px;">
                    <canvas id="chartKedatangan"></canvas>
                </div>
            </div>
        </div>

        <footer class="bg-white border-top py-3 text-center text-muted">
            <div class="container-fluid">
                <small>&copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
            </div>
        </footer>
    </main>

    <script>
    const ctx = document.getElementById('chartKedatangan').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels); ?>,
            datasets: [{
                label: 'Jumlah Kendaraan Masuk',
                data: <?= json_encode($data_jumlah); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 }
                }
            }
        }
    });
    </script>

    <script>
    function playIphoneSound() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const ctx = new AudioContext();

            const playTone = (freq, startTime, duration) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime + startTime);
                
                gain.gain.setValueAtTime(0.12, ctx.currentTime + startTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + startTime + duration);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(ctx.currentTime + startTime);
                osc.stop(ctx.currentTime + startTime + duration);
            };

            playTone(880.00, 0.0, 0.12);   // A5
            playTone(1318.51, 0.12, 0.25); // E6

        } catch (e) {
            console.log("Audio diblokir browser:", e);
        }
    }

    // Fungsi konfirmasi saat tombol Logout diklik
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
                window.location.href = 'logout.php';
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login') === 'sukses') {
        playIphoneSound();

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: 'success',
            title: 'Berhasil login!'
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
    </script>
</body>
</html>