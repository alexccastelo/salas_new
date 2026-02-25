<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Room
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM salas WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function allIncludingInactive()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM salas ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function create($nome)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO salas (nome, ativo) VALUES (:nome, 1)");
        $stmt->execute([':nome' => $nome]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $nome, $ativo = 1)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE salas SET nome = :nome, ativo = :ativo WHERE id = :id");
        return $stmt->execute([
            ':nome' => $nome,
            ':ativo' => $ativo,
            ':id' => $id
        ]);
    }

    public static function delete($id)
    {
        return self::update($id, self::find($id)['nome'], 0);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM salas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
