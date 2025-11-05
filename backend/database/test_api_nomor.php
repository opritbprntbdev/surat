<?php
session_start();
$_SESSION['user_id'] = 7;
$_SESSION['role'] = 'CABANG';
$_SESSION['cabang_id'] = 7;
$_SESSION['username'] = 'kckayangan@bprntb.co.id';

$_GET['action'] = 'list';
$_GET['page'] = 1;
$_GET['page_size'] = 50;

echo "=== Testing API nomor_surat.php with action=list ===" . PHP_EOL;
echo "Session: user_id={$_SESSION['user_id']}, role={$_SESSION['role']}, cabang_id={$_SESSION['cabang_id']}" . PHP_EOL . PHP_EOL;

ob_start();
require __DIR__ . '/../api/nomor_surat.php';
$output = ob_get_clean();

echo "Response:" . PHP_EOL;
echo $output . PHP_EOL;

// Parse JSON
$json = json_decode($output, true);
if ($json) {
    echo PHP_EOL . "Parsed:" . PHP_EOL;
    print_r($json);
} else {
    echo PHP_EOL . "Failed to parse JSON" . PHP_EOL;
}
