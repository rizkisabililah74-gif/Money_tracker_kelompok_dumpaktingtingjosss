<?php
require __DIR__ . '/service/auth.php';
require __DIR__ . '/service/databases.php';
require_login();

# Ambil user_id dari database , tampung sebagai variabel $uid di sesi sekarang 
$uid = (int)$_SESSION['user_id'];

// Selector bulan: ?bulan=YYYY-MM, tidak boleh lewat bulan ini.
$ini   = date('Y-m');
$bulan = $_GET['bulan'] ?? $ini;
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) || $bulan > $ini) $bulan = $ini;
$awal  = "$bulan-01";  
$akhir = date('Y-m-d', strtotime("$awal +1 month"));
$prev  = date('Y-m', strtotime("$awal -1 month"));
$next  = date('Y-m', strtotime("$awal +1 month"));
$nama_bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$label = $nama_bulan[(int)substr($bulan, 5)] . ' ' . substr($bulan, 0, 4);

// 2-4. Pemasukan, pengeluaran, selisih bulan terpilih
$sum = rows($connect_api,
    "SELECT COALESCE(SUM(CASE WHEN t.jenis_transaksi='Pemasukan' THEN t.jumlah END),0) AS masuk, # 3.Kalau ini pemasukan, ambil jumlahnya. Kalau bukan, kosong, lalu panggil sebagai 'masuk'.
    COALESCE(SUM(CASE WHEN t.jenis_transaksi='Pengeluaran' THEN t.jumlah END),0) AS keluar # 4.Kalau ini pengeluaran, ambil jumlahnya. Kalau bukan, kosong, lalu panggil seebagai 'keluar'.
    FROM transactions t JOIN accounts a ON a.account_id = t.account_id # 1.Ambil semua transaksi, sambungkan ke akunnya, Tabel transactions tidak tahu siapa pemiliknya. Pemilik diketahui lewat akunnya.
    WHERE a.user_id = ? AND t.tanggal >= ? AND t.tanggal < ?", [$uid, $awal, $akhir])[0]; # 2.Simpan hanya yang milik user ini dan tanggalnya di bulan terpilih.

// pemasukan
$masuk = (float)$sum['masuk'];
// pengeluaran
$keluar = (float)$sum['keluar'];
// Selisih (pemasukan - pengeluaran)
$selisih = $masuk - $keluar;

// 5. Saldo per akun (saldo terkini, tidak ikut bulan)
// Untuk tiap akun milik user: mulai dari saldo awal, tambah semua pemasukan, kurangi semua pengeluaran, akun kosong tetap tampil dengan saldo awalnya.”
$akun = rows($connect_api, "SELECT a.nama_akun, a.jenis_akun, a.saldo_awal + COALESCE # Ambil semua akun, tempelkan transaksinya masing-masing. Panggil sebagai saldo
    (SUM(CASE t.jenis_transaksi WHEN 'Pemasukan' THEN t.jumlah WHEN 'Pengeluaran' THEN -t.jumlah END),0) AS saldo

    FROM accounts a LEFT JOIN transactions t ON t.account_id = a.account_id
    WHERE a.user_id = ? # hanya akun milik user ini di sesi sekarang
    GROUP BY a.account_id, a.nama_akun, a.jenis_akun, a.saldo_awal #
    ORDER BY a.jenis_akun, a.nama_akun", [$uid]);

$jenis_label = ['Kas' => 'Cash', 'Bank' => 'Mobile Banking', 'E-Wallet' => 'E-Wallet'];

// 6. Transaksi terakhir: 5 terbaru, atau semua jika ?semua=1
$semua = isset($_GET['semua']);
$trx = rows($connect_api, "SELECT t.tanggal, a.nama_akun, c.nama_kategori, t.jenis_transaksi, t.jumlah, t.keterangan
    FROM transactions t
    JOIN accounts a   ON a.account_id  = t.account_id
    JOIN categories c ON c.category_id = t.category_id
    WHERE a.user_id = ? 
    ORDER BY t.tanggal DESC, t.transaction_id DESC " . ($semua ? '' : 'LIMIT 5'), [$uid]);

// 8. Pengeluaran terbesar bulan terpilih
$besar = rows($connect_api, "SELECT c.nama_kategori, SUM(t.jumlah) AS total
    FROM transactions t
    JOIN accounts a   ON a.account_id  = t.account_id
    JOIN categories c ON c.category_id = t.category_id
    WHERE a.user_id = ? AND t.jenis_transaksi = 'Pengeluaran' AND t.tanggal >= ? AND t.tanggal < ?
    GROUP BY c.category_id, c.nama_kategori
    ORDER BY total DESC LIMIT 1", [$uid, $awal, $akhir])[0] ?? null;

// Helper ikon & warna kategori
function ikon_kategori($nama) {
    $k = strtolower(trim($nama));
    if (str_contains($k, 'makan') || str_contains($k, 'minum') || str_contains($k, 'kuliner')) {
        return ['ikon' => 'bi-cup-hot-fill', 'warna' => '#f97316', 'bg' => '#ffedd5']; // Oranye
    }
    if (str_contains($k, 'gaji') || str_contains($k, 'bonus') || str_contains($k, 'kerja')) {
        return ['ikon' => 'bi-briefcase-fill', 'warna' => '#16a34a', 'bg' => '#dcfce7']; // Hijau
    }
    if (str_contains($k, 'belanja') || str_contains($k, 'shop')) {
        return ['ikon' => 'bi-cart-fill', 'warna' => '#9333ea', 'bg' => '#f3e8ff']; // Ungu
    }
    if (str_contains($k, 'transport') || str_contains($k, 'bensin') || str_contains($k, 'ojek')) {
        return ['ikon' => 'bi-car-front-fill', 'warna' => '#0284c7', 'bg' => '#e0f2fe']; // Biru Cerah
    }
    if (str_contains($k, 'hiburan') || str_contains($k, 'nonton') || str_contains($k, 'game')) {
        return ['ikon' => 'bi-controller', 'warna' => '#eab308', 'bg' => '#fef9c3']; // Kuning
    }
    if (str_contains($k, 'tagihan') || str_contains($k, 'listrik') || str_contains($k, 'internet')) {
        return ['ikon' => 'bi-receipt', 'warna' => '#e11d48', 'bg' => '#ffe4e6']; // Merah Muda
    }
    return ['ikon' => 'bi-tag-fill', 'warna' => '#2563eb', 'bg' => '#dbeafe']; // Default Biru
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
// include __DIR__ . '/Layout/header.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - MoneyTracker</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter (Sesuai Figma) -->
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
            overflow-x: hidden;
        }
        h1, h2, h3, h4, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        /* Sidebar biru tua sesuai Figma */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background-color: #0f3694;
            flex-shrink: 0;
        }
        .sidebar .nav-link {
            color: #dbeafe;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-weight: 600;
        }
        .text-navy {
            color: #0b3982;
        }
        /* Pill Selector Bulan */
        .date-pill {
            background: #ffffff;
            border-radius: 50px;
            padding: 6px 16px;
            box-shadow: 0 2px 10px rgba(11, 57, 130, 0.06);
            font-size: 0.85rem;
            color: #0b3982;
            font-weight: 600;
        }
        /* Kartu Pemasukan & Pengeluaran */
        .card-pemasukan {
            background-color: #82e85c;
            border-radius: 18px;
            color: #0b3982;
            border: none;
        }
        .card-pengeluaran {
            background-color: #ff4d4d;
            border-radius: 18px;
            color: #0b3982;
            border: none;
        }
        .icon-circle-white {
            width: 52px;
            height: 52px;
            background-color: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        /* Kotak Tambah Transaksi */
        .btn-tambah-box {
            border: 2px solid #0f172a;
            background-color: #ffffff;
            border-radius: 18px;
            color: #0b3982;
            text-decoration: none;
            transition: 0.2s;
            height: 100%;
            min-height: 110px;
        }
        .btn-tambah-box:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(15, 23, 42, 0.08);
        }
        .plus-badge {
            width: 34px;
            height: 34px;
            background-color: #0f4c81;
            color: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        /* Kartu Info Putih */
        .card-white-soft {
            background-color: #ffffff;
            border-radius: 18px;
            border: none;
            box-shadow: 0 4px 16px rgba(11, 57, 130, 0.04);
        }
        /* Table styling */
        .table-custom thead th {
            background-color: #e2ecf9;
            color: #0b3982;
            font-weight: 600;
            border: none;
            padding: 12px 16px;
            font-size: 0.85rem;
        }
        .table-custom tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #0b3982;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .cat-icon-box {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .badge-akun {
            display: inline-block;
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            margin-top: 3px;
        }
        .badge-pengeluaran {
            background-color: #fee2e2;
            color: #ef4444;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
        }
        .badge-pemasukan {
            background-color: #dcfce7;
            color: #16a34a;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar Kiri -->
    <aside class="sidebar text-white p-4 d-flex flex-column">
        <div class="fs-4 fw-bold mb-5 px-2 font-heading">MoneyTracker</div>

        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active d-flex align-items-center gap-3">
                    <i class="bi bi-house-door-fill fs-5"></i>
                    <span>Beranda</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="tambah_transaksi.php" class="nav-link d-flex align-items-center gap-3">
                    <i class="bi bi-arrow-left-right fs-5"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="tambah_akun.php" class="nav-link d-flex align-items-center gap-3">
                    <i class="bi bi-wallet2 fs-5"></i>
                    <span>Akun</span>
                </a>
            </li>
        </ul>

        <hr class="border-light opacity-25 my-4">

        <ul class="nav flex-column gap-2">
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li class="nav-item">
                <a href="admin/admin_dashboard.php" class="nav-link d-flex align-items-center gap-3">
                    <i class="bi bi-shield-lock fs-5"></i>
                    <span>Panel Admin</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="user/Logout.php" class="nav-link d-flex align-items-center gap-3">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Konten Utama -->
    <main class="flex-grow-1 p-4 p-lg-5">
        <!-- Header Atas -->
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="fw-bold text-navy mb-0 fs-5 font-heading">Halo, <?= e($_SESSION['nama']) ?></p>
                <h1 class="h2 fw-bold text-navy mb-1">Selamat Datang di MoneyTracker</h1>
                <p class="text-navy opacity-75 small mb-0">Kelola keuanganmu dengan lebih mudah dan bijak.</p>
            </div>

            <div class="d-flex flex-column align-items-end gap-3">
                <!-- Ikon Profil Kanan Atas -->
                <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-person-fill text-primary fs-4"></i>
                </div>

                <!-- Selector Bulan -->
                <div class="date-pill d-inline-flex align-items-center gap-3">
                    <a href="?bulan=<?= $prev ?>" class="text-navy text-decoration-none" title="Bulan Sebelumnya">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <span><i class="bi bi-calendar3 me-2 text-primary"></i><?= e($label) ?></span>
                    <?php if ($bulan < $ini): ?>
                        <a href="?bulan=<?= $next ?>" class="text-navy text-decoration-none" title="Bulan Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="text-muted opacity-50"><i class="bi bi-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifikasi Flash -->
        <?php if ($flash): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><strong><?= e($flash) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Baris 1: Pemasukan, Pengeluaran, & Tombol Tambah Transaksi -->
        <div class="row g-3 mb-3 align-items-stretch">
            <!-- Total Pemasukan -->
            <div class="col-12 col-lg-5">
                <div class="card card-pemasukan h-100 p-3 d-flex flex-row align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-circle-white">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <!-- Lipatan belakang dompet -->
                                <path d="M5 9.5C5 7.84315 6.34315 6.5 8 6.5H20C21.1046 6.5 22 7.39543 22 8.5V10.5H5V9.5Z" fill="#EA580C" stroke="#0B3982" stroke-width="1.8"/>
                                <!-- Badan utama dompet -->
                                <rect x="4" y="9.5" width="20" height="13" rx="3" fill="#FB923C" stroke="#0B3982" stroke-width="1.8"/>
                                <!-- Pengait / kancing dompet kanan -->
                                <path d="M18 13.5H24V18.5H18C16.6193 18.5 15.5 17.3807 15.5 16C15.5 14.6193 16.6193 13.5 18 13.5Z" fill="#FDBA74" stroke="#0B3982" stroke-width="1.8"/>
                                <!-- Titik kancing -->
                                <circle cx="18.2" cy="16" r="1.2" fill="#0B3982"/>
                            </svg>
                        </div>
                        <div>
                            <div class="small fw-semibold">Total Pemasukan</div>
                            <div class="fs-4 fw-bold font-heading"><?= rp($masuk) ?></div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-up fs-2 fw-bold text-dark me-2"></i>
                </div>
            </div>

            <!-- Total Pengeluaran -->
            <div class="col-12 col-lg-5">
                <div class="card card-pengeluaran h-100 p-3 d-flex flex-row align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Ikon Dompet Kustom (Sesuai Figma) -->
                        <div class="icon-circle-white">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 9.5C5 7.84315 6.34315 6.5 8 6.5H20C21.1046 6.5 22 7.39543 22 8.5V10.5H5V9.5Z" fill="#EA580C" stroke="#0B3982" stroke-width="1.8"/>
                                <rect x="4" y="9.5" width="20" height="13" rx="3" fill="#FB923C" stroke="#0B3982" stroke-width="1.8"/>
                                <path d="M18 13.5H24V18.5H18C16.6193 18.5 15.5 17.3807 15.5 16C15.5 14.6193 16.6193 13.5 18 13.5Z" fill="#FDBA74" stroke="#0B3982" stroke-width="1.8"/>
                                <circle cx="18.2" cy="16" r="1.2" fill="#0B3982"/>
                            </svg>
                        </div>
                        <div>
                            <div class="large fw-bold">Total Pengeluaran</div>
                            <div class="fs-4 fw-bold font-heading"><?= rp($keluar) ?></div>
                        </div>
                    </div>
                    <i class="bi bi-arrow-down fs-2 fw-bold text-dark me-2"></i>
                </div>
            </div>

            <!-- Tombol Tambah Transaksi -->
            <div class="col-12 col-lg-2">
                <a href="tambah_transaksi.php" class="btn-tambah-box d-flex flex-column align-items-center justify-content-center p-3 text-center">
                    <span class="plus-badge mb-2">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <span class="fw-bold small lh-sm font-heading">Tambah<br>Transaksi</span>
                </a>
            </div>
        </div>

        <!-- Baris 2: Pengeluaran Terbesar & Selisih -->
        <div class="row g-3 mb-4">
            <!-- Pengeluaran Terbesar -->
            <div class="col-12 col-md-6">
                <div class="card card-white-soft h-100 p-4 d-flex flex-column justify-content-center">
                    <div class="fw-bold text-navy mb-2 font-heading">Pengeluaran terbesar bulan ini :</div>
                    <?php if ($besar): ?>
                        <div class="fs-5 fw-bold text-navy font-heading">
                            <?= e($besar['nama_kategori']) ?> : <?= rp($besar['total']) ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted small">Belum ada pengeluaran bulan ini.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Selisih -->
            <div class="col-12 col-md-6">
                <div class="card card-white-soft h-100 p-4 d-flex flex-row align-items-center gap-3">
                    <div class="icon-circle-white" style="width: 56px; height: 56px;">
                        <i class="bi bi-piggy-bank-fill fs-3 text-primary"></i>
                    </div>
                    <div>
                        <div class="small text-navy fw-semibold">Selisih (Pemasukan - Pengeluaran)</div>
                        <div class="fs-4 fw-bold font-heading <?= $selisih < 0 ? 'text-danger' : 'text-navy' ?>">
                            <?= ($selisih < 0 ? '-' : '') . rp(abs($selisih)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Baris 3: Tabel Transaksi Terbaru -->
        <div class="card card-white-soft p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock fs-4 text-primary"></i>
                    <h2 class="h5 fw-bold text-navy mb-0">Transaksi Terbaru</h2>
                </div>

                <?php if (!$semua): ?>
                    <a href="?bulan=<?= $bulan ?>&semua=1" class="text-primary text-decoration-none small fw-semibold">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                <?php else: ?>
                    <a href="?bulan=<?= $bulan ?>" class="text-primary text-decoration-none small fw-semibold">
                        Tampilkan 5 Terbaru
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$trx): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($trx as $t): 
                            $m = $t['jenis_transaksi'] === 'Pemasukan';
                            $kat = ikon_kategori($t['nama_kategori']);
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= $t['keterangan'] ? e($t['keterangan']) : e($t['nama_kategori']) ?></div>
                                    <span class="badge-akun"><i class="bi bi-wallet2 me-1"></i><?= e($t['nama_akun']) ?></span>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <span class="cat-icon-box" style="background-color: <?= $kat['bg'] ?>; color: <?= $kat['warna'] ?>;">
                                            <i class="bi <?= $kat['ikon'] ?>"></i>
                                        </span>
                                        <span><?= e($t['nama_kategori']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($m): ?>
                                        <span class="badge-pemasukan">Pemasukan</span>
                                    <?php else: ?>
                                        <span class="badge-pengeluaran">Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold font-heading <?= $m ? 'text-success' : 'text-danger' ?>">
                                    <?= $m ? '+' : '-' ?><?= rp($t['jumlah']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>