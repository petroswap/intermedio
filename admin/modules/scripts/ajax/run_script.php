<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    if (empty($_SESSION['scripts_auth'])) {
        Response::error('No autenticado. Introduce la contraseña primero.', 401);
    }
    
    $scriptId = $_POST['script_id'] ?? '';
    $params = $_POST['params'] ?? [];
    
    if (empty($scriptId)) {
        Response::error('Script ID requerido');
    }
    
    $adminDir = dirname(dirname(dirname(__DIR__)));
    $scriptsDir = $adminDir . '/scripts';
    $scriptFile = $scriptsDir . '/' . basename($scriptId) . '.php';
    
    if (!file_exists($scriptFile)) {
        Response::error('Script no encontrado: ' . $scriptId);
    }
    
    // Pass params to script via $_POST
    foreach ($params as $key => $value) {
        $_POST[$key] = $value;
    }
    
    $_SESSION['scripts_running'] = true;
    
    $startTime = microtime(true);
    
    ob_start();
    include $scriptFile;
    $output = ob_get_clean();
    
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    
    $_SESSION['scripts_running'] = false;
    
    // Detect if output is JSON
    $isJson = false;
    $jsonData = null;
    $trimmedOutput = trim($output);
    if ($trimmedOutput[0] === '{' || $trimmedOutput[0] === '[') {
        $jsonData = json_decode($trimmedOutput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $isJson = true;
        }
    }
    
    Response::success([
        'script' => $scriptId,
        'output' => $output,
        'elapsed' => $elapsed,
        'is_json' => $isJson,
        'json_data' => $jsonData,
        'size' => strlen($output)
    ]);
} catch (Exception $e) {
    $_SESSION['scripts_running'] = false;
    Response::error('Error al ejecutar script: ' . $e->getMessage());
}
