<?php
/**
 * ============================================
 * LISTAR TABLAS - AJAX (v2)
 * ============================================
 * Obtiene la lista de tablas con conteo de registros
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $conn = $db->getConnection();
    
    // Obtener tablas con conteo de registros
    $sql = "SELECT 
                r.RDB\$RELATION_NAME AS tabla,
                (SELECT COUNT(*) FROM rdb\$relations WHERE rdb\$relation_name = r.RDB\$RELATION_NAME) AS registros
            FROM RDB\$RELATIONS r
            WHERE r.RDB\$SYSTEM_FLAG = 0 
            ORDER BY r.RDB\$RELATION_NAME";
    
    $stmt = $conn->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener conteo de columnas para cada tabla
    $columnSql = "SELECT 
                     RF.RDB\$RELATION_NAME AS tabla,
                     COUNT(*) AS total_columnas
                  FROM RDB\$RELATION_FIELDS RF
                  GROUP BY RF.RDB\$RELATION_NAME";
    
    $colStmt = $conn->query($columnSql);
    $columnCounts = [];
    while ($row = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columnCounts[$row['TABLA']] = (int)$row['TOTAL_COLUMNAS'];
    }
    
    // Combinar datos
    $result = [];
    foreach ($tables as $table) {
        $tableName = $table['TABLA'];
        $result[] = [
            'tabla' => $tableName,
            'registros' => (int)$this->countRecords($conn, $tableName),
            'columnas' => $columnCounts[$tableName] ?? 0
        ];
    }
    
    Response::success($result, "Tablas obtenidas correctamente");
    
} catch (Exception $e) {
    Response::error("Error al obtener tablas: " . $e->getMessage(), 500);
}

/**
 * Contar registros de una tabla de forma segura
 */
function countRecords($conn, $tableName) {
    try {
        // Validar nombre de tabla (solo letras, números, guiones bajos)
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tableName)) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) AS total FROM \"{$tableName}\"";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}
