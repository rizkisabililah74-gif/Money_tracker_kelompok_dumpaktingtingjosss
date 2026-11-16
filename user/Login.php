<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';
include __DIR__ . '/../layout/header.php';

$login_message = '';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $login_as = ($_POST['login_as'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $u = rows($connect_api, "SELECT user_id, nama, password_hash, role FROM users WHERE email = ?", [$email])[0] ?? null;

    // jika BUKAN(NOT) user ATAU(OR) Password verify bernilai false maka tampilkan 'Email atau password salah'
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $login_message = 'Email atau password salah';

    // jika user mencoba Login sebagai admin maka tampilkan 'Akun ini bukan administrator'
    } elseif ($login_as === 'admin' && $u['role'] !== 'admin') {
        $login_message = 'Akun ini bukan administrator';

    // Jika memilih login sebagai admin dan NILAINYA BENAR arahkan ke dashboard admin 
    } else {
        session_regenerate_id(true);
        $_SESSION = ['user_id' => $u['user_id'], 'nama' => $u['nama'], 'role' => $u['role']];  // role dari database
        header('Location: ' . ($login_as === 'admin' ? '../admin/admin_dashboard.php' : '../dashboard.php'));
        exit;
    }
}

?>
<h1>Masuk</h1>
<?php if (isset($_GET['daftar'])): ?><p style="color:green">Pendaftaran berhasil, silakan masuk</p><?php endif; ?>
    
<?php if ($login_message): ?><p style="color:red"><?= e($login_message) ?></p><?php endif; ?>

<form method="post">
    <p>Masuk sebagai:
        <label><input type="radio" name="login_as" value="user" checked> Pengguna</label>
        <label><input type="radio" name="login_as" value="admin"> Administrator</label>
    </p>
    <p><label>Email<br><input type="email" name="email" required></label></p>
    <p><label>Password<br><input type="password" name="password" required></label></p>
    <button type="submit" name="login">Login</button>
</form>
