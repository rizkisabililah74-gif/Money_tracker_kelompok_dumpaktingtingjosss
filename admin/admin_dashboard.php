<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';
require __DIR__ . '/../service/add_user.php';
include __DIR__ . '/../layout/header.php';

// Validasi , hanya atmint yang boleh otak atik
require_admin();
$me = (int)$_SESSION['user_id'];

// Ambil Semua Pengguna dari database
$users = rows($connect_api, "SELECT user_id, nama, email, role, created_at FROM users ORDER BY user_id");

function tombol($aksi, $id, $teks, $konfirmasi = '') {
    return '<form method="post" style="display:inline"' . ($konfirmasi ? ' onsubmit="return confirm(\'' . $konfirmasi . '\')"' : '') . '>'
        . '<input type="hidden" name="csrf" value="' . csrf_token() . '">'
        . '<input type="hidden" name="aksi" value="' . $aksi . '"><input type="hidden" name="user_id" value="' . (int)$id . '">'
        . '<button>' . $teks . '</button></form> ';
}

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
    if (!csrf_ok()) { http_response_code(400); exit('Token tidak valid'); }
    $aksi   = $_POST['aksi'] ?? '';
    $target = (int)($_POST['user_id'] ?? 0);
    try 
    { 
        // Input User baru sebagai
        if ($aksi === 'baru') {
            $nama = trim($_POST['nama'] ?? ''); 
            $email = trim($_POST['email'] ?? '');
            $pw = $_POST['password'] ?? ''; 
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';

            // Error handling
            if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $pesan = 'Nama atau email tidak valid';
            elseif (strlen($pw) < 8)                                        $pesan = 'Password minimal 8 karakter';
            
            // User Created
            else { buat_user($connect_api, $nama, $email, $pw, $role); $pesan = 'User baru dibuat'; }
        } else {
            $t = rows($connect_api, "SELECT nama, role FROM users WHERE user_id = ?", [$target])[0] ?? null;

            // Admin tidak bisa menghapus atau downgrade dirinya sendiri.
            if (!$t)$pesan = 'User tidak ditemukan';
            elseif ($target === $me && in_array($aksi, ['role', 'hapus'], true)) $pesan = 'Tidak boleh pada akun sendiri';

            // Ubah Role
            elseif ($aksi === 'role')
                {
                $connect_api->prepare("UPDATE users SET role = ? WHERE user_id = ?")
                ->execute([$t['role'] === 'admin' ? 'user' : 'admin', $target]);
                $pesan = 'Role diubah';
                }
            // Reset Password lalu generate Dari angka random
             elseif ($aksi === 'reset') {
                $baru = bin2hex(random_bytes(4));
                $connect_api->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                    ->execute([password_hash($baru, PASSWORD_DEFAULT), $target]);
                $pesan = 'Password baru ' . $t['nama'] . ': ' . $baru . ' (tampil sekali, catat sekarang)';
            }
            // Hapus User Dari database
            elseif ($aksi === 'hapus') {
                $connect_api->begin_transaction();
                foreach (["DELETE t FROM transactions t JOIN accounts a ON a.account_id = t.account_id WHERE a.user_id = ?",
                          "DELETE FROM accounts WHERE user_id = ?",
                          "DELETE FROM categories WHERE user_id = ?",
                          "DELETE FROM users WHERE user_id = ?"] 
                          as $q) 
                          {$connect_api->prepare($q)->execute([$target]);}
                $connect_api->commit();
                $pesan = 'User ' . $t['nama'] . ' dan semua datanya dihapus';
            }
        }
    } 
    // error handling email sudah terdaftar
    catch (mysqli_sql_exception $ex) {
        $connect_api->rollback();
        $pesan = $ex->getCode() === 1062 ? 'Email sudah dipakai' : 'Gagal, coba lagi'; 
    }
}
?>

<h1>Selamat Datang di Dashboard Administrator</h1>
<p>Halo, <?= e($_SESSION['nama']) ?>!</p>

<h1>Kelola Pengguna</h1>
<?php if ($pesan): ?><p><b><?= e($pesan) ?></b></p><?php endif; ?>

<h3>Input user baru</h3>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="aksi" value="baru">
    <input name="nama" placeholder="Nama" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password (min 8)" required>
    <select name="role"><option value="user">user</option><option value="admin">admin</option></select>
    <button>Tambah</button>
</form>

<h3>Daftar pengguna</h3>
<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Nama</th>
        <th>Email</th>
        <th>Role</th>
        <th>Daftar</th>
        <th>Aksi</th>
    </tr>
    
    <?php
    // Ubah Var $user jadi $u , Validasi Supaya admin tidak menghapus dirinya sendiri.
    foreach ($users as $u): $diri = (int)$u['user_id'] === $me; ?>

    <tr>
        <td><?= (int)$u['user_id'] ?></td>
        <td><?= e($u['nama']) ?><?= $diri ? '(kamu)' : '' ?></td>
        <td><?= e($u['email']) ?></td> 
        <td><?= e($u['role']) ?></td>
        <td><?= e($u['created_at']) ?></td>

        <!-- Fungsi Tombol -->
        <td>
            <?php if (!$diri) echo tombol('role', $u['user_id'], $u['role'] === 'admin' ? 'Jadikan user' : 'Jadikan admin'); ?>
            <?= tombol('reset', $u['user_id'], 'Reset password', 'Reset password user ini?') ?>
            <?php if (!$diri) echo tombol('hapus', $u['user_id'], 'Hapus', 'Hapus user dan SEMUA datanya? Tidak bisa dibatalkan.'); ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="../User/Logout.php">Keluar</a></p>