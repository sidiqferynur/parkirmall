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

// Proses Aksi Setujui atau Tolak Reservasi
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_reservasi = mysqli_real_escape_string($conn, $_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi == 'setujui') {
        $status_baru = 'Approved';
    } elseif ($aksi == 'tolak') {
        $status_baru = 'Rejected';
    }

    if (isset($status_baru)) {
        $update_sql = "UPDATE tb_reservasi SET status = '$status_baru' WHERE id = '$id_reservasi'";
        $update_sukses = @mysqli_query($conn, $update_sql);

        // Jika disetujui, langsung arahkan ke halaman cetak struk dengan membawa ID reservasi
        if ($aksi == 'setujui' && $update_sukses) {
            header("Location: cetak_struk.php?id=" . $id_reservasi . "&sumber=reservasi");
            exit();
        }
    }

    header("Location: reservasi_petugas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reservasi - Petugas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="mb-3">
            <a href="petugas.php" class="btn btn-secondary btn-sm fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <a href="transaksi.php" class="btn btn-primary btn-sm fw-bold ms-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Menu Transaksi Parkir
            </a>
        </div>

        <h2 class="fw-bold mb-4">Kelola Reservasi Pengguna</h2>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-primary text-white fw-bold py-3 rounded-top-4">
                <i class="bi bi-calendar-check me-2"></i> Daftar Permintaan Reservasi Parkir
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Nama Pemilik</th>
                                <th>Plat Nomor</th>
                                <th>Kendaraan</th>
                                <th>Warna</th>
                                <th>Jadwal Kedatangan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_res = @mysqli_query($conn, "SELECT * FROM tb_reservasi ORDER BY id DESC");
                            if ($query_res && mysqli_num_rows($query_res) > 0) {
                                while ($row = mysqli_fetch_assoc($query_res)) {
                                    $id_res    = $row['id'];
                                    $nama      = $row['nama'] ?? '-';
                                    $plat      = $row['plat_nomor'] ?? '-';
                                    $jenis     = $row['jenis_kendaraan'] ?? '-';
                                    $warna     = $row['warna'] ?? '-';
                                    $tgl       = $row['tanggal_reservasi'] ?? '';
                                    $jam       = $row['jam_reservasi'] ?? '';
                                    $status    = $row['status'] ?? 'Pending';

                                    // Badge warna status
                                    $badge_bg = 'bg-warning text-dark';
                                    if (strtolower($status) == 'approved') $badge_bg = 'bg-success';
                                    elseif (strtolower($status) == 'rejected') $badge_bg = 'bg-danger';
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold">#<?= $id_res; ?></td>
                                <?= "<td>" . htmlspecialchars($nama) . "</td>" ?>
                                <td><strong><?= htmlspecialchars($plat); ?></strong></td>
                                <td><?= htmlspecialchars($jenis); ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($warna); ?></span></td>
                                <td><small class="text-muted"><?= $tgl; ?> <?= $jam; ?></small></td>
                                <td><span class="badge <?= $badge_bg; ?>"><?= $status; ?></span></td>
                                <td class="text-center">
                                    <?php if (strtolower($status) == 'pending'): ?>
                                        <a href="reservasi_petugas.php?aksi=setujui&id=<?= $id_res; ?>" class="btn btn-success btn-sm fw-bold me-1" onclick="return confirm('Setujui reservasi ini dan cetak struk?');">
                                            <i class="bi bi-check-lg"></i> Setujui
                                        </a>
                                        <a href="reservasi_petugas.php?aksi=tolak&id=<?= $id_res; ?>" class="btn btn-danger btn-sm fw-bold" onclick="return confirm('Tolak reservasi ini?');">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </a>
                                    <?php else: ?>
                                        <a href="cetak_struk.php?id=<?= $id_res; ?>&sumber=reservasi" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                                            <i class="bi bi-printer"></i> Cetak Struk
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data reservasi.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>