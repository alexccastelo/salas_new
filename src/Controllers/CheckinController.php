<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Professional;
use Clinica\Models\Room;
use Clinica\Models\Registry;
use Clinica\Helpers\DateHelper;
use DateTime;

class CheckinController extends Controller
{
    public function index()
    {
        Auth::requirePermission('checkin');

        $mensagemSistema = '';
        $mensagemWhatsApp = '';
        $erro = '';

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \Clinica\Helpers\Csrf::requireValidation();
            $action = $_POST['action'] ?? '';

            if ($action === 'novo_checkin') {
                $this->handleNewCheckin($mensagemSistema, $mensagemWhatsApp, $erro);
            } elseif ($action === 'checkout') {
                $this->handleCheckout($mensagemSistema, $mensagemWhatsApp, $erro);
            }
        }

        // Prepare data for View
        $busca = trim($_GET['busca'] ?? '');
        $finalizadosPage = max(1, (int) ($_GET['pagina'] ?? $_GET['page'] ?? 1));
        $porPagina = 5;
        $offset = ($finalizadosPage - 1) * $porPagina;

        $totalItens = Registry::countFinished($busca);
        $totalPaginas = ceil($totalItens / $porPagina);

        $data = [
            'profissionais' => Professional::all(),
            'salas' => Room::all(),
            'abertos' => Registry::getOpen(),
            'finalizados' => Registry::getFinished($porPagina, $offset, $busca),
            'totalPaginas' => $totalPaginas,
            'paginaAtual' => $finalizadosPage,
            'busca' => $busca,
            'mensagemSistema' => $mensagemSistema,
            'mensagemWhatsApp' => $mensagemWhatsApp,
            'erro' => $erro
        ];

        $this->view('checkin/index', $data);
    }

    private function handleNewCheckin(&$msgSys, &$msgWhatsapp, &$erro)
    {
        $profissionalId = (int) ($_POST['profissional_id'] ?? 0);
        $salaId = (int) ($_POST['sala_id'] ?? 0);
        $data = trim($_POST['data'] ?? '');
        $horaCheckin = trim($_POST['hora_checkin'] ?? '');

        if ($profissionalId <= 0 || $salaId <= 0) {
            $erro = 'Selecione o profissional e a sala.';
            return;
        }

        if ($data === '')
            $data = date('Y-m-d');
        if ($horaCheckin === '')
            $horaCheckin = date('H:i');

        $prof = Professional::find($profissionalId);
        $sala = Room::find($salaId);

        if (!$prof || !$sala) {
            $erro = 'Erro ao localizar profissional ou sala.';
            return;
        }

        // Insert
        // Now using IDs
        $success = Registry::create([
            'profissional_id' => $prof['id'],
            'sala_id' => $sala['id'],
            'data' => $data,
            'hora_checkin' => $horaCheckin
        ]);

        if ($success) {
            $msgSys = "Check-in registrado com sucesso para {$prof['nome']} na sala {$sala['nome']}.";

            $dataFmt = DateHelper::formatBr($data);
            $msgWhatsapp = "Olá, {$prof['nome']}! Seu check-in para uso da sala {$sala['nome']} foi registrado:\n" .
                "Data: {$dataFmt}\n" .
                "Check-in: {$horaCheckin}.";
        } else {
            $erro = 'Erro ao salvar no banco de dados.';
        }
    }

    private function handleCheckout(&$msgSys, &$msgWhatsapp, &$erro)
    {
        $registroId = (int) ($_POST['registro_id'] ?? 0);
        $horaCheckout = trim($_POST['hora_checkout'] ?? '');

        if ($registroId <= 0) {
            $erro = 'Registro inválido.';
            return;
        }
        if ($horaCheckout === '')
            $horaCheckout = date('H:i');

        $reg = Registry::find($registroId);

        if (!$reg) {
            $erro = 'Registro não encontrado.';
            return;
        }
        if (!empty($reg['hora_checkout'])) {
            $erro = 'Este registro já possui check-out.';
            return;
        }

        $totalHoras = DateHelper::calcularHorasDecimais($reg['data'], $reg['hora_checkin'], $horaCheckout);

        $success = Registry::checkout($registroId, $horaCheckout, $totalHoras);

        if ($success) {
            $msgSys = "Check-out registrado com sucesso para {$reg['profissional']}.";

            $dataFmt = DateHelper::formatBr($reg['data']);
            $msgWhatsapp = "Olá, {$reg['profissional']}! Segue o registro de uso da sala {$reg['sala']}:\n" .
                "Data: {$dataFmt}\n" .
                "Check-in: {$reg['hora_checkin']}\n" .
                "Check-out: {$horaCheckout}\n" .
                "Tempo total utilizado: " . number_format((float) $totalHoras, 2, ',', '') . " horas.";
        } else {
            $erro = 'Erro ao atualizar registro.';
        }
    }
}
