<?php

namespace Clinica\Core;

class Controller
{
    protected static $twig;

    protected function getTwig()
    {
        if (self::$twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../src/Views');
            self::$twig = new \Twig\Environment($loader, [
                'cache' => false, // Set to a path for production caching
                'auto_reload' => true
            ]);

            // Add global CSRF field function
            self::$twig->addFunction(new \Twig\TwigFunction('csrfField', function () {
                return \Clinica\Helpers\Csrf::csrfField();
            }, ['is_safe' => ['html']]));

            // Add session info as global
            if (isset($_SESSION['usuario_id'])) {
                self::$twig->addGlobal('session_user_email', clone (object) $_SESSION);

                // Add permissions
                $permissions = [
                    'checkin' => \Clinica\Core\Auth::hasPermission('checkin'),
                    'pacotes' => \Clinica\Core\Auth::hasPermission('pacotes'),
                    'profissionais' => \Clinica\Core\Auth::hasPermission('profissionais'),
                    'salas' => \Clinica\Core\Auth::hasPermission('salas'),
                    'usuarios' => \Clinica\Core\Auth::hasPermission('usuarios'),
                    'relatorios' => \Clinica\Core\Auth::hasPermission('relatorios'),
                ];
                self::$twig->addGlobal('permissions', $permissions);
            }

            // Add current route
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            self::$twig->addGlobal('currentRoute', trim($path, '/') ?: 'dashboard');

            // Add base_url
            $scriptName = dirname($_SERVER['SCRIPT_NAME']);
            $baseUrl = rtrim(str_replace('\\', '/', $scriptName), '/');
            self::$twig->addGlobal('base_url', $baseUrl);
        }
        return self::$twig;
    }

    protected function view($view, $data = [])
    {
        $twig = $this->getTwig();

        try {
            echo $twig->render($view . '.twig', $data);
        } catch (\Exception $e) {
            die("Erro ao renderizar View ({$view}.twig): " . htmlspecialchars($e->getMessage()));
        }
    }

    protected function redirect($url)
    {
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = rtrim(str_replace('\\', '/', $scriptName), '/');
        $url = ltrim($url, '/');
        header("Location: {$baseUrl}/{$url}");
        exit;
    }

    protected function jsonResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
