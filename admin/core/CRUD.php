<?php
/**
 * ============================================
 * CRUD - Operaciones Genéricas
 * ============================================
 * 
 * Clase para operaciones CRUD genéricas.
 * Funciona con cualquier tabla de la base de datos.
 * Compatible con PHP 7.4+
 */

class CRUD
{
    /** @var Database */
    private $db;
    
    /** @var string */
    private $table;

    /**
     * Constructor
     * @param Database $db
     * @param string $table
     */
    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Obtener todos los registros con filtros y paginación
     * @param array $filters
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($filters = [], $order = [], $limit = 100, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        // Aplicar filtros
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $field => $value) {
                if (is_array($value)) {
                    $operator = $value['operator'] ?? '=';
                    $conditions[] = "{$field} {$operator} :{$field}";
                    $params[$field] = $value['value'];
                } else {
                    $conditions[] = "{$field} = :{$field}";
                    $params[$field] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Aplicar orden
        if (!empty($order)) {
            $orderParts = [];
            foreach ($order as $field => $direction) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orderParts[] = "{$field} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        // Aplicar paginación (Firebird syntax)
        $sql .= " ROWS {$offset} TO " . ($offset + $limit);

        return $this->db->query($sql, $params);
    }

    /**
     * Obtener registro por ID
     * @param mixed $id
     * @return array|null
     */
    public function getById($id)
    {
        $pk = $this->getPrimaryKey();
        $sql = "SELECT * FROM {$this->table} WHERE {$pk} = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        
        return $result[0] ?? null;
    }

    /**
     * Contar registros con filtros
     * @param array $filters
     * @return int
     */
    public function count($filters = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        $params = [];

        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $field => $value) {
                if (is_array($value)) {
                    $operator = $value['operator'] ?? '=';
                    $conditions[] = "{$field} {$operator} :{$field}";
                    $params[$field] = $value['value'];
                } else {
                    $conditions[] = "{$field} = :{$field}";
                    $params[$field] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->db->query($sql, $params);
        return (int)($result[0]['total'] ?? 0);
    }

    // ============================================
    // OPERACIONES DE ESCRITURA - DESHABILITADAS
    // Inspector es solo lectura
    // ============================================
    
    /**
     * Crear nuevo registro
     * @param array $data
     * @return string
     *
     * NOTA: Deshabilitado - Inspector es solo lectura
     */
    // public function create($data)
    // {
    //     $fields = array_keys($data);
    //     $placeholders = array_map(function($f) {
    //         return ":{$f}";
    //     }, $fields);
    //
    //     $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ")
    //             VALUES (" . implode(', ', $placeholders) . ")";
    //
    //     $this->db->execute($sql, $data);
    //
    //     return $this->db->lastInsertId();
    // }

    /**
     * Actualizar registro
     * @param mixed $id
     * @param array $data
     * @return bool
     *
     * NOTA: Deshabilitado - Inspector es solo lectura
     */
    // public function update($id, $data)
    // {
    //     $pk = $this->getPrimaryKey();
    //     $setParts = [];
    //
    //     foreach ($data as $field => $value) {
    //         $setParts[] = "{$field} = :{$field}";
    //     }
    //
    //     $sql = "UPDATE {$this->table}
    //             SET " . implode(', ', $setParts) . "
    //             WHERE {$pk} = :id";
    //
    //     $data['id'] = $id;
    //
    //     return $this->db->execute($sql, $data) > 0;
    // }

    /**
     * Eliminar registro
     * @param mixed $id
     * @return bool
     *
     * NOTA: Deshabilitado - Inspector es solo lectura
     */
    // public function delete($id)
    // {
    //     $pk = $this->getPrimaryKey();
    //     $sql = "DELETE FROM {$this->table} WHERE {$pk} = :id";
    //
    //     return $this->db->execute($sql, ['id' => $id]) > 0;
    // }

    /**
     * Buscar registros por campo
     * @param string $field
     * @param string $value
     * @param int $limit
     * @return array
     */
    public function search($field, $value, $limit = 20)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$field} LIKE :search 
                ORDER BY {$field} 
                ROWS 1 TO {$limit}";
        
        return $this->db->query($sql, ['search' => "%{$value}%"]);
    }

    /**
     * Obtener clave primaria de la tabla
     * @return string
     */
    public function getPrimaryKey()
    {
        $pk = $this->db->getPrimaryKey($this->table);
        return $pk ?? 'id';
    }

    /**
     * Obtener columnas de la tabla
     * @return array
     */
    public function getColumns()
    {
        return $this->db->getColumns($this->table);
    }

    /**
     * Verificar si existe un registro
     * @param mixed $id
     * @return bool
     */
    public function exists($id)
    {
        $pk = $this->getPrimaryKey();
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE {$pk} = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        
        return (int)($result[0]['total'] ?? 0) > 0;
    }

    /**
     * Obtener nombre de la tabla
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
