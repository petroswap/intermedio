<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $table = $_GET['table'] ?? $_POST['table'] ?? '';
    if (empty($table)) {
        $err = SqlError::make('invalid_input', 'No se indicó la tabla (parámetro table vacío).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        $err = SqlError::make('invalid_input', "Nombre de tabla no válido: «{$table}». Solo se admiten letras, números y guion bajo.");
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    $tableUpper = strtoupper($table);
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $known = [];
    foreach ($db->getTables() as $row) {
        $name = trim((string)($row['TABLA'] ?? $row['tabla'] ?? ''));
        if ($name !== '') {
            $known[] = strtoupper($name);
        }
    }
    if (!in_array($tableUpper, $known, true)) {
        $err = SqlError::make('table_not_found', "La tabla «{$table}» no existe en la base de datos.");
        Response::error(SqlError::message($err), 404, ['error' => $err]);
    }

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
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
