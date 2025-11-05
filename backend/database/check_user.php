<?php
require_once __DIR__ . '/../config/database_wamp.php';

$db = Database::getInstance();
$result = $db->query("SELECT id, username, role, cabang_id FROM user LIMIT 10");

echo "All users:\n";
while ($row = $result->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
?>
