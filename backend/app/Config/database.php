<?php
/**
 * Configuración de Base de Datos
 * Sistema de Comisiones VentasPlus S.A.
 */

namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración de conexión
    private $config = [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'comisiones_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
    
    private function __construct() {
        $this->loadEnvConfig();
        $this->connect();
    }
    
    /**
     * Cargar configuración desde variables de entorno
     */
    private function loadEnvConfig() {
        if (file_exists(__DIR__ . '/../../.env')) {
            $env = parse_ini_file(__DIR__ . '/../../.env');
            
            $this->config['host'] = $env['DB_HOST'] ?? $this->config['host'];
            $this->config['port'] = $env['DB_PORT'] ?? $this->config['port'];
            $this->config['database'] = $env['DB_DATABASE'] ?? $this->config['database'];
            $this->config['username'] = $env['DB_USERNAME'] ?? $this->config['username'];
            $this->config['password'] = $env['DB_PASSWORD'] ?? $this->config['password'];
        }
    }
    
    /**
     * Establecer conexión con la base de datos
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $this->connection = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
        } catch (\PDOException $e) {
            throw new \Exception("Error de conexión: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Ejecutar query
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Obtener último ID insertado
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
?>