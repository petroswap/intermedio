<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $adminDir = dirname(__DIR__, 2);
    $scriptsDir = $adminDir . DIRECTORY_SEPARATOR . 'scripts';
    $scripts = [];
    
    if (is_dir($scriptsDir)) {
        $files = glob($scriptsDir . DIRECTORY_SEPARATOR . '*.php');
        
        if ($files) {
            foreach ($files as $file) {
                $info = pathinfo($file);
                $scriptId = $info['filename'];
                
                $scripts[] = [
                    'id' => $scriptId,
                    'name' => ucwords(str_replace('_', ' ', $scriptId)),
                    'description' => 'Script personalizado',
                    'file' => basename($file)
                ];
            }
        }
    }
    
    Response::success($scripts);
} catch (Exception $e) {
    Response::error('Error al listar scripts: ' . $e->getMessage());
}
