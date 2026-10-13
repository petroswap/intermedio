<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/SqlError.php';

/**
 * Divide un script SQL en sentencias individuales respetando:
 * - cadenas '...' con escape '' y "..." con escape ""
 * - comentarios de línea --
 * - comentarios de bloque /* ... *\/
 * Los ';' dentro de cadenas/comentarios no separan.
 */
function sqlSplitStatements(string $sql): array
{
    $stmts = [];
    $current = '';
    $len = strlen($sql);
    $i = 0;

    while ($i < $len) {
        $ch = $sql[$i];

        // Comentario de línea
        if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            $j = strpos($sql, "\n", $i);
            if ($j === false) {
                $current .= substr($sql, $i);
                $i = $len;
            } else {
                $current .= substr($sql, $i, $j - $i + 1);
                $i = $j + 1;
            }
            continue;
        }

        // Comentario de bloque
        if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $j = strpos($sql, '*/', $i + 2);
            if ($j === false) {
                $current .= substr($sql, $i);
                $i = $len;
            } else {
                $current .= substr($sql, $i, $j + 2 - $i);
                $i = $j + 2;
            }
            continue;
        }

        // Cadena '...'
        if ($ch === "'") {
            $current .= $ch;
            $i++;
            while ($i < $len) {
                $current .= $sql[$i];
                if ($sql[$i] === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $current .= $sql[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $i++;
                    break;
                }
                $i++;
            }
            continue;
        }

        // Identificador "..."
        if ($ch === '"') {
            $current .= $ch;
            $i++;
            while ($i < $len) {
                $current .= $sql[$i];
                if ($sql[$i] === '"') {
                    if ($i + 1 < $len && $sql[$i + 1] === '"') {
                        $current .= $sql[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $i++;
                    break;
                }
                $i++;
            }
            continue;
        }

        // Fin de sentencia
        if ($ch === ';') {
            $trimmed = trim($current);
            if ($trimmed !== '' && !isCommentOnly($trimmed)) {
                $stmts[] = $trimmed;
            }
            $current = '';
            $i++;
            continue;
        }

        $current .= $ch;
        $i++;
    }

    $trimmed = trim($current);
    if ($trimmed !== '' && !isCommentOnly($trimmed)) {
        $stmts[] = $trimmed;
    }

    return $stmts;
}

/**
 * ¿La sentencia contiene solo comentarios (sin SQL real)?
 */
function isCommentOnly(string $stmt): bool
{
    $noBlock = preg_replace('/\/\*.*?\*\//s', '', $stmt);
    $noLine  = preg_replace('/--.*$/m', '', (string)$noBlock);
    return trim((string)$noLine) === '';
}

/**
 * Clasifica una sentencia. Devuelve el tipo o null si no está permitida.
 * Tipos: select | with | set_ro | rollback
 */
function classifyStatement(string $stmt): ?string
{
    $compact = preg_replace('/\s+/', ' ', trim($stmt));
    $upper = mb_strtoupper($compact);

    if (preg_match('/^SELECT\b/', $upper)) {
        return 'select';
    }
    if (preg_match('/^WITH\b/', $upper)) {
        return 'with';
    }
    // Solo READ ONLY: rechaza READ WRITE u otros modos
    if (preg_match('/^SET\s+TRANSACTION\b/', $upper)) {
        if (preg_match('/READ\s+ONLY\b/', $upper) && !preg_match('/READ\s+WRITE\b/', $upper)) {
            return 'set_ro';
        }
        return null;
    }
    if (preg_match('/^ROLLBACK(\s+WORK)?\b/', $upper)) {
        return 'rollback';
    }

    return null;
}

try {
    $sql = $_POST['sql'] ?? '';
    if (empty($sql) || trim($sql) === '') {
        $err = SqlError::make('validation', 'No se envió ninguna consulta SQL (parámetro sql vacío).', [
            'label' => 'Consulta vacía',
            'hint'  => 'Escribe una consulta SELECT (o WITH) en el editor.',
        ]);
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    $stmts = sqlSplitStatements($sql);

    if (empty($stmts)) {
        $err = SqlError::make('validation', 'La consulta no contiene sentencias SQL válidas (solo comentarios).', [
            'label' => 'Consulta vacía',
            'hint'  => 'Escribe una consulta SELECT (o WITH) en el editor.',
        ]);
        Response::error(SqlError::message($err), 400, ['error' => $err]);
    }

    // Validar TODAS las sentencias antes de ejecutar cualquiera
    foreach ($stmts as $idx => $stmtSql) {
        $type = classifyStatement($stmtSql);
        if ($type === null) {
            $compact = preg_replace('/\s+/', ' ', trim($stmtSql));
            $primeraPalabra = '';
            if (preg_match('/^([A-Za-z_]+)/', $compact, $m)) {
                $primeraPalabra = mb_strtoupper($m[1]);
            }
            $num = $idx + 1;
            $total = count($stmts);
            $detalle = $primeraPalabra !== ''
                ? "Sentencia #{$num}/{$total} comienza por «{$primeraPalabra}». Solo se permiten: SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK."
                : "Sentencia #{$num}/{$total} no permitida. Solo se permiten: SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK.";
            $err = SqlError::make('validation', $detalle, [
                'hint' => 'Esta consola es de solo lectura: se permiten SELECT, WITH, SET TRANSACTION READ ONLY y ROLLBACK. Retira la sentencia que modifica datos.',
            ]);
            Response::error(SqlError::message($err), 400, ['error' => $err]);
        }
    }

    $db = Database::getInstance(DB_CONFIG);
    $pdo = $db->getConnection();

    $hasSetReadOnly = false;
    foreach ($stmts as $stmtSql) {
        if (classifyStatement($stmtSql) === 'set_ro') {
            $hasSetReadOnly = true;
            break;
        }
    }

    // pdo_firebird necesita "preparar" la conexión antes de aceptar SET TRANSACTION
    // (un COMMIT/ROLLBACK fallido limpia el handle de transacción residual)
    if ($hasSetReadOnly) {
        try {
            $pdo->query('COMMIT');
        } catch (Throwable $e) {
            // esperado en conexión fresca: ignorar
        }
    }

    $startTime = microtime(true);

    $data = [];
    $rowsAffectedTotal = 0;

    foreach ($stmts as $stmtSql) {
        $type = classifyStatement($stmtSql);

        if ($type === 'set_ro') {
            $pdo->query($stmtSql);
            continue;
        }

        if ($type === 'rollback') {
            // ROLLBACK vía API PDO: query('ROLLBACK') falla con pdo_firebird tras SET TRANSACTION
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            } else {
                try {
                    $pdo->query($stmtSql);
                } catch (Throwable $e) {
                    // sin transacción activa: no hay nada que revertir
                }
            }
            continue;
        }

        // select | with
        $stmt = $pdo->query($stmtSql);
        if ($stmt !== false) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Seguridad: si hubo SET READ ONLY y el script no incluyó ROLLBACK explícito, cerrar transacción
    if ($hasSetReadOnly && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Throwable $e) {
            // ignorar
        }
    }

    $elapsed = round((microtime(true) - $startTime) * 1000, 2);

    $data = array_map(function ($row) {
        return array_map(function ($val) {
            return is_string($val) ? trim($val) : $val;
        }, $row);
    }, $data);

    Response::success([
        'data'       => $data,
        'rows'       => count($data),
        'time'       => $elapsed,
        'statements' => count($stmts),
    ]);
} catch (PDOException $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 400, ['error' => $err]);
} catch (Throwable $e) {
    $err = SqlError::fromException($e);
    Response::error(SqlError::message($err), 500, ['error' => $err]);
}
