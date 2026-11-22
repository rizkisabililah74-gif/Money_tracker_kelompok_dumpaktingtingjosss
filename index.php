<?php
$login_url    = 'User/Login.php';
$register_url = 'User/Register.php';

$h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// [nomor, judul, isi, warna latar kotak, warna angka]
$fitur = [
    ['1', 'Dashboard Harian', 'Lihat saldo, pemasukan, pengeluaran, dan saldo dalam satu layar.',        '#dbeafe', '#0d6efd'],
    ['2', 'Catat Semuanya',   'Tambah catatan pemasukan atau pengeluaran hanya dalam beberapa ketukan.',              '#d1fae5', '#198754'],
    ['3', 'Akun & Kategori',  'Kelompokkan pengeluaran dan pemasukan berdasarkan kategori dan sumber.',              '#fef3c7', '#b45309'],
    ['4', 'Laporan Bulanan',  'Pantau ke mana uangmu pergi lewat laporan dan grafik per kategori.',                   '#fee2e2', '#dc3545'],
];

$langkah = [
    ['1', 'Daftar Akun',          'Buat akun dengan email dan kata sandi.'],
    ['2', 'Catat Transaksi',      'Isi tanggal, jumlah, dan kategori.'],
    ['3', 'Atur Kategori & Dompet ', 'Kelompokkan berdasarkan kategori'],
    ['4', 'Pantau Laporan',       'Lihat pengeluaran dan pemasukan anda dalam satu dashboard.'],
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Money Tracker | Catatan Uang Pribadi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;1,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- styling start -->
    <style>
        :root { --poppins: 'Poppins', sans-serif; --inter: 'Inter', sans-serif;
                --logo: #4169a1; --judul: #1e3a5f; --ink: #1f2937; --muted: #6b7280; --garis: #dce7f3; }
        html { scroll-behavior: smooth; }
        body { font-family: var(--inter); color: var(--ink); overflow-x: hidden; }

        /* Lebar isi 1280px, ikut menyempit di layar kecil */
        .wrap { position: relative; z-index: 1; max-width: 1280px; margin: 0 auto; padding: 0 clamp(16px, 5.5vw, 80px); }
        .sec  { position: relative; overflow: hidden; padding: clamp(64px, 7vw, 96px) 0; }

        /* ===== Hero + navbar ===== */
        .hero { position: relative; overflow: hidden; color: #fff; background: linear-gradient(137deg, #0d2f55, #1938a9); }
        .hero .ring, .hero .blob { position: absolute; border-radius: 50%; pointer-events: none; }
        .hero .ring { border: 1px solid rgba(255, 255, 255, .22); }
        .hero .blob { background: rgba(255, 255, 255, .08); }
        .nav-pill { position: relative; z-index: 2; background: #fff; border-radius: 16px; padding: 13px 32px;
                    margin: 20px clamp(12px, 2vw, 29px) 0; box-shadow: 0 8px 24px rgba(0, 0, 0, .12); }
        .nav-pill .brand { font: 600 18px var(--poppins); color: var(--logo); margin: 0; }
        .nav-pill .nav-link { font: 500 15px var(--inter); color: #37383b; padding: 8px 0; }
        .nav-pill .nav-link:hover { color: var(--logo); }
        .btn-masuk { background: var(--logo); color: #fff; border-radius: 12px; padding: 10px 20px; font: 600 15px var(--inter); }
        .btn-masuk:hover { background: #365a8b; color: #fff; }

        .hero-body { padding: 72px 0 96px; }
        .badge-pill { display: inline-block; background: rgba(255, 255, 255, .12); border-radius: 50px; padding: 8px 16px; font: 500 14px var(--poppins); }
        .hero h1 { font: 700 clamp(2rem, 4.2vw, 3.25rem)/1.2 var(--poppins); margin: 24px 0; }
        .hero .teks { font: 500 18px/1.55 var(--poppins); color: rgba(255, 255, 255, .8); max-width: 492px; margin-bottom: 24px; }
        .btn-putih { background: #fff; color: var(--judul); border-radius: 12px; padding: 16px 32px; font: 600 16px var(--inter); }
        .btn-putih:hover { background: #eef4fb; color: var(--judul); }
        .btn-garis { border: 1.5px solid #fff; color: #fff; border-radius: 12px; padding: 16px 32px; font: 600 16px var(--inter); }
        .btn-garis:hover { background: rgba(255, 255, 255, .12); color: #fff; }
        .preview { display: block; width: 100%; max-width: 543px; height: auto; margin: 0 auto; border-radius: 24px; box-shadow: 0 24px 48px rgba(0, 0, 0, .3); }

        /* ===== Judul tiap bagian ===== */
        .sec-head { text-align: center; margin-bottom: 48px; }
        .sec-head .label { font: 700 16px var(--poppins); color: var(--judul); margin-bottom: 12px; }
        .sec-head h2 { font: 700 clamp(1.7rem, 3.2vw, 2.375rem)/1.2 var(--poppins); margin: 0; }

        /* Lingkaran dekoratif pada bagian terang */
        .deco::before, .deco::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
        #tentang { background: linear-gradient(180deg, #fff, #d8eafb); padding-top: clamp(80px, 10vw, 149px); }
        #tentang::before { width: 380px; height: 380px; left: -170px; bottom: -80px; background: rgba(120, 170, 230, .16); }
        #tentang::after  { width: 330px; height: 330px; right: -135px; top: -135px; border: 1px solid rgba(100, 150, 210, .3); }
        #fitur { background: linear-gradient(180deg, #fff, #e8f3ff); }
        #fitur::before { width: 320px; height: 320px; right: -140px; bottom: -100px; background: rgba(120, 170, 230, .16); }
        #fitur::after  { width: 340px; height: 340px; left: -220px; top: -170px; border: 1px solid rgba(100, 150, 210, .3); }
        #cara-mulai { background: linear-gradient(180deg, #e6f1ff, #bdd8f4); }
        #cara-mulai::before { width: 370px; height: 370px; right: -160px; top: -180px; background: rgba(120, 170, 230, .2); }
        #cara-mulai::after  { width: 450px; height: 450px; right: -220px; top: -220px; border: 1px solid rgba(100, 150, 210, .3); }

        #tentang h2 { font-weight: 800; font-size: clamp(1.9rem, 3.6vw, 2.5rem); }
        #tentang .isi { font: 400 clamp(1.05rem, 1.8vw, 1.5rem)/1.6 var(--inter); max-width: 1000px; margin: 20px auto 0; }
        #tentang .isi b { font-weight: 700; }

        /* ===== Kartu fitur dan langkah ===== */
        .kartu { height: 100%; background: #fff; border: 1px solid var(--garis); border-radius: 20px; padding: 28px; box-shadow: 0 8px 24px rgba(30, 58, 95, .08); }
        .kartu h3 { font: 600 24px/1.25 var(--poppins); margin: 16px 0 12px; }
        .kartu p { font: 400 18px/1.45 var(--inter); color: var(--muted); margin: 0; }
        .tile, .nomor { display: grid; place-items: center; }
        .tile  { width: 48px; height: 48px; border-radius: 12px; font: 700 20px var(--inter); }
        .nomor { width: 44px; height: 44px; border-radius: 50%; background: var(--logo); color: #fff; font: 700 18px var(--inter); }

        /* ===== Kutipan ===== */
        .quote { background: linear-gradient(180deg, #f8fbff, #d6e8fa); padding: clamp(40px, 6vw, 99px) 0 clamp(40px, 6vw, 99px); }
        .quote-box { background: linear-gradient(135deg, #123e69, #22689c); color: #fff; text-align: center; border: 1px solid #e5e7eb;
                     border-radius: 20px; padding: 25px 24px; box-shadow: 0 12px 32px rgba(18, 62, 105, .25); }
        .quote-box h3 { font: italic 700 clamp(1.15rem, 2.2vw, 1.5rem)/1.4 var(--poppins); margin: 0 0 8px; }
        .quote-box p  { font: 400 clamp(1rem, 1.8vw, 1.25rem)/1.4 var(--poppins); margin: 0; }

        /* ===== Footer ===== */
        .foot { position: relative; background: linear-gradient(180deg, #dbebfa, #c2daf1); border-top: 1px dotted rgba(30, 58, 95, .4); padding: 24px 28px 15px; }
        .totop { display: flex; flex-direction: column; align-items: center; gap: 4px; width: max-content; margin: 0 auto 48px;
                 font: 600 19px var(--inter); color: var(--ink); text-decoration: none; }
        .totop:hover { color: var(--logo); }
        .copy { font: 400 13px var(--inter); color: var(--muted); margin: 0; }
    </style>
</head>
            <!-- styling end -->

<body id="top">
<!-- ===== HERO ===== -->
<header class="hero">
    <span class="ring" style="width:890px;height:890px;right:-150px;top:0"></span>
    <span class="ring" style="width:770px;height:770px;right:-90px;top:60px"></span>
    <span class="blob" style="width:650px;height:650px;right:-30px;top:120px"></span>
    <span class="blob" style="width:300px;height:300px;left:-150px;bottom:-130px"></span>

    <nav class="navbar navbar-expand-lg nav-pill">
        <div class="container-fluid p-0">
            <a class="navbar-brand brand" href="#top">Money Tracker</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto me-lg-4 gap-lg-4">
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cara-mulai">Cara Mulai</a></li>
                </ul>
                <a class="btn btn-masuk" href="<?= $login_url ?>">Login</a>
            </div>
        </div>
    </nav>

    <div class="wrap hero-body">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <h1>Catatan uang pribadi yang ringkas dan mudah dipantau.</h1>
                <p class="teks">Catat pemasukan dan pengeluaran, kelompokkan lewat kategori, dan lihat saldo semua akunmu dalam satu dashboard tanpa ribet.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-putih" href="<?= $register_url ?>">Mulai Sekarang</a>
                    <a class="btn btn-garis" href="#fitur">Lihat Fitur &rarr;</a>
                </div>
            </div>
            <!-- img dashbboard -->
            <div class="col-lg-5">
                <img class="preview" src="assets/img/User (beranda) - Desktop.png" width="543" height="345" alt="Tampilan dashboard Money Tracker">
            </div>
        </div>
    </div>
</header>

<!-- ===== TENTANG ===== -->
<main>
    <section id="tentang" class="sec deco">
        <div class="wrap">
            <div class="sec-head mb-0">
                <div class="label">Tentang Money Tracker</div>
                <h2>Catat keuanganmu dengan Mudah</h2>
                <p class="isi">Money Tracker adalah aplikasi pencatat keuangan pribadi yang membantu anda untuk mencatat dan memantau
                    <b>pemasukan</b> dan <b>pengeluaran</b> dalam satu <b>dashboard</b>.</p>
            </div>
        </div>
    </section>

    <!-- ===== FITUR ===== -->
    <section id="fitur" class="sec deco">
        <div class="wrap">
            <div class="sec-head">
                <div class="label">FITUR</div>
                <h2>Semua yang dibutuhkan untuk mencatat keuanganmu.</h2>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php foreach ($fitur as [$no, $judul, $isi, $latar, $warna]): ?>
                <div class="col">
                    <div class="kartu">
                        <div class="tile" style="background:<?= $latar ?>;color:<?= $warna ?>"><?= $no ?></div>
                        <h3><?= $h($judul) ?></h3>
                        <p><?= $h($isi) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== CARA MULAI ===== -->
    <section id="cara-mulai" class="sec deco">
        <div class="wrap">
            <div class="sec-head">
                <div class="label">CARA MULAI</div>
                <h2>Mulai mencatat dalam 4 langkah.</h2>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php foreach ($langkah as [$no, $judul, $isi]): ?>
                <div class="col">
                    <div class="kartu">
                        <div class="nomor"><?= $no ?></div>
                        <h3><?= $h($judul) ?></h3>
                        <p><?= $h($isi) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== KUTIPAN ===== -->
    <section class="quote">
        <div class="wrap">
            <div class="quote-box">
                <h3>&ldquo;Uangmu Bukan Hilang, Cuma Nggak Tahu Pergi ke Mana&rdquo;</class=>
                <p>Daripada nuduh tuyul yang ngambil, mending catat semua pengeluaranmu.<br>Biar jelas siapa yang paling sering menguras dompet</p>
            </div>
        </div>
    </section>
</main>

<!-- ===== FOOTER ===== -->
<footer class="foot">
    <a class="totop" href="#top">
        <svg width="30" height="24" viewBox="0 0 30 24" fill="currentColor" aria-hidden="true"><path d="M15 1l12 11H3z"/><path d="M15 11l12 11H3z"/></svg>
        Back to Top
    </a>
    <p class="copy">&copy; <?= date('Y') ?> Pelacak Keuangan</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>