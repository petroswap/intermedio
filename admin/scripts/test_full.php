<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Loading config...\n";
require_once __DIR__ . '/../config.php';
echo "Step 2: Config loaded. DB_PATH=" . DB_PATH . "\n";

echo "Step 3: Loading Database...\n";
require_once __DIR__ . '/../core/Database.php';
echo "Step 4: Database loaded\n";

echo "Step 5: Loading Response...\n";
require_once __DIR__ . '/../core/Response.php';
echo "Step 6: Response loaded\n";

echo "Step 7: Creating DB instance...\n";
$db = Database::getInstance(DB_CONFIG);
echo "Step 8: DB connected\n";

echo "Step 9: Running query...\n";
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT FIRST 3 * FROM CLIENTES");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Step 10: Got " . count($data) . " rows\n";

echo "Step 11: Encoding JSON...\n";
$json = json_encode([
    'success' => true,
    'data' => $data,
    'msg' => 'test',
    'count' => count($data),
], JSON_UNESCAPED_UNICODE);
echo "Step 12: JSON length=" . strlen($json) . "\n";

echo "Step 13: Outputting...\n";
echo $json;
echo "\nStep 14: Done\n";
