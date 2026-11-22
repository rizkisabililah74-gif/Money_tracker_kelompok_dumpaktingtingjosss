<?php
require __DIR__ . '/../service/auth.php';
require __DIR__ . '/../service/databases.php';
// Pastikan fungsi e() dan csrf_token() sudah di-include di sini

require_admin();
$me = (int)$_SESSION['user_id'];

// Tangkap notifikasi setelah redirect dari tambah_user.php
$pesan = $_SESSION['flash_pesan'] ?? '';
unset($_SESSION['flash_pesan']);

// Fitur Pencarian (Search bar sesuai Figma)
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $like = "%$search%";
    $users = rows(
        $connect_api,
        "SELECT user_id, nama, email, role, created_at FROM users WHERE nama LIKE ? OR email LIKE ? ORDER BY user_id",
        [$like, $like]
    );
} else {
    $users = rows($connect_api, "SELECT user_id, nama, email, role, created_at FROM users ORDER BY user_id");
}

function tombol($aksi, $id, $teks, $btnClass = 'btn-outline-secondary', $konfirmasi = '') {
    return '<form method="post" class="d-inline"' . ($konfirmasi ? ' onsubmit="return confirm(\'' . $konfirmasi . '\')"' : '') . '>'
        . '<input type="hidden" name="csrf" value="' . csrf_token() . '">'
        . '<input type="hidden" name="aksi" value="' . $aksi . '">'
        . '<input type="hidden" name="user_id" value="' . (int)$id . '">'
        . '<button class="btn btn-sm ' . $btnClass . ' py-1 px-2" style="font-size: 0.75rem;">' . $teks . '</button></form> ';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Token tidak valid'); }
    $aksi   = $_POST['aksi'] ?? '';
    $target = (int)($_POST['user_id'] ?? 0);

    try {
        $t = rows($connect_api, "SELECT nama, role FROM users WHERE user_id = ?", [$target])[0] ?? null;

        if (!$t) {
            $pesan = 'User tidak ditemukan';
        } elseif ($target === $me && in_array($aksi, ['role', 'hapus'], true)) {
            $pesan = 'Tidak boleh pada akun sendiri';
        } elseif ($aksi === 'role') {
            $connect_api->prepare("UPDATE users SET role = ? WHERE user_id = ?")
                ->execute([$t['role'] === 'admin' ? 'user' : 'admin', $target]);
            $pesan = 'Role berhasil diubah';
        } elseif ($aksi === 'reset') {
            $baru = bin2hex(random_bytes(4));
            $connect_api->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $target]);
            $pesan = 'Password baru ' . $t['nama'] . ': ' . $baru . ' (tampil sekali, catat sekarang)';
        } elseif ($aksi === 'hapus') {
            $connect_api->begin_transaction();
            foreach ([
                "DELETE t FROM transactions t JOIN accounts a ON a.account_id = t.account_id WHERE a.user_id = ?",
                "DELETE FROM accounts WHERE user_id = ?",
                "DELETE FROM categories WHERE user_id = ?",
                "DELETE FROM users WHERE user_id = ?"
            ] as $q) {
                $connect_api->prepare($q)->execute([$target]);
            }
            $connect_api->commit();
            $pesan = 'User ' . $t['nama'] . ' dan semua datanya dihapus';
        }
    } catch (mysqli_sql_exception $ex) {
        $connect_api->rollback();
        $pesan = 'Terjadi kesalahan, coba lagi';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator - Money Tracker</title>
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7fb; font-family: 'Segoe UI', system-ui, sans-serif; }
        .sidebar { width: 250px; min-height: 100vh; background-color: #154c79; }
        .sidebar .nav-link { color: #cbd5e1; border-radius: 8px; padding: 10px 16px; font-size: 0.9rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #2563eb; color: #fff; font-weight: 600; }
        .text-primary-dark { color: #0f4c81; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <aside class="sidebar text-white p-3 d-flex flex-column">
        <div class="fs-5 fw-bold px-2 pt-2 mb-4">Money Tracker</div>
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active">
                    <i class="bi bi-people-fill me-2"></i> Kelola User
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../User/Logout.php" class="nav-link text-light">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4 p-md-5">
        <div class="mb-4">
            <h1 class="h3 fw-bold text-primary-dark mb-1">Selamat Datang di Dashboard Administrator</h1>
            <p class="text-primary fw-semibold small text-uppercase mb-0">HALLO, <?= e($_SESSION['nama']) ?>!</p>
        </div>

        <?php if ($pesan): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <strong><?= e($pesan) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Card Tabel Pengguna -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h2 class="h5 fw-bold text-primary-dark mb-0">
                        <i class="bi bi-people-fill me-2"></i>Daftar Pengguna
                    </h2>

                    <!-- Search Bar -->
                    <form method="get" class="flex-grow-1" style="max-width: 340px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Cari nama atau email pengguna..." value="<?= e($search) ?>">
                        </div>
                    </form>

                    <!-- Tombol Tambah User -->
                    <a href="tambahpengguna.php" class="btn btn-primary btn-sm px-3 py-2 fw-semibold rounded-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambah User
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead class="table-light text-primary-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Daftar</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($users as $u): $diri = (int)$u['user_id'] === $me; ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <i class="bi bi-person-circle text-primary me-2"></i>
                                    <span class="fw-semibold"><?= e($u['nama']) ?></span>
                                    <?= $diri ? '<span class="text-muted small">(kamu)</span>' : '' ?>
                                </td>
                                <td class="text-muted"><?= e($u['email']) ?></td>
                                <td class="text-muted"><?= date('d/m/Y H:i:s', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$diri): ?>
                                        <?= tombol('role', $u['user_id'], $u['role'] === 'admin' ? 'Jadikan user' : 'Jadikan admin', 'btn-outline-primary') ?>
                                    <?php endif; ?>
                                    
                                    <?= tombol('reset', $u['user_id'], 'Reset Password', 'btn-outline-secondary', 'Reset password user ini?') ?>
                                    
                                    <?php if (!$diri): ?>
                                        <?= tombol('hapus', $u['user_id'], '<i class="bi bi-trash"></i>', 'btn-outline-danger', 'Hapus user dan SEMUA datanya? Tidak bisa dibatalkan.') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Data pengguna tidak ditemukan.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>