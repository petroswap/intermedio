<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';

$favoritesFile = __DIR__ . '/../../../data/favorites.json';

function loadFavorites($file) {
    if (!file_exists($file)) {
        return ['version' => 1, 'favorites' => []];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (!is_array($data) || !isset($data['favorites'])) {
        return ['version' => 1, 'favorites' => []];
    }
    return $data;
}

function saveFavorites($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($method === 'GET' && $action === 'export') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="fuelops_favoritos.json"');
        readfile($favoritesFile);
        exit;
    }

    if ($method === 'GET') {
        $data = loadFavorites($favoritesFile);
        Response::success($data['favorites']);
        exit;
    }

    if ($method === 'POST') {
        if ($action === 'import') {
            $importRaw = $_POST['favorites'] ?? '[]';
            $importFavorites = json_decode($importRaw, true);
            if (!is_array($importFavorites)) {
                Response::error('Formato de importación no válido');
                exit;
            }

            $data = loadFavorites($favoritesFile);
            $existing = $data['favorites'];
            $existingIds = array_column($existing, 'id');

            $added = 0;
            $updated = 0;
            foreach ($importFavorites as $fav) {
                if (empty($fav['id']) || empty($fav['sql'])) continue;
                $idx = array_search($fav['id'], $existingIds);
                if ($idx !== false) {
                    $existing[$idx] = $fav;
                    $updated++;
                } else {
                    $existing[] = $fav;
                    $added++;
                }
            }

            $data['favorites'] = $existing;
            saveFavorites($favoritesFile, $data);
            Response::success([
                'total' => count($existing),
                'added' => $added,
                'updated' => $updated
            ]);
            exit;
        }

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $sql = trim($_POST['sql'] ?? '');
            $table = trim($_POST['table'] ?? '');

            if (empty($sql)) {
                Response::error('SQL es requerido');
                exit;
            }

            $data = loadFavorites($favoritesFile);
            $newFav = [
                'id' => 'f_' . time() . '_' . substr(md5($sql . $name), 0, 8),
                'name' => $name ?: 'Sin nombre',
                'sql' => $sql,
                'table' => $table,
                'created' => date('c')
            ];
            $data['favorites'][] = $newFav;
            saveFavorites($favoritesFile, $data);
            Response::success($newFav);
            exit;
        }

        if ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                Response::error('ID requerido');
                exit;
            }

            $data = loadFavorites($favoritesFile);
            $data['favorites'] = array_values(array_filter($data['favorites'], function($f) use ($id) {
                return $f['id'] !== $id;
            }));
            saveFavorites($favoritesFile, $data);
            Response::success(['total' => count($data['favorites'])]);
            exit;
        }

        Response::error('Acción no válida');
        exit;
    }

    Response::error('Método no permitido');
} catch (Exception $e) {
    Response::error('Error en favoritos');
}
