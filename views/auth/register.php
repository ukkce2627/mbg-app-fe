<?php
require_once __DIR__ . '/../../includes/session_init.php';
require_once __DIR__ . '/../../includes/ApiClient.php';

if (fe_current_user()) {
    header('Location: ' . base_url('/views/' . fe_current_user()['role'] . '/dashboard.php'));
    exit;
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    $res = (new ApiClient())->postJson('/auth/register', [
        'nama' => $nama, 'username' => $username, 'password' => $pass,
    ]);

    if (!empty($res['success'])) {
        $success = true;
    } else {
        $error = $res['error'] ?? 'Registrasi gagal';
    }
}

$pageTitle = 'Daftar';
require __DIR__ . '/../../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>Buat Akun Masyarakat</h1>
    <p class="sub">Untuk mengirim aduan dan memantau tanggapannya</p>

    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert success">Registrasi berhasil! Silakan <a href="<?= base_url('/views/auth/login.php') ?>">masuk</a>.</div>
    <?php else: ?>

    <form method="post">
      <div class="field">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" required autofocus>
      </div>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_.]{3,50}"
               title="Huruf, angka, titik, underscore (3-50 karakter)" autocomplete="username">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter">
      </div>
      <button type="submit" class="btn block">Daftar</button>
    </form>
    <?php endif; ?>

    <div class="auth-switch">
      Sudah punya akun? <a href="<?= base_url('/views/auth/login.php') ?>">Masuk</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
