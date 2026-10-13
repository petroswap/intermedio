<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

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
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
