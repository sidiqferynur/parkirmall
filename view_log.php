<?php
include 'koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Log Aktivitas Sistem</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>
        <h2>Log Aktivitas Petugas & Admin</h2>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-danger text-white">System Activity Log</div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark"><tr><th>Waktu</th><th>Pelaku (User)</th><th>Role</th><th>Aktivitas Terlaksana</th></tr></thead>
                    <tbody>
                        <?php
                        $query = "SELECT l.*, u.nama_lengkap, u.role FROM tb_log_aktivitas l JOIN tb_user u ON l.id_user = u.id_user ORDER BY l.waktu_aktivitas DESC";
                        $res = mysqli_query($conn, $query);
                        while($row = mysqli_fetch_assoc($res)) {
                            echo "<tr>
                                    <td>{$row['waktu_aktivitas']}</td>
                                    <td>{$row['nama_lengkap']}</td>
                                    <td><span class='badge bg-secondary'>{$row['role']}</span></td>
                                    <td>{$row['aktivitas']}</td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>