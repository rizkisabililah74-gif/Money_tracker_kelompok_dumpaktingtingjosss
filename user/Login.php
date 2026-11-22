<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';

$login_message = '';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $login_as = ($_POST['login_as'] ?? 'user') === 'admin' ? 'admin' : 'user';

// validasi sql

// pilih elemen dari tabel user
    $u = rows($connect_api, 
    "SELECT user_id, nama, password_hash, role FROM users WHERE email = ?", [$email])[0] ?? null;

    // jika password/email tidak cocok maka tampilkan 'Email atau password salah'
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $login_message = 'Email atau password salah';

    // jika login sebagai admin, maka cek apakah role akun == admin ? , jika tidak tampilkan " "
    } elseif ($login_as === 'admin' && $u['role'] !== 'admin') {
        $login_message = 'Akun ini bukan administrator';

    // jika login sebagai admin dan semua nilai benar, arahkan ke dashboard admin, jika login sebagai pengguna dan semua nilai benar maka arahkan ke dashboard pengguna
    } else {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => $u['user_id'], 'nama' => $u['nama'], 'role' => $u['role']];
        header('Location: ' . ($login_as === 'admin' ? '../admin/admin_dashboard.php' : '../dashboard.php'));
        exit;
    }
}
$login_as_dipilih = ($_POST['login_as'] ?? 'user') === 'admin' ? 'admin' : 'user';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Money Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- styling start -->
    <style>
        :root { --poppins: 'Poppins', sans-serif; --inter: 'Inter', sans-serif; --logo: #4169a1; --ink: #1f2937; --muted: #6b7280; }
        body { font-family: var(--inter); color: var(--ink); }
        .login-page { min-height: 100vh; }

        /* ===== Panel kiri (merek) ===== */
        .brand-panel { position: relative; overflow: hidden; flex: 0 0 37.6%; flex-direction: column; color: #fff;
                       padding: 38px 22px 22px; background: linear-gradient(150deg, #0d2f55 49%, #1938a9 100%); }
        .brand-panel .ring, .brand-panel .blob { position: absolute; border-radius: 50%; pointer-events: none; }
        .brand-panel .ring { border: 1.5px solid #9edfff; }
        .brand-panel .blob { background: rgba(129, 215, 255, .14); }
        .brand-panel > .isi { position: relative; z-index: 1; }
        .brand-logo { font: 600 20px var(--poppins); color: #fff; text-decoration: none; width: max-content; }
        .brand-logo:hover { color: #fff; opacity: .9; }
        .brand-msg { margin: auto 0; }
        .brand-msg h1 { font: 700 clamp(2.2rem, 3.3vw, 2.94rem)/1.15 var(--poppins); margin: 0 0 24px; }
        .brand-msg p  { font: 500 20px/1.3 var(--poppins); color: rgba(255, 255, 255, .8); margin: 0 0 24px; }
        .stat { background: rgba(255, 255, 255, .12); border-radius: 16px; padding: 20px; flex: 1; }
        .stat small { display: block; font: 500 14px var(--inter); margin-bottom: 6px; }
        .stat b { font: 700 22px var(--inter); }
        .brand-copy { font: 500 13px var(--poppins); color: rgba(255, 255, 255, .6); }

        /* ===== Panel kanan (form) ===== */
        .form-panel { position: relative; overflow: hidden; background: linear-gradient(120deg, #f4f9ff, #c2dcf5); }
        .form-panel .soft, .form-panel .outline { position: absolute; border-radius: 50%; pointer-events: none; }
        .form-panel .soft { background: rgba(113, 175, 231, .2); }
        .form-panel .outline { border: 1.5px solid rgba(93, 158, 219, .4); }

        .login-card { position: relative; z-index: 1; width: 100%; max-width: 553px; background: rgba(255, 255, 255, .97);
                      border: 1px solid #0f4c81; border-radius: 20px; box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
                      padding: clamp(28px, 5vw, 48px) clamp(20px, 5vw, 40px); }
        .login-card h2 { font: 700 32px/1.25 var(--poppins); color: var(--logo); margin: 0 0 8px; }
        .login-card .sub { font: 400 15px/1.47 var(--poppins); color: var(--muted); margin: 0 0 24px; }
        .login-card label.judul { font: 500 14px var(--inter); color: var(--ink); margin-bottom: 8px; display: block; }

        .form-check-input { border-color: #1f2937; border-width: 1.5px; }
        .form-check-input:checked { background-color: #1f2937; border-color: #1f2937; }
        .form-check-input:focus { box-shadow: 0 0 0 .2rem rgba(65, 105, 161, .2); border-color: #1f2937; }
        .form-check-label { font: 400 14px var(--inter); }

        .login-card .form-control { height: 48px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 16px; font: 400 15px var(--inter); }
        .login-card .form-control::placeholder { color: var(--muted); }
        .login-card .form-control:focus { border-color: var(--logo); box-shadow: 0 0 0 .2rem rgba(65, 105, 161, .2); }
        .pw { position: relative; }
        .pw .form-control { padding-right: 104px; }
        .pw .toggle { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); border: 0; background: none; padding: 0;
                      font: 500 14px var(--inter); color: #0d6efd; }
        .btn-login { width: 100%; height: 48px; background: var(--logo); color: #fff; border: 0; border-radius: 8px; font: 600 16px var(--poppins); }
        .btn-login:hover, .btn-login:focus { background: #365a8b; color: #fff; }
        .daftar { text-align: center; font: 400 13px var(--inter); color: var(--muted); margin: 24px 0 0; }
        .daftar a { font-weight: 600; color: #bf1717; text-decoration: none; }
        .daftar a:hover { text-decoration: underline; }
    </style>
</head>

<body>
<div class="login-page d-flex">

    <!-- Panel kiri: tampil mulai layar lebar -->
    <aside class="brand-panel d-none d-lg-flex">
        <span class="ring" style="width:570px;height:570px;right:-384px;top:-185px"></span>
        <span class="ring" style="width:660px;height:660px;right:-429px;top:-230px;opacity:.55"></span>
        <span class="ring" style="width:410px;height:410px;left:-140px;bottom:-182px;opacity:.85"></span>
        <span class="blob" style="width:480px;height:480px;right:-339px;top:-140px"></span>
        <span class="blob" style="width:320px;height:320px;left:-96px;bottom:-137px"></span>

        <a class="isi brand-logo" href="../index.php">Money Tracker</a>
        <div class="isi brand-msg">
            <h1>Selamat datang kembali!</h1>
            <p>Yuk, cek kondisi dompet hari ini.<br>Masukkan email dan kata sandi untuk melanjutkan.</p>
            <div class="d-flex gap-3">
                <div class="stat"><small style="color:#a7f3d0">Pemasukan</small><b>+Rp 4.000.000</b></div>
                <div class="stat"><small style="color:#fecaca">Pengeluaran</small><b>-Rp 405.000</b></div>
            </div>
        </div>
        <div class="isi brand-copy">&copy; <?= date('Y') ?> Pelacak Keuangan</div>
    </aside>

    <!-- Panel kanan: form -->
    <main class="form-panel flex-grow-1 d-flex flex-column align-items-center justify-content-center p-3">
        <span class="soft"    style="width:380px;height:380px;right:-190px;top:-135px"></span>
        <span class="outline" style="width:470px;height:470px;right:-235px;top:-180px"></span>
        <span class="soft"    style="width:350px;height:350px;left:-160px;bottom:-105px"></span>
        <span class="outline" style="width:430px;height:430px;left:-200px;bottom:-145px"></span>

        <a class="d-lg-none brand-logo mb-3" style="color:var(--logo);position:relative;z-index:1" href="../index.php">Money Tracker</a>

        <div class="login-card">
            <h2>Login</h2>
            <p class="sub">Selamat datang kembali. Masuk untuk melihat kondisi keuanganmu.</p>

            <!-- jika daftar berhasil maka tampilkan " "  (direct dari register.php)-->
           <?php if (isset($_GET['daftar'])): ?><div class="alert alert-success py-2 small" role="alert">Pendaftaran berhasil, silakan masuk</div><?php endif; ?>
            <?php if ($login_message): ?><div class="alert alert-danger py-2 small" role="alert"><?= e($login_message) ?></div><?php endif; ?> 

                <!-- Form -->
            <form method="post" novalidate>
                <div class="mb-4">
                    <span class="judul">Masuk sebagai :</span>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="login_as" id="as-user" value="user" <?= $login_as_dipilih === 'user' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="as-user">Pengguna</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="login_as" id="as-admin" value="admin" <?= $login_as_dipilih === 'admin' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="as-admin">Admin</label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="judul" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="nama@email.com" autocomplete="username" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="judul" for="password">Kata sandi</label>
                    <div class="pw">
                        <input class="form-control" type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button class="toggle" type="button" id="toggle-pw" aria-controls="password">Tampilkan</button>
                    </div>
                </div>

                <button class="btn btn-login" type="submit" name="login">Login</button>
            </form>
            <p class="daftar">Belum punya akun? <a href="Register.php">Register</a></p>
        </div>
    </main>
</div>

<script>
    // Tombol "Tampilkan": ganti kolom sandi antara tersembunyi dan terlihat.
    const pw = document.getElementById('password'), tg = document.getElementById('toggle-pw');
    tg.addEventListener('click', () => {
        const lihat = pw.type === 'password';
        pw.type = lihat ? 'text' : 'password';
        tg.textContent = lihat ? 'Sembunyikan' : 'Tampilkan';
    });
</script>
</body>
</html>