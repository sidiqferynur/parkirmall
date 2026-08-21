<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$conn = $conn ?? $koneksi ?? null;

/* =====================================================================
   HANDLER AJAX: SIMPAN ULASAN
   Ditangani di file yang sama (index/welcome.php) supaya tidak perlu
   file terpisah. Dikenali lewat $_POST['aksi'] === 'simpan_ulasan'
   yang dikirim oleh fetch() di JavaScript. Kalau ini request AJAX,
   proses, kirim balasan JSON, lalu hentikan eksekusi (jangan lanjut
   render HTML di bawahnya).
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan_ulasan') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    if (!$conn) {
        http_response_code(500);
        $response['message'] = 'Koneksi database tidak tersedia.';
        echo json_encode($response);
        exit;
    }

    $nama     = trim($_POST['nama'] ?? '');
    $rating   = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($nama === '' || $komentar === '' || $rating < 1 || $rating > 5) {
        http_response_code(422);
        $response['message'] = 'Data tidak valid. Pastikan nama, rating (1-5), dan komentar terisi.';
        echo json_encode($response);
        exit;
    }

    $nama     = mb_substr($nama, 0, 100);
    $komentar = mb_substr($komentar, 0, 1000);

    $stmt = mysqli_prepare($conn, "INSERT INTO tb_ulasan (nama, rating, komentar, waktu) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        http_response_code(500);
        $response['message'] = 'Gagal menyiapkan query.';
        echo json_encode($response);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'sis', $nama, $rating, $komentar);

    if (mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['message'] = 'Ulasan berhasil disimpan.';
    } else {
        http_response_code(500);
        $response['message'] = 'Gagal menyimpan ulasan ke database.';
    }

    mysqli_stmt_close($stmt);
    echo json_encode($response);
    exit; // Hentikan di sini, jangan render halaman HTML
}

// Menyamakan nilai default agar sesuai dengan sistem dashboard
$total_slot = 45;
$terisi = 0;
$tersedia = 45;

// Daftar kategori kendaraan & style tampilan (icon + warna)
$daftar_kategori = ['Motor', 'Mobil', 'Truk', 'Bis'];
$kategori_style = [
    'Motor' => ['icon' => 'bi-scooter',       'warna' => '#e0a54c'],
    'Mobil' => ['icon' => 'bi-car-front-fill','warna' => '#0d6efd'],
    'Truk'  => ['icon' => 'bi-truck',         'warna' => '#6c757d'],
    'Bis'   => ['icon' => 'bi-bus-front-fill','warna' => '#212529'],
];

// Rekap ketersediaan per kategori kendaraan
$rekap_kategori = [];
foreach ($daftar_kategori as $k) {
    $rekap_kategori[$k] = ['total' => 0, 'terisi' => 0, 'tersedia' => 0];
}

// Daftar ulasan yang sudah tersimpan di database
$daftar_ulasan_db = [];

if ($conn) {
    // 1. Menghitung total kapasitas
    $q_kapasitas = mysqli_query($conn, "SELECT SUM(kapasitas) as total_cap FROM tb_area_parkir");
    if ($q_kapasitas) {
        $data_cap = mysqli_fetch_assoc($q_kapasitas);
        if (!empty($data_cap['total_cap']) && $data_cap['total_cap'] > 0) {
            $total_slot = (int)$data_cap['total_cap'];
        }
    }

    // 2. Menghitung jumlah kendaraan terisi
    $q_terisi = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_transaksi WHERE waktu_keluar IS NULL");
    if ($q_terisi) {
        $data_terisi = mysqli_fetch_assoc($q_terisi);
        $terisi = (int)($data_terisi['total'] ?? 0);
    }

    // 3. Menghitung slot tersedia
    $tersedia = max(0, $total_slot - $terisi);

    /* =================================================================
       4. Rekap total slot, terisi & tersedia per kategori kendaraan
       Sebelumnya kolom "terisi" diambil langsung dari tb_area_parkir,
       padahal kolom itu statis (di-set 0 saat area dibuat) dan tidak
       pernah di-update oleh proses kendaraan masuk/keluar di
       transaksi.php. Sekarang dihitung dinamis dengan COUNT() dari
       tb_transaksi yang masih aktif (belum ada waktu_keluar) untuk
       setiap area pada kategori tsb, sama seperti perhitungan pada
       halaman Kelola Area Parkir & dropdown Pilih Area di transaksi.php.
       ================================================================= */
    $q_rekap = mysqli_query($conn, "SELECT a.kategori, 
                                     SUM(a.kapasitas) as total_kap, 
                                     SUM((SELECT COUNT(*) FROM tb_transaksi t 
                                          WHERE t.id_area = a.id_area 
                                          AND (t.waktu_keluar IS NULL OR t.waktu_keluar = ''))) as total_terisi 
                                     FROM tb_area_parkir a 
                                     GROUP BY a.kategori");
    if ($q_rekap) {
        while ($r = mysqli_fetch_assoc($q_rekap)) {
            $kat = $r['kategori'];
            if (isset($rekap_kategori[$kat])) {
                $t_total  = (int)$r['total_kap'];
                $t_terisi = (int)$r['total_terisi'];
                $rekap_kategori[$kat]['total']    = $t_total;
                $rekap_kategori[$kat]['terisi']   = $t_terisi;
                $rekap_kategori[$kat]['tersedia'] = max(0, $t_total - $t_terisi);
            }
        }
    }

    // 5. Mengambil daftar ulasan dari database (terbaru di atas)
    $q_ulasan = mysqli_query($conn, "SELECT nama, rating, komentar, waktu FROM tb_ulasan ORDER BY waktu DESC LIMIT 50");
    if ($q_ulasan) {
        while ($row = mysqli_fetch_assoc($q_ulasan)) {
            $daftar_ulasan_db[] = $row;
        }
    }
}

/* =====================================================================
   DATA DEKORASI ANIMASI "MALL SCENE"
   Sebelumnya setiap elemen (banner, storefront, plant, particle, balloon,
   shopper) ditulis manual berulang-ulang di HTML. Sekarang didefinisikan
   sebagai data lalu di-render lewat loop -> markup HTML/SVG yang
   dihasilkan identik dengan versi asli, tampilan tidak berubah.
   ===================================================================== */

// Umbul-umbul promo
$banners = [
    ['left' => '16%', 'delay' => '0s',    'color' => '#f2718c', 'text' => 'SALE'],
    ['left' => '48%', 'delay' => '-1.4s', 'color' => '#3fb8ab', 'text' => '50%'],
    ['left' => '80%', 'delay' => '-0.6s', 'color' => '#5b9bf2', 'text' => 'NEW'],
];

// Etalase toko (warna diambil dari class modifier CSS yang sudah ada)
$storefronts = [
    ['class' => 'sun',   'badge' => "DISC<br>30%", 'mannequin_left' => '14%', 'fill' => '#b98637', 'op' => '.55'],
    ['class' => 'mint',  'badge' => null,           'mannequin_left' => '35%', 'fill' => '#2f8778', 'op' => '.55'],
    ['class' => 'lilac', 'badge' => "HOT<br>ITEM",  'mannequin_left' => '20%', 'fill' => '#7a5fc7', 'op' => '.5'],
    ['class' => 'rose',  'badge' => null,           'mannequin_left' => '40%', 'fill' => '#c74d69', 'op' => '.5'],
    ['class' => 'sky',   'badge' => 'SALE',         'mannequin_left' => '30%', 'fill' => '#3574c9', 'op' => '.5'],
];

// Tanaman hias
$plants = [
    ['side' => 'left: 6%;',  'delay' => ''],
    ['side' => 'right: 6%;', 'delay' => ' animation-delay: -2s;'],
];

// Butiran cahaya melayang
$particles = [
    ['left' => '12%', 'dur' => '9s',   'delay' => '0s'],
    ['left' => '28%', 'dur' => '11s',  'delay' => '-3s'],
    ['left' => '47%', 'dur' => '8s',   'delay' => '-6s'],
    ['left' => '63%', 'dur' => '12s',  'delay' => '-1.5s'],
    ['left' => '80%', 'dur' => '10s',  'delay' => '-4.5s'],
    ['left' => '92%', 'dur' => '9.5s', 'delay' => '-7s'],
];

// Balon anak-anak
$balloons = [
    ['color' => '#ff8fa3', 'dur' => '6s, 30s', 'delay' => '0s, -8s'],
    ['color' => '#ffd166', 'dur' => '7s, 34s', 'delay' => '-2s, -20s'],
];

// Siluet pengunjung berjalan (tiap entri = 1 varian visual persis seperti versi asli)
$shoppers = [
    [
        'wrap' => 'mall-scene__shopper--far',
        'dur' => '30s', 'delay' => '-4s',
        'svg' => '<svg viewBox="0 0 12 30"><ellipse cx="6" cy="5" rx="3.4" ry="4" fill="#94a3b8"/><path d="M2,11 h8 l-1.5,17 h-2 l-1-9 -1,9 h-2 z" fill="#94a3b8"/></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--far mall-scene__shopper--rev',
        'dur' => '33s', 'delay' => '-16s',
        'svg' => '<svg viewBox="0 0 12 30"><ellipse cx="6" cy="5" rx="3.4" ry="4" fill="#a8b3c4"/><path d="M2,11 h8 l-1.5,17 h-2 l-1-9 -1,9 h-2 z" fill="#a8b3c4"/></svg>',
    ],
    [
        'wrap' => '', 'dur' => '22s', 'delay' => '-2s',
        'svg' => '<svg viewBox="0 0 12 34"><ellipse cx="6" cy="5" rx="3.4" ry="4" fill="#334155"/><rect x="2.5" y="10" width="7" height="11" rx="2" fill="#334155"/><rect class="leg-l" x="3" y="20" width="2.4" height="12" rx="1.2" fill="#334155"/><rect class="leg-r" x="6.6" y="20" width="2.4" height="12" rx="1.2" fill="#334155"/></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--rev', 'dur' => '26s', 'delay' => '-10s',
        'svg' => '<svg viewBox="0 0 12 34"><ellipse cx="6" cy="5" rx="3.4" ry="4" fill="#475569"/><rect x="2.5" y="10" width="7" height="11" rx="2" fill="#475569"/><rect class="leg-l" x="3" y="20" width="2.4" height="12" rx="1.2" fill="#475569"/><rect class="leg-r" x="6.6" y="20" width="2.4" height="12" rx="1.2" fill="#475569"/></svg>',
    ],
    [
        'wrap' => '', 'dur' => '19s', 'delay' => '-6s',
        'svg' => '<svg viewBox="0 0 12 34"><ellipse cx="6" cy="5" rx="3.4" ry="4" fill="#64748b"/><rect x="2.5" y="10" width="7" height="11" rx="2" fill="#64748b"/><rect class="leg-l" x="3" y="20" width="2.4" height="12" rx="1.2" fill="#64748b"/><rect class="leg-r" x="6.6" y="20" width="2.4" height="12" rx="1.2" fill="#64748b"/></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--near', 'dur' => '24s', 'delay' => '-3s',
        'svg' => '<svg viewBox="0 0 17 40"><ellipse cx="8.5" cy="5" rx="4" ry="4.6" fill="#334155"/><rect x="4" y="10.5" width="9" height="13" rx="2.4" fill="#334155"/><rect class="leg-l" x="4.4" y="23" width="3" height="14" rx="1.5" fill="#334155"/><rect class="leg-r" x="9.4" y="23" width="3" height="14" rx="1.5" fill="#334155"/><g class="bag-swing"><line x1="3" y1="14" x2="1.5" y2="20" stroke="#334155" stroke-width="1"/><rect x="-0.5" y="20" width="6" height="7" rx="1" fill="#f2718c"/></g></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--near mall-scene__shopper--rev', 'dur' => '27s', 'delay' => '-14s',
        'svg' => '<svg viewBox="0 0 17 40"><ellipse cx="8.5" cy="5" rx="4" ry="4.6" fill="#475569"/><rect x="4" y="10.5" width="9" height="13" rx="2.4" fill="#475569"/><rect class="leg-l" x="4.4" y="23" width="3" height="14" rx="1.5" fill="#475569"/><rect class="leg-r" x="9.4" y="23" width="3" height="14" rx="1.5" fill="#475569"/><g class="bag-swing"><line x1="13.5" y1="14" x2="15" y2="20" stroke="#475569" stroke-width="1"/><rect x="12.5" y="20" width="6" height="7" rx="1" fill="#5b9bf2"/></g></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--near', 'dur' => '30s', 'delay' => '-18s',
        'svg' => '<svg viewBox="0 0 26 40"><ellipse cx="8" cy="5" rx="4" ry="4.6" fill="#7a5fc7" opacity=".85"/><rect x="3.5" y="10.5" width="9" height="13" rx="2.4" fill="#7a5fc7" opacity=".85"/><rect class="leg-l" x="4" y="23" width="3" height="14" rx="1.5" fill="#7a5fc7" opacity=".85"/><rect class="leg-r" x="9" y="23" width="3" height="14" rx="1.5" fill="#7a5fc7" opacity=".85"/><ellipse cx="20" cy="16" rx="2.8" ry="3.2" fill="#ffd166"/><rect x="17" y="20" width="6" height="8" rx="2" fill="#ffd166"/><rect class="leg-l" x="17.3" y="27" width="2" height="9" rx="1" fill="#ffd166"/><rect class="leg-r" x="20.6" y="27" width="2" height="9" rx="1" fill="#ffd166"/><line x1="12" y1="17" x2="17" y2="22" stroke="#94a3b8" stroke-width="1.4"/></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--near', 'dur' => '33s', 'delay' => '-9s',
        'svg' => '<svg viewBox="0 0 40 40"><ellipse cx="9" cy="5" rx="4" ry="4.6" fill="#16a34a"/><rect x="4.5" y="10.5" width="9" height="13" rx="2.4" fill="#16a34a"/><rect class="leg-l" x="5" y="23" width="3" height="14" rx="1.5" fill="#16a34a"/><rect class="leg-r" x="10" y="23" width="3" height="14" rx="1.5" fill="#16a34a"/><line x1="14" y1="16" x2="24" y2="20" stroke="#334155" stroke-width="1.3"/><rect x="22" y="18" width="15" height="10" rx="1.5" fill="none" stroke="#94a3b8" stroke-width="1.4"/><rect x="24" y="19" width="6" height="6" rx="1" fill="#f2a65a" opacity=".8"/><circle class="cart-wheel" cx="25" cy="30" r="2.4" fill="none" stroke="#334155" stroke-width="1.2"/><circle class="cart-wheel" cx="34" cy="30" r="2.4" fill="none" stroke="#334155" stroke-width="1.2"/></svg>',
    ],
    [
        'wrap' => 'mall-scene__shopper--near', 'dur' => '21s', 'delay' => '-19s',
        'svg' => '<svg viewBox="0 0 20 40"><ellipse cx="9" cy="5" rx="4" ry="4.6" fill="#e0a54c"/><rect x="4.5" y="10.5" width="9" height="13" rx="2.4" fill="#e0a54c"/><rect class="leg-l" x="5" y="23" width="3" height="14" rx="1.5" fill="#e0a54c"/><rect class="leg-r" x="10" y="23" width="3" height="14" rx="1.5" fill="#e0a54c"/><g class="bag-swing"><line x1="3.5" y1="14" x2="2" y2="20" stroke="#e0a54c" stroke-width="1"/><rect x="-1" y="20" width="6" height="7" rx="1" fill="#3fb8ab"/></g></svg>',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - E-Parkir Mall System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { margin:0; padding:20px; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative; overflow-x:hidden; background-color:#eef2f4; }

        .mall-scene { position:fixed; inset:0; z-index:-2; overflow:hidden; background:linear-gradient(180deg,#eaf4ff 0%,#f3f7fb 30%,#f7f4ee 55%,#efe6d8 100%); animation:sceneBreathe 18s ease-in-out infinite; transform-origin:center bottom; }
        @keyframes sceneBreathe { 0%,100%{transform:scale(1)} 50%{transform:scale(1.035)} }

        .mall-scene__sky { position:absolute; top:0; left:0; width:100%; height:30%; background:linear-gradient(180deg,#cfe8ff 0%,#eaf4ff 100%); overflow:hidden; }
        .mall-scene__sky .cloud { position:absolute; top:20%; width:140px; height:46px; background:#fff; border-radius:50px; opacity:.8; filter:blur(1px); animation:driftCloud 60s linear infinite; }
        .mall-scene__sky .cloud::before, .mall-scene__sky .cloud::after { content:""; position:absolute; background:#fff; border-radius:50%; }
        .mall-scene__sky .cloud::before { width:70px; height:70px; top:-34px; left:12px; }
        .mall-scene__sky .cloud::after { width:56px; height:56px; top:-24px; left:70px; }
        .mall-scene__sky .cloud--1 { left:-20%; top:8%; animation-delay:0s; transform:scale(.8); }
        .mall-scene__sky .cloud--2 { left:-30%; top:40%; animation-delay:-22s; transform:scale(1.1); opacity:.65; }
        .mall-scene__sky .cloud--3 { left:-25%; top:65%; animation-delay:-40s; transform:scale(.6); opacity:.7; }
        @keyframes driftCloud { 0%{transform:translateX(0) scale(var(--s,1))} 100%{transform:translateX(140vw) scale(var(--s,1))} }

        .mall-scene__skylight-frame { position:absolute; top:0; left:0; width:100%; height:30%; opacity:.35; }
        .mall-scene__skylight-frame svg { width:100%; height:100%; }

        .mall-scene__beam { position:absolute; top:0; width:14%; height:60%; background:linear-gradient(180deg,rgba(255,250,225,.55) 0%,rgba(255,250,225,0) 100%); transform:skewX(-10deg); animation:beamSway 10s ease-in-out infinite; }
        .mall-scene__beam:nth-child(1) { left:10%; animation-delay:0s; }
        .mall-scene__beam:nth-child(2) { left:42%; animation-delay:-4s; opacity:.7; }
        .mall-scene__beam:nth-child(3) { left:72%; animation-delay:-2s; }
        @keyframes beamSway { 0%,100%{opacity:.55; transform:skewX(-10deg) translateX(0)} 50%{opacity:.85; transform:skewX(-4deg) translateX(2%)} }

        .mall-scene__banner { position:absolute; top:6%; width:26px; height:60px; transform-origin:top center; animation:bannerWave 3.2s ease-in-out infinite; z-index:1; }
        .mall-scene__banner svg { width:100%; height:100%; display:block; filter:drop-shadow(0 4px 6px rgba(0,0,0,.12)); }
        @keyframes bannerWave { 0%,100%{transform:rotate(-6deg)} 50%{transform:rotate(6deg)} }

        .mall-scene__pillars { position:absolute; bottom:16%; left:0; width:100%; height:46%; display:flex; justify-content:space-between; padding:0 2%; pointer-events:none; }
        .pillar { width:14px; height:100%; background:linear-gradient(90deg,#fff 0%,#e7e1d4 50%,#d8d0bd 100%); border-radius:3px; box-shadow:1px 0 3px rgba(0,0,0,.08); }
        .pillar::before { content:""; position:absolute; top:-10px; left:-7px; width:28px; height:12px; background:#f3ede0; border-radius:3px; }

        .mall-scene__storefronts { position:absolute; bottom:17%; left:0; width:100%; height:40%; display:flex; align-items:flex-end; justify-content:space-between; padding:0 3%; gap:1.8%; }
        .storefront { position:relative; flex:1; height:92%; max-width:190px; background:linear-gradient(180deg,#fff 0%,#f4f1ea 100%); border-radius:60px 60px 8px 8px; box-shadow:0 10px 22px -8px rgba(60,50,30,.18), 0 0 0 1px rgba(0,0,0,.03) inset; }
        .storefront::before { content:""; position:absolute; top:-16px; left:-4%; width:108%; height:24px; border-radius:50% 50% 0 0 / 100% 100% 0 0; background:var(--awning,#f2a65a); box-shadow:0 3px 8px rgba(0,0,0,.12); }
        .storefront__arch { position:absolute; top:14%; left:14%; width:72%; height:68%; border-radius:50% 50% 6px 6px / 40% 40% 6px 6px; background:linear-gradient(160deg,var(--glow-a,#ffe6c2),var(--glow-b,#ffcf94)); box-shadow:0 0 20px 4px var(--glow-shadow,rgba(255,183,94,.35)) inset, 0 0 14px 2px rgba(255,200,120,.25); overflow:hidden; animation:windowGlow 5s ease-in-out infinite; }
        .storefront__arch .mannequin { position:absolute; bottom:0; width:24%; opacity:.55; animation:mannequinSway 6s ease-in-out infinite; transform-origin:bottom center; }
        @keyframes mannequinSway { 0%,100%{transform:rotate(-1.5deg)} 50%{transform:rotate(1.5deg)} }
        .storefront__sign { position:absolute; bottom:4px; left:50%; transform:translateX(-50%); width:60%; height:6px; border-radius:3px; background:var(--awning,#f2a65a); opacity:.55; }
        .storefront__badge { position:absolute; top:6px; right:6%; width:30px; height:30px; border-radius:50%; background:#ef4444; color:#fff; font-size:8px; font-weight:800; display:flex; align-items:center; justify-content:center; text-align:center; line-height:1.1; box-shadow:0 4px 10px rgba(239,68,68,.4); animation:badgePulse 1.8s ease-in-out infinite; z-index:2; }
        @keyframes badgePulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }

        .storefront--rose { --awning:#f2718c; } .storefront--rose .storefront__arch { --glow-a:#ffdce4; --glow-b:#ffb6c6; --glow-shadow:rgba(242,113,140,.3); }
        .storefront--mint { --awning:#3fb8ab; } .storefront--mint .storefront__arch { --glow-a:#d7f5ef; --glow-b:#a6e8dd; --glow-shadow:rgba(63,184,171,.3); }
        .storefront--sun { --awning:#e0a54c; } .storefront--sun .storefront__arch { --glow-a:#fff1d6; --glow-b:#ffdd9e; --glow-shadow:rgba(224,165,76,.3); }
        .storefront--lilac { --awning:#a78bfa; } .storefront--lilac .storefront__arch { --glow-a:#ece4ff; --glow-b:#d6c5ff; --glow-shadow:rgba(167,139,250,.3); }
        .storefront--sky { --awning:#5b9bf2; } .storefront--sky .storefront__arch { --glow-a:#e1edff; --glow-b:#bcd9ff; --glow-shadow:rgba(91,155,242,.3); }
        @keyframes windowGlow { 0%,100%{filter:brightness(1)} 50%{filter:brightness(1.08)} }

        .mall-scene__plant { position:absolute; bottom:17%; width:30px; z-index:1; animation:plantSway 4.5s ease-in-out infinite; transform-origin:bottom center; }
        .mall-scene__plant svg { width:100%; height:auto; display:block; }
        @keyframes plantSway { 0%,100%{transform:rotate(-2deg)} 50%{transform:rotate(2deg)} }

        .mall-scene__fountain { position:absolute; bottom:17%; left:50%; transform:translateX(-50%); width:130px; height:60px; z-index:1; }
        .mall-scene__fountain .basin { position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:130px; height:16px; background:linear-gradient(180deg,#dceeff,#b9d9f5); border-radius:50%; box-shadow:0 0 20px 4px rgba(140,190,255,.35), 0 2px 4px rgba(0,0,0,.08); }
        .mall-scene__fountain .jet { position:absolute; bottom:12px; left:50%; width:3px; height:36px; background:linear-gradient(180deg,rgba(150,200,255,.9),rgba(150,200,255,0)); border-radius:2px; animation:jetPulse 1.6s ease-in-out infinite; }
        .mall-scene__fountain .jet:nth-child(2) { left:42%; height:24px; animation-delay:-.3s; }
        .mall-scene__fountain .jet:nth-child(3) { left:58%; height:24px; animation-delay:-.6s; }
        @keyframes jetPulse { 0%,100%{opacity:.5; transform:translateX(-50%) scaleY(.85)} 50%{opacity:1; transform:translateX(-50%) scaleY(1)} }

        .mall-scene__particle { position:absolute; bottom:18%; width:4px; height:4px; border-radius:50%; background:radial-gradient(circle, rgba(255,255,255,.95), rgba(255,255,255,0) 70%); animation:floatUp linear infinite; z-index:1; }
        @keyframes floatUp { 0%{transform:translateY(0) translateX(0); opacity:0} 10%{opacity:.9} 50%{transform:translateY(-30vh) translateX(2vw)} 90%{opacity:.3} 100%{transform:translateY(-55vh) translateX(-1vw); opacity:0} }

        .mall-scene__balloon { position:absolute; bottom:20%; width:16px; height:20px; z-index:1; animation:balloonFloat 6s ease-in-out infinite, walkAcross linear infinite; }
        .mall-scene__balloon svg { width:100%; height:100%; display:block; }
        @keyframes balloonFloat { 0%,100%{margin-top:0px} 50%{margin-top:-10px} }

        .mall-scene__shopper { position:absolute; bottom:17.5%; width:13px; height:32px; opacity:.5; z-index:1; animation:walkAcross linear infinite; }
        .mall-scene__shopper svg { width:100%; height:100%; display:block; }
        .mall-scene__shopper--rev { animation-direction:reverse; }
        .mall-scene__shopper--near { bottom:16.5%; width:17px; height:40px; opacity:.62; }
        .mall-scene__shopper--far { bottom:19%; width:10px; height:24px; opacity:.38; }
        @keyframes walkAcross { 0%{transform:translateX(-8vw)} 100%{transform:translateX(108vw)} }

        .leg-l, .leg-r { animation:legSwing .6s ease-in-out infinite; transform-origin:top center; }
        .leg-r { animation-delay:.3s; }
        @keyframes legSwing { 0%,100%{transform:rotate(14deg)} 50%{transform:rotate(-14deg)} }
        .bag-swing { animation:bagSwing .6s ease-in-out infinite; transform-origin:top center; }
        @keyframes bagSwing { 0%,100%{transform:rotate(-10deg)} 50%{transform:rotate(14deg)} }
        .cart-wheel { animation:wheelSpin .5s linear infinite; transform-origin:center; }
        @keyframes wheelSpin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }

        .mall-scene__floor { position:absolute; bottom:0; left:0; width:100%; height:17%; background:linear-gradient(180deg,#fbf8f2 0%,#ece3d2 100%); overflow:hidden; }
        .mall-scene__floor::before { content:""; position:absolute; inset:0; background:repeating-linear-gradient(90deg, rgba(140,120,80,.05) 0 1.5px, transparent 1.5px 110px); }
        .mall-scene__floor::after { content:""; position:absolute; top:0; left:-40%; width:40%; height:100%; background:linear-gradient(100deg, transparent, rgba(255,255,255,.55) 45%, transparent); animation:floorShine 7s linear infinite; }
        @keyframes floorShine { 0%{left:-40%} 100%{left:120%} }

        .scene-overlay { position:fixed; inset:0; z-index:-1; background:radial-gradient(ellipse at center, rgba(255,255,255,.55) 0%, rgba(255,255,255,.28) 45%, rgba(255,255,255,.06) 100%); }

        .welcome-card { background:rgba(255,255,255,.94); backdrop-filter:blur(14px); border-radius:24px; box-shadow:0 25px 60px -12px rgba(30,40,60,.22); border:1px solid rgba(255,255,255,.5); max-width:480px; width:100%; padding:35px 32px; position:relative; z-index:2; margin-bottom:20px; }
        .brand-icon { width:64px; height:64px; background:linear-gradient(135deg,#0d6efd 0%,#0a58ca 100%); color:#fff; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:800; margin:0 auto 16px auto; box-shadow:0 10px 25px rgba(13,110,253,.4); }

        .stat-card { border-radius:16px; padding:12px 6px; text-align:center; border:1px solid transparent; }
        .stat-card.total { background-color:#f8fafc; border-color:#e2e8f0; }
        .stat-card.available { background-color:#f0fdf4; border-color:#bbf7d0; }
        .stat-card.occupied { background-color:#fff7ed; border-color:#fed7aa; }
        .stat-number { font-size:24px; font-weight:800; line-height:1.2; }
        .stat-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }

        .kategori-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
        .kategori-card { border-radius:14px; padding:10px 8px; text-align:center; background:#f8fafc; border:1px solid #e2e8f0; }
        .kategori-card__icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 6px auto; color:#fff; font-size:15px; }
        .kategori-card__nama { font-size:11px; font-weight:700; color:#334155; margin-bottom:2px; }
        .kategori-card__slot { font-size:14px; font-weight:800; color:#0f172a; }
        .kategori-card__slot small { font-size:10px; font-weight:600; color:#94a3b8; }

        .btn-custom { border-radius:12px; padding:11px 15px; font-size:14px; font-weight:700; transition:all .3s ease; text-align:left; display:flex; align-items:center; }
        .btn-custom i { font-size:16px; margin-right:10px; }
        .btn-login { background:linear-gradient(135deg,#0d6efd 0%,#0b5ed7 100%); border:none; color:#fff; box-shadow:0 6px 15px rgba(13,110,253,.3); justify-content:center; text-align:center; }
        .btn-login:hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(13,110,253,.45); color:#fff; }
        .btn-menu { background:#fff; border:1px solid #e2e8f0; color:#334155; }
        .btn-menu:hover { background:#f8fafc; border-color:#cbd5e1; color:#0d6efd; }

        .prosedur-step { display:flex; gap:14px; align-items:flex-start; padding:12px 4px; border-bottom:1px dashed #e5e7eb; }
        .prosedur-step:last-child { border-bottom:none; }
        .prosedur-step__num { flex:0 0 34px; width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#0d6efd 0%,#0a58ca 100%); color:#fff; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(13,110,253,.3); }
        .prosedur-step__body h6 { font-size:13.5px; font-weight:700; margin-bottom:2px; color:#1f2937; }
        .prosedur-step__body p { font-size:12.5px; color:#6b7280; margin-bottom:0; line-height:1.5; }
        .prosedur-masuk .prosedur-step__num { background:linear-gradient(135deg,#16a34a 0%,#15803d 100%); box-shadow:0 4px 10px rgba(22,163,74,.3); }
        .prosedur-keluar .prosedur-step__num { background:linear-gradient(135deg,#f97316 0%,#ea580c 100%); box-shadow:0 4px 10px rgba(249,115,22,.3); }
    </style>
</head>
<body>

    <div class="mall-scene" aria-hidden="true">
        <div class="mall-scene__sky">
            <div class="cloud cloud--1"></div>
            <div class="cloud cloud--2"></div>
            <div class="cloud cloud--3"></div>
        </div>

        <div class="mall-scene__skylight-frame">
            <svg viewBox="0 0 1000 300" preserveAspectRatio="none">
                <g stroke="#ffffff" stroke-width="2" opacity="0.6" fill="none">
                    <line x1="0" y1="0" x2="200" y2="300"/>
                    <line x1="150" y1="0" x2="350" y2="300"/>
                    <line x1="300" y1="0" x2="500" y2="300"/>
                    <line x1="450" y1="0" x2="650" y2="300"/>
                    <line x1="600" y1="0" x2="800" y2="300"/>
                    <line x1="750" y1="0" x2="950" y2="300"/>
                    <line x1="900" y1="0" x2="1100" y2="300"/>
                    <line x1="0" y1="90" x2="1000" y2="90"/>
                    <line x1="0" y1="190" x2="1000" y2="190"/>
                </g>
            </svg>
        </div>

        <div class="mall-scene__beam"></div>
        <div class="mall-scene__beam"></div>
        <div class="mall-scene__beam"></div>

        <?php foreach ($banners as $b): ?>
        <div class="mall-scene__banner" style="left:<?php echo $b['left']; ?>; animation-delay:<?php echo $b['delay']; ?>;">
            <svg viewBox="0 0 26 60"><rect x="0" y="0" width="26" height="46" fill="<?php echo $b['color']; ?>" rx="2"/><path d="M0,46 L13,60 L26,46 Z" fill="<?php echo $b['color']; ?>"/><text x="13" y="28" font-size="9" fill="#fff" text-anchor="middle" font-weight="800"><?php echo $b['text']; ?></text></svg>
        </div>
        <?php endforeach; ?>

        <div class="mall-scene__pillars">
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="pillar"></div>
            <?php endfor; ?>
        </div>

        <?php foreach ($plants as $p): ?>
        <div class="mall-scene__plant" style="<?php echo $p['side'] . $p['delay']; ?>">
            <svg viewBox="0 0 30 60"><rect x="9" y="42" width="12" height="16" rx="2" fill="#c9a876"/><path d="M15,10 C4,18 4,34 15,42 C26,34 26,18 15,10 Z" fill="#4f9c6d"/><path d="M15,4 C7,12 7,26 15,32 C23,26 23,12 15,4 Z" fill="#5fb37f"/></svg>
        </div>
        <?php endforeach; ?>

        <div class="mall-scene__storefronts">
            <?php foreach ($storefronts as $s): ?>
            <div class="storefront storefront--<?php echo $s['class']; ?>">
                <?php if ($s['badge']): ?><div class="storefront__badge"><?php echo $s['badge']; ?></div><?php endif; ?>
                <div class="storefront__arch">
                    <svg viewBox="0 0 40 60" class="mannequin" style="left:<?php echo $s['mannequin_left']; ?>;"><ellipse cx="20" cy="10" rx="6" ry="7" fill="<?php echo $s['fill']; ?>" opacity="<?php echo $s['op']; ?>"/><rect x="10" y="18" width="20" height="34" rx="6" fill="<?php echo $s['fill']; ?>" opacity="<?php echo $s['op']; ?>"/></svg>
                </div>
                <div class="storefront__sign"></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mall-scene__fountain">
            <div class="jet"></div>
            <div class="jet"></div>
            <div class="jet"></div>
            <div class="basin"></div>
        </div>

        <?php foreach ($particles as $p): ?>
        <div class="mall-scene__particle" style="left:<?php echo $p['left']; ?>; animation-duration: <?php echo $p['dur']; ?>; animation-delay: <?php echo $p['delay']; ?>;"></div>
        <?php endforeach; ?>

        <?php foreach ($balloons as $bal): ?>
        <div class="mall-scene__balloon" style="animation-duration: <?php echo $bal['dur']; ?>; animation-delay: <?php echo $bal['delay']; ?>;">
            <svg viewBox="0 0 16 20"><ellipse cx="8" cy="8" rx="8" ry="9" fill="<?php echo $bal['color']; ?>"/><path d="M8,17 L6,20 L10,20 Z" fill="<?php echo $bal['color']; ?>"/><line x1="8" y1="20" x2="8" y2="30" stroke="#c2c2c2" stroke-width="1"/></svg>
        </div>
        <?php endforeach; ?>

        <?php foreach ($shoppers as $sh): ?>
        <div class="mall-scene__shopper<?php echo $sh['wrap'] ? ' ' . $sh['wrap'] : ''; ?>" style="animation-duration: <?php echo $sh['dur']; ?>; animation-delay: <?php echo $sh['delay']; ?>;">
            <?php echo $sh['svg']; ?>
        </div>
        <?php endforeach; ?>

        <div class="mall-scene__floor"></div>
    </div>

    <div class="scene-overlay" aria-hidden="true"></div>

    <div class="welcome-card">
        <div class="brand-icon">
            <i class="bi bi-p-square-fill"></i>
        </div>

        <h2 class="text-center fw-extrabold mb-1" style="color: #1e293b; font-size: 24px;">Selamat Datang</h2>
        <p class="text-center text-muted mb-3" style="font-size: 13px; font-weight: 600;">DI PARKIR MALL SMKN 1 SANDEN</p>
        <p class="text-center text-secondary mb-3" style="font-size: 12px;">Sistem informasi Management Parkir Mall. Ketersediaan slot parkir real-time saat ini:</p>

        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="stat-card total">
                    <div class="stat-label text-secondary">Total Slot</div>
                    <div class="stat-number text-dark"><?php echo $total_slot; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card available">
                    <div class="stat-label text-success">Tersedia</div>
                    <div class="stat-number text-success"><?php echo $tersedia; ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card occupied">
                    <div class="stat-label text-warning">Terisi</div>
                    <div class="stat-number text-warning"><?php echo $terisi; ?></div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="kategori-label"><i class="bi bi-grid-fill me-1"></i> Ketersediaan per Kategori Kendaraan</div>
            <div class="row g-2">
                <?php foreach ($daftar_kategori as $k):
                    $style = $kategori_style[$k];
                    $data  = $rekap_kategori[$k];
                ?>
                <div class="col-3">
                    <div class="kategori-card">
                        <div class="kategori-card__icon" style="background:<?php echo $style['warna']; ?>;">
                            <i class="bi <?php echo $style['icon']; ?>"></i>
                        </div>
                        <div class="kategori-card__nama"><?php echo $k; ?></div>
                        <div class="kategori-card__slot"><?php echo $data['tersedia']; ?><small>/<?php echo $data['total']; ?></small></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-grid gap-2 mb-3">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn btn-custom btn-login text-decoration-none">
                    <i class="bi bi-speedometer2 me-2"></i> Masuk ke Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-custom btn-login text-decoration-none">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Halaman Login
                </a>
            <?php endif; ?>

            <button type="button" class="btn btn-custom btn-menu" data-bs-toggle="modal" data-bs-target="#modalProsedur">
                <i class="bi bi-journal-text text-success"></i> Tata Cara Parkir
            </button>

            <button type="button" class="btn btn-custom btn-menu" data-bs-toggle="modal" data-bs-target="#modalRating">
                <i class="bi bi-star-fill text-warning"></i> Beri Rating & Komentar
            </button>

            <button type="button" class="btn btn-custom btn-menu" data-bs-toggle="modal" data-bs-target="#modalFaq">
                <i class="bi bi-question-circle-fill text-info"></i> Pusat Bantuan (FAQ)
            </button>
        </div>

        <div class="text-center">
            <span class="text-muted" style="font-size: 11px; font-weight: 600;">
                <i class="bi bi-shield-check text-success me-1"></i> Aman, Cepat & Terintegrasi Real-Time
            </span>
        </div>
    </div>

    <div class="modal fade" id="modalProsedur" tabindex="-1" aria-labelledby="modalProsedurLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-extrabold" id="modalProsedurLabel" style="color: #1e293b;">
                        <i class="bi bi-info-circle-fill text-primary me-2"></i> Tata Cara Parkir Mall
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="mb-4">
                        <h6 class="text-success fw-bold mb-3" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Prosedur Masuk Parkir
                        </h6>
                        <div class="prosedur-masuk">
                            <div class="prosedur-step">
                                <div class="prosedur-step__num">1</div>
                                <div class="prosedur-step__body">
                                    <h6>Ambil Tiket / Scan Plat</h6>
                                    <p>Kendaraan mendekati gerbang masuk, ambil tiket parkir dari mesin dispenser atau gunakan sistem otomatis.</p>
                                </div>
                            </div>
                            <div class="prosedur-step">
                                <div class="prosedur-step__num">2</div>
                                <div class="prosedur-step__body">
                                    <h6>Pilih Area Parkir</h6>
                                    <p>Ikuti papan petunjuk arah di dalam area mall untuk mencari slot kosong yang tertera.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h6 class="text-warning fw-bold mb-3" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bi bi-box-arrow-right me-1"></i> Prosedur Keluar Parkir
                        </h6>
                        <div class="prosedur-keluar">
                            <div class="prosedur-step">
                                <div class="prosedur-step__num">1</div>
                                <div class="prosedur-step__body">
                                    <h6>Siapkan Tiket</h6>
                                    <p>Bawa tiket parkir fisik Anda menuju pos gerbang keluar atau loket pembayaran terdekat.</p>
                                </div>
                            </div>
                            <div class="prosedur-step">
                                <div class="prosedur-step__num">2</div>
                                <div class="prosedur-step__body">
                                    <h6>Lakukan Pembayaran</h6>
                                    <p>Bayar tarif parkir sesuai durasi waktu (tunai atau non-tunai).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-secondary w-100 py-2" data-bs-dismiss="modal" style="border-radius: 12px; font-weight: 700;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

<div class="container mt-4">
    <h4 class="fw-bold mb-3">Ulasan Pengguna</h4>
    <div id="daftarUlasan" class="row g-3">
        <?php if (empty($daftar_ulasan_db)): ?>
            <div id="pesanKosong" class="text-muted fst-italic">Belum ada ulasan. Jadilah yang pertama memberikan ulasan!</div>
        <?php else: ?>
            <?php foreach ($daftar_ulasan_db as $u): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-3" style="border-radius: 15px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8'); ?></h6>
                            <small class="text-muted"><?php echo date('d M Y H:i', strtotime($u['waktu'])); ?></small>
                        </div>
                        <div class="mb-2" style="font-size: 14px;">
                            <?php echo str_repeat('⭐', (int)$u['rating']); ?>
                        </div>
                        <p class="mb-0 text-secondary" style="font-size: 14px;"><?php echo nl2br(htmlspecialchars($u['komentar'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalRating" tabindex="-1" aria-labelledby="modalRatingLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-extrabold" id="modalRatingLabel" style="color: #1e293b;">
                    <i class="bi bi-star-fill text-warning me-2"></i> Beri Rating & Komentar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRating" onsubmit="tambahUlasan(event)">
                <div class="modal-body px-4 py-3">
                    <div id="alertUlasan" class="alert d-none" role="alert" style="border-radius: 10px; font-size: 13px;"></div>
                    <div class="mb-3">
                        <label for="nama" class="form-label fw-bold" style="font-size: 13px;">Nama Anda</label>
                        <input type="text" class="form-control" id="nama" name="nama" required placeholder="Masukkan nama Anda" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label for="rating" class="form-label fw-bold" style="font-size: 13px;">Rating (1 - 5 Bintang)</label>
                        <select class="form-select" id="rating" name="rating" required style="border-radius: 10px;">
                            <option value="5">⭐⭐⭐⭐⭐ - Sangat Memuaskan</option>
                            <option value="4">⭐⭐⭐⭐ - Memuaskan</option>
                            <option value="3">⭐⭐⭐ - Cukup</option>
                            <option value="2">⭐⭐ - Kurang</option>
                            <option value="1">⭐ - Buruk</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="komentar" class="form-label fw-bold" style="font-size: 13px;">Komentar / Masukan</label>
                        <textarea class="form-control" id="komentar" name="komentar" rows="3" placeholder="Tuliskan pengalaman atau saran Anda tentang sistem parkir ini..." style="border-radius: 10px;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="submit" id="btnKirimUlasan" class="btn btn-primary w-100 py-2" style="border-radius: 12px; font-weight: 700;">Kirim Komentar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function tambahUlasan(event) {
        event.preventDefault();

        const nama = document.getElementById('nama').value;
        const ratingValue = document.getElementById('rating').value;
        const komentar = document.getElementById('komentar').value;

        const btnKirim = document.getElementById('btnKirimUlasan');
        const alertBox = document.getElementById('alertUlasan');
        alertBox.classList.add('d-none');
        btnKirim.disabled = true;
        btnKirim.textContent = 'Mengirim...';

        try {
            const formData = new FormData();
            formData.append('aksi', 'simpan_ulasan');
            formData.append('nama', nama);
            formData.append('rating', ratingValue);
            formData.append('komentar', komentar);

            const res = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            const hasil = await res.json();

            if (!hasil.success) {
                throw new Error(hasil.message || 'Gagal menyimpan ulasan.');
            }

            let bintang = '';
            for (let i = 0; i < parseInt(ratingValue); i++) {
                bintang += '⭐';
            }

            const pesanKosong = document.getElementById('pesanKosong');
            if (pesanKosong) {
                pesanKosong.remove();
            }

            const daftarUlasan = document.getElementById('daftarUlasan');
            const cardUlasan = document.createElement('div');
            cardUlasan.className = 'col-12';
            cardUlasan.innerHTML = `
                <div class="card border-0 shadow-sm p-3" style="border-radius: 15px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold mb-0 text-dark">${escapeHtml(nama)}</h6>
                        <small class="text-muted">Baru saja</small>
                    </div>
                    <div class="mb-2" style="font-size: 14px;">
                        ${bintang}
                    </div>
                    <p class="mb-0 text-secondary" style="font-size: 14px;">${escapeHtml(komentar)}</p>
                </div>
            `;

            daftarUlasan.prepend(cardUlasan);

            document.getElementById('formRating').reset();

            const modalElement = document.getElementById('modalRating');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            modalInstance.hide();

        } catch (err) {
            alertBox.textContent = err.message || 'Terjadi kesalahan, silakan coba lagi.';
            alertBox.classList.remove('d-none');
            alertBox.classList.add('alert-danger');
        } finally {
            btnKirim.disabled = false;
            btnKirim.textContent = 'Kirim Komentar';
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
    <div class="modal fade" id="modalFaq" tabindex="-1" aria-labelledby="modalFaqLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-extrabold" id="modalFaqLabel" style="color: #1e293b;">
                        <i class="bi bi-question-circle-fill text-info me-2"></i> Pusat Bantuan (FAQ)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="accordion" id="faqAccordion">
                        <?php
                        $faqs = [
                            [
                                'q' => 'Bagaimana cara melihat slot parkir yang kosong?',
                                'a' => 'Anda dapat melihat ketersediaan slot parkir secara real-time langsung melalui halaman utama dashboard sistem e-parkir ini pada kartu statistik "Tersedia".',
                                'show' => true,
                            ],
                            [
                                'q' => 'Apa yang harus dilakukan jika tiket parkir hilang?',
                                'a' => 'Segera laporkan kehilangan tiket ke petugas pos pengelola parkir di gerbang keluar dengan membawa STNK serta identitas diri yang sah untuk proses verifikasi.',
                                'show' => false,
                            ],
                            [
                                'q' => 'Bagaimana cara melakukan reservasi slot parkir?',
                                'a' => 'Klik tombol "Reservasi Slot Parkir" di halaman utama, lalu ikuti langkah-langkah pengisian form pemesanan slot sesuai jenis kendaraan Anda.',
                                'show' => false,
                            ],
                        ];
                        foreach ($faqs as $i => $faq):
                            $collapseId = 'collapse' . $i;
                            $headingId  = 'heading' . $i;
                        ?>
                        <div class="accordion-item border-0 mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                            <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                <button class="accordion-button<?php echo $faq['show'] ? '' : ' collapsed'; ?> fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $faq['show'] ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>" style="font-size: 13.5px;">
                                    <?php echo $faq['q']; ?>
                                </button>
                            </h2>
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse<?php echo $faq['show'] ? ' show' : ''; ?>" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-secondary" style="font-size: 12.5px; line-height: 1.5;">
                                    <?php echo $faq['a']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-secondary w-100 py-2" data-bs-dismiss="modal" style="border-radius: 12px; font-weight: 700;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <footer style="text-align: center; padding: 10px 0 20px 0; font-family: 'Plus Jakarta Sans', sans-serif; color: #475569; position: relative; z-index: 2;">
        <p style="margin: 3px 0; font-size: 13px;">&copy; <?php echo date('Y'); ?> <strong style="color: #334155;">Parkir Mall</strong>. All Rights Reserved.</p>
        <p style="margin: 3px 0; font-size: 12px;">Dibuat oleh: <strong style="color: #1e293b;">Sidiq Fery Nur'cahya</strong> | <strong style="color: #1e293b;">SMKN 1 SANDEN 2026</strong></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>