<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $scriptsDir = dirname(__DIR__, 2) . '/scripts/';
    $scriptsDir = realpath($scriptsDir) ?: $scriptsDir;
    $scripts = [];
    
    if (is_dir($scriptsDir)) {
        $files = glob($scriptsDir . '/*.php');
        
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
    
    Response::success($scripts);
} catch (Exception $e) {
    Response::error('Error al listar scripts: ' . $e->getMessage());
}
