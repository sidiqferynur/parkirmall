<?php
session_start();
include 'koneksi.php'; // Menghubungkan ke database

$pesan = '';
$sukses_login = false;
$nama_user = '';
$halaman_tujuan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 1. Cek Akun Hardcoded Terlebih Dahulu
    // Setiap role diberi id_user unik (negatif) supaya tidak bentrok
    // dengan id_user asli di tabel tb_user, dan supaya log aktivitas
    // bisa membedakan siapa yang login (tidak semua tercatat sebagai id 1).
    $users_hardcoded = [
        'admin'   => ['password' => 'admin',   'role' => 'admin',   'nama' => 'Administrator',   'id' => -1],
        'owner'   => ['password' => 'owner',   'role' => 'owner',   'nama' => 'Pemilik Mall',     'id' => -2],
        'petugas' => ['password' => 'petugas', 'role' => 'petugas', 'nama' => 'Petugas Parkir',   'id' => -3],
        'user'    => ['password' => 'user',    'role' => 'user',    'nama' => 'Pengunjung User',  'id' => -4]
    ];

    if (array_key_exists($username, $users_hardcoded)) {
        if ($password === $users_hardcoded[$username]['password']) {
            $_SESSION['login']    = true;
            $_SESSION['id_user']  = $users_hardcoded[$username]['id'];
            $_SESSION['username'] = $username;
            $_SESSION['nama']     = $users_hardcoded[$username]['nama'];

            $role = $users_hardcoded[$username]['role'];
            $_SESSION['role']     = $role;
            $_SESSION['level']    = $role;

            $sukses_login = true;
            $nama_user = $users_hardcoded[$username]['nama'];

            if ($role === 'admin') {
                $halaman_tujuan = "admin.php";
            } elseif ($role === 'owner') {
                $halaman_tujuan = "owner.php";
            } elseif ($role === 'petugas') {
                $halaman_tujuan = "petugas.php";
            } elseif ($role === 'user') {
                $halaman_tujuan = "dashboard_user.php";
            }
        } else {
            $pesan = "Password salah!";
        }
    } else {
        // 2. Cek ke Database (untuk User Baru dari Register)
        $username_clean = mysqli_real_escape_string($koneksi, $username);

        $query = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE username = '$username_clean'");

        if (mysqli_num_rows($query) > 0) {
            $user = mysqli_fetch_assoc($query);

            // Cocokkan password menggunakan password_verify(),
            // pasangan resmi dari password_hash() yang dipakai saat register.
            if (password_verify($password, $user['password'])) {
                $_SESSION['login']    = true;
                $_SESSION['id_user']  = $user['id_user'] ?? $user['id'] ?? 1;
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama_lengkap'] ?? $user['nama'] ?? 'User';

                $role = strtolower(trim($user['level'] ?? $user['role'] ?? 'user'));
                $_SESSION['role']     = $role;
                $_SESSION['level']    = $role;

                $sukses_login = true;
                $nama_user = $_SESSION['nama'];

                if ($role === 'admin') {
                    $halaman_tujuan = "admin.php";
                } elseif ($role === 'owner') {
                    $halaman_tujuan = "owner.php";
                } elseif ($role === 'petugas') {
                    $halaman_tujuan = "petugas.php";
                } elseif ($role === 'user') {
                    $halaman_tujuan = "dashboard_user.php";
                } else {
                    $halaman_tujuan = "index.php";
                }
            } else {
                $pesan = "Password salah!";
            }
        } else {
            $pesan = "Username tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Parkir Mall System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: url('https://images.unsplash.com/photo-1519567241046-7f570eee3ce6?q=80&w=1600') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 20px 30px 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        .login-header {
            background: linear-gradient(135deg, #0d6efd, #0043a8);
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .footer-custom {
            text-align: center;
            padding: 5px 0;
            color: #ffffff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.6);
            position: relative;
            z-index: 2;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="bi bi-shield-lock-fill display-4 mb-2"></i>
        <h4 class="fw-bold mb-0">Aplikasi Parkir Login</h4>
        <small>Silakan masuk ke akun user/petugas/admin/owner</small>
    </div>

    <div class="p-4">
        <?php if (!empty($pesan)): ?>
            <div class="alert alert-danger text-center p-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $pesan; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm mb-3">
                Login
            </button>

            <a href="index.php" class="btn btn-outline-secondary w-100 py-2 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                Belum punya akun? <a href="register.php" class="text-primary text-decoration-none fw-bold">Daftar di sini</a>
            </small>
        </div>
    </div>
</div>

<footer class="footer-custom">
    <p style="margin: 2px 0; font-size: 12px;">&copy; <?php echo date('Y'); ?> <strong style="color: #fff;">Parkir Mall</strong>. All Rights Reserved.</p>
    <p style="margin: 2px 0; font-size: 11px;">Dibuat oleh: <strong style="color: #fff;">Sidiq Fery Nur'cahya</strong> | <strong style="color: #fff;">SMKN 1 SANDEN 2026</strong></p>
</footer>

<?php if ($sukses_login): ?>
<script>
    // Fungsi JavaScript untuk membunyikan suara sukses secara otomatis
    function playSuccessSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(587.33, audioCtx.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.15);

            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.3);
        } catch (e) {
            console.log("Audio API tidak didukung:", e);
        }
    }

    playSuccessSound();

    Swal.fire({
        icon: 'success',
        title: 'Login Berhasil!',
        text: 'Selamat datang kembali, <?= $nama_user; ?>!',
        showConfirmButton: false,
        timer: 1500
    }).then(function() {
        window.location.href = '<?= $halaman_tujuan; ?>';
    });
</script>
<?php endif; ?>

</body>
</html>