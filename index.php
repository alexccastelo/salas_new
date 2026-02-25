<?php
// index.php - Main Entry Point

require_once __DIR__ . '/config/autoloader.php';

// Set Timezone to GMT-3
date_default_timezone_set('America/Sao_Paulo');

// Set Timezone
date_default_timezone_set('America/Sao_Paulo');

use Clinica\Core\Auth;
use Clinica\Core\Database;

// Simple Router
$route = $_GET['route'] ?? 'dashboard';

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic Routing Table
switch ($route) {
    case 'login':
        // Legacy login handling or new controller
        require 'login.php';
        break;
    case 'logout':
        require 'logout.php';
        break;
    case 'dashboard':
        Auth::check();
        // For now, load the legacy index content or a new DashboardController
        // Let's forward to the new DashboardController if ready, else legacy
        // For the pilot, we are focusing on Checkin, so let's stick to legacy for dashboard for a moment
        // But wait, the user wants me to improve it.
        // Let's use a DashboardController
        (new \Clinica\Controllers\DashboardController())->index();
        break;
    case 'usuarios':
        (new \Clinica\Controllers\UserController())->index();
        break;
    case 'pacotes':
        (new \Clinica\Controllers\PackageController())->index();
        break;
    case 'relatorios':
        (new \Clinica\Controllers\ReportController())->index();
        break;
    case 'profissionais':
        (new \Clinica\Controllers\ProfessionalController())->index();
        break;
    case 'salas':
        (new \Clinica\Controllers\RoomController())->index();
        break;
    case 'checkin':
        Auth::check();
        (new \Clinica\Controllers\CheckinController())->index();
        break;
    default:
        echo "404 - Página não encontrada";
        break;
}
