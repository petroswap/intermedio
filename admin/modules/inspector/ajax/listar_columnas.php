<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $table = $_POST['table'] ?? '';
    if (empty($table)) {
        $err = SqlError::make('invalid_input', 'No se indicó la tabla (parámetro table vacío).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        $err = SqlError::make('invalid_input', "Nombre de tabla no válido: «{$table}». Solo se admiten letras, números y guion bajo.");
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    $db = Database::getInstance(DB_CONFIG);
    $columns = $db->getColumns($table);
    $pk = $db->getPrimaryKey($table);

    if (empty($columns)) {
        $err = SqlError::make('table_not_found', "La tabla «{$table}» no existe en la base de datos o no tiene columnas visibles.");
        Response::error(SqlError::message($err), 404, ['error' => $err]);
    }

    foreach ($columns as &$col) {
        $col['IS_PK'] = (strtoupper($col['COLUMNA']) === strtoupper($pk)) ? 1 : 0;
    }
    
    Response::success($columns);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}