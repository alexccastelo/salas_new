<?php

namespace Clinica\Core;

class Router
{
    private $routes = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler)
    {
        $this->add('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_SERVER['REQUEST_URI'];

        // Remove query string from path
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Se a aplicação rodar em um subdiretório ou usar o server embutido
        // Aqui estamos simplificando, considerando que as rotas baseiam-se no request
        // Em um PHP Built-in server, $path começa na raiz. ex: /checkin
        // Mas o legado era localhost:8000/index.php?route=checkin

        // Suporte a compatibilidade temporária com o formato ?route=...
        if (isset($_GET['route'])) {
            $routeParam = $_GET['route'];
            $path = '/' . ltrim($routeParam, '/');
        } elseif ($path === '/index.php' || $path === '/') {
            $path = '/dashboard';
            // Default if no route provided
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                $handler = $route['handler'];

                if (is_array($handler) && count($handler) === 2) {
                    $controller = new $handler[0]();
                    $action = $handler[1];
                    $controller->$action();
                } elseif (is_callable($handler)) {
                    call_user_func($handler);
                } else {
                    die("Invalid handler for route {$path}");
                }

                return;
            }
        }

        // 404
        http_response_code(404);
        echo "<h1>404 - Página não encontrada</h1>";
    }
}
