# 1. Create Database
CREATE DATABASE pelacak_keuangan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE pelacak_keuangan;
# 2. Create tabel

## tabel user
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    nama           VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,      -- email tidak boleh sama
    password_hash  VARCHAR(255) NOT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users
  ADD COLUMN role VARCHAR(10) NOT NULL DEFAULT 'user' AFTER password_hash,
  ADD CONSTRAINT chk_role CHECK (role IN ('user', 'admin'));

## tabel accounts
CREATE TABLE accounts (
    account_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,                      -- FK ke users
    nama_akun      VARCHAR(100) NOT NULL,
    jenis_akun     VARCHAR(20)  NOT NULL,
    saldo_awal     DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);

## tabel categories
CREATE TABLE categories (
    category_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,                     -- FK ke users
    nama_kategori   VARCHAR(100) NOT NULL,
    jenis_transaksi VARCHAR(15)  NOT NULL
);

## tabel transactions
CREATE TABLE transactions (
    transaction_id  INT AUTO_INCREMENT PRIMARY KEY,
    account_id      INT NOT NULL,                     -- FK ke accounts
    category_id     INT NOT NULL,                     -- FK ke categories
    tanggal         DATE NOT NULL,
    jenis_transaksi VARCHAR(15)  NOT NULL,
    jumlah          DECIMAL(15,2) NOT NULL,
    keterangan      VARCHAR(255)
);

## Cek hasil
SHOW TABLES;
# 3. Foreign key dan pembentukan relasi
ALTER TABLE accounts
    ADD CONSTRAINT fk_accounts_users
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE RESTRICT;

ALTER TABLE categories
    ADD CONSTRAINT fk_categories_users
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE RESTRICT;

ALTER TABLE transactions
    ADD CONSTRAINT fk_transactions_accounts
    FOREIGN KEY (account_id) REFERENCES accounts(account_id)
    ON DELETE RESTRICT;

ALTER TABLE transactions
    ADD CONSTRAINT fk_transactions_categories
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
    ON DELETE RESTRICT;

## ATURAN VALIDASI DAN INDEX (aturan bisnis, Bab 2.3)
1. jenis akun hanya Kas / Bank / E-Wallet
2. jenis transaksi hanya Pemasukan / Pengeluaran
3. jumlah transaksi harus lebih besar dari nol
4. kategori tidak boleh kembar pada satu user
5. index untuk mempercepat pencarian per akun/kategori dan tanggal

ALTER TABLE accounts
    ADD CONSTRAINT chk_jenis_akun
    CHECK (jenis_akun IN ('Kas', 'Bank', 'E-Wallet'));

ALTER TABLE categories
    ADD CONSTRAINT chk_jenis_kategori
    CHECK (jenis_transaksi IN ('Pemasukan', 'Pengeluaran')),
    ADD CONSTRAINT uq_kategori_user
    UNIQUE (user_id, nama_kategori, jenis_transaksi);

ALTER TABLE transactions
    ADD CONSTRAINT chk_jenis_transaksi
    CHECK (jenis_transaksi IN ('Pemasukan', 'Pengeluaran')),
    ADD CONSTRAINT chk_jumlah_positif
    CHECK (jumlah > 0),
    ADD INDEX idx_akun_tanggal (account_id, tanggal),
    ADD INDEX idx_kategori_tanggal (category_id, tanggal);

# Hapus isi Database
DELETE FROM transactions;
DELETE FROM categories;
DELETE FROM accounts;
DELETE FROM users;
ALTER TABLE transactions AUTO_INCREMENT = 1;
ALTER TABLE categories   AUTO_INCREMENT = 1;
ALTER TABLE accounts     AUTO_INCREMENT = 1;
ALTER TABLE users        AUTO_INCREMENT = 1;

# Admin dan user

## Add admin
1. Run di terminal, ini akan meng-generate password hash untuk admin
C:\xampp\php\php.exe -r "echo password_hash(' isi pw disini ', PASSWORD_DEFAULT);"

2. Jalankan di sql hilangkan simbol <>
INSERT INTO users (nama, email, password_hash, role) VALUES ('< nama admin >', '< isi email disini >', '< Tempel hash disini >', 'admin');

## Jadikan admin
UPDATE users SET role = 'admin' WHERE email = '??????@??.com'; (Paste di sql, isi email dengan target)


## admin dan user terdaftar

### admin
1. email : admin2@gmail.com pw :sandiadmin2


### user
#### polosan
1. email : lebahganteng1234@gmail.com pw :lebahganteng1234

#### isian
1. email : pigai98@gmail.com pw : babikecap