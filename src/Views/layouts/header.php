<?php
// src/Views/layouts/header.php
use Clinica\Core\Auth;

// Check permissions
$canCheckin = Auth::hasPermission('checkin');
$canPacotes = Auth::hasPermission('pacotes');
$canProfissionais = Auth::hasPermission('profissionais');
$canSalas = Auth::hasPermission('salas');
$canUsuarios = Auth::hasPermission('usuarios');
$canRelatorios = Auth::hasPermission('relatorios');

$currentRoute = $_GET['route'] ?? 'dashboard';

// User Info
$user = Auth::user();
$userEmail = $user['email'] ?? 'Usuário';
?>
<header class="site-header">
    <div class="header-inner">
        <div class="logo-area">
            <img src="img/logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <div class="logo-text">
                <span class="system-name">Espaço Vital</span>
                <span class="system-subtitle">Sistema de Gestão</span>
            </div>
        </div>

        <div class="user-area" style="margin-left:auto; margin-right:20px; color:var(--text-muted); font-size:0.9rem;">
            Olá, <strong><?= htmlspecialchars($userEmail) ?></strong>
        </div>

        <nav class="main-nav">
            <a href="index.php?route=dashboard"
                class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>">Dashboard</a>

            <a href="<?= $canCheckin ? 'index.php?route=checkin' : '#' ?>"
                onclick="<?= $canCheckin ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'checkin' ? 'active' : '' ?>">Check-in</a>

            <a href="<?= $canPacotes ? 'index.php?route=pacotes' : '#' ?>"
                onclick="<?= $canPacotes ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'pacotes' ? 'active' : '' ?>">Pacotes</a>

            <a href="<?= $canRelatorios ? 'index.php?route=relatorios' : '#' ?>"
                onclick="<?= $canRelatorios ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'relatorios' ? 'active' : '' ?>">Relatórios</a>

            <a href="<?= $canProfissionais ? 'index.php?route=profissionais' : '#' ?>"
                onclick="<?= $canProfissionais ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'profissionais' ? 'active' : '' ?>">Profissionais</a>

            <a href="<?= $canSalas ? 'index.php?route=salas' : '#' ?>"
                onclick="<?= $canSalas ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'salas' ? 'active' : '' ?>">Salas</a>

            <a href="<?= $canUsuarios ? 'index.php?route=usuarios' : '#' ?>"
                onclick="<?= $canUsuarios ? '' : "alert('Acesso negado'); return false;" ?>"
                class="<?= $currentRoute === 'usuarios' ? 'active' : '' ?>">Usuários</a>

            <a href="#" onclick="alert('Em breve'); return false;">Configurações</a>
            <a href="index.php?route=logout">Sair</a>
        </nav>
    </div>
</header>