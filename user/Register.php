<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';
require __DIR__ . '/../service/add_user.php';
include __DIR__ . '/../Layout/header.php';

// Deklarasi error handling
$err = '';

// jika tombol daftar mengirim data (POST) kirim nama,email,dan pw
if (isset($_POST['daftar'])) {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

// Error Handling
        // Jika nama bernilai kosong ATAU(OR) Melebihi 100 karakter
    if ($nama === '' || strlen($nama) > 100) {
        $err = 'Nama wajib diisi (maksimal 100 karakter)';

        // Jika Email tidak terdafar tidak ditemukan di DB ATAU(OR) Email melebihi 100 karakter
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $err = 'Email tidak valid';
        
        // Jika Password kurang dari 8 karakter
    } elseif (strlen($pw) < 8) {
        $err = 'Kata sandi minimal 8 karakter';

        // Jika diluar kondisi maka tambah pengguna, eksekusi fungsi buat_user (folder service/add_user.php)
    } else {
        try {
            buat_user($connect_api, $nama, $email, $pw);   // role selalu 'user', admint hanya bisa ditambah manual 
            header('Location: Login.php?daftar=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            $err = $ex->getCode() === 1062 ? 'Email sudah terdaftar' : 'Pendaftaran gagal, coba lagi';
        }
    }
}
?>

<a href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ===== Reset ===== */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; overflow: hidden; height: 100%; }

/* ===== Wrapper — full viewport, no scroll (Figma 141:125) ===== */
.reg-page {
    position: fixed; inset: 0; z-index: 9999;
    display: flex; overflow: hidden;
    font-family: 'Inter', sans-serif;
}

/* ══════════════════════════════════════════
   Brand Panel — kiri  (37.5% ~ 541 / 1440)
══════════════════════════════════════════ */
.reg-brand {
    width: 37.5%;
    flex-shrink: 0;
    background: linear-gradient(109.53deg, rgb(13,47,85) 23.703%, rgb(25,56,169) 82.362%);
    position: relative; overflow: hidden;
    display: flex; flex-direction: column;
    padding: clamp(24px,3.8vh,38px) clamp(16px,1.7vw,22px);
}

/* Lingkaran dekoratif */
.reg-brand::before {
    content: ''; position: absolute;
    width: 480px; height: 480px; right: -180px; top: -140px;
    border-radius: 50%; border: 1px solid rgba(255,255,255,0.15);
    pointer-events: none;
}
.reg-brand::after {
    content: ''; position: absolute;
    width: 320px; height: 320px; left: -96px; bottom: -80px;
    border-radius: 50%; background: rgba(255,255,255,0.06);
    pointer-events: none;
}

/* Logo */
.brand-logo {
    font-family: 'Poppins', sans-serif;
    font-weight: 600; font-size: clamp(15px,1.4vw,20px);
    color: #fff; text-decoration: none;
    flex-shrink: 0; position: relative; z-index: 1;
}

/* Konten tengah — dorong ke tengah secara vertikal */
.brand-content {
    flex: 1;
    display: flex; flex-direction: column; justify-content: center;
    position: relative; z-index: 1;
    padding: clamp(16px,2.5vh,32px) 0;
    min-height: 0;
}
.brand-title {
    font-family: 'Poppins', sans-serif; font-weight: 800;
    font-size: clamp(22px, 3.3vw, 48px); line-height: 1.12;
    color: #fff; margin: 0 0 clamp(10px,1.5vh,20px);
}
.brand-tagline {
    font-family: 'Poppins', sans-serif; font-weight: 500;
    font-size: clamp(13px,1.3vw,18px); line-height: 1.6;
    color: rgba(255,255,255,0.8);
    margin: 0 0 clamp(14px,2.5vh,32px);
}
.stat-cards { display: flex; gap: 12px; }
.stat-card {
    background: rgba(255,255,255,0.12); border-radius: 16px;
    padding: clamp(12px,1.8vh,20px); flex: 1;
}
.stat-label {
    font-family: 'Inter', sans-serif;
    font-size: clamp(11px,0.95vw,14px); font-weight: 500;
    margin: 0 0 6px;
}
.stat-value {
    font-family: 'Inter', sans-serif;
    font-size: clamp(15px,1.5vw,22px); font-weight: 700;
    color: #fff; margin: 0;
}
.stat-income  .stat-label { color: #a7f3d0; }
.stat-expense .stat-label { color: #fecaca; }

/* Copyright */
.brand-copy {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(11px,0.9vw,13px);
    color: rgba(255,255,255,0.6);
    flex-shrink: 0; position: relative; z-index: 1;
}

/* ══════════════════════════════════════════
   Form Panel — kanan (flex:1)
══════════════════════════════════════════ */
.reg-form-panel {
    flex: 1; min-width: 0; overflow: hidden;   /* NO scroll */
    background: linear-gradient(117.31deg, rgb(244,249,255) 8.889%, rgb(194,220,245) 82.963%);
    display: flex; align-items: center; justify-content: center;
    position: relative;
    padding: 0 clamp(16px,3vw,40px);
}

/* Lingkaran dekoratif kanan */
.reg-form-panel::before {
    content: ''; position: absolute;
    width: 500px; height: 500px; right: -150px; top: -100px;
    border-radius: 50%; border: 1px solid rgba(100,150,210,0.25);
    pointer-events: none;
}
.reg-form-panel::after {
    content: ''; position: absolute;
    width: 350px; height: 350px; left: -100px; bottom: -80px;
    border-radius: 50%; background: rgba(100,150,220,0.1);
    pointer-events: none;
}

/* ── Form Card ── */
.reg-card {
    background: rgba(255,255,255,0.97);
    border: 1px solid #0f4c81; border-radius: 20px;
    padding: clamp(28px,5.5vh,56px) clamp(24px,3.5vw,50px);
    width: min(557px, 100%);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    position: relative; z-index: 1;
}
.reg-card-title {
    font-family: 'Poppins', sans-serif; font-weight: 700;
    font-size: clamp(22px,2.5vw,32px); color: #4169a1;
    margin: 0 0 4px; line-height: 1.25;
}
.reg-card-subtitle {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(13px,1vw,15px);
    color: #6b7280; margin: 0 0 clamp(14px,2vh,24px);
}
.reg-error {
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 8px; padding: 10px 14px;
    color: #dc3545; font-size: 14px; margin-bottom: 14px;
}
.reg-field { margin-bottom: clamp(8px,1.4vh,16px); }
.reg-label {
    display: block; font-family: 'Inter', sans-serif;
    font-weight: 500; font-size: 14px;
    color: #1f2937; margin-bottom: 8px;
}
.reg-label span { color: #dc3545; }
.reg-input {
    display: block; width: 100%; height: 48px;
    border: 1px solid #d1d5db; border-radius: 8px;
    padding: 0 16px; font-size: 15px;
    font-family: 'Inter', sans-serif; color: #1f2937;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.reg-input::placeholder { color: #9ca3af; }
.reg-input:focus {
    outline: none; border-color: #4169a1;
    box-shadow: 0 0 0 3px rgba(65,105,161,0.15);
}
.reg-btn {
    display: block; width: 100%; height: 48px;
    background: #4169a1; color: #fff;
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 16px;
    cursor: pointer; margin-top: clamp(8px,1.5vh,16px);
    transition: background .2s, transform .1s;
}
.reg-btn:hover  { background: #365a8b; }
.reg-btn:active { transform: scale(0.99); }
.reg-login-link {
    text-align: center; margin: clamp(10px,1.6vh,20px) 0 0;
    font-size: 14px; color: #6b7280; font-family: 'Inter', sans-serif;
}
.reg-login-link a {
    color: #bf1717; font-weight: 600; text-decoration: none;
}
.reg-login-link a:hover { text-decoration: underline; }

/* ══════════════════════════════════════════
   Responsive breakpoints
══════════════════════════════════════════ */

/* Tablet — sempit brand panel */
@media (max-width: 991px) {
    .reg-brand { width: 44%; }
    .stat-cards { flex-direction: column; }
}

/* Tablet kecil — sembunyikan brand panel */
@media (max-width: 767px) {
    .reg-brand { display: none; }
    /* Gradient biru gelap di belakang form jika brand panel hilang */
    .reg-form-panel {
        background: linear-gradient(135deg, rgb(13,47,85) 0%, rgb(25,56,169) 100%);
    }
    .reg-card { border-radius: 16px; }
}

/* Mobile */
@media (max-width: 480px) {
    .reg-form-panel { padding: 0 12px; }
    .reg-card {
        padding: 28px 20px;
        border-radius: 14px;
        width: 100%;
    }
    .reg-card-title { font-size: 24px; }
    .reg-input, .reg-btn { height: 44px; }
}

    .reg-page {
        position: fixed; inset: 0; z-index: 9999;
        display: flex; font-family: 'Inter', sans-serif;
    }

    /* ── Brand Panel (Left) ── */
    .reg-brand {
        width: 541px; flex-shrink: 0;
        background: linear-gradient(109.53deg, rgb(13,47,85) 23.703%, rgb(25,56,169) 82.362%);
        position: relative; overflow: hidden;
        display: flex; flex-direction: column;
        padding: 38px 22px 24px;
        box-sizing: border-box;
    }
    .reg-brand::before {
        content: ''; position: absolute;
        width: 480px; height: 480px; right: -180px; top: -140px;
        border-radius: 50%; border: 1px solid rgba(255,255,255,0.15);
        pointer-events: none;
    }
    .reg-brand::after {
        content: ''; position: absolute;
        width: 320px; height: 320px; left: -96px; bottom: -80px;
        border-radius: 50%; background: rgba(255,255,255,0.06);
        pointer-events: none;
    }
    .brand-logo {
        font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 20px;
        color: #fff; text-decoration: none; display: block;
    }
    .brand-content { margin-top: auto; padding-bottom: 32px; position: relative; z-index: 1; }
    .brand-title {
        font-family: 'Poppins', sans-serif; font-weight: 800;
        font-size: clamp(32px, 3.5vw, 48px); line-height: 1.12;
        color: #fff; margin: 0 0 20px;
    }
    .brand-tagline {
        font-family: 'Poppins', sans-serif; font-weight: 500;
        font-size: 18px; line-height: 1.6;
        color: rgba(255,255,255,0.8); margin: 0 0 32px;
    }
    .stat-cards { display: flex; gap: 16px; }
    .stat-card {
        background: rgba(255,255,255,0.12); border-radius: 16px;
        padding: 20px; flex: 1;
    }
    .stat-label { font-size: 14px; font-weight: 500; margin: 0 0 6px; font-family: 'Inter', sans-serif; }
    .stat-value { font-size: 22px; font-weight: 700; color: #fff; margin: 0; font-family: 'Inter', sans-serif; }
    .stat-income  .stat-label { color: #a7f3d0; }
    .stat-expense .stat-label { color: #fecaca; }
    .brand-copy {
        font-family: 'Poppins', sans-serif; font-size: 13px;
        color: rgba(255,255,255,0.6); margin-top: 24px; display: block;
        position: relative; z-index: 1;
    }

    /* ── Form Panel (Right) ── */
    .reg-form-panel {
        flex: 1; min-width: 0; overflow-y: auto;
        background: linear-gradient(117.31deg, rgb(244,249,255) 8.889%, rgb(194,220,245) 82.963%);
        display: flex; align-items: center; justify-content: center;
        position: relative;
    }
    .reg-form-panel::before {
        content: ''; position: absolute;
        width: 500px; height: 500px; right: -150px; top: -100px;
        border-radius: 50%; border: 1px solid rgba(100,150,210,0.25);
        pointer-events: none;
    }
    .reg-form-panel::after {
        content: ''; position: absolute;
        width: 350px; height: 350px; left: -100px; bottom: -80px;
        border-radius: 50%; background: rgba(100,150,220,0.1);
        pointer-events: none;
    }

    /* ── Form Card ── */
    .reg-card {
        background: rgba(255,255,255,0.97);
        border: 1px solid #0f4c81; border-radius: 20px;
        padding: 56px 50px; width: 557px;
        max-width: calc(100vw - 48px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        position: relative; z-index: 1;
        box-sizing: border-box; margin: 24px 0;
    }
    .reg-card-title {
        font-family: 'Poppins', sans-serif; font-weight: 700;
        font-size: 32px; color: #4169a1; margin: 0 0 4px; line-height: 1.25;
    }
    .reg-card-subtitle {
        font-family: 'Poppins', sans-serif; font-size: 15px;
        color: #6b7280; margin: 0 0 24px;
    }
    .reg-error {
        background: #fef2f2; border: 1px solid #fecaca;
        border-radius: 8px; padding: 10px 14px;
        color: #dc3545; font-size: 14px; margin-bottom: 16px;
    }
    .reg-field { margin-bottom: 16px; }
    .reg-label {
        display: block; font-family: 'Inter', sans-serif;
        font-weight: 500; font-size: 14px;
        color: #1f2937; margin-bottom: 8px;
    }
    .reg-label span { color: #dc3545; }
    .reg-input {
        width: 100%; height: 48px;
        border: 1px solid #d1d5db; border-radius: 8px;
        padding: 0 16px; font-size: 15px;
        font-family: 'Inter', sans-serif; color: #1f2937;
        background: #fff; transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }
    .reg-input::placeholder { color: #6b7280; }
    .reg-input:focus {
        outline: none; border-color: #4169a1;
        box-shadow: 0 0 0 3px rgba(65,105,161,0.15);
    }
    .reg-btn {
        width: 100%; height: 48px;
        background: #4169a1; color: #fff;
        border: none; border-radius: 8px;
        font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 16px;
        cursor: pointer; margin-top: 16px;
        transition: background .2s, transform .1s;
    }
    .reg-btn:hover  { background: #365a8b; }
    .reg-btn:active { transform: scale(0.99); }
    .reg-login-link {
        text-align: center; margin-top: 20px; margin-bottom: 0;
        font-size: 14px; color: #6b7280; font-family: 'Inter', sans-serif;
    }
    .reg-login-link a { color: #bf1717; font-weight: 600; text-decoration: none; }
    .reg-login-link a:hover { text-decoration: underline; }
</style>

<div class="reg-page">

    <!-- ══ Brand Panel ══ -->
    <div class="reg-brand">
        <a class="brand-logo" href="../index.php">Money Tracker</a>

        <div class="brand-content">
            <h1 class="brand-title">Mulai Kelola keuangan Anda Sekarang</h1>
            <p class="brand-tagline">
                Hanya butuh waktu kurang dari 1 menit.<br>
                Nikmati berbagai kemudahan dalam mengatur keuangan
            </p>
            <div class="stat-cards">
                <div class="stat-card stat-income">
                    <p class="stat-label">Pemasukan</p>
                    <p class="stat-value">+Rp 4.000.000</p>
                </div>
                <div class="stat-card stat-expense">
                    <p class="stat-label">Pengeluaran</p>
                    <p class="stat-value">-Rp 405.000</p>
                </div>
            </div>
        </div>

        <span class="brand-copy">© 2026 Pelacak Keuangan</span>
    </div>

    <!-- ══ Form Panel ══ -->
    <div class="reg-form-panel">
        <div class="reg-card">

            <h2 class="reg-card-title">Daftar</h2>
            <p class="reg-card-subtitle">Silakan isi dengan data yang sesuai</p>

            <?php if ($err): ?>
            <div class="reg-error"><?= e($err) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="reg-field">
                    <label class="reg-label" for="reg-email">Email <span>*</span></label>
                    <input id="reg-email" class="reg-input"
                           type="email" name="email" maxlength="100" required
                           placeholder="nama@email.com"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="reg-field">
                    <label class="reg-label" for="reg-nama">Nama <span>*</span></label>
                    <input id="reg-nama" class="reg-input"
                           type="text" name="nama" maxlength="100" required
                           placeholder="Nama lengkap"
                           value="<?= e($_POST['nama'] ?? '') ?>">
                </div>
                <div class="reg-field">
                    <label class="reg-label" for="reg-pw">Kata sandi <span>*</span></label>
                    <input id="reg-pw" class="reg-input"
                           type="password" name="password" minlength="8" required
                           placeholder="Buat kata sandi (Minimal 8 Karakter)">
                </div>
                <button class="reg-btn" type="submit" name="daftar">Register</button>
            </form>

            <p class="reg-login-link">Sudah punya akun? <a href="Login.php">Masuk</a></p>

        </div>
    </div>

</div>


<div class="reg-page">

    <!-- ── Brand Panel ── -->
    <div class="reg-brand">
        <a class="brand-logo" href="../index.php">Money Tracker</a>
        <div class="brand-content">
            <h1 class="brand-title">Mulai Kelola keuangan Anda Sekarang</h1>
            <p class="brand-tagline">Hanya butuh waktu kurang dari 1 menit.<br>Nikmati berbagai kemudahan dalam mengatur keuangan</p>
            <div class="stat-cards">
                <div class="stat-card stat-income">
                    <p class="stat-label">Pemasukan</p>
                    <p class="stat-value">+Rp 4.000.000</p>
                </div>
                <div class="stat-card stat-expense">
                    <p class="stat-label">Pengeluaran</p>
                    <p class="stat-value">-Rp 405.000</p>
                </div>
            </div>
        </div>
        <span class="brand-copy">© 2026 Pelacak Keuangan</span>
    </div>

    <!-- ── Form Panel ── -->
    <div class="reg-form-panel">
        <div class="reg-card">
            <h2 class="reg-card-title">Daftar</h2>
            <p class="reg-card-subtitle">Silakan isi dengan data yang sesuai</p>

            <?php if ($err): ?>
            <div class="reg-error"><?= e($err) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="reg-field">
                    <label class="reg-label" for="reg-email">Email <span>*</span></label>
                    <input id="reg-email" class="reg-input" type="email" name="email"
                           maxlength="100" required placeholder="nama@email.com"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="reg-field">
                    <label class="reg-label" for="reg-nama">Nama <span>*</span></label>
                    <input id="reg-nama" class="reg-input" type="text" name="nama"
                           maxlength="100" required placeholder="Nama lengkap"
                           value="<?= e($_POST['nama'] ?? '') ?>">
                </div>
                <div class="reg-field">
                    <label class="reg-label" for="reg-pw">Kata sandi <span>*</span></label>
                    <input id="reg-pw" class="reg-input" type="password" name="password"
                           minlength="8" required placeholder="Buat kata sandi (Minimal 8 Karakter)">
                </div>
                <button class="reg-btn" type="submit" name="daftar">Register</button>
            </form>

            <p class="reg-login-link">Sudah punya akun? <a href="Login.php">Masuk</a></p>
        </div>
    </div>

</div>


