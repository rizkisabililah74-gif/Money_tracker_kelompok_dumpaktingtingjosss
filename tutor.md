** Git clone dulu reponya cuyy baru lanjut okeyy
# Ini adalah tutorial instalasi projek
## Preparation
Ini adalah software yang harus terinstall
1. XAMPP
2. VSCODE dengan extension PHP Intelephense

## Tutorial
1. Buka xampp yang sudah terinstall dan klik start pada apache, sql

2. Klik tombol 'admin' pada sql dan nanti akan diarahkan ke browser dan membuka localhost

3. Setup database (baca info.md pada section 1,2,dan 3) untuk membuat Database, paste semua perintah sql di CONSOLE yang ada di pojok bawah menu phpmyadmin (Pastikan nama database sesuai)

4. Jika sudah setup , lihat ke folder /service/databases.php , pastikan NAMA DATABASE yang ada di variabel $database sesuai dengan nama yang diberikan pada perintah sql (CREATE DATABASE pelacak_keuangan --- nama DB harus sesuai dan sama.
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
    USE pelacak_keuangan;)

5. Untuk run project ini ketik http://localhost/basis.mana/ atau http://localhost/Basis.mana/user/Login.php di browser

6. cara nambah administrator ada di file readme.md
7. menambahkan user bisa via login admin atau klik register 

