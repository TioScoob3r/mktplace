<?php
/**
 * Configuração de Conexão com Banco de Dados
 * Utiliza arquivo .env para configurações
 */

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->loadEnvironment();
        $this->connect();
    }
    
    /**
     * Carrega variáveis do arquivo .env
     */
    private function loadEnvironment() {
        $envFile = __DIR__ . '/.env';
        
        if (!file_exists($envFile)) {
            throw new Exception("Arquivo .env não encontrado em: " . $envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->config = [];
        
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Processar linha key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $this->config[$key] = $value;
            }
        }
    }
    
    /**
     * Estabelece conexão com o banco de dados
     */
    private function connect() {
        try {
            $host = $this->config['DB_HOST'] ?? 'localhost';
            $dbname = $this->config['DB_NAME'] ?? 'marketplace_digital';
            $username = $this->config['DB_USER'] ?? 'root';
            $password = $this->config['DB_PASS'] ?? '';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Retorna instância singleton da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Retorna configuração específica
     */
    public function getConfig($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Retorna todas as configurações
     */
    public function getAllConfig() {
        return $this->config;
    }
    
    /**
     * Testa a conexão com o banco
     */
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Fecha a conexão
     */
    public function closeConnection() {
        $this->connection = null;
    }
    
    /**
     * Previne clonagem da instância
     */
    private function __clone() {}
    
    /**
     * Previne deserialização da instância
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Função helper para obter conexão rapidamente
 */
function getDB() {
    return DatabaseConnection::getInstance()->getConnection();
}

/**
 * Função helper para obter configuração
 */
function getConfig($key, $default = null) {
    return DatabaseConnection::getInstance()->getConfig($key, $default);
}
?>