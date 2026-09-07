<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance(DB_CONFIG);
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT FIRST 3 * FROM CLIENTES");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check each value for encoding issues
foreach ($data as $i => $row) {
    echo "Row {$i}:\n";
    foreach ($row as $key => $val) {
        if (is_string($val)) {
            $clean = trim($val);
            $jsonTest = json_encode($clean);
            if ($jsonTest === null) {
                echo "  {$key}: ENCODE FAIL (hex: " . bin2hex($clean) . ")\n";
            } elseif (strlen($jsonTest) === 0) {
                echo "  {$key}: EMPTY JSON for value '{$clean}'\n";
            }
        }
    }
}

echo "\nTrying json_encode on full data...\n";
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
if ($json === null) {
    echo "FAILED: " . json_last_error_msg() . "\n";
    // Try to find the problematic value
    echo "json_last_error: " . json_last_error() . "\n";
} else {
    echo "SUCCESS: length=" . strlen($json) . "\n";
    echo substr($json, 0, 500) . "\n";
}
