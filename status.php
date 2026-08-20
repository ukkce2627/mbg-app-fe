<?php
/**
 * Endpoint status untuk ditampilkan di layer/bar bawah FE (lihat
 * includes/footer.php). Dipanggil lewat fetch() dari browser secara
 * berkala, BUKAN dipanggil server-side, supaya bar bisa auto-refresh
 * tanpa reload halaman.
 *
 * Melaporkan:
 *  - instance   : identitas instance FE ini sendiri (hostname, IP privat,
 *                 dst) — penting karena FE di-autoscale, jadi user/ops
 *                 perlu tahu sedang "dilayani" oleh instance yang mana.
 *  - fe_to_be   : status koneksi FE -> BE (hit endpoint /health milik BE).
 *  - fe_to_efs  : status akses FE -> folder session shared di EFS
 *                 (session_save_path di config.php). Hanya dicek folder-nya
 *                 ada & readable (bukan write test), karena EFS mount yang
 *                 hilang/lepas biasanya membuat folder tidak terbaca sama
 *                 sekali (bukan sekadar gagal ditulis).
 *  - be_health  : hasil mentah dari BE /health (status db/s3/sns BE),
 *                 di-relay apa adanya supaya bar FE bisa tampilkan status
 *                 BE juga tanpa user perlu buka endpoint BE secara terpisah.
 *
 * Endpoint ini SELALU balas 200 + JSON (kecuali PHP fatal error), sekalipun
 * BE/EFS bermasalah — kegagalan dilaporkan di dalam body, bukan lewat kode
 * HTTP, karena ini bukan health check untuk Load Balancer (itu tugas
 * health.php), melainkan status informasional untuk pengguna/ops.
 */
require_once __DIR__ . '/includes/session_init.php';

header('Content-Type: application/json');

$cfg = require __DIR__ . '/config.php';

echo json_encode([
    'instance'  => getInstanceInfo(),
    'fe_to_be'  => checkFeToBe($cfg),
    'fe_to_efs' => checkFeToEfs($cfg),
    'be_health' => fetchBeHealth($cfg),
    'checked_at'=> date('c'),
]);

/**
 * Identitas instance FE ini. Karena FE di-autoscale (banyak instance EC2
 * identik di belakang Load Balancer), info ini membantu memastikan user/ops
 * tahu persis instance mana yang sedang melayani request saat ini —
 * misalnya saat debug masalah yang cuma muncul di satu instance tertentu.
 */
function getInstanceInfo(): array
{
    return [
        'hostname'    => gethostname() ?: 'unknown',
        // IP privat server (bukan IP publik) — inilah alamat instance EC2
        // ini di dalam VPC, sesuai apa yang dilihat oleh Load Balancer.
        'server_ip'   => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'php_version' => PHP_VERSION,
    ];
}

/**
 * Cek FE -> BE dengan memanggil endpoint /health milik BE (bukan endpoint
 * bisnis), supaya cepat & tidak bergantung pada auth/API key.
 */
function checkFeToBe(array $cfg): array
{
    $apiBase = rtrim($cfg['api_base_url'] ?? '', '/');
    if ($apiBase === '') {
        return ['status' => 'not_configured', 'detail' => 'api_base_url kosong'];
    }

    // api_base_url biasanya berupa ".../api" -> endpoint /health BE ada
    // satu level di atasnya (lihat backend/public/health.php).
    $beRoot = preg_replace('#/api/?$#', '', $apiBase);
    $healthUrl = $beRoot . '/health';

    $start = microtime(true);
    $ch = curl_init($healthUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $elapsedMs = (int) round((microtime(true) - $start) * 1000);

    if ($raw === false) {
        return ['status' => 'disconnected', 'detail' => $err, 'url' => $healthUrl];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['status' => 'connected', 'http_code' => $httpCode, 'latency_ms' => $elapsedMs];
    }

    return ['status' => 'disconnected', 'detail' => 'HTTP ' . $httpCode, 'http_code' => $httpCode, 'url' => $healthUrl];
}

/**
 * Cek FE -> EFS: pastikan folder session_save_path ada & bisa dibaca.
 * Tidak melakukan write test — folder EFS yang lepas mount biasanya membuat
 * is_dir()/is_readable() langsung gagal, jadi ini cukup untuk mendeteksi
 * masalah mount tanpa risiko menumpuk file test di storage shared.
 */
function checkFeToEfs(array $cfg): array
{
    $path = $cfg['session_save_path'] ?? null;

    if (empty($path)) {
        return ['status' => 'not_configured', 'detail' => 'session_save_path kosong, memakai storage session lokal'];
    }

    if (!is_dir($path)) {
        return ['status' => 'disconnected', 'detail' => 'Folder tidak ditemukan (EFS mungkin belum ter-mount)', 'path' => $path];
    }

    if (!is_readable($path)) {
        return ['status' => 'disconnected', 'detail' => 'Folder ada tapi tidak bisa dibaca (cek permission)', 'path' => $path];
    }

    return ['status' => 'connected', 'path' => $path];
}

/**
 * Ambil status health BE (db/s3/sns) dengan memanggil ulang BE /health.
 * Dipisah dari checkFeToBe() supaya body response BE (yang berisi detail
 * db/s3/sns) bisa langsung diteruskan ke FE tanpa perlu parsing ganda.
 */
function fetchBeHealth(array $cfg): array
{
    $apiBase = rtrim($cfg['api_base_url'] ?? '', '/');
    if ($apiBase === '') {
        return ['status' => 'not_configured', 'detail' => 'api_base_url kosong'];
    }

    $beRoot = preg_replace('#/api/?$#', '', $apiBase);
    $healthUrl = $beRoot . '/health';

    $ch = curl_init($healthUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['status' => 'unreachable', 'detail' => $err];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['status' => 'unreachable', 'detail' => 'Response BE /health tidak valid'];
    }

    return $decoded;
}
