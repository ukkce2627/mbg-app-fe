<?php
require_once __DIR__ . '/session_init.php';
require_once __DIR__ . '/ApiClient.php';

(new ApiClient())->postJson('/auth/logout', []);
$_SESSION = [];
session_destroy();
header('Location: ' . base_url('/views/auth/login.php'));
exit;
