<?php
header("Content-Type: application/json; charset=UTF-8");
ob_clean();

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../core/Response.php';
require_once __DIR__ . '/../../../core/Request.php';
require_once __DIR__ . '/../../../core/Database.php';

try {
    $request = new Request();

    $table = trim($request->get('table', ''));
    $type = trim($request->get('type', 'list'));
    $name = trim($request->get('name', 'endpoint'));
    $config = $request->get('config', '{}');

    if (empty($table)) {
        Response::error("La tabla es obligatoria");
    }

    $configData = json_decode($config, true);
    if ($configData === null) {
        $configData = [];
    }

    $db = Database::getInstance(DB_CONFIG);
    $columns = $db->getColumns($table);

    $templateFile = __DIR__ . '/../../../../.opencode/templates/' . $type . '.php';
    if (!file_exists($templateFile)) {
        Response::error("Template no encontrado: {$type}.php");
    }

    $template = file_get_contents($templateFile);

    $slug = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));
    $filename = $slug . '.php';

    $replacements = [
        '{{TABLE}}' => $table,
        '{{NAME}}' => $name,
        '{{DESCRIPTION}}' => $configData['description'] ?? "Endpoint {$type} para {$table}",
        '{{TYPE}}' => $type,
        '{{METHOD}}' => $configData['method'] ?? 'POST',
        '{{DATE}}' => date('Y-m-d H:i:s'),
        '{{DEFAULT_ORDER}}' => $configData['order_field'] ?? ($columns[0]['COLUMNA'] ?? 'ID'),
    ];

    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }

    $filtersBlock = '';
    if (!empty($configData['filters'])) {
        foreach ($configData['filters'] as $filter) {
            $fname = $filter['name'] ?? '';
            $fop = $filter['operator'] ?? '=';
            if ($fname) {
                $filtersBlock .= "    if (\$request->get('{$fname}')) {\n";
                $filtersBlock .= "        \$filters['{$fname}'] = \$request->get('{$fname}');\n";
                $filtersBlock .= "    }\n";
            }
        }
    }
    $template = str_replace('{{FILTERS_BLOCK}}', $filtersBlock, $template);

    $requiredFields = '';
    if (!empty($configData['required_fields'])) {
        $quoted = array_map(function($f) { return "'" . addslashes($f) . "'"; }, $configData['required_fields']);
        $requiredFields = implode(', ', $quoted);
    } else {
        $requiredFields = "'id'";
    }
    $template = str_replace('{{REQUIRED_FIELDS}}', $requiredFields, $template);

    $fieldsBlock = '';
    if (!empty($configData['fields'])) {
        foreach ($configData['fields'] as $field) {
            $fname = $field['name'] ?? '';
            if ($fname) {
                $fieldsBlock .= "    \$data['{$fname}'] = \$request->sanitize(\$request->get('{$fname}'));\n";
            }
        }
    }
    $template = str_replace('{{FIELDS_BLOCK}}', $fieldsBlock, $template);

    $template = str_replace('{{VALIDATIONS_BLOCK}}', '// Validaciones adicionales aquí', $template);

    $paramsBlock = '';
    if (!empty($configData['params'])) {
        foreach ($configData['params'] as $param) {
            $pname = $param['name'] ?? '';
            if ($pname) {
                $paramsBlock .= "    \$request->required('{$pname}');\n";
                $paramsBlock .= "    \${$pname} = \$request->get('{$pname}');\n\n";
            }
        }
    }
    $template = str_replace('{{PARAMS_BLOCK}}', $paramsBlock, $template);

    $customSql = $configData['sql'] ?? "SELECT * FROM {$table}";
    $template = str_replace('{{CUSTOM_SQL}}', addslashes($customSql), $template);

    $paramsBind = '';
    if (!empty($configData['params'])) {
        foreach ($configData['params'] as $param) {
            $pname = $param['name'] ?? '';
            if ($pname) {
                $paramsBind .= "        ':{$pname}' => \${$pname},\n";
            }
        }
    }
    $template = str_replace('{{PARAMS_BIND}}', $paramsBind, $template);

    Response::success([
        'code' => $template,
        'filename' => $filename,
        'type' => $type,
        'table' => $table
    ], "Código generado correctamente");
} catch (Exception $e) {
    Response::error($e->getMessage());
}
