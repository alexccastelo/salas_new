<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Professional;

class ProfessionalController extends Controller
{
    public function index()
    {
        Auth::requirePermission('profissionais');

        $mensagem = '';
        $erro = '';
        $editando = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $editando = Professional::find($id);
        }

        $this->view('professionals/index', [
            'profissionais' => Professional::allIncludingInactive(),
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

        Professional::create($nome);
        $mensagem = 'Profissional cadastrado com sucesso.';
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

        Professional::update($id, $nome, $ativo);
        $mensagem = 'Profissional atualizado.';
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }

        // Soft delete
        Professional::delete($id);
        $mensagem = 'Profissional desativado.';
    }
}
