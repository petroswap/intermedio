<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

try {
    $nombre = $_GET['nombre'] ?? $_POST['nombre'] ?? '';
    if (empty($nombre)) {
        $err = SqlError::make('invalid_input', 'No se indicó el procedure (parámetro nombre vacío).');
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $nombre)) {
        $err = SqlError::make('invalid_input', "Nombre de procedure no válido: «{$nombre}». Solo se admiten letras, números y guion bajo.");
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    // --- cabecera + fuente ---
    $sql = "SELECT
                CAST(p.RDB\$PROCEDURE_NAME AS VARCHAR(64)) AS nombre,
                CAST(p.RDB\$PROCEDURE_TYPE AS INTEGER) AS tipo,
                CAST(p.RDB\$SYSTEM_FLAG AS INTEGER) AS sistema,
                CAST(p.RDB\$DESCRIPTION AS VARCHAR(500)) AS descripcion,
                CAST(p.RDB\$PROCEDURE_SOURCE AS BLOB SUB_TYPE TEXT) AS fuente
            FROM RDB\$PROCEDURES p
            WHERE p.RDB\$PROCEDURE_NAME = '{$nombre}'";
    $info = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        $err = SqlError::make('table_not_found', "El procedure «{$nombre}» no existe en la base de datos.");
        Response::error(SqlError::message($err), 404, ['error' => $err]);
    }

    // --- parámetros ---
    $sqlParams = "SELECT
                      CAST(pp.RDB\$PARAMETER_NAME AS VARCHAR(64)) AS nombre,
                      CAST(pp.RDB\$PARAMETER_TYPE AS INTEGER) AS direccion,
                      CAST(pp.RDB\$PARAMETER_NUMBER AS INTEGER) AS posicion,
                      CAST(pp.RDB\$FIELD_SOURCE AS VARCHAR(64)) AS campo
                  FROM RDB\$PROCEDURE_PARAMETERS pp
                  WHERE pp.RDB\$PROCEDURE_NAME = '{$nombre}'
                  ORDER BY pp.RDB\$PARAMETER_TYPE, pp.RDB\$PARAMETER_NUMBER";
    $paramsRaw = $pdo->query($sqlParams)->fetchAll(PDO::FETCH_ASSOC);

    // tipo de campo por separado (evita -804 del JOIN con placeholders en pdo_firebird)
    $params = [];
    foreach ($paramsRaw as $p) {
        $campo = trim($p['CAMPO'] ?? '');
        $ftype = null; $flen = null; $fscale = null; $fsubtype = null;
        if ($campo !== '' && preg_match('/^[A-Za-z0-9_$]+$/', $campo)) {
            $f = $pdo->query("SELECT CAST(f.RDB\$FIELD_TYPE AS INTEGER) AS ft, CAST(f.RDB\$FIELD_LENGTH AS INTEGER) AS fl, CAST(f.RDB\$FIELD_SCALE AS INTEGER) AS fs, CAST(f.RDB\$FIELD_SUB_TYPE AS INTEGER) AS fst, CAST(f.RDB\$CHARACTER_LENGTH AS INTEGER) AS fcl FROM RDB\$FIELDS f WHERE f.RDB\$FIELD_NAME = '{$campo}'")->fetch(PDO::FETCH_ASSOC);
            if ($f) {
                $ftype = (int)$f['FT']; $flen = (int)$f['FL']; $fscale = (int)$f['FS']; $fsubtype = (int)$f['FST'];
                $fcl = $f['FCL'] !== null ? (int)$f['FCL'] : null;
            }
        }
        $params[] = [
            'nombre'    => trim($p['NOMBRE'] ?? ''),
            'direccion' => (int)($p['DIRECCION'] ?? 0), // 0 = IN, 1 = OUT
            'posicion'  => (int)($p['POSICION'] ?? 0),
            'campo'     => $campo,
            'tipo_code' => $ftype,
            'longitud'  => $flen,
            'escala'    => $fscale,
            'subtipo'   => $fsubtype,
            'char_len'  => isset($fcl) ? $fcl : null,
        ];
    }

    // --- dependencias: tablas/vistas que usa este procedure ---
    // GROUP BY: RDB$DEPENDENCIES guarda una fila por columna referenciada
    // (índice a nivel de campo), así que una tabla usada en N columnas
    // aparece N veces — al usuario solo interesa una vez.
    $deps = [];
    $depRows = $pdo->query("SELECT CAST(d.RDB\$DEPENDED_ON_NAME AS VARCHAR(64)) AS objeto, CAST(d.RDB\$DEPENDED_ON_TYPE AS INTEGER) AS tipo FROM RDB\$DEPENDENCIES d WHERE d.RDB\$DEPENDENT_NAME = '{$nombre}' AND CAST(d.RDB\$DEPENDED_ON_TYPE AS INTEGER) IN (0,1) GROUP BY 1, 2 ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($depRows as $d) {
        $obj = trim($d['OBJETO'] ?? '');
        if ($obj === '') continue;
        $deps[] = ['objeto' => $obj, 'tipo' => (int)$d['TIPO']]; // 0=tabla, 1=vista
    }

    // --- inverso: qué usa este procedure (triggers/procedures que lo llaman) ---
    $callers = [];
    $callRows = $pdo->query("SELECT CAST(d.RDB\$DEPENDENT_NAME AS VARCHAR(64)) AS objeto, CAST(d.RDB\$DEPENDENT_TYPE AS INTEGER) AS tipo FROM RDB\$DEPENDENCIES d WHERE d.RDB\$DEPENDED_ON_NAME = '{$nombre}' AND CAST(d.RDB\$DEPENDENT_TYPE AS INTEGER) IN (2,9) GROUP BY 1, 2 ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($callRows as $d) {
        $obj = trim($d['OBJETO'] ?? '');
        if ($obj === '') continue;
        $callers[] = ['objeto' => $obj, 'tipo' => (int)$d['TIPO']]; // 2=trigger, 9=procedure
    }

    Response::success([
        'nombre'      => trim($info['NOMBRE'] ?? $nombre),
        'tipo'        => (int)($info['TIPO'] ?? 1),
        'sistema'     => (int)($info['SISTEMA'] ?? 0),
        'descripcion' => trim((string)($info['DESCRIPCION'] ?? '')),
        'fuente'      => $info['FUENTE'] ?? '',
        'params'      => $params,
        'dependencias'=> $deps,
        'callers'     => $callers,
    ]);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
