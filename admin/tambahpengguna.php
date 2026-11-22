<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';
require __DIR__ . '/../service/add_user.php';
// Pastikan fungsi e() dan csrf_token() sudah di-include di sini

require_admin();

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Token tidak valid'); }

    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $role  = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';

    try {
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = 'Nama atau email tidak valid';
        } elseif (strlen($pw) < 8) {
            $pesan = 'Password minimal 8 karakter';
        } else {
            buat_user($connect_api, $nama, $email, $pw, $role);
            $_SESSION['flash_pesan'] = "User baru ($nama) berhasil ditambahkan!";
            header('Location: admin_dashboard.php');
            exit;
        }
    } catch (mysqli_sql_exception $ex) {
        $pesan = $ex->getCode() === 1062 ? 'Email sudah dipakai' : 'Gagal menyimpan data, coba lagi';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User/Admin - Money Tracker</title>
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #eaf2fb;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .main-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(15, 76, 129, 0.06);
            width: 100%;
            max-width: 720px;
            padding: 36px 40px;
        }
        .btn-back {
            width: 44px;
            height: 44px;
            background-color: #dbeafe;
            color: #154c79;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.25rem;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .btn-back:hover {
            background-color: #bfdbfe;
            color: #0f3a5d;
        }
        .title-primary {
            color: #154c79;
            font-weight: 700;
        }
        .subtitle-primary {
            color: #3b82f6;
            font-size: 0.85rem;
        }
        .form-box {
            background-color: #f4f4f5;
            border: 1px solid #a1a1aa;
            border-radius: 16px;
            padding: 28px 32px;
        }
        .form-label-custom {
            color: #154c79;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .select-pill {
            border-radius: 50px;
            border: 1px solid #cbd5e1;
            padding: 8px 36px 8px 18px;
            color: #64748b;
            font-size: 0.9rem;
            width: auto;
            min-width: 120px;
            background-color: #ffffff;
        }
        .pill-input-group {
            background: #ffffff;
            border-radius: 50px;
            padding: 6px 18px;
            display: flex;
            align-items: center;
            max-width: 380px;
            border: 1px solid #e2e8f0;
            transition: border-color 0.2s;
        }
        .pill-input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .pill-input-group input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 0.9rem;
            color: #334155;
            padding-left: 12px;
        }
        .pill-input-group input::placeholder {
            color: #94a3b8;
        }
        .btn-submit-pill {
            background-color: #1d5bbf;
            color: #ffffff;
            border-radius: 50px;
            padding: 9px 24px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            transition: 0.2s;
        }
        .btn-submit-pill:hover {
            background-color: #154799;
            color: #ffffff;
        }
        .btn-cancel-pill {
            background-color: #ffffff;
            color: #1d5bbf;
            border: 1px solid #1d5bbf;
            border-radius: 50px;
            padding: 8px 32px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }
        .btn-cancel-pill:hover {
            background-color: #eff6ff;
            color: #154799;
        }
    </style>
</head>
<body>

<div class="main-card">
    <!-- Header dengan Tombol Kembali -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="admin_dashboard.php" class="btn-back" title="Kembali ke Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h4 title-primary mb-0">Tambah User/Admin</h1>
            <p class="subtitle-primary mb-0">Silakan isi Formulir dibawah ini</p>
        </div>
    </div>

    <!-- Kotak Abu-abu Formulir -->
    <div class="form-box">
        <?php if ($pesan): ?>
            <div class="alert alert-danger rounded-4 py-2 px-3 small mb-4">
                <i class="bi bi-exclamation-circle-fill me-1"></i> <strong><?= e($pesan) ?></strong>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <!-- Role -->
            <div class="mb-3">
                <label class="form-label-custom d-block">Role :</label>
                <select name="role" class="form-select select-pill shadow-none">
                    <option value="user" <?= ($_POST['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <!-- Nama -->
            <div class="mb-3">
                <label class="form-label-custom d-block">Nama :</label>
                <div class="pill-input-group">
                    <i class="bi bi-person-circle fs-4" style="color: #1d5bbf;"></i>
                    <input type="text" name="nama" placeholder="Masukkan Nama" value="<?= e($_POST['nama'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label-custom d-block">Email :</label>
                <div class="pill-input-group">
                    <i class="bi bi-envelope-fill fs-5 text-dark"></i>
                    <input type="email" name="email" placeholder="Somebody Pleasure's@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="form-label-custom d-block">Password</label>
                <div class="pill-input-group">
                    <i class="bi bi-lock fs-5 text-dark"></i>
                    <input type="password" name="password" placeholder="Min. 8 karakter" required>
                </div>
            </div>

            <!-- Tombol Aksi Kanan Bawah -->
            <div class="d-flex justify-content-end align-items-center gap-3 mt-4 pt-2">
                <button type="submit" class="btn btn-submit-pill">
                    <i class="bi bi-plus-lg me-1"></i> Tambah User
                </button>
                <a href="admin_dashboard.php" class="btn-cancel-pill">Batal</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>