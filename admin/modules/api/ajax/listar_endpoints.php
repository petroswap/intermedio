<?php
header("Content-Type: application/json; charset=UTF-8");
ob_clean();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    // Cargar registry de endpoints
    $registryFile = __DIR__ . '/../../../config/endpoints_registry.php';
    $registry = [];
    if (file_exists($registryFile)) {
        $registry = require $registryFile;
    }

    // Cargar endpoints guardados por usuario
    $savedFile = __DIR__ . '/../../../config/endpoints.json';
    $saved = [];
    if (file_exists($savedFile)) {
        $content = file_get_contents($savedFile);
        $saved = json_decode($content, true) ?? [];
    }

    // Combinar: registry primero, luego los guardados
    $all = array_merge($registry, $saved);

    Response::success($all, "Endpoints cargados");
} catch (Exception $e) {
    Response::error($e->getMessage());
}
