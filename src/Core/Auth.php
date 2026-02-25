<?php

namespace Clinica\Core;

class Auth
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?route=login');
            exit;
        }
    }

    public static function id()
    {
        self::check();
        return $_SESSION['usuario_id'];
    }

    public static function user()
    {
        self::check();
        if (isset($_SESSION['usuario_email'])) {
            return [
                'id' => $_SESSION['usuario_id'],
                'email' => $_SESSION['usuario_email']
            ];
        }
        // Fallback if not in session (e.g. legacy login didn't set it? it does but just in case)
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $u = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($u) {
            $_SESSION['usuario_email'] = $u['email'];
        }
        return $u;
    }

    public static function hasPermission(string $modulo): bool
    {
        self::check();

        $usageId = $_SESSION['usuario_id'];
        $pdo = Database::getInstance()->getConnection();

        // Se o usuário NÃO tem nenhuma permissão cadastrada,
        // consideramos ACESSO TOTAL (modo compatibilidade).
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_modulos WHERE usuario_id = :id");
        $stmt->execute([':id' => $usageId]);
        $total = (int) $stmt->fetchColumn();

        if ($total === 0) {
            return true;
        }

        // Verifica permissão explícita
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM usuarios_modulos 
            WHERE usuario_id = :id AND modulo = :modulo
        ");
        $stmt->execute([
            ':id' => $usageId,
            ':modulo' => $modulo,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function requirePermission(string $modulo): void
    {
        if (self::hasPermission($modulo)) {
            return;
        }

        http_response_code(403);
        // We might want to render a nice view here, but for now specific to the old behavior
        die('<h1>Acesso negado</h1><p>Você não tem permissão para acessar este módulo.</p><a href="index.php">Voltar</a>');
    }
}
