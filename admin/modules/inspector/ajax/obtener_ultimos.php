<?php
/**
 * ============================================
 * OBTENER ÚLTIMOS DATOS - AJAX (v2)
 * ============================================
 * Obtiene los últimos N registros de una tabla
 * Para carga inicial rápida antes de "Mostrar todos"
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';
require_once __DIR__ . '/../../../core/CRUD.php';

try {
    $request = new Request();
    $request->required('table');
    
    $table = $request->sanitize($request->get('table'));
    $limit = min($request->int('limit', 10), 50); // Máximo 50
    
    // Validar nombre de tabla
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
        throw new InvalidArgumentException('Nombre de tabla inválido');
    }
    
    // Obtener campos seleccionados
    $fields = $request->get('fields', '*');
    if (is_string($fields) && $fields !== '*') {
        $fields = array_map('trim', explode(',', $fields));
    }
    
    // Conectar y obtener datos
    $db = Database::getInstance(DB_CONFIG);
    $conn = $db->getConnection();
    
    // Obtener columna primaria o primera columna para ordenar
    $orderCol = getOrderByColumn($conn, $table);
    
    // Construir consulta para últimos registros
    if (is_array($fields) && !empty($fields)) {
        $fieldList = array_map(function($f) {
            return '"' . strtoupper($f) . '"';
        }, $fields);
        $fieldStr = implode(', ', $fieldList);
    } else {
        $fieldStr = '*';
    }
    
    // Usar subconsulta para obtener los últimos N registros
    $sql = "SELECT {$fieldStr} FROM (
                SELECT {$fieldStr} FROM \"{$table}\" ORDER BY \"{$orderCol}\" DESC ROWS 1 TO {$limit}
            ) sub ORDER BY \"{$orderCol}\" ASC";
    
    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total de registros
    $countSql = "SELECT COUNT(*) AS total FROM \"{$table}\"";
    $countStmt = $conn->query($countSql);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)($countResult['total'] ?? 0);
    
    Response::success($data, "Últimos {$limit} registros obtenidos", $total);
    
} catch (InvalidArgumentException $e) {
    Response::error($e->getMessage(), 400);
} catch (PDOException $e) {
    if (APP_DEBUG) {
        Response::error('Error de BD: ' . $e->getMessage(), 500);
    } else {
        Response::error('Error al obtener datos de la tabla', 500);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

/**
 * Obtener columna para ordenar (primaria o primera columna)
 */
function getOrderByColumn($conn, $tableName) {
    // Intentar obtener clave primaria
    $sql = "SELECT S.RDB\$FIELD_NAME AS campo
            FROM RDB\$RELATION_CONSTRAINTS RC
            JOIN RDB\$INDEX_SEGMENTS S ON RC.RDB\$INDEX_NAME = S.RDB\$INDEX_NAME
            WHERE RC.RDB\$RELATION_NAME = '{$tableName}' 
            AND RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
            ORDER BY S.RDB\$FIELD_POSITION
            ROWS 1 TO 1";
    
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['CAMPO'];
    }
    
    // Si no hay primaria, usar primera columna
    $sql = "SELECT RF.RDB\$FIELD_NAME AS campo
            FROM RDB\$RELATION_FIELDS RF
            WHERE RF.RDB\$RELATION_NAME = '{$tableName}'
            ORDER BY RF.RDB\$FIELD_POSITION
            ROWS 1 TO 1";
    
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['CAMPO'] ?? 'RDB\$KEY';
}
