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

<h1>Buat akun</h1>
<p>Silakan isi dengan data yang sesuai</p>
<?php if ($err): ?><p style="color:red"><?= e($err) ?></p><?php endif; ?>
<form method="post">
    <p><label>Email<br><input type="email" name="email" maxlength="100" required value="<?= e($_POST['email'] ?? '') ?>"></label></p>
    <p><label>Nama<br><input type="text" name="nama" maxlength="100" required value="<?= e($_POST['nama'] ?? '') ?>"></label></p>
    <p><label>Kata sandi (minimal 8 karakter)<br><input type="password" name="password" minlength="8" required></label></p>
    <button type="submit" name="daftar">Daftar</button>
</form>
<p>Sudah punya akun? <a href="Login.php">Masuk</a></p>


