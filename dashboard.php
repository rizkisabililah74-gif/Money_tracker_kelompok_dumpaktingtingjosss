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

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
include __DIR__ . '/Layout/header.php';
?>

<style>.in{color:green}.out{color:red}.kartu{display:inline-block;border:1px solid #ccc;padding:8px 16px;margin:4px}</style>

<h1>Halo, <?= e($_SESSION['nama']) ?>!</h1>
<?php if ($flash): ?><p class="in"><?= e($flash) ?></p><?php endif; ?>
<p>
    <a href="?bulan=<?= $prev ?>">&lt;</a>
    <b><?= e($label) ?></b>
    <?php if ($bulan < $ini): ?><a href="?bulan=<?= $next ?>">&gt;</a><?php else: ?>&gt;<?php endif; ?>
    &nbsp; <a href="tambah_transaksi.php"><b>+ Tambah Transaksi</b></a>
</p>

<div class="kartu out">Pengeluaran<br><b>-<?= rp($keluar) ?></b></div>
<div class="kartu in">Pemasukan<br><b>+<?= rp($masuk) ?></b></div>
<div class="kartu <?= $selisih < 0 ? 'out' : 'in' ?>">Selisih<br><b><?= ($selisih < 0 ? '-' : '+') . rp(abs($selisih)) ?></b></div>

<h3>Ringkasan pengeluaran terbesar</h3>
<?php if ($besar): ?>
    <p><?= e($besar['nama_kategori']) ?>: <b><?= rp($besar['total']) ?></b>
<?php else: ?>
    <p>Belum ada pengeluaran bulan ini.</p>
<?php endif; ?>

<h3>Transaksi terakhir</h3>
<?php if (!$trx): ?><p>Belum ada transaksi.</p><?php endif; ?>
<?php foreach ($trx as $t): $m = $t['jenis_transaksi'] === 'Pemasukan'; ?>
    <p><?= e($t['tanggal']) ?> | <?= e($t['nama_kategori']) ?> | <?= e($t['nama_akun']) ?> |
        <span class="<?= $m ? 'in' : 'out' ?>"><?= $m ? '+' : '-' ?><?= rp($t['jumlah']) ?></span>
        <?= $t['keterangan'] ? '| '.e($t['keterangan']) : '' ?></p>
<?php endforeach; ?>
<?php if (!$semua): ?><p><a href="?bulan=<?= $bulan ?>&semua=1">Lihat semua</a></p><?php endif; ?>

<p><?php if ($_SESSION['role'] === 'admin'): ?><a href="admin/admin_dashboard.php">Admin</a> | <?php endif; ?><a href="user/Logout.php">Keluar</a></p>
