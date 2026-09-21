<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $tableA = strtoupper($_POST['table_a'] ?? '');
    $tableB = strtoupper($_POST['table_b'] ?? '');

    if (empty($tableA) || empty($tableB)) {
        Response::error('Parámetros table_a y table_b requeridos');
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableA) || !preg_match('/^[A-Za-z0-9_]+$/', $tableB)) {
        Response::error('Nombre de tabla no válido');
        exit;
    }

    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    // Find FK relationships - use the same query as relaciones.php
    $sql = "
        SELECT
            rc_child.RDB\$RELATION_NAME AS tabla_hija,
            idx_seg_child.RDB\$FIELD_NAME AS columna_fk,
            rc_parent.RDB\$RELATION_NAME AS tabla_padre,
            idx_seg_parent.RDB\$FIELD_NAME AS columna_pk
        FROM RDB\$REF_CONSTRAINTS refc
        JOIN RDB\$RELATION_CONSTRAINTS rc_child ON refc.RDB\$CONSTRAINT_NAME = rc_child.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS idx_seg_child ON rc_child.RDB\$INDEX_NAME = idx_seg_child.RDB\$INDEX_NAME
        JOIN RDB\$RELATION_CONSTRAINTS rc_parent ON refc.RDB\$CONST_NAME_UQ = rc_parent.RDB\$CONSTRAINT_NAME
        JOIN RDB\$INDEX_SEGMENTS idx_seg_parent ON rc_parent.RDB\$INDEX_NAME = idx_seg_parent.RDB\$INDEX_NAME
        WHERE rc_child.RDB\$RELATION_NAME = :table
        ORDER BY rc_child.RDB\$RELATION_NAME
    ";

    // Search for FK where tableA references tableB or tableB references tableA
    $allRelations = [];

    // Check tableA as child (references tableB)
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['table' => $tableA]);
    $relationsA = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($relationsA as $rel) {
        $padre = trim($rel['TABLA_PADRE']);
        if ($padre === $tableB) {
            $allRelations[] = [
                'type' => 'LEFT',
                'join_table' => $tableB,
                'on' => 'a.' . trim($rel['COLUMNA_FK']) . ' = b.' . trim($rel['COLUMNA_PK']),
                'from_column' => trim($rel['COLUMNA_FK']),
                'to_column' => trim($rel['COLUMNA_PK']),
            ];
        }
    }

    // Check tableB as child (references tableA)
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute(['table' => $tableB]);
    $relationsB = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    foreach ($relationsB as $rel) {
        $padre = trim($rel['TABLA_PADRE']);
        if ($padre === $tableA) {
            $allRelations[] = [
                'type' => 'LEFT',
                'join_table' => $tableA,
                'on' => 'a.' . trim($rel['COLUMNA_PK']) . ' = b.' . trim($rel['COLUMNA_FK']),
                'from_column' => trim($rel['COLUMNA_PK']),
                'to_column' => trim($rel['COLUMNA_FK']),
            ];
        }
    }

    Response::success([
        'joins' => $allRelations,
        'count' => count($allRelations)
    ]);
} catch (Exception $e) {
    Response::error('Error al detectar JOINs: ' . $e->getMessage());
}
