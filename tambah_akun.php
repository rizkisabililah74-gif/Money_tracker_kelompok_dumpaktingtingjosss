<?php
require __DIR__ . '/service/auth.php';
require __DIR__ . '/service/databases.php';
require_login();
$uid = (int)$_SESSION['user_id'];

$jenis_label = ['Kas' => 'Cash', 'Bank' => 'Bank', 'E-Wallet' => 'E-Wallet'];

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis_akun'] ?? '';
    $nama  = trim($_POST['nama_akun'] ?? '');
    $saldo = trim($_POST['saldo_awal'] ?? '');
    if ($saldo === '') $saldo = '0';
    $dobel = rows($connect_api, "SELECT 1 FROM accounts WHERE user_id = ? AND nama_akun = ?", [$uid, $nama]);

    if (!csrf_ok())                               $err = 'Token tidak valid, muat ulang halaman';
    elseif (!isset($jenis_label[$jenis]))         $err = 'Jenis dompet tidak valid';
    elseif ($nama === '' || strlen($nama) > 100)  $err = 'Nama akun wajib diisi (maksimal 100 karakter)';
    elseif ($dobel)                               $err = 'Nama akun sudah dipakai';
    elseif (!is_numeric($saldo) || $saldo < 0 || $saldo > 9999999999999.99) $err = 'Saldo awal tidak valid';
    else {
        $connect_api->prepare("INSERT INTO accounts (user_id, nama_akun, jenis_akun, saldo_awal) VALUES (?,?,?,?)")
            ->execute([$uid, $nama, $jenis, $saldo]);   // pemilik dari sesi, bukan dari form
        $_SESSION['flash'] = 'Akun tersimpan';
        header('Location: dashboard.php');
        exit;
    }
}
// include __DIR__ . '/Layout/header.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Akun - MoneyTracker</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #eaf2fb;
            font-family: 'Inter', sans-serif;
            color: #0b3982;
            min-height: 100vh;
            padding: 32px 16px;
        }
        h1, h2, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .text-navy {
            color: #0b3982;
        }
        /* Tombol Back Bulat di Kiri Atas */
        .btn-back-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background-color: #dbeafe;
            color: #0b3982;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .btn-back-circle:hover {
            background-color: #bfdbfe;
            color: #082960;
        }
        /* Kartu Putih Utama */
        .main-card {
            background-color: #ffffff;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(11, 57, 130, 0.05);
            padding: 44px 56px;
            max-width: 780px;
            width: 100%;
        }
        /* Kotak Formulir Abu-abu di Dalam Kartu */
        .inner-form-box {
            background-color: #eeedeb;
            border: 1px solid #334155;
            border-radius: 22px;
            padding: 36px 32px 64px 32px;
            margin-top: 24px;
        }
        .form-label-navy {
            color: #0b3982;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        /* Input Kapsul (Pill) */
        .pill-field {
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 50px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .pill-field:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .pill-field select,
        .pill-field input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            color: #0b3982;
            font-size: 0.92rem;
            font-weight: 500;
        }
        .pill-field input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }
        .pill-dompet {
            max-width: 270px;
        }
        .pill-wide {
            max-width: 410px;
        }
        /* Badge Rp Kuning */
        .rp-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #fde047;
            color: #713f12;
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        /* Tombol + Tambah Akun & Batal */
        .btn-tambah-pill {
            background-color: #c7dcf8;
            color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 50px;
            padding: 10px 22px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-tambah-pill:hover {
            background-color: #b1cef5;
            color: #0f172a;
        }
        .btn-batal-pill {
            background-color: #ffffff;
            color: #0b3982;
            border: 1.5px solid #3b82f6;
            border-radius: 50px;
            padding: 10px 36px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .btn-batal-pill:hover {
            background-color: #eff6ff;
            color: #0b3982;
        }
        @media (max-width: 576px) {
            .main-card { padding: 28px 20px; }
            .inner-form-box { padding: 24px 18px 36px 18px; }
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 920px;">
    <div class="d-flex flex-column flex-md-row align-items-start gap-3 gap-md-4">
        <!-- Tombol Panah Kembali di Kiri Luar Kartu -->
        <a href="dashboard.php" class="btn-back-circle mt-1" title="Kembali ke Beranda">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>

        <!-- Kartu Putih Utama -->
        <div class="main-card mx-auto">
            <h1 class="h3 fw-bold text-navy mb-1">Tambah Akun</h1>
            <p class="text-primary small mb-0">Silakan isi Formulir dibawah ini</p>

            <!-- Kotak Formulir Abu-abu -->
            <div class="inner-form-box">
                <?php if ($err): ?>
                    <div class="alert alert-danger rounded-4 py-2 px-3 small mb-4">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <strong><?= e($err) ?></strong>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <!-- Jenis Dompet -->
                    <div class="mb-4">
                        <label class="form-label-navy d-block font-heading">Jenis Dompet</label>
                        <div class="pill-field pill-dompet">
                            <!-- Ikon Dompet Kustom Oranye -->
                            <svg width="24" height="24" viewBox="0 0 28 28" fill="none" class="flex-shrink-0">
                                <path d="M5 9.5C5 7.84315 6.34315 6.5 8 6.5H20C21.1046 6.5 22 7.39543 22 8.5V10.5H5V9.5Z" fill="#EA580C" stroke="#0B3982" stroke-width="1.8"/>
                                <rect x="4" y="9.5" width="20" height="13" rx="3" fill="#FB923C" stroke="#0B3982" stroke-width="1.8"/>
                                <path d="M18 13.5H24V18.5H18C16.6193 18.5 15.5 17.3807 15.5 16C15.5 14.6193 16.6193 13.5 18 13.5Z" fill="#FDBA74" stroke="#0B3982" stroke-width="1.8"/>
                                <circle cx="18.2" cy="16" r="1.2" fill="#0B3982"/>
                            </svg>
                            <select name="jenis_akun" required>
                                <option value="" disabled <?= empty($_POST['jenis_akun']) ? 'selected' : '' ?>>Pilih Dompet</option>
                                <?php foreach ($jenis_label as $nilai => $teks): ?>
                                    <option value="<?= e($nilai) ?>" <?= ($_POST['jenis_akun'] ?? '') === $nilai ? 'selected' : '' ?>><?= e($teks) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Nama Akun -->
                    <div class="mb-4">
                        <label class="form-label-navy d-block font-heading">Nama Akun</label>
                        <div class="pill-field pill-wide">
                            <!-- Ikon 4 Kotak Warna-warni (Sesuai Figma) -->
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="flex-shrink-0">
                                <rect x="2" y="2" width="8.5" height="8.5" rx="2" fill="#EF4444" stroke="#0F172A" stroke-width="1.4"/>
                                <rect x="13.5" y="2" width="8.5" height="8.5" rx="2" fill="#FACC15" stroke="#0F172A" stroke-width="1.4"/>
                                <rect x="2" y="13.5" width="8.5" height="8.5" rx="2" fill="#22C55E" stroke="#0F172A" stroke-width="1.4"/>
                                <rect x="13.5" y="13.5" width="8.5" height="8.5" rx="2" fill="#1D4ED8" stroke="#0F172A" stroke-width="1.4"/>
                            </svg>
                            <input type="text" name="nama_akun" maxlength="100" required placeholder="Contoh : Gopay, BCA, Cash" value="<?= e($_POST['nama_akun'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Saldo Awal -->
                    <div class="mb-4">
                        <label class="form-label-navy d-block font-heading">Saldo awal</label>
                        <div class="pill-field pill-wide">
                            <span class="rp-circle">Rp</span>
                            <input type="number" name="saldo_awal" min="0" step="0.01" placeholder="Masukkan jumlah (Default : Rp.0)" value="<?= e($_POST['saldo_awal'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Tombol Tambah Akun & Batal -->
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-4 pt-2">
                        <button type="submit" class="btn-tambah-pill">
                            <i class="bi bi-plus-lg fs-6"></i>
                            <span>Tambah Akun</span>
                        </button>
                        <a href="dashboard.php" class="btn-batal-pill">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>