<?php
// src/Views/auth/login.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Espaço Vital Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Em pages que não requerem header do sistema, apenas omitimos -->
    <?php if (file_exists(__DIR__ . '/../../../../header.php')): ?>
        <?php include __DIR__ . '/../../../../header.php'; ?>
    <?php endif; ?>

    <div class="container" style="max-width: 420px; margin-top: 24px;">
        <div class="card">
            <h1 style="margin-bottom: 12px;">Acesso ao sistema</h1>

            <?php if (!empty($mensagemErro)): ?>
                <div class="msg-alerta">
                    <?= htmlspecialchars($mensagemErro) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/login">
                <?= $csrfField ?? \Clinica\Helpers\Csrf::csrfField() ?>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>

                <button type="submit">Entrar</button>
            </form>
        </div>

        <p class="info small" style="text-align:center; margin-top: 12px;">
            Espaço Vital Clínica · Controle de Salas · v0.1<br>
            &copy;
            <?= date('Y'); ?> Espaço Vital Clínica. Todos os direitos reservados.
        </p>
    </div>
</body>

</html>