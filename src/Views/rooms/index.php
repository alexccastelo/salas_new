<?php
// src/Views/rooms/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salas - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="topbar">
            <h1>Cadastro de Salas</h1>
            <?php if ($editando): ?>
                <a href="index.php?route=salas" class="btn-secondary">Voltar para Nova</a>
            <?php endif; ?>
        </div>

        <?php if ($mensagem): ?>
            <div class="msg-alerta" style="background-color:#d1fae5; border-color:#10b981; color:#065f46;">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="msg-alerta" style="background-color:#fee2e2; border-color:#ef4444; color:#991b1b;">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- FORM -->
            <div class="card">
                <h2>
                    <?= $editando ? 'Editar Sala' : 'Nova Sala' ?>
                </h2>
                <form method="post" action="index.php?route=salas<?= $editando ? '&edit=' . $editando['id'] : '' ?>">
                    <input type="hidden" name="action" value="<?= $editando ? 'update' : 'create' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                    <?php endif; ?>

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label>Nome da Sala</label>
                        <input type="text" name="nome" required
                            value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
                    </div>

                    <?php if ($editando): ?>
                        <div style="margin-bottom:12px;">
                            <label style="display:flex; gap:8px; align-items:center;">
                                <input type="checkbox" name="ativo" style="width:auto; margin:0;" <?= $editando['ativo'] ? 'checked' : '' ?>>
                                Ativa
                            </label>
                        </div>
                    <?php endif; ?>

                    <div style="text-align:right;">
                        <button type="submit" class="btn-primary">
                            <?= $editando ? 'Salvar Alterações' : 'Cadastrar' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- LIST -->
            <div class="card">
                <h2>Salas Cadastradas</h2>
                <?php if (empty($salas)): ?>
                    <p class="info">Nenhum registro.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Status</th>
                                <th style="text-align:right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salas as $s): ?>
                                <tr style="<?= $s['ativo'] ? '' : 'opacity:0.6;' ?>">
                                    <td>
                                        <?= $s['id'] ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($s['nome']) ?>
                                    </td>
                                    <td>
                                        <span
                                            style="font-size:0.8rem; padding:2px 6px; border-radius:4px; background:<?= $s['ativo'] ? '#d1fae5' : '#f3f4f6' ?>; color:<?= $s['ativo'] ? '#065f46' : '#6b7280' ?>;">
                                            <?= $s['ativo'] ? 'Ativa' : 'Inativa' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="index.php?route=salas&edit=<?= $s['id'] ?>" class="btn-link">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>