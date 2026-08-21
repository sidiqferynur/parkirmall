<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$conn = $conn ?? $koneksi ?? null;
$error = '';
$success = '';

// Daftar role yang boleh dipilih saat registrasi mandiri.
// Catatan: form ini bersifat publik (bisa diakses tanpa login), jadi
// menyediakan pilihan admin/owner di sini berarti siapa pun yang tahu
// alamat halaman ini bisa membuat akun admin/owner sendiri. Cocok untuk
// kebutuhan lokal/tugas sekolah, tapi perlu dibatasi lagi kalau nanti
// aplikasi ini dipakai publik/online.
$role_tersedia = [
    'user'    => 'Pengunjung / User',
    'petugas' => 'Petugas Parkir',
    'admin'   => 'Admin',
    'owner'   => 'Pemilik Mall / Owner',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $level = trim($_POST['level'] ?? 'user');

    // Validasi role: kalau nilai yang dikirim bukan salah satu pilihan
    // yang sah, paksa jadi 'user' supaya tidak bisa dimanipulasi lewat
    // request manual (misal jadi 'admin').
    if (!array_key_exists($level, $role_tersedia)) {
        $level = 'user';
    }

    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        $error = "Semua kolom wajib diisi!";
    } elseif ($conn) {
        $stmt_cek = mysqli_prepare($conn, "SELECT id_user FROM tb_user WHERE username = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $username);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);

        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $error = "Username sudah digunakan, silakan pilih yang lain.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt_insert = mysqli_prepare($conn, "INSERT INTO tb_user (username, password, nama_lengkap, no_telepon, level) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $username, $hashed_password, $nama_lengkap, $no_telepon, $level);

            if (mysqli_stmt_execute($stmt_insert)) {
                $success = "Registrasi berhasil! Silakan <a href='login.php'>masuk di sini</a>.";
            } else {
                $error = "Terjadi kesalahan pada sistem. Gagal mendaftar.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - E-Parkir Mall System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            margin: 0;
            padding: 20px 20px 30px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            background-color: #eef2f4;
        }

        .mall-scene {
            position: fixed;
            inset: 0;
            z-index: -2;
            overflow: hidden;
            background: linear-gradient(180deg, #eaf4ff 0%, #f3f7fb 30%, #f7f4ee 55%, #efe6d8 100%);
        }
        .mall-scene__sky {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 30%;
            background: linear-gradient(180deg, #cfe8ff 0%, #eaf4ff 100%);
        }
        .mall-scene__floor {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%;
            height: 17%;
            background: linear-gradient(180deg, #fbf8f2 0%, #ece3d2 100%);
        }
        .scene-overlay {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: radial-gradient(ellipse at center, rgba(255,255,255,.55) 0%, rgba(255,255,255,.28) 45%, rgba(255,255,255,.06) 100%);
        }

        .register-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
            border-radius: 24px;
            box-shadow: 0 25px 60px -12px rgba(30, 40, 60, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.5);
            max-width: 440px;
            width: 100%;
            padding: 26px 26px;
            position: relative;
            z-index: 2;
            margin-bottom: 15px;
        }
        .brand-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            margin: 0 auto 10px auto;
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.35);
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
            border-color: #198754;
        }
        .btn-register {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            border: none;
            color: #ffffff;
            border-radius: 10px;
            padding: 10px;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 6px 15px rgba(25, 135, 84, 0.3);
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.4);
            color: #ffffff;
        }
        .footer-custom {
            text-align: center;
            padding: 5px 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #475569;
            position: relative;
            z-index: 2;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="mall-scene" aria-hidden="true">
        <div class="mall-scene__sky"></div>
        <div class="mall-scene__floor"></div>
    </div>
    <div class="scene-overlay" aria-hidden="true"></div>

    <div class="register-card">
        <div class="brand-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>

        <h2 class="text-center fw-extrabold mb-1" style="color: #1e293b; font-size: 21px;">Daftar Akun Baru</h2>
        <p class="text-center text-muted mb-2" style="font-size: 11.5px; font-weight: 600;">E-PARKIR MALL SYSTEM</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-2" style="font-size: 12px; border-radius: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success py-2 mb-2" style="font-size: 12px; border-radius: 10px;"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-2">
                <label class="form-label fw-bold text-secondary mb-1" style="font-size: 11px;">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" name="nama_lengkap" class="form-control border-start-0" placeholder="Masukkan nama lengkap" required style="border-radius: 0 10px 10px 0;" value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label fw-bold text-secondary mb-1" style="font-size: 11px;">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-at text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="Pilih username unik" required style="border-radius: 0 10px 10px 0;" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label fw-bold text-secondary mb-1" style="font-size: 11px;">Nomor Telepon / WhatsApp</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-telephone text-muted"></i></span>
                    <input type="text" name="no_telepon" class="form-control border-start-0" placeholder="Contoh: 08123456789" style="border-radius: 0 10px 10px 0;" value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label fw-bold text-secondary mb-1" style="font-size: 11px;">Daftar Sebagai</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-person-badge text-muted"></i></span>
                    <select name="level" class="form-select border-start-0" style="border-radius: 0 10px 10px 0;">
                        <?php foreach ($role_tersedia as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo (($_POST['level'] ?? 'user') === $value) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary mb-1" style="font-size: 11px;">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-key text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="Minimal 6 karakter" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn btn-register">Daftar Sekarang</button>
            </div>

            <div class="d-grid">
                <a href="login.php" class="btn btn-light text-secondary border py-2" style="border-radius: 10px; font-weight: 700; font-size: 12.5px;">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Login
                </a>
            </div>
        </form>
    </div>

    <footer class="footer-custom">
        <p style="margin: 2px 0; font-size: 12px;">&copy; <?php echo date('Y'); ?> <strong style="color: #334155;">Parkir Mall</strong>. All Rights Reserved.</p>
        <p style="margin: 2px 0; font-size: 11px;">Dibuat oleh: <strong style="color: #1e293b;">Sidiq Fery Nur'cahya</strong> | <strong style="color: #1e293b;">SMKN 1 SANDEN 2026</strong></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>