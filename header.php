<?php
// Página atual para marcar o menu ativo
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="header-inner">
        <div class="logo-area">
            <img src="img/logo.png" alt="Espaço Vital Clínica" class="logo-img">
            <div class="logo-text">
                <span class="system-name">Espaço Vital Clínica</span>
                <span class="system-subtitle">Controle de Salas &amp; Pacotes</span>
            </div>
        </div>

        <nav class="main-nav">
            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('dashboard')): ?>
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Dashboard</a>
            <?php endif; ?>

            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('checkin')): ?>
                <a href="checkin.php" class="<?= $currentPage === 'checkin.php' ? 'active' : '' ?>">Check-in</a>
            <?php endif; ?>

            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('registros')): ?>
                <a href="registros.php" class="<?= $currentPage === 'registros.php' ? 'active' : '' ?>">Registros</a>
            <?php endif; ?>

            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('pacotes')): ?>
                <a href="pacotes.php" class="<?= $currentPage === 'pacotes.php' ? 'active' : '' ?>">Pacotes</a>
            <?php endif; ?>

            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('relatorios')): ?>
                <a href="relatorio.php" class="<?= $currentPage === 'relatorio.php' ? 'active' : '' ?>">Relatórios</a>
            <?php endif; ?>

            <?php if (function_exists('usuarioTemPermissao') && usuarioTemPermissao('usuarios')): ?>
                <a href="usuarios.php" class="<?= $currentPage === 'usuarios.php' ? 'active' : '' ?>">Usuários</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
