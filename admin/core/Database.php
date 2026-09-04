<?php
/**
 * ============================================
 * DATABASE - Conexión PDO Firebird
 * ============================================
 * 
 * Patrón Singleton para gestión de conexión a BD.
 * Soporta Firebird (principal) y MySQL (secundario).
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private array $config;
    private string $driver;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = $config['driver'] ?? 'firebird';
        $this->connect();
    }

    /**
     * Obtener instancia singleton
     */
    public static function getInstance(array $config = []): Database
    {
        if (self::$instance === null) {
            if (empty($config)) {
                $config = defined('DB_CONFIG') ? DB_CONFIG : [];
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Establecer conexión a BD
     */
    private function connect(): void
    {
        try {
            if ($this->driver === 'firebird') {
                $this->connectFirebird();
            } elseif ($this->driver === 'mysql') {
                $this->connectMySQL();
            } else {
                throw new InvalidArgumentException("Driver no soportado: {$this->driver}");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Error de conexión a BD: " . $e->getMessage());
        }
    }

    /**
     * Conexión a Firebird
     */
    private function connectFirebird(): void
    {
        $server = $this->config['server'] ?? 'localhost';
        $port = $this->config['port'] ?? '3050';
        $path = $this->config['path'] ?? '';
        $charset = $this->config['charset'] ?? 'UTF-8';

        $dsn = sprintf(
            'firebird:host=%s;dbname=%s/%s:%s;charset=%s',
            $server,
            $server,
            $port,
            $path,
            $charset
        );

        $this->pdo = new PDO(
            $dsn,
            $this->config['user'] ?? '',
            $this->config['password'] ?? ''
        );

        $this->pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Conexión a MySQL
     */
    private function connectMySQL(): void
    {
        $server = $this->config['server'] ?? 'localhost';
        $port = $this->config['port'] ?? '3306';
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$server};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new PDO(
            $dsn,
            $this->config['user'] ?? 'root',
            $this->config['password'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /**
     * Obtener conexión PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Ejecutar query con resultados
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ejecutar query sin resultados (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Obtener último ID insertado
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Obtener driver actual
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Verificar si la conexión está activa
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Cerrar conexión
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        self::$instance = null;
    }

    /**
     * Obtener listar tablas (Firebird)
     */
    public function getTables(): array
    {
        $sql = "SELECT RDB\$RELATION_NAME AS tabla 
                FROM RDB\$RELATIONS 
                WHERE RDB\$SYSTEM_FLAG = 0 
                ORDER BY RDB\$RELATION_NAME";
        
        return $this->query($sql);
    }

    /**
     * Obtener columnas de una tabla (Firebird)
     */
    public function getColumns(string $table): array
    {
        $sql = "SELECT 
                    RF.RDB\$FIELD_NAME AS columna,
                    F.RDB\$FIELD_LENGTH AS longitud,
                    F.RDB\$FIELD_TYPE AS tipo,
                    RF.RDB\$NULL_FLAG AS not_null
                FROM RDB\$RELATION_FIELDS RF
                JOIN RDB\$FIELDS F ON RF.RDB\$FIELD_SOURCE = F.RDB\$FIELD_NAME
                WHERE RF.RDB\$RELATION_NAME = :table
                ORDER BY RF.RDB\$FIELD_POSITION";
        
        return $this->query($sql, ['table' => $table]);
    }

    /**
     * Obtener clave primaria de una tabla
     */
    public function getPrimaryKey(string $table): ?string
    {
        $sql = "SELECT S.RDB\$FIELD_NAME AS campo
                FROM RDB\$RELATION_CONSTRAINTS RC
                JOIN RDB\$INDEX_SEGMENTS S ON RC.RDB\$INDEX_NAME = S.RDB\$INDEX_NAME
                WHERE RC.RDB\$RELATION_NAME = :table 
                AND RC.RDB\$CONSTRAINT_TYPE = 'PRIMARY KEY'
                ORDER BY S.RDB\$FIELD_POSITION";
        
        $result = $this->query($sql, ['table' => $table]);
        return $result[0]['campo'] ?? null;
    }

    /**
     * Contar registros de una tabla
     */
    public function count(string $table, array $filters = []): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        $params = [];

        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $field => $value) {
                $conditions[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->query($sql, $params);
        return (int)($result[0]['total'] ?? 0);
    }
}
