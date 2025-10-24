<?php
$mysqli = new mysqli('localhost', 'root', '', 'surat_app', port: '3308');
if ($mysqli->connect_error) {
    die('Koneksi gagal: ' . $mysqli->connect_error);
}
echo 'Koneksi Sukses';
$mysqli->close();
?>