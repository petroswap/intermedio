<?php
/**
 * ============================================
 * EJECUTAR SQL - AJAX
 * ============================================
 * Ejecuta una consulta SQL libre y retorna resultados
 * 
 * IMPORTANTE: Solo permit SELECT - Bloqueo total de modificaciones
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';

try {
    $request = new Request();
    $request->required('sql');
    
    $sql = $request->get('sql');
    
    // ============================================
    // VALIDACIÓN DE SEGURIDAD - BLOQUEO TOTAL
    // ============================================
    
    // 1. Limpiar el SQL
    $sqlClean = $sql;
    
    // Eliminar comentarios de línea (-- ...)
    $sqlClean = preg_replace('/--.*$/m', '', $sqlClean);
    
    // Eliminar comentarios de bloque (/* ... */)
    $sqlClean = preg_replace('/\/\*[\s\S]*?\*\//', '', $sqlClean);
    
    // Eliminar espacios múltiples
    $sqlClean = preg_replace('/\s+/', ' ', $sqlClean);
    
    // Convertir a mayúsculas para análisis
    $sqlUpper = strtoupper(trim($sqlClean));
    
    // 2. Lista completa de palabras prohibidas (incluyendo variantes)
    $forbiddenWords = [
        // Modificación de datos
        'INSERT', 'UPDATE', 'DELETE', 'MERGE', 'UPSERT',
        
        // Estructura de tablas
        'CREATE', 'ALTER', 'DROP', 'TRUNCATE', 'RENAME',
        
        // Permisos
        'GRANT', 'REVOKE',
        
        // Transacciones (para evitar bypass)
        'COMMIT', 'ROLLBACK', 'SAVEPOINT',
        
        // Otras operaciones peligrosas
        'EXEC', 'EXECUTE', 'CALL', 'DO',
        
        // Firebird específicos
        'SET TERM', 'RECREATE', 'SUSPEND', 'WHEN',
    ];
    
    // 3. Verificar si contiene alguna palabra prohibida
    foreach ($forbiddenWords as $word) {
        // Buscar la palabra completa (no como parte de otro nombre)
        $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
        if (preg_match($pattern, $sqlUpper)) {
            Response::error(
                "Operación BLOQUEADA: '{$word}'. Solo se permiten consultas SELECT de lectura.",
                403
            );
        }
    }
    
    // 4. Verificar que empiece con SELECT (después de limpiar)
    $firstKeyword = '';
    if (preg_match('/^(WITH|SELECT)\b/i', $sqlClean, $matches)) {
        $firstKeyword = strtoupper($matches[1]);
    }
    
    if ($firstKeyword !== 'SELECT' && $firstKeyword !== 'WITH') {
        Response::error(
            "Solo se permiten consultas SELECT o WITH (CTE). Operación denegada.",
            403
        );
    }
    
    // 5. Verificar que no haya múltiples statements (evitar ;)
    if (preg_match('/;\s*\S/', $sqlClean)) {
        Response::error(
            "No se permiten múltiples sentencias SQL en una sola consulta.",
            403
        );
    }
    
    // 6. Verificar subqueries peligrosas
    $dangerousPatterns = [
        '/SELECT.*FROM.*DELETE/i',
        '/SELECT.*FROM.*UPDATE/i',
        '/SELECT.*FROM.*INSERT/i',
        '/SELECT.*FROM.*DROP/i',
        '/SELECT.*FROM.*ALTER/i',
        '/SELECT.*FROM.*CREATE/i',
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $sqlClean)) {
            Response::error(
                "Consulta con subquery peligroso detectada. Operación denegada.",
                403
            );
        }
    }
    
    // 7. Verificar UNION con operaciones peligrosas
    if (preg_match('/UNION\s+(ALL\s+)?(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE)/i', $sqlClean)) {
        Response::error(
            "UNION con operación de modificación detectado. Operación denegada.",
            403
        );
    }
    
    // 8. Verificar funciones peligrosas
    $dangerousFunctions = [
        'xp_cmdshell', 'sp_executesql', 'OPENROWSET',
        'OPENQUERY', 'LINKEDSERVER', 'OPENROWSET',
    ];
    
    foreach ($dangerousFunctions as $func) {
        if (stripos($sqlClean, $func) !== false) {
            Response::error(
                "Función del sistema prohibida detectada: {$func}",
                403
            );
        }
    }
    
    // ============================================
    // EJECUTAR CONSULTA (solo si pasó todas las validaciones)
    // ============================================
    
    $db = Database::getInstance(DB_CONFIG);
    $result = $db->query($sql);
    
    $count = count($result);
    
    Response::paginated($result, $count, 1, $count > 0 ? $count : 1);
    
} catch (PDOException $e) {
    Response::error("Error de SQL: " . $e->getMessage(), 400);
} catch (InvalidArgumentException $e) {
    Response::error($e->getMessage(), 400);
} catch (Exception $e) {
    Response::error("Error al ejecutar SQL: " . $e->getMessage(), 500);
}
