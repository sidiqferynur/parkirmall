<?php
// 1. Memulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

// Menyesuaikan nama variabel koneksi
$conn = $conn ?? $koneksi ?? null;

// Otomatis buat tabel tb_log jika belum ada di database
if ($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tb_log (
        id_log INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        aktivitas TEXT NOT NULL,
        waktu DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

// --- FUNGSI CATAT LOG ---
if (!function_exists('write_log')) {
    function write_log($koneksi_db, $id_user, $aktivitas) {
        if (!$koneksi_db) return false;

        $id_user   = (int)$id_user;
        $aktivitas = mysqli_real_escape_string($koneksi_db, $aktivitas);

        $query = "INSERT INTO tb_log (id_user, aktivitas, waktu) VALUES ('$id_user', '$aktivitas', NOW())";
        return mysqli_query($koneksi_db, $query);
    }
}

// --- BAGIAN TAMPILAN HALAMAN ---
$is_direct_access = (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']));

if ($is_direct_access) {
    if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: admin.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas System - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        html, body {
            height: 100%;
        }
        .wrapper {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1 0 auto;
        }
        .footer {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-light">

    <div class="wrapper">
        <div class="content container mt-4 mb-5">
            <a href="admin.php" class="btn btn-secondary mb-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>

            <h2>Riwayat Log Aktivitas System</h2>

            <div class="card shadow-sm mt-3 border-0 rounded-3">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bi bi-clock-history me-2"></i>Catatan Aktivitas Pengguna
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Waktu</th>
                                    <th width="25%">ID / Name User</th>
                                    <th>Aktivitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($conn) {
                                    // Nama role hardcoded (id negatif) — dipetakan manual
                                    // karena akun ini tidak ada di tabel tb_user.
                                    $nama_hardcoded = [
                                        -1 => 'Administrator',
                                        -2 => 'Pemilik Mall',
                                        -3 => 'Petugas Parkir',
                                        -4 => 'Pengunjung User',
                                    ];

                                    $query_log = "SELECT l.*, u.username 
                                                  FROM tb_log l 
                                                  LEFT JOIN tb_user u ON l.id_user = u.id_user 
                                                  ORDER BY l.id_log DESC";
                                    $res = mysqli_query($conn, $query_log);

                                    if ($res && mysqli_num_rows($res) > 0) {
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($res)) {
                                            $id_user_row = (int)$row['id_user'];
                                            if (!empty($row['username'])) {
                                                $user_display = htmlspecialchars($row['username']);
                                            } elseif (isset($nama_hardcoded[$id_user_row])) {
                                                $user_display = $nama_hardcoded[$id_user_row];
                                            } else {
                                                $user_display = "User ID: " . $id_user_row;
                                            }
                                            $waktu = date('d M Y, H:i:s', strtotime($row['waktu']));
                                            ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><span class="badge bg-secondary"><?= $waktu; ?></span></td>
                                                <td><strong><?= $user_display; ?></strong></td>
                                                <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-3'>Belum ada catatan aktivitas.</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer bg-white text-center text-muted py-3 border-top mt-auto shadow-sm">
            <div class="container">
                <small>&copy; <?= date('Y'); ?> <strong>Parkir Mall Sidiq Fery Nur'cahya|SMKN 1 SANDEN 2026</strong>. All rights reserved.</small>
            </div>
        </footer>
    </div>

</body>
</html>
<?php 
}
?>