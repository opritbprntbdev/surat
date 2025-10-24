<?php
session_start();

// Jika user belum login, tendang ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Ambil data user dari session untuk ditampilkan
$nama_user = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$initial_user = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat App BPR NTB</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📧</text></svg>">
</head>

<body>
    <div class="gmail-container">