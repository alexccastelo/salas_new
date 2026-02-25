<?php

namespace Clinica\Helpers;

class Csrf
{
    /**
     * Gera e armazena um token CSRF na sessão
     *
     * @return string
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valida se o token recebido no POST é igual ao da sessão
     *
     * @param string|null $token
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Retorna o input HTML já formatado para inserir nos formulários
     *
     * @return string
     */
    public static function csrfField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Força a verificação na requisição atual (útil nos Controllers que recebem POST)
     * Se falhar, encerra a aplicação com erro 403.
     */
    public static function requireValidation(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';

            if (!self::validateToken($token)) {
                http_response_code(403);
                die('Erro de Segurança: Token CSRF inválido ou expirado. Volte e tente enviar o formulário novamente.');
            }
        }
    }
}
