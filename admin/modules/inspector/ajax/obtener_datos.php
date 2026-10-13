<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $table = $_POST['table'] ?? '';
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = max(1, min(500, intval($_POST['per_page'] ?? 25)));
    $fields = $_POST['fields'] ?? '';
    $filters = $_POST['filters'] ?? [];
    
    if (empty($table)) {
        $err = SqlError::make('invalid_input', 'No se indicó la tabla a consultar (parámetro table vacío).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        $err = SqlError::make('invalid_input', "Nombre de tabla no válido: «{$table}». Solo se admiten letras, números y guion bajo.");
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }
    
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $tableUpper = strtoupper($table);
    
    $countSql = "SELECT COUNT(*) AS total FROM \"{$tableUpper}\"";
    $countResult = $pdo->query($countSql)->fetch(PDO::FETCH_ASSOC);
    $total = intval($countResult['TOTAL'] ?? 0);
    
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
    
    $where = '';
    $params = [];
    if (!empty($filters) && is_array($filters)) {
        $conditions = [];
        foreach ($filters as $f) {
            $col = strtoupper(trim((string)($f['field'] ?? '')));
            $op = $f['operator'] ?? 'LIKE';
            $val = $f['value'] ?? '';
            if ($col === '' || $val === null || $val === '') continue;

            if (!preg_match('/^[A-Za-z0-9_]+$/', $col)) {
                $err = SqlError::make('invalid_input', "Nombre de columna no válido en el filtro: «{$col}». Solo se admiten letras, números y guion bajo.");
                Response::error(SqlError::message($err), 400, ['error' => $err]);
            }
            
            $colEscaped = '"' . str_replace('"', '""', $col) . '"';
            switch (strtoupper($op)) {
                case '=': $conditions[] = "{$colEscaped} = ?"; $params[] = $val; break;
                case '!=': $conditions[] = "{$colEscaped} != ?"; $params[] = $val; break;
                case '>': $conditions[] = "{$colEscaped} > ?"; $params[] = $val; break;
                case '<': $conditions[] = "{$colEscaped} < ?"; $params[] = $val; break;
                case '>=': $conditions[] = "{$colEscaped} >= ?"; $params[] = $val; break;
                case '<=': $conditions[] = "{$colEscaped} <= ?"; $params[] = $val; break;
                case 'BETWEEN':
                    $parts = array_map('trim', explode(',', (string)$val));
                    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                        $err = SqlError::make('invalid_input', "El filtro BETWEEN de «{$col}» requiere dos valores separados por coma. Ejemplo: 100, 500.");
                        Response::error(SqlError::message($err), 400, ['error' => $err]);
                    }
                    $conditions[] = "{$colEscaped} BETWEEN ? AND ?";
                    $params[] = $parts[0];
                    $params[] = $parts[1];
                    break;
                default: $conditions[] = "{$colEscaped} LIKE ?"; $params[] = "%{$val}%";
            }
        }
        if (!empty($conditions)) {
            $where = ' WHERE ' . implode(' AND ', $conditions);
        }
    }
    
    $skip = ($page - 1) * $perPage;
    $sql = "SELECT FIRST {$perPage} SKIP {$skip} {$fieldSql} FROM \"{$tableUpper}\"{$where}";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success([
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage),
    ]);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
