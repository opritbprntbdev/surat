<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /surat/frontend/login.html');
    exit;
}

// Tentukan base URL untuk assets
$base_url = '/surat/frontend/';
$v = '20251029';

$nama_user = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$initial_user = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat App BPR NTB</title>
    <!-- Gunakan base URL untuk path CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/main.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/components.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/responsive.css?v=<?php echo $v; ?>">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📧</text></svg>">
</head>

<body data-role="<?php echo strtoupper($_SESSION['role'] ?? 'CABANG'); ?>">
    <div class="gmail-container">