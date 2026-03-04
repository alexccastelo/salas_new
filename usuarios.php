<?php
require_once 'config.php';
require_once 'auth.php';

exigirPermissao('usuarios');

$mensagem = '';
$erro     = '';

$usuarioLogadoId = $_SESSION['usuario_id'] ?? null;

$modulosDisponiveis = [
    'dashboard'  => 'Dashboard',
    'checkin'    => 'Check-in',
    'registros'  => 'Registros',
    'pacotes'    => 'Pacotes',
    'relatorios' => 'Relatórios',
    'usuarios'   => 'Administração de usuários',
];

function senha_eh_forte(string $senha): bool
{
    $temMin = preg_match('/[a-z]/', $senha);
    $temMai = preg_match('/[A-Z]/', $senha);
    $temNum = preg_match('/\d/', $senha);
    return strlen($senha) >= 8 && $temMin && $temMai && $temNum;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CRIAR
    if ($action === 'create') {
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif (!senha_eh_forte($senha)) {
            $erro = 'A senha precisa ter pelo menos 8 caracteres, com letras maiúsculas, minúsculas e números.';
        } else {
            // Verifica se já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $erro = 'Já existe um usuário com este e-mail.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (email, senha_hash)
                    VALUES (:email, :senha_hash)
                ");
                $stmt->execute([
                    ':email'      => $email,
                    ':senha_hash' => $hash,
                ]);

                $novoId = (int)$pdo->lastInsertId();

                // Permissões de módulos
                $modulosSelecionados = $_POST['modulos'] ?? [];
                if (!is_array($modulosSelecionados)) {
                    $modulosSelecionados = [];
                }
                // Garante que só entra módulo válido
                $modulosSelecionados = array_intersect(array_keys($modulosDisponiveis), $modulosSelecionados);

                if (!empty($modulosSelecionados)) {
                    $stmtPerm = $pdo->prepare("
                        INSERT INTO usuarios_modulos (usuario_id, modulo)
                        VALUES (:usuario_id, :modulo)
                    ");
                    foreach ($modulosSelecionados as $mod) {
                        $stmtPerm->execute([
                            ':usuario_id' => $novoId,
                            ':modulo'     => $mod,
                        ]);
                    }
                }

                // E-mail com dados de acesso (se servidor permitir)
                $assunto = 'Acesso ao sistema da clínica';
                $mensagemEmail = "Olá,\n\n"
                    . "Você foi cadastrado no sistema da clínica.\n\n"
                    . "Acesse com:\n"
                    . "E-mail: {$email}\n"
                    . "Senha: {$senha}\n\n"
                    . "Recomendamos que altere a senha após o primeiro acesso.\n";

                @mail($email, $assunto, $mensagemEmail);

                $mensagem = 'Usuário criado com sucesso. Se configurado no servidor, o e-mail foi enviado.';
            }
        }

    // ATUALIZAR
    } elseif ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        if ($id <= 0) {
            $erro = 'ID inválido.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            // Verifica se e-mail já existe em outro user
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email AND id <> :id");
            $stmt->execute([':email' => $email, ':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                $erro = 'Já existe outro usuário com este e-mail.';
            } else {
                if ($senha !== '') {
                    if (!senha_eh_forte($senha)) {
                        $erro = 'A nova senha precisa ter pelo menos 8 caracteres, com letras maiúsculas, minúsculas e números.';
                    } else {
                        $hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE usuarios
                            SET email = :email,
                                senha_hash = :senha_hash
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':email'      => $email,
                            ':senha_hash' => $hash,
                            ':id'         => $id,
                        ]);
                        $mensagem = 'Usuário atualizado com nova senha.';
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
                    $stmt->execute([
                        ':email' => $email,
                        ':id'    => $id,
                    ]);
                    $mensagem = 'Usuário atualizado com sucesso.';
                }

                // Atualiza módulos
                $modulosSelecionados = $_POST['modulos'] ?? [];
                if (!is_array($modulosSelecionados)) {
                    $modulosSelecionados = [];
                }
                $modulosSelecionados = array_intersect(array_keys($modulosDisponiveis), $modulosSelecionados);

                // Apaga módulos antigos
                $stmtDel = $pdo->prepare("DELETE FROM usuarios_modulos WHERE usuario_id = :id");
                $stmtDel->execute([':id' => $id]);

                if (!empty($modulosSelecionados)) {
                    $stmtPerm = $pdo->prepare("
                        INSERT INTO usuarios_modulos (usuario_id, modulo)
                        VALUES (:usuario_id, :modulo)
                    ");
                    foreach ($modulosSelecionados as $mod) {
                        $stmtPerm->execute([
                            ':usuario_id' => $id,
                            ':modulo'     => $mod,
                        ]);
                    }
                }

                // Se o usuário editado for o próprio logado e o e-mail mudou
                if ($usuarioLogadoId && $usuarioLogadoId === $id) {
                    $_SESSION['usuario_email'] = $email;
                }
            }
        }

    // EXCLUIR
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $erro = 'ID inválido para exclusão.';
        } elseif ($usuarioLogadoId && $usuarioLogadoId === $id) {
            $erro = 'Você não pode excluir o próprio usuário logado.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensagem = 'Usuário excluído com sucesso.';
        }
    }
}

// Lista de usuários
$stmtLista = $pdo->query("SELECT id, email FROM usuarios ORDER BY id ASC");
$usuarios = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

// Usuário em edição
$usuarioEditando = null;
$modulosUsuario  = [];
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $usuarioEditando = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioEditando) {
        $stmt = $pdo->prepare("SELECT modulo FROM usuarios_modulos WHERE usuario_id = :id");
        $stmt->execute([':id' => $usuarioEditando['id']]);
        $modulosUsuario = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Espaço Vital Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h1>Cadastro de Usuários</h1>

    <?php include 'user_info.php'; ?>

    <?php if ($mensagem): ?>
        <div class="msg-alerta" style="background-color:#ecfdf3;border-color:#22c55e;color:#166534;">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="msg-alerta">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><?= $usuarioEditando ? 'Editar usuário' : 'Novo usuário' ?></h2>
        <p class="info">
            Senhas devem ter pelo menos 8 caracteres, com letras maiúsculas, minúsculas e números.<br>
            Se nenhum módulo for marcado, o usuário terá acesso a todos os módulos (modo compatibilidade).
        </p>

        <form method="post">
            <?php if ($usuarioEditando): ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$usuarioEditando['id'] ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($usuarioEditando['email'] ?? '') ?>" required>

            <label for="senha">
                <?= $usuarioEditando ? 'Nova senha (opcional)' : 'Senha' ?>
            </label>
            <input type="password" id="senha" name="senha"
                   <?= $usuarioEditando ? '' : 'required' ?>>

            <fieldset class="modulos-fieldset">
                <legend>Módulos com acesso</legend>
                <div class="modulos-grid">
                    <?php foreach ($modulosDisponiveis as $slug => $label): ?>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="modulos[]"
                                   value="<?= htmlspecialchars($slug) ?>"
                                <?= in_array($slug, $modulosUsuario, true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <button type="submit" style="margin-top:12px;">
                <?= $usuarioEditando ? 'Salvar alterações' : 'Cadastrar usuário' ?>
            </button>

            <?php if ($usuarioEditando): ?>
                <a href="usuarios.php" class="btn-secondary" style="margin-left:8px;">Cancelar edição</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>Usuários cadastrados</h2>
        <?php if (empty($usuarios)): ?>
            <p>Não há usuários cadastrados.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>E-mail</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <a class="btn-link" href="usuarios.php?edit=<?= (int)$u['id'] ?>">Editar</a>
                            |
                            <form method="post" class="actions-form"
                                  onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
