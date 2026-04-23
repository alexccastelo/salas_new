<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Services\GoogleCalendarService;

class GoogleAuthController extends Controller
{
    /**
     * Redireciona o admin para o consent screen do Google.
     * GET /google/auth
     */
    public function auth(): void
    {
        Auth::check();

        $gcal = new GoogleCalendarService();
        $url  = $gcal->getAuthUrl();

        header('Location: ' . $url);
        exit;
    }

    /**
     * Recebe o callback OAuth2 do Google, troca o code pelo token
     * e salva o refresh token no banco.
     * GET /google/callback
     */
    public function callback(): void
    {
        Auth::check();

        $code  = $_GET['code'] ?? '';
        $error = $_GET['error'] ?? '';

        if ($error) {
            $this->view('google/callback', [
                'sucesso' => false,
                'mensagem' => 'Autorização negada: ' . htmlspecialchars($error),
            ]);
            return;
        }

        if (!$code) {
            $this->view('google/callback', [
                'sucesso' => false,
                'mensagem' => 'Código de autorização ausente.',
            ]);
            return;
        }

        try {
            $gcal = new GoogleCalendarService();
            $gcal->exchangeCode($code);

            $this->view('google/callback', [
                'sucesso' => true,
                'mensagem' => 'Google Calendar conectado com sucesso!',
            ]);
        } catch (\Throwable $e) {
            error_log('[GoogleAuth] Erro no callback: ' . $e->getMessage());
            $this->view('google/callback', [
                'sucesso' => false,
                'mensagem' => 'Erro ao conectar: ' . $e->getMessage(),
            ]);
        }
    }
}
