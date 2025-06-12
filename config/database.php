<?php
/**
 * Classe Database atualizada para usar a nova estrutura
 * Mantém compatibilidade com código existente
 */

require_once __DIR__ . '/db.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connection = DatabaseConnection::getInstance()->getConnection();
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Testa a conexão
     */
    public function testConnection() {
        return DatabaseConnection::getInstance()->testConnection();
    }
    
    /**
     * Fecha a conexão
     */
    public function closeConnection() {
        DatabaseConnection::getInstance()->closeConnection();
    }
}

/**
 * Funções helper para facilitar o uso
 */

/**
 * Executa uma query preparada
 */
function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Busca um registro
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Busca múltiplos registros
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Insere um registro e retorna o ID
 */
function insertRecord($table, $data) {
    $db = getDB();
    
    $columns = array_keys($data);
    $placeholders = array_map(function($col) { return ":$col"; }, $columns);
    
    $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    return $db->lastInsertId();
}

/**
 * Atualiza um registro
 */
function updateRecord($table, $data, $where, $whereParams = []) {
    $db = getDB();
    
    $setParts = array_map(function($col) { return "$col = :$col"; }, array_keys($data));
    $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
    
    $params = array_merge($data, $whereParams);
    $stmt = $db->prepare($sql);
    
    return $stmt->execute($params);
}

/**
 * Deleta registros
 */
function deleteRecord($table, $where, $params = []) {
    $db = getDB();
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Conta registros
 */
function countRecords($table, $where = '1=1', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = fetchOne($sql, $params);
    return $result['total'] ?? 0;
}

/**
 * Verifica se um registro existe
 */
function recordExists($table, $where, $params = []) {
    return countRecords($table, $where, $params) > 0;
}
?>