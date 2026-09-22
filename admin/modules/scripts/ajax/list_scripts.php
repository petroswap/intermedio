<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

try {
    $adminDir = dirname(dirname(dirname(__DIR__)));
    $scriptsDir = $adminDir . '/scripts';
    $scripts = [];
    
    $scriptsConfig = [
        'listar_tablas' => [
            'name' => 'Listar Tablas',
            'description' => 'Muestra todas las tablas de Firebird',
            'params' => []
        ],
        'test_conexion' => [
            'name' => 'Test Conexión',
            'description' => 'Verifica conexión y muestra info del servidor',
            'params' => []
        ],
        'obtener_ventas_historicas' => [
            'name' => 'Obtener Ventas Históricas',
            'description' => 'Suma de litros por día y estación',
            'params' => [
                ['name' => 'productos', 'label' => 'Productos', 'type' => 'text', 'placeholder' => '1,2,5', 'default' => '1'],
                ['name' => 'desde', 'label' => 'Desde', 'type' => 'text', 'placeholder' => '2026-01-01 00:00:00', 'default' => '2026-01-01 00:00:00'],
                ['name' => 'hasta', 'label' => 'Hasta', 'type' => 'text', 'placeholder' => '2026-12-31 23:59:59', 'default' => date('Y-m-d') . ' 23:59:59']
            ]
        ],
        'test_ventas_historicas' => [
            'name' => 'Test Ventas Históricas',
            'description' => 'Prueba con datos de ejemplo',
            'params' => []
        ]
    ];
    
    if (is_dir($scriptsDir)) {
        $files = glob($scriptsDir . '/*.php');
        
        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                $info = pathinfo($file);
                $scriptId = $info['filename'];
                
                if ($filename === 'script_guard.php') {
                    continue;
                }
                
                $config = $scriptsConfig[$scriptId] ?? [
                    'name' => ucwords(str_replace('_', ' ', $scriptId)),
                    'description' => 'Script personalizado',
                    'params' => []
                ];
                
                $scripts[] = [
                    'id' => $scriptId,
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'params' => $config['params'],
                    'file' => $filename
                ];
            }
        }
    }
    
    Response::success($scripts);
} catch (Exception $e) {
    Response::error('Error al listar scripts: ' . $e->getMessage());
}
