<?php
require_once __DIR__ . '/../../includes/session_init.php';
require_once __DIR__ . '/../../includes/ApiClient.php';

if (fe_current_user()) {
    header('Location: ' . base_url('/views/' . fe_current_user()['role'] . '/dashboard.php'));
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    $res = (new ApiClient())->postJson('/auth/login', ['username' => $username, 'password' => $pass]);

    if (!empty($res['success'])) {
        $_SESSION['user'] = $res['data'];
        header('Location: ' . base_url('/views/' . $res['data']['role'] . '/dashboard.php'));
        exit;
    }
    $error = $res['error'] ?? 'Login gagal';
}

// Link "Jalankan Setup Awal" hanya ditampilkan kalau database/tabel belum
// pernah di-seed (users masih kosong). Begitu sudah pernah setup, link ini
// otomatis hilang dari halaman login — tidak perlu dihapus manual dari kode.
// Kalau BE tidak bisa dihubungi saat pengecekan ini, anggap SUDAH ter-setup
// (fail-safe: link disembunyikan) supaya tidak menyesatkan operator di
// production; halaman /views/auth/setup.php tetap bisa diakses langsung
// lewat URL kalau operator memang tahu perlu menjalankannya lagi.
$showSetupLink = false;
$statusRes = (new ApiClient())->get('/setup/status');
if (!empty($statusRes['success']) && empty($statusRes['data']['seeded'])) {
    $showSetupLink = true;
}

$pageTitle = 'Masuk';
require __DIR__ . '/../../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>Masuk ke MBG App</h1>
    <p class="sub">Pelaporan, Monitoring &amp; Aduan</p>

    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus placeholder="username" autocomplete="username">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn block">Masuk</button>
    </form>

    <div class="auth-switch">
      Belum punya akun? <a href="<?= base_url('/views/auth/register.php') ?>">Daftar sebagai Masyarakat</a>
    </div>
    <?php if ($showSetupLink): ?>
    <div class="auth-switch">
      Deploy pertama kali? <a href="<?= base_url('/views/auth/setup.php') ?>">Jalankan Setup Awal</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
