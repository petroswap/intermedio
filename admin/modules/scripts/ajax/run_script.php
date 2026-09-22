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
    
    if (empty($scriptId)) {
        Response::error('Script ID requerido');
    }
    
    $adminDir = dirname(dirname(dirname(__DIR__)));
    $scriptsDir = $adminDir . '/scripts';
    $scriptFile = $scriptsDir . '/' . basename($scriptId) . '.php';
    
    if (!file_exists($scriptFile)) {
        Response::error('Script no encontrado: ' . $scriptId);
    }
    
    $_SESSION['scripts_running'] = true;
    
    ob_start();
    include $scriptFile;
    $output = ob_get_clean();
    
    $_SESSION['scripts_running'] = false;
    
    Response::success([
        'script' => $scriptId,
        'output' => $output
    ]);
} catch (Exception $e) {
    $_SESSION['scripts_running'] = false;
    Response::error('Error al ejecutar script: ' . $e->getMessage());
}
