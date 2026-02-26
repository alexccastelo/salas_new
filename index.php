<?php
// index.php - Main Entry Point

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Composer AutoLoader
require_once __DIR__ . '/vendor/autoload.php';

// Load Environment Variables (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Set Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

use Clinica\Core\Auth;
use Clinica\Core\Database;
use Clinica\Core\Router;

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$router = new Router();

// Rota padrão
$router->get('/', function () {
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = rtrim(str_replace('\\', '/', $scriptName), '/');
    header("Location: {$baseUrl}/dashboard");
    exit;
});

// Autenticação
$router->get('/login', [\Clinica\Controllers\AuthController::class, 'login']);
$router->post('/login', [\Clinica\Controllers\AuthController::class, 'login']);
$router->get('/logout', [\Clinica\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [\Clinica\Controllers\DashboardController::class, 'index']);

// Checkin
$router->get('/checkin', [\Clinica\Controllers\CheckinController::class, 'index']);
$router->post('/checkin', [\Clinica\Controllers\CheckinController::class, 'index']);

// Pacotes
$router->get('/pacotes', [\Clinica\Controllers\PackageController::class, 'index']);
$router->post('/pacotes', [\Clinica\Controllers\PackageController::class, 'index']);

// Relatórios
$router->get('/relatorios', [\Clinica\Controllers\ReportController::class, 'index']);
$router->post('/relatorios', [\Clinica\Controllers\ReportController::class, 'index']); // Caso haja filtros POST

// Cadastros
$router->get('/profissionais', [\Clinica\Controllers\ProfessionalController::class, 'index']);
$router->post('/profissionais', [\Clinica\Controllers\ProfessionalController::class, 'index']);

$router->get('/salas', [\Clinica\Controllers\RoomController::class, 'index']);
$router->post('/salas', [\Clinica\Controllers\RoomController::class, 'index']);

// Usuários
$router->get('/usuarios', [\Clinica\Controllers\UserController::class, 'index']);
$router->post('/usuarios', [\Clinica\Controllers\UserController::class, 'index']);

// Dispatch
$router->dispatch();