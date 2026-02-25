<?php

namespace Clinica\Core;

class Controller
{
    protected function view($view, $data = [])
    {
        extract($data);
        $viewFile = __DIR__ . '/../../src/Views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View {$view} not found.");
        }
    }

    protected function redirect($url)
    {
        header("Location: {$url}");
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
