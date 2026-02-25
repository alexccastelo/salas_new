<?php
// src/Views/users/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="topbar">
            <h1>Administração de Usuários</h1>
            <?php if ($usuarioEditando): ?>
                <a href="\/usuarios" class="btn-secondary">Voltar para Novo Usuário</a>
            <?php endif; ?>
        </div>

        <!-- Feedback -->
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
                    <?= $usuarioEditando ? 'Editar Usuário' : 'Novo Usuário' ?>
                </h2>
                <p class="info">Sua senha deve ser forte (8+ chars, números, letras maiúsc/minúsc).</p>

                <form method="post"
                    action="\/usuarios<?= $usuarioEditando ? '&edit=' . $usuarioEditando['id'] : '' ?>">
                    <input type="hidden" name="action" value="<?= $usuarioEditando ? 'update' : 'create' ?>">
                    <?php if ($usuarioEditando): ?>
                        <input type="hidden" name="id" value="<?= $usuarioEditando['id'] ?>">
                    <?php endif; ?>

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label>E-mail</label>
                        <input type="email" name="email" required
                            value="<?= htmlspecialchars($usuarioEditando['email'] ?? '') ?>">
                    </div>

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label>
                            <?= $usuarioEditando ? 'Nova Senha (deixe em branco para manter)' : 'Senha' ?>
                        </label>
                        <input type="password" name="senha" <?= $usuarioEditando ? '' : 'required' ?>>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="margin-bottom:8px;">Permissões de Acesso</label>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                            <?php foreach ($modulosDisponiveis as $slug => $label): ?>
                                <label style="display:flex; gap:8px; align-items:center; font-weight:normal;">
                                    <input type="checkbox" name="modulos[]" value="<?= $slug ?>"
                                        style="width:auto; margin:0;" <?= in_array($slug, $modulosUsuario) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <button type="submit" class="btn-primary">
                            <?= $usuarioEditando ? 'Salvar Alterações' : 'Criar Usuário' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- LIST -->
            <div class="card">
                <h2>Usuários Cadastrados</h2>
                <?php if (empty($usuarios)): ?>
                    <p class="info">Nenhum registro.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>E-mail</th>
                                <th style="text-align:right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td>
                                        <?= $u['id'] ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['email']) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="\/usuarios?edit=<?= $u['id'] ?>" class="btn-link">Editar</a>
                                        &nbsp;|&nbsp;
                                        <form method="post" action="\/usuarios" style="display:inline;"
                                            onsubmit="return confirm('Excluir?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn-link"
                                                style="color:var(--text-muted);">Excluir</button>
                                        </form>
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