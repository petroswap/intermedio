<?php
$_POST['sql'] = 'SELECT FIRST 3 * FROM CLIENTES';
ob_start();
require __DIR__ . '/../modules/inspector/ajax/ejecutar_sql.php';
$output = ob_get_clean();
echo "Output length: " . strlen($output) . "\n";
echo "Output: " . substr($output, 0, 500) . "\n";
