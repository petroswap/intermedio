<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $adminDir = dirname(dirname(dirname(__DIR__)));
    $scriptsDir = $adminDir . '/scripts';
    $scripts = [];
    
    if (is_dir($scriptsDir)) {
        $files = glob($scriptsDir . '/*.php');
        
        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                
                if ($filename === 'index.php' || $filename === 'script_guard.php') {
                    continue;
                }
                
                $info = pathinfo($file);
                $scriptId = $info['filename'];
                
                $scripts[] = [
                    'id' => $scriptId,
                    'name' => ucwords(str_replace('_', ' ', $scriptId)),
                    'description' => 'Script personalizado',
                    'file' => $filename
                ];
            }
        }
    }
    
    Response::success($scripts);
} catch (Exception $e) {
    Response::error('Error al listar scripts: ' . $e->getMessage());
}
