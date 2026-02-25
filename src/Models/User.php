<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class User
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT id, email FROM usuarios ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function findByEmail($email)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public static function exists($email, $excludeId = 0)
    {
        $pdo = Database::getInstance()->getConnection();
        if ($excludeId > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id <> :id");
            $stmt->execute([':email' => $email, ':id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
        }
        return $stmt->fetchColumn() > 0;
    }

    public static function create($email, $senhaHash)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha_hash) VALUES (:email, :senha_hash)");
        $stmt->execute([
            ':email' => $email,
            ':senha_hash' => $senhaHash
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update($id, $email, $senhaHash = null)
    {
        $pdo = Database::getInstance()->getConnection();
        if ($senhaHash) {
            $stmt = $pdo->prepare("UPDATE usuarios SET email = :email, senha_hash = :senha_hash WHERE id = :id");
            return $stmt->execute([
                ':email' => $email,
                ':senha_hash' => $senhaHash,
                ':id' => $id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
            return $stmt->execute([
                ':email' => $email,
                ':id' => $id
            ]);
        }
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Permissions
    public static function getPermissions($userId)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT modulo FROM usuarios_modulos WHERE usuario_id = :id");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function setPermissions($userId, $modulos)
    {
        $pdo = Database::getInstance()->getConnection();
        // Clear existing
        $stmtDel = $pdo->prepare("DELETE FROM usuarios_modulos WHERE usuario_id = :id");
        $stmtDel->execute([':id' => $userId]);

        if (!empty($modulos)) {
            $stmt = $pdo->prepare("INSERT INTO usuarios_modulos (usuario_id, modulo) VALUES (:usuario_id, :modulo)");
            foreach ($modulos as $mod) {
                $stmt->execute([
                    ':usuario_id' => $userId,
                    ':modulo' => $mod
                ]);
            }
        }
    }
}
