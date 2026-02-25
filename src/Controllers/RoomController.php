<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Room;

class RoomController extends Controller
{
    public function index()
    {
        Auth::requirePermission('salas');

        $mensagem = '';
        $erro = '';
        $editando = null;

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

        if (isset($_GET['edit'])) {
            $id = (int) $_GET['edit'];
            $editando = Room::find($id);
        }

        $this->view('rooms/index', [
            'salas' => Room::allIncludingInactive(),
            'editando' => $editando,
            'mensagem' => $mensagem,
            'erro' => $erro
        ]);
    }

    private function handleCreate(&$mensagem, &$erro)
    {
        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            $erro = 'Nome é obrigatório.';
            return;
        }

        Room::create($nome);
        $mensagem = 'Sala cadastrada com sucesso.';
    }

    private function handleUpdate(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $nome === '') {
            $erro = 'Dados inválidos.';
            return;
        }

        Room::update($id, $nome, $ativo);
        $mensagem = 'Sala atualizada.';
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }

        // Soft delete
        Room::delete($id);
        $mensagem = 'Sala desativada.';
    }
}
