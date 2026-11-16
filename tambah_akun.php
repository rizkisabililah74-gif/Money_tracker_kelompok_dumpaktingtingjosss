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
include __DIR__ . '/Layout/header.php';
?>
<h1>Tambah Akun</h1>
<?php if ($err): ?><p style="color:red"><?= e($err) ?></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <p><label>Jenis dompet<br>
        <select name="jenis_akun" required>
            <?php foreach ($jenis_label as $nilai => $teks): ?>
                <option value="<?= e($nilai) ?>" <?= ($_POST['jenis_akun'] ?? '') === $nilai ? 'selected' : '' ?>><?= e($teks) ?></option>
            <?php endforeach; ?>
        </select></label></p>
    <p><label>Nama akun<br><input type="text" name="nama_akun" maxlength="100" required placeholder="contoh: BCA, GoPay" value="<?= e($_POST['nama_akun'] ?? '') ?>"></label></p>
    <p><label>Saldo awal<br><input type="number" name="saldo_awal" min="0" step="0.01" value="<?= e($_POST['saldo_awal'] ?? '0') ?>"></label></p>

    <button type="submit">Simpan</button> <a href="dashboard.php">Batal</a>
</form>
