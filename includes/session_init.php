<?php
/**
 * WAJIB di-include paling atas di setiap halaman FE, SEBELUM output apa pun.
 * Mengarahkan session.save_path ke folder shared antar-server (Bab 8),
 * karena FE dijalankan di banyak server sekaligus dan tidak boleh
 * menyimpan session di disk lokal /tmp masing-masing server.
 */

$cfg = require __DIR__ . '/../config.php';

if (!empty($cfg['session_save_path'])) {
    ini_set('session.save_path', $cfg['session_save_path']);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * BASE_PATH — prefix URL tempat aplikasi FE ini berada, dihitung OTOMATIS
 * (bukan hardcode), supaya seluruh href/src/redirect di FE tetap benar baik
 * saat dijalankan di root domain (mis. http://10.11.12.13/) MAUPUN di
 * subfolder (mis. http://localhost/mbg-app-frontend/).
 *
 * Caranya: bandingkan path file (__DIR__, filesystem) dengan path URL script
 * yang sedang jalan (SCRIPT_NAME) — selisihnya adalah prefix folder aplikasi.
 */
if (!defined('BASE_PATH')) {
    $fsAppRoot   = str_replace('\\', '/', dirname(__DIR__));               // .../mbg-app-frontend
    $fsScript    = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $urlScript   = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath    = '';

    if ($fsScript !== '' && str_starts_with($fsScript, $fsAppRoot)) {
        $suffix = substr($fsScript, strlen($fsAppRoot)); // mis. "/views/bgn/dashboard.php"
        if ($suffix !== '' && substr($urlScript, -strlen($suffix)) === $suffix) {
            $basePath = substr($urlScript, 0, strlen($urlScript) - strlen($suffix));
        }
    }
    define('BASE_PATH', rtrim($basePath, '/'));
}

/** Bangun URL relatif terhadap root aplikasi FE, mis. base_url('/views/auth/login.php') */
function base_url(string $path = ''): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function fe_current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function fe_require_login(): array
{
    $user = fe_current_user();
    if (!$user) {
        header('Location: ' . base_url('/views/auth/login.php'));
        exit;
    }
    return $user;
}

function fe_require_role(array $roles): array
{
    $user = fe_require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Anda tidak berhak mengakses halaman ini.';
        exit;
    }
    return $user;
}
