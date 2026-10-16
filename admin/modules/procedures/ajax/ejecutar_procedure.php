<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

// Misma lógica que run_script.php: ejecutar requiere haber verificado la contraseña
// (verify_password.php — misma sesión scripts_auth / misma SCRIPTS_PASSWORD)
if (empty($_SESSION['scripts_auth'])) {
    $err = SqlError::make('validation', 'No autenticado. Introduce la contraseña primero.', [
        'label' => 'Autenticación requerida',
        'hint'  => 'La ejecución de procedures requiere la misma contraseña que los scripts.',
    ]);
    Response::error(SqlError::message($err), 401, ['error' => $err]);
}

/**
 * Quita comentarios SQL y colapsa espacios (para validar la primera palabra).
 */
function procStripComments(string $sql): string
{
    $noBlock = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $noLine  = preg_replace('/--.*$/m', '', (string)$noBlock);
    return trim((string)$noLine);
}

try {
    $modo = $_POST['modo'] ?? 'procedure'; // procedure | block

    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $startTime = microtime(true);

    if ($modo === 'block') {
        // ---- Bloque anónimo EXECUTE BLOCK (código propio, no se crea en la BD) ----
        $code = $_POST['code'] ?? '';
        if (empty(trim($code))) {
            $err = SqlError::make('validation', 'No se envió código para el bloque anónimo (parámetro code vacío).', [
                'label' => 'Bloque vacío',
                'hint'  => 'Escribe un bloque EXECUTE BLOCK ... AS BEGIN ... END en el editor.',
            ]);
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }

        $limpio = procStripComments($code);
        $compact = preg_replace('/\s+/', ' ', $limpio);
        $upper = mb_strtoupper($compact);

        if (!preg_match('/^EXECUTE\s+BLOCK\b/', $upper)) {
            $err = SqlError::make('validation', 'El código debe comenzar por EXECUTE BLOCK. Ejemplo: EXECUTE BLOCK AS BEGIN ... END', [
                'label' => 'Bloque no válido',
                'hint'  => 'Esta zona ejecuta bloques anónimos EXECUTE BLOCK (no se guardan en la BD). Envuelve tu código en EXECUTE BLOCK ... AS BEGIN ... END.',
            ]);
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }

        // DDL no permitido (EXECUTE BLOCK de Firebird lo rechaza, pero validamos antes para mejor mensaje)
        if (preg_match('/\b(CREATE|ALTER|DROP|GRANT|REVOKE)\b/', $upper)) {
            $err = SqlError::make('validation', 'El bloque anónimo no puede contener sentencias DDL (CREATE/ALTER/DROP/GRANT/REVOKE).', [
                'label' => 'DDL no permitido',
                'hint'  => 'EXECUTE BLOCK sirve para lógica y DML temporal; no para definir objetos en la BD.',
            ]);
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }

        $stmt = $pdo->query($code);
        $data = ($stmt !== false) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        $data = array_map(function ($row) {
            return array_map(function ($val) {
                return is_string($val) ? trim($val) : $val;
            }, $row);
        }, $data);

        Response::success([
            'data'      => $data,
            'rows'      => count($data),
            'time'      => $elapsed,
            'mode'      => 'block',
            'persistio' => true, // el bloque pudo modificar datos; Firebird confirma la transacción
        ]);

    } else {
        // ---- Procedure existente: EXECUTE PROCEDURE nombre(args) ----
        $nombre = $_POST['nombre'] ?? '';
        if (empty($nombre)) {
            $err = SqlError::make('invalid_input', 'No se indicó el procedure (parámetro nombre vacío).');
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $nombre)) {
            $err = SqlError::make('invalid_input', "Nombre de procedure no válido: «{$nombre}». Solo se admiten letras, números y guion bajo.");
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }

        // comprobar que existe y obtener tipo
        $chk = $pdo->query("SELECT CAST(p.RDB\$PROCEDURE_TYPE AS INTEGER) AS tipo FROM RDB\$PROCEDURES p WHERE p.RDB\$PROCEDURE_NAME = '{$nombre}'")->fetch(PDO::FETCH_ASSOC);
        if (!$chk) {
            $err = SqlError::make('table_not_found', "El procedure «{$nombre}» no existe en la base de datos.");
            Response::error(SqlError::message($err), 404, ['error' => $err]);
        }
        $tipo = (int)($chk['TIPO'] ?? 1);

        // parámetros posicionales desde params[] (array de strings/numeros)
        $params = $_POST['params'] ?? [];
        if (!is_array($params)) $params = ($params === '' || $params === null) ? [] : [$params];

        $placeholders = [];
        $bindValues = [];
        foreach (array_values($params) as $i => $v) {
            $ph = ':p' . $i;
            $placeholders[] = $ph;
            // vacío → NULL (Firebird distingue '' de NULL en parámetros)
            $bindValues[$ph] = ($v === '' || $v === null) ? null : $v;
        }
        $argList = $placeholders ? '(' . implode(', ', $placeholders) . ')' : '';

        // tipo 1 (seleccionable): SELECT * FROM proc(...) devuelve todas las filas (SUSPEND incluido)
        // tipo 2 (ejecutable): EXECUTE PROCEDURE proc(...) devuelve params de salida como fila
        $sql = ($tipo === 2)
            ? "EXECUTE PROCEDURE {$nombre}{$argList}"
            : "SELECT * FROM {$nombre}{$argList}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindValues);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        $data = array_map(function ($row) {
            return array_map(function ($val) {
                return is_string($val) ? trim($val) : $val;
            }, $row);
        }, $data);

        Response::success([
            'data'      => $data,
            'rows'      => count($data),
            'time'      => $elapsed,
            'mode'      => 'procedure',
            'tipo'      => $tipo,
            'sql'       => $sql, // para transparencia (sin values)
            'persistio' => $tipo === 2, // procedures tipo 2 suelen hacer DML; el usuario lo confirma
        ]);
    }
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
