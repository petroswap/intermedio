<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $table = $_POST['table'] ?? '';
    $limit = max(1, min(1000, intval($_POST['limit'] ?? 10)));
    $fields = $_POST['fields'] ?? '';
    
    if (empty($table)) {
        $err = SqlError::make('invalid_input', 'No se indicó la tabla a consultar (parámetro table vacío).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        $err = SqlError::make('invalid_input', "Nombre de tabla no válido: «{$table}». Solo se admiten letras, números y guion bajo.");
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }
    
    $db = Database::getInstance(DB_CONFIG);
    
    $tableUpper = strtoupper($table);
    
    $fieldSql = '*';
    if (!empty($fields)) {
        $fieldList = array_filter(array_map('trim', explode(',', $fields)), function($f) { return $f !== ''; });
        $safeFields = [];
        foreach ($fieldList as $f) {
            if ($f === '*') {
                $safeFields[] = '*';
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $f)) {
                $err = SqlError::make('invalid_input', "Nombre de columna no válido: «{$f}». Solo se admiten letras, números y guion bajo.");
                Response::error(SqlError::message($err), 400, ['error' => $err]);
            }
            $safeFields[] = '"' . strtoupper($f) . '"';
        }
        $fieldSql = !empty($safeFields) ? implode(', ', $safeFields) : '*';
    }
    
    $sql = "SELECT FIRST {$limit} {$fieldSql} FROM \"{$tableUpper}\" ORDER BY 1";
    $data = $db->query($sql);
    
    Response::success($data);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
