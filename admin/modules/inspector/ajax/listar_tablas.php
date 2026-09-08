<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $tables = $db->getTables();

    $result = [];
    foreach ($tables as $t) {
        $name = trim($t['TABLA'] ?? $t['tabla'] ?? '');
        if (empty($name)) continue;
        $result[] = ['TABLA' => $name];
    }

    Response::success($result);
} catch (Exception $e) {
    Response::error($e->getMessage());
}
