<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $table = $_POST['table'] ?? '';
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = max(1, min(500, intval($_POST['per_page'] ?? 25)));
    $fields = $_POST['fields'] ?? '';
    $filters = $_POST['filters'] ?? [];
    
    if (empty($table)) {
        Response::error('Parámetro table requerido');
        exit;
    }
    
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        Response::error('Nombre de tabla no válido');
        exit;
    }
    
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $tableUpper = strtoupper($table);
    
    $countSql = "SELECT COUNT(*) AS total FROM \"{$tableUpper}\"";
    $countResult = $pdo->query($countSql)->fetch(PDO::FETCH_ASSOC);
    $total = intval($countResult['TOTAL'] ?? 0);
    
    $fieldSql = '*';
    if (!empty($fields)) {
        $fieldList = array_map('trim', explode(',', $fields));
        $safeFields = [];
        foreach ($fieldList as $f) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $f)) {
                $safeFields[] = '"' . strtoupper($f) . '"';
            }
        }
        $fieldSql = !empty($safeFields) ? implode(', ', $safeFields) : '*';
    }
    
    $where = '';
    $params = [];
    if (!empty($filters) && is_array($filters)) {
        $conditions = [];
        foreach ($filters as $f) {
            $col = strtoupper($f['field'] ?? '');
            $op = $f['operator'] ?? 'LIKE';
            $val = $f['value'] ?? '';
            if (empty($col) || empty($val)) continue;
            
            if (!preg_match('/^[A-Za-z0-9_]+$/', $col)) continue;
            
            $colEscaped = '"' . str_replace('"', '""', $col) . '"';
            switch (strtoupper($op)) {
                case '=': $conditions[] = "{$colEscaped} = ?"; $params[] = $val; break;
                case '!=': $conditions[] = "{$colEscaped} != ?"; $params[] = $val; break;
                case '>': $conditions[] = "{$colEscaped} > ?"; $params[] = $val; break;
                case '<': $conditions[] = "{$colEscaped} < ?"; $params[] = $val; break;
                case '>=': $conditions[] = "{$colEscaped} >= ?"; $params[] = $val; break;
                case '<=': $conditions[] = "{$colEscaped} <= ?"; $params[] = $val; break;
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
} catch (Exception $e) {
    Response::error('Error al obtener datos');
}
