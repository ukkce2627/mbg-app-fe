<?php
/**
 * Halaman "Setup Awal" — tombol sekali klik untuk memicu seeding akun
 * default di Back End (endpoint POST /auth/setup/seed), tanpa perlu akses
 * CLI/SSH ke server BE yang private.
 *
 * TIDAK butuh login (belum ada akun sama sekali saat pertama dipakai),
 * tapi endpoint BE-nya sendiri sudah otomatis mengunci diri begitu tabel
 * users terisi (lihat api/setup.php di backend), jadi halaman ini aman
 * dibiarkan ada — hanya berguna sekali di awal, sesudah itu selalu
 * menampilkan status "sudah pernah di-setup".
 */
require_once __DIR__ . '/../../includes/session_init.php';
require_once __DIR__ . '/../../includes/ApiClient.php';

$result = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = (new ApiClient())->postJson('/setup/seed', []);
    if (!empty($res['success'])) {
        $result = $res['data'];
    } else {
        $error = $res['error'] ?? 'Setup gagal';
    }
}

$pageTitle = 'Setup Awal';
require __DIR__ . '/../../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>Setup Awal MBG App</h1>
    <p class="sub">Membuat skema tabel &amp; akun default di Back End</p>

    <?php if ($error): ?>
      <div class="alert error">
        <?= htmlspecialchars($error) ?>
        <?php if (str_contains($error, 'sudah berisi')): ?>
          <br><small>Ini normal kalau setup sudah pernah dijalankan sebelumnya. Silakan langsung <a href="<?= base_url('/views/auth/login.php') ?>">login</a>.</small>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($result): ?>
      <div class="alert success">
        Setup berhasil. <?= count($result['sppg_created']) ?> SPPG dan <?= count($result['accounts_created']) ?> akun dibuat dengan password default
        <strong><?= htmlspecialchars($result['default_password']) ?></strong>:
      </div>
      <p><strong>SPPG:</strong></p>
      <ul class="setup-account-list">
        <?php foreach ($result['sppg_created'] as $s): ?>
          <li><code><?= htmlspecialchars($s) ?></code></li>
        <?php endforeach; ?>
      </ul>
      <p><strong>Akun:</strong></p>
      <ul class="setup-account-list">
        <?php foreach ($result['accounts_created'] as $acc): ?>
          <li><code><?= htmlspecialchars($acc) ?></code></li>
        <?php endforeach; ?>
      </ul>
      <div class="alert warn">
        Segera ganti password akun-akun ini setelah login pertama. Akun <code>sppg1/2/3</code>
        sudah otomatis terkait ke SPPG 1/2/3 masing-masing.
      </div>
      <a href="<?= base_url('/views/auth/login.php') ?>" class="btn block">Lanjut ke Halaman Login</a>
    <?php elseif (!$error): ?>
      <p>
        Klik tombol di bawah untuk membuat database beserta tabel (jika belum ada),
        3 data SPPG, dan akun awal (<code>bgn</code>, <code>sppg1-3</code> yang otomatis
        terkait ke masing-masing SPPG, <code>masy1-3</code>).
        Tombol ini hanya berfungsi <strong>satu kali</strong> — setelah akun pertama
        dibuat, Back End otomatis menolak permintaan setup berikutnya.
      </p>
      <form method="post">
        <button type="submit" class="btn block">Jalankan Setup Sekarang</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
