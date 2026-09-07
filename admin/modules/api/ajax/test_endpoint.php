<?php
header("Content-Type: application/json; charset=UTF-8");
ob_clean();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/CRUD.php';

try {
    $request = new Request();

    $endpointId = trim($request->get('endpoint_id', ''));
    $table = trim($request->get('table', ''));
    $type = trim($request->get('type', 'list'));
    $config = $request->get('config', '{}');

    if (empty($table)) {
        Response::error("La tabla es obligatoria");
    }

    $configData = json_decode($config, true);
    if ($configData === null) {
        $configData = [];
    }

    $db = Database::getInstance(DB_CONFIG);
    $crud = new CRUD($db, $table);

    $result = null;

    switch ($type) {
        case 'list':
            $filters = $configData['filters'] ?? [];
            $order = $configData['order'] ?? [];
            $perPage = (int)($configData['per_page'] ?? 5);
            $perPage = min($perPage, 25);
            $data = $crud->getAll($filters, $order, $perPage, 0);
            $total = $crud->count($filters);
            $result = [
                'data' => $data,
                'total' => $total,
                'page' => 1,
                'per_page' => $perPage,
                'type' => 'list'
            ];
            break;

        case 'get':
            $idField = $configData['id_field'] ?? 'ID';
            $testId = $configData['test_id'] ?? null;
            if ($testId) {
                $data = $crud->getById($testId);
                $result = ['data' => $data, 'type' => 'get'];
            } else {
                $data = $crud->getAll([], [], 1, 0);
                $result = ['data' => $data, 'type' => 'get', 'note' => 'Usando primer registro como ejemplo'];
            }
            break;

        case 'create':
            $fields = $configData['fields'] ?? [];
            $sample = [];
            foreach ($fields as $f) {
                $sample[$f['name']] = $f['sample'] ?? '';
            }
            $result = ['data' => $sample, 'type' => 'create', 'note' => 'Datos de ejemplo (no se ejecuta INSERT)'];
            break;

        case 'update':
            $idField = $configData['id_field'] ?? 'ID';
            $testId = $configData['test_id'] ?? null;
            $fields = $configData['fields'] ?? [];
            $sample = [];
            foreach ($fields as $f) {
                $sample[$f['name']] = $f['sample'] ?? '';
            }
            $result = ['data' => ['id' => $testId, 'updates' => $sample], 'type' => 'update', 'note' => 'Datos de ejemplo (no se ejecuta UPDATE)'];
            break;

        case 'delete':
            $result = ['data' => null, 'type' => 'delete', 'note' => 'Endpoint de eliminación - no se ejecuta en prueba'];
            break;

        case 'custom':
            $sql = $configData['sql'] ?? '';
            if (!empty($sql) && stripos(trim($sql), 'SELECT') === 0) {
                $pdo = $db->getConnection();
                $stmt = $pdo->query($sql);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $result = ['data' => $data, 'type' => 'custom', 'sql' => $sql];
            } else {
                $result = ['data' => null, 'type' => 'custom', 'note' => 'SQL vacío o no es SELECT'];
            }
            break;

        default:
            Response::error("Tipo de endpoint no válido: {$type}");
    }

    Response::success($result, "Prueba ejecutada correctamente");
} catch (PDOException $e) {
    Response::error("Error de BD: " . $e->getMessage());
} catch (Exception $e) {
    Response::error($e->getMessage());
}
