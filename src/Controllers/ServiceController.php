<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        Auth::requirePermission('servicos');

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
            $editando = Service::find($id);
        }

        $this->view('services/index', [
            'servicos' => Service::allIncludingInactive(),
            'todos_servicos' => Service::all(), // para select de vínculo
            'editando' => $editando,
            'mensagem' => $mensagem,
            'erro' => $erro
        ]);
    }

    private function extractData()
    {
        return [
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'especialidade' => trim($_POST['especialidade'] ?? ''),
            'tempo_atendimento' => $_POST['tempo_atendimento'] ?? 60,
            'quantidade_sessoes' => $_POST['quantidade_sessoes'] ?? '',
            'servico_vinculado_id' => $_POST['servico_vinculado_id'] ?? '',
            'valor_base' => $_POST['valor_base'] ?? '',
            'valor_avista' => $_POST['valor_avista'] ?? '',
            'valor_prazo' => $_POST['valor_prazo'] ?? '',
            'qtd_parcelas' => $_POST['qtd_parcelas'] ?? '',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function handleCreate(&$mensagem, &$erro)
    {
        $data = $this->extractData();
        if ($data['nome'] === '') {
            $erro = 'Nome do serviço é obrigatório.';
            return;
        }
        Service::create($data);
        $mensagem = 'Serviço cadastrado com sucesso.';
    }

    private function handleUpdate(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->extractData();
        if ($id <= 0 || $data['nome'] === '') {
            $erro = 'Dados inválidos.';
            return;
        }
        Service::update($id, $data);
        $mensagem = 'Serviço atualizado.';
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }
        Service::delete($id);
        $mensagem = 'Serviço desativado.';
    }
}
