<?php
// Buat user + akun "Dompet" + kategori awal. Lempar exception jika email sudah ada.
// Dipakai Register.php dan Admin/users.php.
function buat_user(mysqli $db, string $nama, string $email, string $password, string $role = 'user'): int {
    $db->begin_transaction();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?,?,?,?)")
           ->execute([$nama, $email, $hash, $role]);
        $id = $db->insert_id;

        $db->prepare("INSERT INTO accounts (user_id, nama_akun, jenis_akun, saldo_awal) VALUES (?, 'Dompet', 'Kas', 0)")
           ->execute([$id]);

        $kat = $db->prepare("INSERT INTO categories (user_id, nama_kategori, jenis_transaksi) VALUES (?,?,?)");
        foreach ([['Gaji','Pemasukan'], ['Bonus','Pemasukan'], ['Makan','Pengeluaran'],
                  ['Transportasi','Pengeluaran'], ['Tagihan','Pengeluaran'], ['Hiburan','Pengeluaran']] as $k) {
            $kat->execute([$id, $k[0], $k[1]]);
        }
        $db->commit();
        return $id;
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}