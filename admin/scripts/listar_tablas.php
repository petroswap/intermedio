<?php
/**
 * @name Listar Tablas
 * @description Devuelve el listado completo de tablas de usuario definidas en la base de datos Firebird, excluyendo las tablas internas del motor (las que tienen RDB$SYSTEM_FLAG = 0). Para cada tabla se muestra el nombre según aparece en RDB$RELATIONS, ordenado alfabéticamente. Al final se indica el número total de tablas encontradas. Sin parámetros: es una consulta directa de metadatos del sistema, útil para conocer la estructura general de la base de datos y validar que la conexión devuelve las tablas esperadas.
 * @method POST
 * @output TEXT
 */

$SCRIPT_CONFIG = [
    'params' => []
];

require_once __DIR__ . '/script_guard.php';

require_once dirname(__DIR__, 1) . '/config.php';
require_once dirname(__DIR__, 1) . '/core/Database.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();
    
    $sql = 'SELECT RDB$RELATION_NAME as tabla
            FROM RDB$RELATIONS
            WHERE RDB$SYSTEM_FLAG = 0
            ORDER BY RDB$RELATION_NAME';
    
    $res = $pdo->query($sql);
    $tablas = [];
    
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $tablas[] = trim($row['TABLA']);
    }
    
    echo "=== TABLAS DE LA BASE DE DATOS ===\n";
    echo "Total: " . count($tablas) . " tablas\n\n";
    
    foreach ($tablas as $i => $tabla) {
        echo ($i + 1) . ". " . $tabla . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
