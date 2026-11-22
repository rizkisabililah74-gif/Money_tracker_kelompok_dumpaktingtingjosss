<?php
require __DIR__ . '/service/auth.php';
require __DIR__ . '/service/databases.php';
require_login();
$uid = (int)$_SESSION['user_id'];

$akun = rows($connect_api, "SELECT account_id, nama_akun, jenis_akun FROM accounts WHERE user_id = ? ORDER BY nama_akun", [$uid]);
$kat  = rows($connect_api, "SELECT category_id, nama_kategori, jenis_transaksi FROM categories WHERE user_id = ? ORDER BY nama_kategori", [$uid]);

$jenis_label = ['Kas' => 'Cash', 'Bank' => 'Bank', 'E-Wallet' => 'E-Wallet'];

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akun_id = (int)($_POST['akun_id'] ?? 0);
    $kat_id  = (int)($_POST['kategori_id'] ?? 0);
    $tgl     = $_POST['tanggal'] ?? '';
    $jumlah  = $_POST['jumlah'] ?? '';
    $ket     = trim($_POST['keterangan'] ?? '');
    $d       = DateTime::createFromFormat('!Y-m-d', $tgl);

    // Kepemilikan dicek di server. Jenis transaksi diambil dari kategori, bukan dari form.
    $a = rows($connect_api, "SELECT 1 FROM accounts WHERE account_id = ? AND user_id = ?", [$akun_id, $uid]);
    $c = rows($connect_api, "SELECT jenis_transaksi FROM categories WHERE category_id = ? AND user_id = ?", [$kat_id, $uid]);

    if (!csrf_ok())                                   $err = 'Token tidak valid, muat ulang halaman';
    elseif (!$a || !$c)                               $err = 'Akun atau kategori tidak valid';
    elseif (!$d || $d->format('Y-m-d') !== $tgl)      $err = 'Tanggal tidak valid';
    elseif (!is_numeric($jumlah) || $jumlah <= 0 || $jumlah > 9999999999999.99) $err = 'Jumlah harus lebih dari 0';
    elseif (strlen($ket) > 255)                       $err = 'Keterangan maksimal 255 karakter';
    else {
        $connect_api->prepare("INSERT INTO transactions (account_id, category_id, tanggal, jenis_transaksi, jumlah, keterangan) VALUES (?,?,?,?,?,?)")
            ->execute([$akun_id, $kat_id, $tgl, $c[0]['jenis_transaksi'], $jumlah, $ket ?: null]);
        $_SESSION['flash'] = 'Transaksi tersimpan';
        header('Location: dashboard.php?bulan=' . substr($tgl, 0, 7));
        exit;
    }
}

// Helper visual ikon & warna untuk kartu kategori di sebelah kanan
function visual_kategori($nama) {
    $k = strtolower(trim($nama));
    if (str_contains($k, 'makan') || str_contains($k, 'minum') || str_contains($k, 'kuliner')) {
        return ['ikon' => 'bi-cup-hot-fill', 'warna' => '#f97316'];
    }
    if (str_contains($k, 'transport') || str_contains($k, 'bensin') || str_contains($k, 'ojek')) {
        return ['ikon' => 'bi-car-front-fill', 'warna' => '#ef4444'];
    }
    if (str_contains($k, 'tagihan') || str_contains($k, 'listrik') || str_contains($k, 'rumah')) {
        return ['ikon' => 'bi-house-door-fill', 'warna' => '#22c55e'];
    }
    if (str_contains($k, 'hiburan') || str_contains($k, 'game') || str_contains($k, 'nonton')) {
        return ['ikon' => 'bi-controller', 'warna' => '#6366f1'];
    }
    if (str_contains($k, 'bonus')) {
        return ['ikon' => 'bi-currency-dollar', 'warna' => '#16a34a'];
    }
    if (str_contains($k, 'gaji') || str_contains($k, 'kerja')) {
        return ['ikon' => 'bi-cash-stack', 'warna' => '#0f3694'];
    }
    if (str_contains($k, 'belanja') || str_contains($k, 'shop')) {
        return ['ikon' => 'bi-cart-fill', 'warna' => '#9333ea'];
    }
    return ['ikon' => 'bi-grid-fill', 'warna' => '#2563eb'];
}

// include __DIR__ . '/Layout/header.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi - MoneyTracker</title>
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
        }
        h1, h2, h3, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .text-navy {
            color: #0b3982;
        }
        /* Tombol Back Bulat */
        .btn-back-circle {
            width: 44px;
            height: 44px;
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
        /* Card Utama Putih */
        .main-form-card {
            background-color: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(11, 57, 130, 0.05);
            padding: 36px 40px;
        }
        /* Pill Toggle Jenis Transaksi */
        .btn-jenis {
            border-radius: 50px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #0b3982;
            transition: all 0.2s ease;
            min-width: 165px;
        }
        .btn-jenis:hover {
            background-color: #f8fafc;
        }
        .btn-jenis.active-pemasukan {
            background-color: #16a34a;
            border-color: #16a34a;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        }
        .btn-jenis.active-pengeluaran {
            background-color: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
        }
        /* Input Kapsul & Kotak */
        .custom-input-box {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 8px 16px;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: border-color 0.2s;
        }
        .custom-input-box:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .custom-input-box select,
        .custom-input-box input,
        .custom-input-box textarea {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            color: #0b3982;
            font-size: 0.92rem;
            font-weight: 500;
        }
        .custom-input-box input::placeholder,
        .custom-input-box textarea::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .rp-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #fef08a;
            color: #854d0e;
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .btn-tambah-akun {
            border: 1px solid #0b3982;
            color: #0b3982;
            background: #ffffff;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition: 0.2s;
        }
        .btn-tambah-akun:hover {
            background-color: #eff6ff;
            color: #0b3982;
        }
        /* Panel Kartu Kategori Kanan */
        .category-panel {
            background: linear-gradient(145deg, #f0f6ff 0%, #e4effd 100%);
            border-radius: 20px;
            padding: 28px;
        }
        .cat-tile {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #0b3982;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cat-tile:hover {
            transform: translateY(-2px);
            border-color: #93c5fd;
            background-color: #ffffff;
        }
        .cat-tile.selected {
            background: linear-gradient(180deg, #1d5bbf 0%, #103b82 100%);
            border-color: #103b82;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(16, 59, 130, 0.25);
        }
        .cat-tile.selected i {
            color: #ffffff !important;
        }
        /* Tombol Simpan & Batal */
        .btn-simpan-pill {
            background-color: #0f3d7a;
            color: #ffffff;
            border-radius: 50px;
            padding: 12px 36px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            min-width: 175px;
            transition: 0.2s;
        }
        .btn-simpan-pill:hover {
            background-color: #0a2b57;
            color: #ffffff;
        }
        .btn-batal-pill {
            background-color: #ffffff;
            color: #0f3d7a;
            border: 1.5px solid #0f3d7a;
            border-radius: 50px;
            padding: 11px 36px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            min-width: 175px;
            text-align: center;
            transition: 0.2s;
        }
        .btn-batal-pill:hover {
            background-color: #f1f5f9;
            color: #0f3d7a;
        }
    </style>
</head>
<body>

<div class="container py-4 py-lg-5" style="max-width: 1060px;">
    <!-- Header Atas -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn-back-circle" title="Kembali ke Beranda">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <h1 class="h3 fw-bold text-navy mb-0">Tambah Transaksi</h1>
                <p class="text-navy opacity-75 small mb-0" id="subtitle-teks">Silakan catat pengeluaran Anda pada form di bawah ini</p>
            </div>
        </div>

        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
            <i class="bi bi-person-fill text-primary fs-4"></i>
        </div>
    </div>

    <!-- Alert Error & Peringatan Akun Kosong -->
    <?php if ($err): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i><strong><?= e($err) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!$akun): ?>
        <div class="alert alert-warning rounded-4 shadow-sm mb-4">
            <i class="bi bi-wallet2 me-2"></i>Belum ada akun. <a href="tambah_akun.php" class="fw-bold text-decoration-underline">Tambah akun dulu</a>
        </div>
    <?php endif; ?>

    <!-- Main Form Card -->
    <div class="main-form-card">
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <!-- Select asli tetap dipertahankan (hidden) agar fungsi saring() asli bekerja mulus -->
            <select id="jenis_trx" class="d-none">
                <option value="Pengeluaran" selected>Pengeluaran</option>
                <option value="Pemasukan">Pemasukan</option>
            </select>

            <div class="row g-4 g-lg-5">
                <!-- KOLOM KIRI: Form Input -->
                <div class="col-12 col-lg-6">
                    <!-- Jenis Transaksi -->
                    <div class="mb-4">
                        <label class="fw-bold small mb-2 d-block font-heading">Jenis Transaksi</label>
                        <div class="d-flex flex-wrap gap-3">
                            <button type="button" class="btn btn-jenis" id="btn-pemasukan" onclick="pilihJenis('Pemasukan')">
                                <i class="bi bi-arrow-up me-2"></i>Pemasukan
                            </button>
                            <button type="button" class="btn btn-jenis active-pengeluaran" id="btn-pengeluaran" onclick="pilihJenis('Pengeluaran')">
                                <i class="bi bi-arrow-down me-2"></i>Pengeluaran
                            </button>
                        </div>
                    </div>

                    <!-- Pilih Akun (Sumber Dana) -->
                    <div class="mb-3">
                        <label class="fw-bold small mb-2 d-block font-heading">Pilih Akun (Sumber Dana)</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="custom-input-box flex-grow-1">
                                <!-- Ikon Dompet Kustom -->
                                <svg width="24" height="24" viewBox="0 0 28 28" fill="none" class="flex-shrink-0">
                                    <path d="M5 9.5C5 7.84315 6.34315 6.5 8 6.5H20C21.1046 6.5 22 7.39543 22 8.5V10.5H5V9.5Z" fill="#EA580C" stroke="#0B3982" stroke-width="1.8"/>
                                    <rect x="4" y="9.5" width="20" height="13" rx="3" fill="#FB923C" stroke="#0B3982" stroke-width="1.8"/>
                                    <path d="M18 13.5H24V18.5H18C16.6193 18.5 15.5 17.3807 15.5 16C15.5 14.6193 16.6193 13.5 18 13.5Z" fill="#FDBA74" stroke="#0B3982" stroke-width="1.8"/>
                                    <circle cx="18.2" cy="16" r="1.2" fill="#0B3982"/>
                                </svg>
                                <select name="akun_id" required>
                                    <?php if (!$akun): ?>
                                        <option value="">Pilih Dompet</option>
                                    <?php endif; ?>
                                    <?php foreach ($akun as $a): ?>
                                        <option value="<?= (int)$a['account_id'] ?>" <?= (($_POST['akun_id'] ?? '') == $a['account_id']) ? 'selected' : '' ?>>
                                            <?= e($a['nama_akun']) ?> (<?= e($jenis_label[$a['jenis_akun']] ?? $a['jenis_akun']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <a href="tambah_akun.php" class="btn-tambah-akun">+ Tambah Akun</a>
                        </div>
                    </div>

                    <!-- Kategori (Dropdown) -->
                    <div class="mb-3">
                        <label class="fw-bold small mb-2 d-block font-heading">Kategori</label>
                        <div class="custom-input-box">
                            <i class="bi bi-grid-fill text-primary fs-5"></i>
                            <select name="kategori_id" id="kategori" required>
                                <?php foreach ($kat as $k): ?>
                                    <option value="<?= (int)$k['category_id'] ?>" data-jenis="<?= e($k['jenis_transaksi']) ?>">
                                        <?= e($k['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Jumlah -->
                    <div class="mb-3">
                        <label class="fw-bold small mb-2 d-block font-heading">Jumlah</label>
                        <div class="custom-input-box">
                            <span class="rp-badge">Rp</span>
                            <input type="number" name="jumlah" min="0.01" step="0.01" placeholder="Masukkan jumlah" required value="<?= e($_POST['jumlah'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div class="mb-3">
                        <label class="fw-bold small mb-2 d-block font-heading">Tanggal</label>
                        <div class="custom-input-box">
                            <i class="bi bi-calendar3 text-primary fs-5"></i>
                            <input type="date" name="tanggal" required value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <!-- Keterangan (Opsional) -->
                    <div class="mb-2">
                        <label class="fw-bold small mb-2 d-block font-heading">Keterangan (Opsional)</label>
                        <div class="custom-input-box align-items-start py-2">
                            <i class="bi bi-chat-dots text-secondary fs-5 mt-1"></i>
                            <textarea name="keterangan" rows="2" maxlength="255" placeholder="Tambahkan catatan..."><?= e($_POST['keterangan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Card Kategori Dinamis + Tombol Aksi -->
                <div class="col-12 col-lg-6 d-flex flex-column justify-content-between">
                    <!-- Panel Kartu Kategori -->
                    <div class="category-panel mt-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-grid-fill text-primary fs-4"></i>
                            <h2 class="h5 fw-bold text-navy mb-0" id="judul-panel-kategori">Kategori Pengeluaran</h2>
                        </div>

                        <div class="row g-3" id="grid-kategori">
                            <?php foreach ($kat as $k): 
                                $vis = visual_kategori($k['nama_kategori']);
                            ?>
                                <div class="col-4 cat-card-item" data-jenis="<?= e($k['jenis_transaksi']) ?>">
                                    <div class="cat-tile" data-id="<?= (int)$k['category_id'] ?>" onclick="klikKartuKategori(<?= (int)$k['category_id'] ?>)">
                                        <i class="bi <?= $vis['ikon'] ?> fs-3" style="color: <?= $vis['warna'] ?>;"></i>
                                        <span class="small fw-semibold lh-sm"><?= e($k['nama_kategori']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Tombol Simpan & Batal di Kanan Bawah -->
                    <div class="d-flex flex-wrap justify-content-end gap-3 mt-4 pt-3">
                        <button type="submit" class="btn btn-simpan-pill">
                            <i class="bi bi-floppy-fill me-2"></i>Simpan
                        </button>
                        <a href="dashboard.php" class="btn-batal-pill">Batal</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Kategori hanya menampilkan opsi yang cocok dengan jenis transaksi.
function saring(sel, jenis) {
    let pertama = null;
    for (const o of sel.options) {
        o.hidden = o.disabled = o.dataset.jenis !== jenis;
        if (!o.disabled && !pertama) pertama = o;
    }
    sel.value = pertama ? pertama.value : '';

    // Tampilkan/sembunyikan kartu kategori di panel kanan sesuai jenis transaksi
    document.querySelectorAll('.cat-card-item').forEach(card => {
        if (card.dataset.jenis === jenis) {
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    });

    // Update judul & subjudul sesuai jenis transaksi
    document.getElementById('judul-panel-kategori').textContent = 'Kategori ' + jenis;
    document.getElementById('subtitle-teks').textContent = 
        jenis === 'Pemasukan' 
        ? 'Silakan catat pemasukan Anda pada form di bawah ini' 
        : 'Silakan catat pengeluaran Anda pada form di bawah ini';

    // Update warna tombol pill Pemasukan / Pengeluaran
    const btnMasuk  = document.getElementById('btn-pemasukan');
    const btnKeluar = document.getElementById('btn-pengeluaran');
    if (jenis === 'Pemasukan') {
        btnMasuk.classList.add('active-pemasukan');
        btnKeluar.classList.remove('active-pengeluaran');
    } else {
        btnKeluar.classList.add('active-pengeluaran');
        btnMasuk.classList.remove('active-pemasukan');
    }

    sorotKartuAktif(sel.value);
}

// Highlight kartu kategori yang sedang terpilih
function sorotKartuAktif(katId) {
    document.querySelectorAll('.cat-tile').forEach(tile => {
        if (tile.dataset.id === String(katId)) {
            tile.classList.add('selected');
        } else {
            tile.classList.remove('selected');
        }
    });
}

// Saat tombol Pemasukan / Pengeluaran diklik
function pilihJenis(jenis) {
    const jt = document.getElementById('jenis_trx');
    jt.value = jenis;
    jt.onchange();
}

// Saat kotak kategori di kanan diklik, sinkronkan ke dropdown kiri
function klikKartuKategori(katId) {
    const sel = document.getElementById('kategori');
    sel.value = katId;
    sorotKartuAktif(katId);
}

const jt = document.getElementById('jenis_trx');
const selKat = document.getElementById('kategori');

jt.onchange = () => saring(selKat, jt.value);
selKat.onchange = () => sorotKartuAktif(selKat.value);

// Jalankan pertama kali saat halaman dimuat
jt.onchange();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>