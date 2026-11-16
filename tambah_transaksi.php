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
include __DIR__ . '/Layout/header.php';
?>
<h1>Tambah Transaksi</h1>
<?php if ($err): ?><p style="color:red"><?= e($err) ?></p><?php endif; ?>
<?php if (!$akun): ?><p>Belum ada akun. <a href="tambah_akun.php">Tambah akun dulu</a></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <p><label>Akun<br>
        <select name="akun_id" required>
            <?php foreach ($akun as $a): ?><option value="<?= (int)$a['account_id'] ?>"><?= e($a['nama_akun']) ?> (<?= e($jenis_label[$a['jenis_akun']] ?? $a['jenis_akun']) ?>)</option><?php endforeach; ?>
        </select></label> <a href="tambah_akun.php">+ Tambah akun</a></p>

    <p><label>Jenis transaksi<br>
        <select id="jenis_trx"><option>Pemasukan</option><option>Pengeluaran</option></select></label></p>
    <p><label>Kategori<br>
        <select name="kategori_id" id="kategori" required>
            <?php foreach ($kat as $k): ?><option value="<?= (int)$k['category_id'] ?>" data-jenis="<?= e($k['jenis_transaksi']) ?>"><?= e($k['nama_kategori']) ?></option><?php endforeach; ?>
        </select></label></p>

    <p><label>Jumlah<br><input type="number" name="jumlah" min="0.01" step="0.01" required value="<?= e($_POST['jumlah'] ?? '') ?>"></label></p>
    <p><label>Tanggal<br><input type="date" name="tanggal" required value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>"></label></p>
    <p><label>Keterangan (opsional)<br><input type="text" name="keterangan" maxlength="255" value="<?= e($_POST['keterangan'] ?? '') ?>"></label></p>

    <button type="submit">Simpan</button> <a href="dashboard.php">Batal</a>
</form>
<script>
// Kategori hanya menampilkan opsi yang cocok dengan jenis transaksi.
function saring(sel, jenis) {
    let pertama = null;
    for (const o of sel.options) {
        o.hidden = o.disabled = o.dataset.jenis !== jenis;
        if (!o.disabled && !pertama) pertama = o;
    }
    sel.value = pertama ? pertama.value : '';
}
const jt = document.getElementById('jenis_trx');
jt.onchange = () => saring(document.getElementById('kategori'), jt.value);
jt.onchange();
</script>
