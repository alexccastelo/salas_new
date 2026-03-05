<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Room;
use Clinica\Models\Service;

class RoomController extends Controller
{
    public function index()
    {
        Auth::requirePermission('salas');

        $mensagem = '';
        $erro = '';
        $editando = null;
        $servicosVinculados = [];

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
            if ($editando) {
                $servicosVinculados = Room::getServicoIds($id);
            }
        }

        $this->view('rooms/index', [
            'salas' => Room::allIncludingInactive(),
            'servicos' => Service::all(),
            'editando' => $editando,
            'servicosVinculados' => $servicosVinculados,
            'mensagem' => $mensagem,
            'erro' => $erro
        ]);
    }

    private function extractData()
    {
        return [
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'capacidade' => $_POST['capacidade'] ?? '',
            'cor_agenda' => $_POST['cor_agenda'] ?? '#10B981',
            'servicos' => $_POST['servicos'] ?? [],
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function handleCreate(&$mensagem, &$erro)
    {
        $data = $this->extractData();
        if ($data['nome'] === '') {
            $erro = 'Nome é obrigatório.';
            return;
        }
        Room::create($data);
        $mensagem = 'Sala cadastrada com sucesso.';
    }

    private function handleUpdate(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->extractData();
        if ($id <= 0 || $data['nome'] === '') {
            $erro = 'Dados inválidos.';
            return;
        }
        Room::update($id, $data);
        $mensagem = 'Sala atualizada.';
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }
        Room::delete($id);
        $mensagem = 'Sala desativada.';
    }
}
