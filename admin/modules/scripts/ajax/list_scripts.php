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
                
                if ($filename === 'script_guard.php') {
                    continue;
                }
                
                $info = pathinfo($file);
                $scriptId = $info['filename'];
                
                // Read script metadata from docblock and $SCRIPT_CONFIG
                $meta = readScriptMeta($file);
                
                $scripts[] = [
                    'id' => $scriptId,
                    'name' => $meta['name'] ?? ucwords(str_replace('_', ' ', $scriptId)),
                    'description' => $meta['description'] ?? '',
                    'method' => $meta['method'] ?? 'POST',
                    'output' => $meta['output'] ?? 'text',
                    'params' => $meta['params'] ?? [],
                    'file' => $filename
                ];
            }
        }
    }
    
    usort($scripts, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    Response::success($scripts);
} catch (Exception $e) {
    Response::error('Error al listar scripts: ' . $e->getMessage());
}

function readScriptMeta($file) {
    $content = file_get_contents($file);
    $meta = [];
    
    // Read docblock annotations
    if (preg_match_all('/@(\w+)\s+(.+)/', $content, $matches)) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            $key = strtolower($matches[1][$i]);
            $meta[$key] = trim($matches[2][$i]);
        }
    }
    
    // Read $SCRIPT_CONFIG (evaluate the file safely)
    $config = extractScriptConfig($file);
    if ($config) {
        $meta['params'] = $config['params'] ?? [];
    }
    
    return $meta;
}

function extractScriptConfig($file) {
    $content = file_get_contents($file);
    
    // Find $SCRIPT_CONFIG = [...]; block
    if (preg_match('/\$SCRIPT_CONFIG\s*=\s*(\[.*?\]);/s', $content, $match)) {
        // Safe evaluation - only allow array structure
        $configStr = $match[1];
        
        // Convert PHP array syntax to JSON-like for parsing
        // Replace => with :, remove quotes around keys
        $configStr = preg_replace("/'([^']*)'/", '"$1"', $configStr);
        
        $jsonStr = preg_replace('/(\w+)\s*=>/', '"$1":', $configStr);
        
        $config = json_decode($jsonStr, true);
        
        if ($config && is_array($config)) {
            return $config;
        }
    }
    
    return null;
}
