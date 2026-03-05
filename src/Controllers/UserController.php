<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\User;

class UserController extends Controller
{
    private $modulosDisponiveis = [
        'dashboard' => 'Dashboard',
        'checkin' => 'Check-in',
        'registros' => 'Registros',
        'pacotes' => 'Pacotes',
        'relatorios' => 'Relatórios',
        'profissionais' => 'Profissionais',
        'salas' => 'Salas',
        'servicos' => 'Serviços',
        'usuarios' => 'Administração de usuários',
    ];

    public function index()
    {
        Auth::requirePermission('usuarios');

        $mensagem = '';
        $erro = '';
        $usuarioEditando = null;
        $modulosUsuario = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \Clinica\Helpers\Csrf::requireValidation();
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $this->handleCreate($mensagem, $erro);
            } elseif ($action === 'update') {
                $this->handleUpdate($mensagem, $erro);
            } elseif ($action === 'delete') {
                $this->handleDelete($mensagem, $erro);
            }
        }

        // Edit Mode
        if (isset($_GET['edit'])) {
            $id = (int) $_GET['edit'];
            $usuarioEditando = User::find($id);
            if ($usuarioEditando) {
                $modulosUsuario = User::getPermissions($id);
            }
        }

        $this->view('users/index', [
            'usuarios' => User::all(),
            'usuarioEditando' => $usuarioEditando,
            'modulosUsuario' => $modulosUsuario,
            'modulosDisponiveis' => $this->modulosDisponiveis,
            'mensagem' => $mensagem,
            'erro' => $erro
        ]);
    }

    private function handleCreate(&$mensagem, &$erro)
    {
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $modulos = $_POST['modulos'] ?? [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
            return;
        }
        if (!$this->senhaForte($senha)) {
            $erro = 'A senha deve ter 8+ caracteres, maiúsculas, minúsculas e números.';
            return;
        }
        if (User::exists($email)) {
            $erro = 'E-mail já cadastrado.';
            return;
        }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $id = User::create($email, $hash);

        // Filter valid modules
        if (!is_array($modulos)) {
            $modulos = [];
        }
        $validKeys = array_keys($this->modulosDisponiveis);
        $modulosToSave = [];

        foreach ($modulos as $m) {
            if (in_array($m, $validKeys)) {
                $modulosToSave[] = $m;
            }
        }

        User::setPermissions($id, $modulosToSave);

        $mensagem = 'Usuário criado com sucesso.';
    }

    private function handleUpdate(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $modulos = $_POST['modulos'] ?? [];

        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
            return;
        }

        if (User::exists($email, $id)) {
            $erro = 'E-mail já em uso por outro usuário.';
            return;
        }

        $hash = null;
        if (!empty($senha)) {
            if (!$this->senhaForte($senha)) {
                $erro = 'A senha deve ter 8+ caracteres, maiúsculas, minúsculas e números.';
                return;
            }
            $hash = password_hash($senha, PASSWORD_DEFAULT);
        }

        User::update($id, $email, $hash);

        // Ensure modulos is an array
        if (!is_array($modulos)) {
            $modulos = [];
        }

        $validKeys = array_keys($this->modulosDisponiveis);
        $modulosToSave = [];

        foreach ($modulos as $m) {
            if (in_array($m, $validKeys)) {
                $modulosToSave[] = $m;
            }
        }

        User::setPermissions($id, $modulosToSave);

        // Update session if self-edit
        if (Auth::id() === $id) {
            $_SESSION['usuario_email'] = $email;
        }

        $mensagem = 'Usuário atualizado com sucesso.';
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === Auth::id()) {
            $erro = 'Não pode excluir a si mesmo.';
            return;
        }
        User::delete($id);
        $mensagem = 'Usuário excluído.';
    }

    private function senhaForte($senha)
    {
        if (strlen($senha) < 8)
            return false;
        if (!preg_match('/[A-Z]/', $senha))
            return false;
        if (!preg_match('/[a-z]/', $senha))
            return false;
        if (!preg_match('/\d/', $senha))
            return false;
        return true;
    }
}
