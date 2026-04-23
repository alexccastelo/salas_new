<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Package;
use Clinica\Models\Professional;

class PackageController extends Controller
{
    public function index()
    {
        Auth::requirePermission('pacotes');

        $mensagem = '';
        $erro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \Clinica\Helpers\Csrf::requireValidation();
            $action = $_POST['action'] ?? '';

            if ($action === 'criar_pacote') {
                $this->handleCreate($mensagem, $erro);
            } elseif ($action === 'usar_parcela') {
                $this->handleUseInstallment($mensagem, $erro);
            } elseif ($action === 'editar_parcela') {
                $this->handleEditInstallment($mensagem, $erro);
            } elseif ($action === 'excluir_pacote') {
                $this->handleDelete($mensagem, $erro);
            }
        }

        // View logic
        $pacoteSelecionado = null;
        $parcelasPacote = [];
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $pacoteSelecionado = Package::find($id);
            if ($pacoteSelecionado) {
                $parcelasPacote = Package::getInstallments($id);
            }
        }

        $busca = trim($_GET['busca'] ?? '');
        $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($paginaAtual < 1) $paginaAtual = 1;
        $itensPorPagina = 5;
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        $totalItens = Package::countFiltered($busca);
        $totalPaginas = ceil($totalItens / $itensPorPagina);
        $pacotes = Package::getPaginatedAndFiltered($busca, $itensPorPagina, $offset);

        $this->view('packages/index', [
            'pacotes' => $pacotes,
            'profissionais' => Professional::all(),
            'pacoteSelecionado' => $pacoteSelecionado,
            'parcelasPacote' => $parcelasPacote,
            'mensagem' => $mensagem,
            'erro' => $erro,
            'busca' => $busca,
            'paginaAtual' => $paginaAtual,
            'totalPaginas' => $totalPaginas
        ]);
    }

    private function handleCreate(&$mensagem, &$erro)
    {
        $tipo = $_POST['tipo'] ?? 'paciente';
        $contratanteNome = trim($_POST['contratante_nome'] ?? '');
        $profissionalRelacionado = trim($_POST['profissional_relacionado'] ?? '');
        $pacienteRelacionado = trim($_POST['paciente_relacionado'] ?? '');
        $dataContrato = $_POST['data_contrato'] ?? date('Y-m-d');
        $dataPagamento = $_POST['data_pagamento'] ?? '';
        $pagamentoAoFinal = isset($_POST['pagamento_ao_final']) ? 1 : 0;
        $quantidade = (int) ($_POST['quantidade'] ?? 0);
        $valorUnitarioStr = $_POST['valor_unitario'] ?? '0';

        $valorUnitario = $this->parseMoney($valorUnitarioStr);

        if ($contratanteNome === '') {
            $erro = 'Nome contratante obrigatório.';
            return;
        }
        if ($quantidade <= 0) {
            $erro = 'Quantidade inválida.';
            return;
        }
        if ($valorUnitario <= 0) {
            $erro = 'Valor unitário inválido.';
            return;
        }

        $valorTotal = $quantidade * $valorUnitario;

        try {
            Package::create([
                'tipo' => $tipo,
                'contratante_nome' => $contratanteNome,
                'profissional_relacionado' => $profissionalRelacionado,
                'paciente_relacionado' => $pacienteRelacionado,
                'data_contrato' => $dataContrato,
                'data_pagamento' => $dataPagamento,
                'pagamento_ao_final' => $pagamentoAoFinal,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal
            ]);
            $mensagem = 'Pacote criado com sucesso.';
        } catch (\Exception $e) {
            $erro = 'Erro ao criar pacote: ' . $e->getMessage();
        }
    }

    private function handleUseInstallment(&$mensagem, &$erro)
    {
        $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
        $dataUtilizacao = $_POST['data_utilizacao'] ?? date('Y-m-d');

        if ($parcelaId <= 0) {
            $erro = 'Parcela inválida.';
            return;
        }

        Package::markInstallmentUsed($parcelaId, $dataUtilizacao);
        $mensagem = 'Parcela marcada.';
    }

    private function handleEditInstallment(&$mensagem, &$erro)
    {
        $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
        $acaoEdicao = $_POST['acao_edicao'] ?? ''; // 'atualizar' ou 'desmarcar'
        $dataUtilizacao = $_POST['data_utilizacao'] ?? date('Y-m-d');

        if ($parcelaId <= 0) {
            $erro = 'Parcela inválida.';
            return;
        }

        if ($acaoEdicao === 'desmarcar') {
            Package::updateInstallment($parcelaId, null, 0);
            $mensagem = 'Marcação de uso removida.';
        } else {
            Package::updateInstallment($parcelaId, $dataUtilizacao, 1);
            $mensagem = 'Marcação de uso atualizada.';
        }
    }

    private function handleDelete(&$mensagem, &$erro)
    {
        $id = (int) ($_POST['pacote_id'] ?? 0);
        if ($id <= 0) {
            $erro = 'ID inválido.';
            return;
        }
        Package::delete($id);
        $mensagem = 'Pacote excluído.';
    }

    private function parseMoney($str)
    {
        $v = trim($str);
        $v = str_replace(['.', ' '], ['', ''], $v);
        $v = str_replace(',', '.', $v);
        return (float) $v;
    }
}
