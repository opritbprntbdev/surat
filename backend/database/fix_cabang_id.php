<?php
require_once __DIR__ . '/../config/database_wamp.php';

$db = Database::getInstance();

// Update user pokp (KC POKPKMP kode 035) 
$db->query("UPDATE user SET cabang_id=35 WHERE username='pokp@bprntb.co.id'");
echo "✓ Updated pokp@bprntb.co.id to cabang_id=35 (KC POKPKMP)\n";

// Verify
$result = $db->query("SELECT id, username, role, cabang_id FROM user WHERE role='CABANG' ORDER BY id");
echo "\nAll CABANG users:\n";
while ($row = $result->fetch_assoc()) {
    $status = $row['cabang_id'] ? "✓" : "✗";
    echo "$status {$row['username']}: cabang_id={$row['cabang_id']}\n";
}
?>
