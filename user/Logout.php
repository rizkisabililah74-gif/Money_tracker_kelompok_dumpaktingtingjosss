<?php
require __DIR__ . '/../service/auth.php';   // memulai sesi

// Tangkap sesi yang sedang aktif lalu hancurkan , setelah itu arahkan ke Form Login.php
$_SESSION = [];
session_destroy();
header("Location: Login.php");
exit;