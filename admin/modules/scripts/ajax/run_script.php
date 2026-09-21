<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $scriptId = $_POST['script_id'] ?? '';
    $params = $_POST['params'] ?? [];
    
    if (empty($scriptId)) {
        Response::error('Script ID requerido');
    }
    
    $scriptsDir = dirname(__DIR__, 2) . '/scripts/';
    $scriptsDir = realpath($scriptsDir) ?: $scriptsDir;
    $scriptFile = $scriptsDir . '/' . basename($scriptId) . '.php';
    
    if (!file_exists($scriptFile)) {
        Response::error('Script no encontrado: ' . $scriptId);
    }
    
    ob_start();
    include $scriptFile;
    $output = ob_get_clean();
    
    Response::success([
        'script' => $scriptId,
        'output' => $output
    ]);
} catch (Exception $e) {
    Response::error('Error al ejecutar script: ' . $e->getMessage());
}
