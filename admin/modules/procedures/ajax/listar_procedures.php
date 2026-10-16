<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $sql = "SELECT
                CAST(p.RDB\$PROCEDURE_NAME AS VARCHAR(64)) AS nombre,
                CAST(p.RDB\$PROCEDURE_TYPE AS INTEGER) AS tipo,
                CAST(p.RDB\$SYSTEM_FLAG AS INTEGER) AS sistema,
                CAST(p.RDB\$DESCRIPTION AS VARCHAR(500)) AS descripcion
            FROM RDB\$PROCEDURES p
            WHERE p.RDB\$SYSTEM_FLAG = 0 OR p.RDB\$SYSTEM_FLAG IS NULL
            ORDER BY p.RDB\$PROCEDURE_NAME";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $nombre = trim($r['NOMBRE'] ?? '');
        if ($nombre === '') continue;

        // contadores de params por procedure (inline validado, pdo_firebird da -804 con placeholders en system tables)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $nombre)) continue;
        $cnt = $pdo->query(
            "SELECT
                SUM(CASE WHEN CAST(pp.RDB\$PARAMETER_TYPE AS INTEGER) = 0 THEN 1 ELSE 0 END) AS entradas,
                SUM(CASE WHEN CAST(pp.RDB\$PARAMETER_TYPE AS INTEGER) = 1 THEN 1 ELSE 0 END) AS salidas
             FROM RDB\$PROCEDURE_PARAMETERS pp
             WHERE pp.RDB\$PROCEDURE_NAME = '{$nombre}'"
        )->fetch(PDO::FETCH_ASSOC);

        $out[] = [
            'nombre'      => $nombre,
            'tipo'        => (int)($r['TIPO'] ?? 1), // 1 = seleccionable (SELECTable), 2 = ejecutable
            'sistema'     => (int)($r['SISTEMA'] ?? 0),
            'descripcion' => trim((string)($r['DESCRIPCION'] ?? '')),
            'entradas'    => (int)($cnt['ENTRADAS'] ?? 0),
            'salidas'     => (int)($cnt['SALIDAS'] ?? 0),
        ];
    }

    Response::success($out);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
