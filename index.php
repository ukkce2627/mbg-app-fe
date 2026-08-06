<?php
require_once __DIR__ . '/includes/session_init.php';

$user = fe_current_user();
if ($user) {
    header('Location: ' . base_url('/views/' . $user['role'] . '/dashboard.php'));
} else {
    header('Location: ' . base_url('/views/auth/login.php'));
}
exit;
