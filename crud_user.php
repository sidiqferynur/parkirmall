<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Admin';

// --- LOGIKA TAMBAH USER ---
if (isset($_POST['tambah_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_usr = $_POST['nama'];
    $role     = $_POST['role'];

    $q = mysqli_query($koneksi, "INSERT INTO tb_user (username, password, nama, role) VALUES ('$username', '$password', '$nama_usr', '$role')");
    if (!$q) {
        mysqli_query($koneksi, "INSERT INTO tb_user (username, password, nama_user, role) VALUES ('$username', '$password', '$nama_usr', '$role')");
    }
    header("Location: crud_user.php");
    exit;
}

// --- LOGIKA EDIT USER ---
if (isset($_POST['edit_user'])) {
    $id_user  = (int)$_POST['id_user'];
    $username = $_POST['username'];
    $nama_usr = $_POST['nama'];
    $role     = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $q = mysqli_query($koneksi, "UPDATE tb_user SET username='$username', password='$password', nama='$nama_usr', role='$role' WHERE id_user=$id_user");
        if (!$q) {
            mysqli_query($koneksi, "UPDATE tb_user SET username='$username', password='$password', nama_user='$nama_usr', role='$role' WHERE id_user=$id_user");
        }
    } else {
        $q = mysqli_query($koneksi, "UPDATE tb_user SET username='$username', nama='$nama_usr', role='$role' WHERE id_user=$id_user");
        if (!$q) {
            mysqli_query($koneksi, "UPDATE tb_user SET username='$username', nama_user='$nama_usr', role='$role' WHERE id_user=$id_user");
        }
    }
    header("Location: crud_user.php");
    exit;
}

// --- LOGIKA HAPUS USER ---
if (isset($_GET['hapus'])) {
    $id_user = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM tb_user WHERE id_user=$id_user");
    header("Location: crud_user.php");
    exit;
}

$query_user = mysqli_query($koneksi, "SELECT * FROM tb_user ORDER BY id_user DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Parkir Mall</title>
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
                    <a href="crud_user.php" class="nav-link active"><i class="bi bi-people-fill"></i> User</a>
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
                <strong><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($nama); ?> (Admin)</strong>
            </div>
            <a href="logout.php" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-body">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Kelola Data User</h3>
                    <p class="text-muted mb-0">Tambah, ubah, atau hapus data pengguna sistem.</p>
                </div>
                <button class="btn btn-primary rounded-3 shadow-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                    <i class="bi bi-person-plus-fill me-2"></i> Tambah User Baru
                </button>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Role</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($query_user && mysqli_num_rows($query_user) > 0) { ?>
                                    <?php $no = 1; while ($u = mysqli_fetch_assoc($query_user)) { ?>
                                        <?php 
                                            if (isset($u['nama']) && !empty($u['nama'])) {
                                                $nama_tampil = $u['nama'];
                                            } elseif (isset($u['nama_user']) && !empty($u['nama_user'])) {
                                                $nama_tampil = $u['nama_user'];
                                            } elseif (isset($u['nama_lengkap']) && !empty($u['nama_lengkap'])) {
                                                $nama_tampil = $u['nama_lengkap'];
                                            } else {
                                                $nama_tampil = '-';
                                            }
                                            $user_role = isset($u['role']) ? $u['role'] : 'USER';
                                        ?>
                                        <tr>
                                            <td class="ps-4"><?php echo $no++; ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($u['username']); ?></td>
                                            <td><?php echo htmlspecialchars($nama_tampil); ?></td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                                                    <?php echo strtoupper($user_role); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEditUser<?php echo $u['id_user']; ?>" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a href="crud_user.php?hapus=<?php echo $u['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')" title="Hapus">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="modalEditUser<?php echo $u['id_user']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content rounded-4 border-0">
                                                    <form action="crud_user.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Edit User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id_user" value="<?php echo $u['id_user']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Username</label>
                                                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($u['username']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Password <small class="text-muted">(Kosongkan jika tidak diganti)</small></label>
                                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                                                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($nama_tampil); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Role</label>
                                                                <select name="role" class="form-select" required>
                                                                    <option value="admin" <?php echo strtolower($user_role) == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="petugas" <?php echo strtolower($user_role) == 'petugas' ? 'selected' : ''; ?>>Petugas</option>
                                                                    <option value="owner" <?php echo strtolower($user_role) == 'owner' ? 'selected' : ''; ?>>Owner</option>
                                                                    <option value="user" <?php echo strtolower($user_role) == 'user' ? 'selected' : ''; ?>>User</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data user.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="modal fade" id="modalTambahUser" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content rounded-4 border-0">
                    <form action="crud_user.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Tambah User Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="" disabled selected>Pilih Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="petugas">Petugas</option>
                                    <option value="owner">Owner</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah_user" class="btn btn-primary">Simpan User</button>
                        </div>
                    </form>
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