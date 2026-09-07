<?php
header("Content-Type: application/json; charset=UTF-8");
ob_clean();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';

try {
    $request = new Request();

    $name = trim($request->get('name', ''));
    $table = trim($request->get('table', ''));
    $type = trim($request->get('type', 'list'));
    $method = trim($request->get('method', 'POST'));
    $description = trim($request->get('description', ''));
    $config = $request->get('config', '{}');

    if (empty($name)) {
        Response::error("El nombre es obligatorio");
    }
    if (empty($table)) {
        Response::error("La tabla es obligatoria");
    }

    $configData = json_decode($config, true);
    if ($configData === null) {
        $configData = [];
    }

    $endpoint = [
        'id' => uniqid('ep_'),
        'name' => $name,
        'description' => $description,
        'table' => $table,
        'type' => $type,
        'method' => strtoupper($method),
        'config' => $configData,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $endpointsFile = __DIR__ . '/../../../config/endpoints.json';
    $endpoints = [];

    if (file_exists($endpointsFile)) {
        $content = file_get_contents($endpointsFile);
        $endpoints = json_decode($content, true) ?? [];
    }

    $endpoints[] = $endpoint;

    file_put_contents($endpointsFile, json_encode($endpoints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    Response::success($endpoint, "Endpoint guardado correctamente");
} catch (Exception $e) {
    Response::error($e->getMessage());
}
