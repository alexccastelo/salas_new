<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Professional
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM profissionais WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function allIncludingInactive()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM profissionais ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function create($nome)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO profissionais (nome, ativo) VALUES (:nome, 1)");
        $stmt->execute([':nome' => $nome]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $nome, $ativo = 1)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE profissionais SET nome = :nome, ativo = :ativo WHERE id = :id");
        return $stmt->execute([
            ':nome' => $nome,
            ':ativo' => $ativo,
            ':id' => $id
        ]);
    }

    public static function delete($id)
    {
        // Soft delete (mark as inactive) or hard delete if not used?
        // Plan said toggle active, but let's allow hard delete if no records exist, else soft.
        // For simplicity, let's just toggle active in the update, or provide a specific toggle method.
        // Let's implement a 'delete' that actually marks as inactive for safety.
        return self::update($id, self::find($id)['nome'], 0);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
