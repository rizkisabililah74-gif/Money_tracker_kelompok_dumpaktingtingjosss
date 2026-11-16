<?php
// Helper umum: sesi, penjaga halaman, CSRF, format, query singkat.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // mysqli lempar exception
if (session_status() === PHP_SESSION_NONE) session_start(); // jika tidak ada sesi maka mulai sesi

function e($s)  { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

// Fungsi untuk Validasi user/admin Harus login, direct ke halaman login
function require_login() {
    if (empty($_SESSION['user_id'])) { header('Location: /Basis.mana/user/Login.php'); exit; }
}
// Fungi untuk Validasi kedua , harus login dulu, lalu harus admin. Kalau bukan admin, tampil “Akses ditolak”
function require_admin() {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Akses ditolak'); }
}

// Membuat kode rahasia acak untuk form.
function csrf_token() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }

// Mencocokkan. Kode di sesi vs kode yang dikirim form.
function csrf_ok()    { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }

// SELECT dengan prepared statement, kembalikan semua baris.
function rows(mysqli $db, string $sql, array $params = []): array {
    $s = $db->prepare($sql);
    $s->execute($params);
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
