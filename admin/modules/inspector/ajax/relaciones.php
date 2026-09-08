<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $table = $_GET['table'] ?? '';
    if (empty($table)) {
        Response::error('Parámetro table requerido');
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        Response::error('Nombre de tabla no válido');
        exit;
    }

    $tableUpper = strtoupper($table);
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $sqlHijos = "
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
        WHERE rc_parent.RDB\$RELATION_NAME = :table
        ORDER BY rc_child.RDB\$RELATION_NAME
    ";

    $stmt = $pdo->prepare($sqlHijos);
    $stmt->execute(['table' => $tableUpper]);
    $hijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlPadres = "
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
        ORDER BY rc_parent.RDB\$RELATION_NAME
    ";

    $stmt = $pdo->prepare($sqlPadres);
    $stmt->execute(['table' => $tableUpper]);
    $padres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allTables = array_unique(array_merge(
        array_column($hijos, 'tabla_hija'),
        array_column($padres, 'tabla_padre')
    ));

    Response::success([
        'table' => $table,
        'hijos' => $hijos,
        'padres' => $padres,
        'allTables' => array_values($allTables)
    ]);
} catch (Exception $e) {
    Response::error('Error al consultar relaciones');
}
