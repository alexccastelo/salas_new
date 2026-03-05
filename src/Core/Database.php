<?php

namespace Clinica\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $driver = $_ENV['DB_CONNECTION'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dbFile = __DIR__ . '/../../' . ltrim($_ENV['DB_DATABASE'] ?? 'clinica_salas.db', '/');
                $dsn = 'sqlite:' . $dbFile;
                $user = null;
                $pass = null;
            } else {
                $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbName = $_ENV['DB_DATABASE'] ?? 'erp_saude';
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
                $user = $_ENV['DB_USERNAME'] ?? 'root';
                $pass = $_ENV['DB_PASSWORD'] ?? '';
            }

            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco de dados: ' . htmlspecialchars($e->getMessage()));
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    // Prevent cloning and unserialization
    private function __clone()
    {
    }
    public function __wakeup()
    {
    }
}
