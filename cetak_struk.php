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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sumber = isset($_GET['sumber']) ? $_GET['sumber'] : '';

$data = null;

if ($sumber === 'reservasi') {
    // ---- Ambil dari tb_reservasi, pakai waktu keluar ASLI ----
    $query_res = "SELECT r.id AS id,
                         r.plat_nomor,
                         r.jenis_kendaraan,
                         'Area Reservasi Mall' AS nama_area,
                         CONCAT(r.tanggal_reservasi, ' ', r.jam_reservasi) AS waktu_masuk,
                         r.waktu_keluar_aktual AS waktu_keluar,
                         0 AS biaya_total,
                         'reservasi' AS sumber
                  FROM tb_reservasi r
                  WHERE r.id = $id
                  LIMIT 1";
    $result_res = mysqli_query($conn, $query_res);
    if ($result_res && mysqli_num_rows($result_res) > 0) {
        $data = mysqli_fetch_assoc($result_res);
    }
} else {
    // ---- Ambil dari tb_transaksi ----
    $query = "SELECT t.id_parkir AS id, 
                     COALESCE(k.plat_nomor, '-') AS plat_nomor, 
                     COALESCE(k.jenis_kendaraan, 'Motor') AS jenis_kendaraan, 
                     COALESCE(a.nama_area, 'Area Regular') AS nama_area,
                     t.waktu_masuk,
                     t.waktu_keluar,
                     t.biaya_total,
                     'transaksi' AS sumber
              FROM tb_transaksi t
              LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
              LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
              WHERE t.id_parkir = $id
              LIMIT 1";
    $result = mysqli_query($conn, $query);
    $data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

if (!$data) {
    echo "<script>alert('Data struk tidak ditemukan!'); window.location='riwayat_struk.php';</script>";
    exit;
}

$waktu_masuk = $data['waktu_masuk'];
$sudah_keluar = !empty($data['waktu_keluar']) && $data['waktu_keluar'] != '0000-00-00 00:00:00';

// Jika kendaraan reservasi belum benar-benar keluar, jangan cetak struk final dulu
if ($data['sumber'] === 'reservasi' && !$sudah_keluar) {
    echo "<script>alert('Kendaraan ini belum tercatat keluar. Klik tombol Keluar dulu di halaman Riwayat Struk.'); window.location='riwayat_struk.php';</script>";
    exit;
}

// Format waktu tanpa microseconds (.000000)
$waktu_keluar = $sudah_keluar ? date('Y-m-d H:i:s', strtotime($data['waktu_keluar'])) : '-';
$waktu_masuk_fmt = date('Y-m-d H:i:s', strtotime($waktu_masuk));

// Format waktu versi tampilan yang lebih enak dibaca (mis. 24 Jul 2026, 10:49)
$bulan_id = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
function format_tanggal_id($ts, $bulan_id) {
    if (!$ts) return '-';
    $bln = $bulan_id[date('m', $ts)];
    return date('d', $ts) . ' ' . $bln . ' ' . date('Y, H:i', $ts);
}
$waktu_masuk_tampil  = format_tanggal_id($masuk_ts = strtotime($waktu_masuk), $bulan_id);
$waktu_keluar_tampil = $sudah_keluar ? format_tanggal_id(strtotime($data['waktu_keluar']), $bulan_id) : '-';

// Hitung durasi
$masuk = strtotime($waktu_masuk);
$keluar = $sudah_keluar ? strtotime($data['waktu_keluar']) : time();
$durasi_jam = ceil(($keluar - $masuk) / 3600);
if ($durasi_jam < 1) $durasi_jam = 1;
$durasi_hari = intdiv($durasi_jam, 24);
$durasi_sisa_jam = $durasi_jam % 24;

// Hitung biaya
$jk = mysqli_real_escape_string($conn, $data['jenis_kendaraan']);
$q_t = mysqli_query($conn, "SELECT tarif_per_jam FROM tb_tarif WHERE LOWER(TRIM(jenis_kendaraan)) = LOWER(TRIM('$jk')) LIMIT 1");
$tarif = ($q_t && mysqli_num_rows($q_t) > 0) ? intval(mysqli_fetch_assoc($q_t)['tarif_per_jam']) : 2000;

$biaya = intval($data['biaya_total']);
if ($biaya <= 0) {
    $biaya = $durasi_jam * $tarif;
}

$nama_petugas = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas (Disetujui)';

// Kode singkat untuk hiasan "barcode" tekstual di bagian bawah struk
$kode_nota = 'PRK-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Parkir - ID #<?= $data['id']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1f2430;
            --muted: #8b93a7;
            --line: #dfe3ea;
            --accent: #16a34a;
            --paper: #ffffff;
        }
        body {
            background: linear-gradient(180deg, #eef1f6 0%, #e4e8f0 100%);
            font-family: 'JetBrains Mono', monospace;
            color: var(--ink);
            min-height: 100vh;
            padding: 30px 12px 60px;
        }

        /* --- Bar aksi (Riwayat / Print) --- */
        .action-bar {
            max-width: 420px;
            margin: 0 auto 18px auto;
            display: flex;
            gap: 10px;
        }
        .action-bar .btn {
            flex: 1;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
        }
        .btn-riwayat { background: #eef1f6; color: #475467; border: 1px solid #d8dce4 !important; }
        .btn-riwayat:hover { background: #e2e6ee; color: #1f2430; }
        .btn-print { background: linear-gradient(135deg,#16a34a 0%,#15803d 100%); color: #fff; box-shadow: 0 8px 18px rgba(22,163,74,.3); }
        .btn-print:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 10px 22px rgba(22,163,74,.4); }

        /* --- Kertas struk --- */
        .struk-wrap {
            max-width: 420px;
            margin: 0 auto;
            position: relative;
            filter: drop-shadow(0 18px 30px rgba(20,25,40,.16));
        }
        .struk-container {
            background: var(--paper);
            padding: 34px 30px 26px;
            position: relative;
        }
        /* Sobekan kertas atas & bawah */
        .struk-wrap::before, .struk-wrap::after {
            content: "";
            display: block;
            height: 14px;
            background:
                linear-gradient(-45deg, var(--paper) 8px, transparent 0),
                linear-gradient(45deg, var(--paper) 8px, transparent 0);
            background-repeat: repeat-x;
            background-size: 16px 16px;
            background-position: left top;
        }
        .struk-wrap::before { background-color: transparent; transform: translateY(1px); }
        .struk-wrap::after { transform: translateY(-1px) rotate(180deg); }

        .status-badge {
            position: absolute;
            top: 16px;
            right: -8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .5px;
            padding: 5px 14px 5px 12px;
            border-radius: 6px 0 0 6px;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,.15);
        }
        .status-badge.lunas { background: linear-gradient(135deg,#16a34a,#15803d); }
        .status-badge.berjalan { background: linear-gradient(135deg,#f59e0b,#d97706); }

        .brand-mark {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg,#0d6efd 0%,#0a58ca 100%);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin: 0 auto 10px auto;
            box-shadow: 0 8px 18px rgba(13,110,253,.35);
        }
        .struk-header { text-align: center; margin-bottom: 6px; }
        .struk-header h5 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 16.5px;
            letter-spacing: .3px;
            margin-bottom: 3px;
            line-height: 1.3;
        }
        .struk-header p { font-size: 11.5px; color: var(--muted); margin-bottom: 0; }

        .dashed-line {
            border: none;
            border-top: 1.5px dashed var(--line);
            margin: 16px 0;
        }

        .kv-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12.5px;
            padding: 3px 0;
        }
        .kv-row .k { color: var(--muted); white-space: nowrap; }
        .kv-row .v { font-weight: 700; text-align: right; }
        .kv-row .v.plat {
            background: #eef2ff;
            color: #3730a3;
            padding: 1px 8px;
            border-radius: 5px;
            letter-spacing: .5px;
        }

        .section-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .total-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 4px 0 2px;
        }
        .total-box .label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            color: #15803d;
            letter-spacing: .4px;
        }
        .total-box .amount {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 19px;
            font-weight: 800;
            color: #14532d;
        }

        .qris-box {
            border: 1.5px dashed var(--line);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            margin-top: 6px;
        }
        .qris-box p.title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .qris-box img { width: 148px; height: auto; border-radius: 6px; }
        .qris-box .nmid { font-size: 10px; color: var(--muted); margin-top: 8px; margin-bottom: 0; letter-spacing: .3px; }

        .barcode-fake {
            display: flex;
            gap: 2px;
            align-items: flex-end;
            justify-content: center;
            height: 34px;
            margin: 18px 0 6px;
        }
        .barcode-fake span { display:block; width: 2px; background: #1f2430; }

        .footer-note {
            text-align: center;
            font-size: 11.5px;
            color: var(--muted);
            line-height: 1.6;
        }
        .footer-note .thanks {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            color: var(--ink);
            font-size: 12.5px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .struk-wrap { filter: none; max-width: 100%; }
            .struk-container { padding: 10px 18px; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <a href="riwayat_struk.php" class="btn btn-riwayat"><i class="bi bi-arrow-left"></i> Riwayat Struk</a>
    <button onclick="window.print()" class="btn btn-print"><i class="bi bi-printer-fill"></i> Print Struk</button>
</div>

<div class="struk-wrap">
    <div class="struk-container">

        <span class="status-badge <?= $sudah_keluar ? 'lunas' : 'berjalan'; ?>">
            <?= $sudah_keluar ? 'LUNAS' : 'BERJALAN'; ?>
        </span>

        <div class="struk-header">
            <div class="brand-mark"><i class="bi bi-p-square-fill"></i></div>
            <h5>PARKIR MALL<br>SIDIQ FERY NUR'CAHYA</h5>
            <p>Jl. Raya Mall Utama No. 12</p>
        </div>

        <hr class="dashed-line">

        <div class="section-label"><i class="bi bi-receipt me-1"></i> Detail Transaksi</div>
        <div class="kv-row"><span class="k">Nota ID</span><span class="v">#<?= $data['id']; ?></span></div>
        <div class="kv-row"><span class="k">Plat No</span><span class="v plat"><?= htmlspecialchars($data['plat_nomor']); ?></span></div>
        <div class="kv-row"><span class="k">Kategori</span><span class="v"><?= htmlspecialchars($data['jenis_kendaraan']); ?></span></div>
        <div class="kv-row"><span class="k">Area</span><span class="v"><?= htmlspecialchars($data['nama_area']); ?></span></div>
        <div class="kv-row"><span class="k">Petugas</span><span class="v"><?= htmlspecialchars($nama_petugas); ?></span></div>

        <hr class="dashed-line">

        <div class="section-label"><i class="bi bi-clock-history me-1"></i> Waktu Parkir</div>
        <div class="kv-row"><span class="k">Jam Masuk</span><span class="v"><?= $waktu_masuk_tampil; ?></span></div>
        <div class="kv-row"><span class="k">Jam Keluar</span><span class="v"><?= $waktu_keluar_tampil; ?></span></div>
        <div class="kv-row">
            <span class="k">Durasi</span>
            <span class="v">
                <?php if ($durasi_hari > 0): ?>
                    <?= $durasi_hari; ?> Hari <?= $durasi_sisa_jam; ?> Jam
                <?php else: ?>
                    <?= $durasi_jam; ?> Jam
                <?php endif; ?>
            </span>
        </div>

        <hr class="dashed-line">

        <div class="total-box">
            <span class="label">TOTAL BIAYA</span>
            <span class="amount">Rp <?= number_format($biaya, 0, ',', '.'); ?></span>
        </div>

        <div class="qris-box">
            <p class="title"><i class="bi bi-qr-code"></i> Scan untuk Pembayaran QRIS</p>
            <img src="qris.png" alt="QRIS" onerror="this.style.display='none'">
            <p class="nmid">NMID: ID1026554732622</p>
        </div>

        <div class="barcode-fake" aria-hidden="true">
            <?php
            // Barisan garis acak (tapi konsisten per nota) sekadar hiasan visual ala barcode
            mt_srand((int)$data['id']);
            for ($i = 0; $i < 42; $i++) {
                $w = mt_rand(0, 2) === 0 ? 3 : 1.5;
                $h = mt_rand(14, 34);
                echo "<span style=\"width:{$w}px;height:{$h}px;\"></span>";
            }
            ?>
        </div>
        <p class="text-center" style="font-size:10.5px; letter-spacing:2px; color:var(--muted); margin-top:2px;"><?= $kode_nota; ?></p>

        <hr class="dashed-line">

        <div class="footer-note">
            <p class="thanks mb-1">Terima Kasih Atas Kunjungan Anda</p>
            <p class="mb-0">Semoga Selamat Sampai Tujuan</p>
        </div>
    </div>
</div>

</body>
</html>