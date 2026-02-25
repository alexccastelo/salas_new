<?php
// src/Views/professionals/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissionais - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="topbar">
            <h1>Cadastro de Profissionais</h1>
            <?php if ($editando): ?>
                <a href="\/profissionais" class="btn-secondary">Voltar para Novo</a>
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
                    <?= $editando ? 'Editar Profissional' : 'Novo Profissional' ?>
                </h2>
                <form method="post"
                    action="\/profissionais<?= $editando ? '&edit=' . $editando['id'] : '' ?>">
                    <input type="hidden" name="action" value="<?= $editando ? 'update' : 'create' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                    <?php endif; ?>

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label>Nome do Profissional</label>
                        <input type="text" name="nome" required
                            value="<?= htmlspecialchars($editando['nome'] ?? '') ?>">
                    </div>

                    <?php if ($editando): ?>
                        <div style="margin-bottom:12px;">
                            <label style="display:flex; gap:8px; align-items:center;">
                                <input type="checkbox" name="ativo" style="width:auto; margin:0;" <?= $editando['ativo'] ? 'checked' : '' ?>>
                                Ativo
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
                <h2>Profissionais Cadastrados</h2>
                <?php if (empty($profissionais)): ?>
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
                            <?php foreach ($profissionais as $p): ?>
                                <tr style="<?= $p['ativo'] ? '' : 'opacity:0.6;' ?>">
                                    <td>
                                        <?= $p['id'] ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['nome']) ?>
                                    </td>
                                    <td>
                                        <span
                                            style="font-size:0.8rem; padding:2px 6px; border-radius:4px; background:<?= $p['ativo'] ? '#d1fae5' : '#f3f4f6' ?>; color:<?= $p['ativo'] ? '#065f46' : '#6b7280' ?>;">
                                            <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="\/profissionais?edit=<?= $p['id'] ?>" class="btn-link">Editar</a>
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