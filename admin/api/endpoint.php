<?php
/**
 * Endpoint Genérico - Dispatcher
 * 
 * Maneja operaciones CRUD genéricas y SQL personalizado
 * Lee la configuración de la tabla y ejecuta la operación
 * 
 * Uso: endpoint.php?action=list|get|create|update|delete|sql
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ob_clean();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/CRUD.php';

try {
    $request = new Request();
    $action = $request->get('action', $_GET['action'] ?? 'list');
    $table = strtoupper(trim($request->get('table', '')));

    if (empty($table) && $action !== 'sql') {
        Response::error("El parámetro 'table' es obligatorio");
    }

    $db = Database::getInstance(DB_CONFIG);

    switch ($action) {
        case 'list':
            handleList($request, $db, $table);
            break;

        case 'get':
            handleGet($request, $db, $table);
            break;

        case 'create':
            handleCreate($request, $db, $table);
            break;

        case 'update':
            handleUpdate($request, $db, $table);
            break;

        case 'delete':
            handleDelete($request, $db, $table);
            break;

        case 'sql':
            handleSql($request, $db);
            break;

        default:
            Response::error("Acción no válida: {$action}. Use: list, get, create, update, delete, sql");
    }

} catch (PDOException $e) {
    Response::error("Error de BD: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}

// ============================================
// HANDLERS
// ============================================

function handleList($request, $db, $table) {
    $page = max(1, (int) $request->get('page', 1));
    $perPage = min(100, max(1, (int) $request->get('per_page', 25)));
    $offset = ($page - 1) * $perPage;
    $orderField = strtoupper(trim($request->get('order', '')));
    $orderDir = strtoupper(trim($request->get('order_dir', 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';

    // Auto-detect primary key if no order specified
    if (empty($orderField)) {
        $pk = $db->getPrimaryKey($table);
        $orderField = $pk ?: '1';
    }

    // Recoger filtros dinámicos
    $filters = [];
    for ($i = 0; $i < 20; $i++) {
        $field = $request->get("filter_field_{$i}");
        $value = $request->get("filter_value_{$i}");
        $operator = $request->get("filter_operator_{$i}", '=');

        if ($field && $value !== null && $value !== '') {
            $filters[strtoupper($field)] = ['op' => $operator, 'value' => $value];
        }
    }

    $crud = new CRUD($db, $table);

    // Construir filtros para el CRUD
    $crudFilters = [];
    foreach ($filters as $field => $condition) {
        $crudFilters[$field] = $condition['value'];
    }

    $order = [$orderField => $orderDir];
    $data = $crud->getAll($crudFilters, $order, $perPage, $offset);
    $total = $crud->count($crudFilters);

    Response::success([
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]);
}

function handleGet($request, $db, $table) {
    $id = (int) $request->get('id', 0);

    if ($id <= 0) {
        Response::error("El parámetro 'id' es obligatorio y debe ser un número positivo", 400);
    }

    $crud = new CRUD($db, $table);
    $data = $crud->getById($id);

    if (!$data) {
        Response::error("Registro no encontrado", 404);
    }

    Response::success($data);
}

function handleCreate($request, $db, $table) {
    $dataStr = $request->get('data', '');

    if (empty($dataStr)) {
        Response::error("El parámetro 'data' es obligatorio (JSON)", 400);
    }

    $data = json_decode($dataStr, true);
    if (!is_array($data) || empty($data)) {
        Response::error("El parámetro 'data' debe ser un JSON válido con al menos un campo", 400);
    }

    $crud = new CRUD($db, $table);
    $newId = $crud->create($data);

    if (!$newId) {
        Response::error("No se pudo crear el registro", 500);
    }

    $newRecord = $crud->getById($newId);
    Response::success($newRecord, "Registro creado correctamente", 201);
}

function handleUpdate($request, $db, $table) {
    $id = (int) $request->get('id', 0);
    $dataStr = $request->get('data', '');

    if ($id <= 0) {
        Response::error("El parámetro 'id' es obligatorio", 400);
    }

    if (empty($dataStr)) {
        Response::error("El parámetro 'data' es obligatorio (JSON)", 400);
    }

    $data = json_decode($dataStr, true);
    if (!is_array($data) || empty($data)) {
        Response::error("El parámetro 'data' debe ser un JSON válido", 400);
    }

    $crud = new CRUD($db, $table);
    $existing = $crud->getById($id);

    if (!$existing) {
        Response::error("Registro no encontrado", 404);
    }

    $updated = $crud->update($id, $data);

    if (!$updated) {
        Response::error("No se pudo actualizar el registro", 500);
    }

    $updatedRecord = $crud->getById($id);
    Response::success($updatedRecord, "Registro actualizado correctamente");
}

function handleDelete($request, $db, $table) {
    $id = (int) $request->get('id', 0);

    if ($id <= 0) {
        Response::error("El parámetro 'id' es obligatorio", 400);
    }

    $crud = new CRUD($db, $table);
    $existing = $crud->getById($id);

    if (!$existing) {
        Response::error("Registro no encontrado", 404);
    }

    $deleted = $crud->delete($id);

    if (!$deleted) {
        Response::error("No se pudo eliminar el registro", 500);
    }

    Response::success(null, "Registro eliminado correctamente");
}

function handleSql($request, $db) {
    $sql = trim($request->get('sql', ''));

    if (empty($sql)) {
        Response::error("El parámetro 'sql' es obligatorio", 400);
    }

    // Validar que sea SELECT
    $upperSql = strtoupper(ltrim($sql));
    if (stripos($upperSql, 'SELECT') !== 0 && stripos($upperSql, 'WITH') !== 0) {
        Response::error("Solo se permiten consultas SELECT o WITH (CTE)", 400);
    }

    // Bloquear peligros
    $blocked = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE'];
    foreach ($blocked as $word) {
        if (preg_match('/\b' . $word . '\b/i', $sql)) {
            Response::error("Operación no permitida: {$word}", 400);
        }
    }

    $pdo = $db->getConnection();
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success([
        'data' => $data,
        'total' => count($data),
        'sql' => $sql
    ]);
}
