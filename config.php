<?php
/**
 * config.php — Front End. Satu-satunya sumber konfigurasi (tidak pakai .env lagi).
 * Isi langsung nilai di bawah ini saat deploy. SESSION_SAVE_PATH WAJIB berupa
 * folder shared yang sama persis terlihat di semua salinan FE (lihat Bab 8
 * dokumen teknis) karena FE berjalan di banyak server Apache sekaligus.
 *
 * Catatan soal 'api_base_url': nilai ini SATU-SATUNYA tempat di seluruh FE
 * yang boleh berupa URL absolut (termasuk domain), karena dipanggil dari
 * PHP sisi server (cURL) ke Back End yang bisa saja berada di domain/port
 * lain sama sekali — bukan link di HTML, jadi tidak bisa dibuat "relatif".
 * Semua link/redirect DI DALAM FE sendiri sudah otomatis mengikuti folder
 * tempat FE ini ditaruh (lihat includes/session_init.php -> base_url()).
 */

return [
    // Contoh lokal (FE & BE ditaruh sebagai subfolder di htdocs yang sama):
    // PAKAI 127.0.0.1, JANGAN 'localhost' — di banyak setup Windows/XAMPP,
    // PHP-cURL me-resolve 'localhost' ke IPv6 (::1) lebih dulu sementara
    // Apache cuma listen di IPv4, sehingga request FE->BE nyangkut sampai
    // timeout walau BE-nya sendiri sehat (bisa diakses normal dari browser).
    'api_base_url'      => 'http://IP-mbg-ec2-be/api',
    'api_key'           => 'mbg-secret-key-2024',  // harus SAMA dengan api_key di backend/config.php
    'session_save_path' => null,                    // isi path folder shared, mis. '/mnt/efs/mbg-session'
];
