<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Appointment;
use Clinica\Models\Professional;
use Clinica\Models\Room;

class AppointmentController extends Controller
{
    public function index()
    {
        Auth::requirePermission('agenda');

        // ── Semana de referência ──────────────────────────────────────────
        $semanaParam = $_GET['semana'] ?? null;          // formato: 2025-W10
        if ($semanaParam && preg_match('/^(\d{4})-W(\d{2})$/', $semanaParam, $m)) {
            $inicio = new \DateTime();
            $inicio->setISODate((int) $m[1], (int) $m[2], 1);  // segunda-feira
        } else {
            $inicio = new \DateTime();
            $diaSemana = (int) $inicio->format('N');           // 1=seg … 7=dom
            $inicio->modify('-' . ($diaSemana - 1) . ' days');
        }
        $inicio->setTime(0, 0, 0);
        $fim = clone $inicio;
        $fim->modify('+7 days');

        // Semanas adjacentes para navegação
        $semanaAnterior = clone $inicio;
        $semanaAnterior->modify('-7 days');
        $semanaProxima = clone $inicio;
        $semanaProxima->modify('+7 days');

        // ── Filtro por profissional ───────────────────────────────────────
        $filtroProf = isset($_GET['profissional_id']) && $_GET['profissional_id'] !== ''
            ? (int) $_GET['profissional_id']
            : null;

        // ── POST: criar / editar / cancelar ──────────────────────────────
        $mensagem = '';
        $erro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \Clinica\Helpers\Csrf::requireValidation();
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $this->handleCreate($mensagem, $erro);
            } elseif ($action === 'update') {
                $this->handleUpdate($mensagem, $erro);
            } elseif ($action === 'cancel') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    Appointment::cancel($id);
                    $mensagem = 'Agendamento cancelado.';
                }
            }
        }

        // ── Dados para a view ─────────────────────────────────────────────
        $agendamentos = Appointment::findByRange(
            $inicio->format('Y-m-d H:i:s'),
            $fim->format('Y-m-d H:i:s'),
            $filtroProf
        );

        // Agrupa por dia (Y-m-d) para facilitar na view
        $porDia = [];
        foreach ($agendamentos as $a) {
            $dia = substr($a['data_inicio'], 0, 10);
            $porDia[$dia][] = $a;
        }

        $this->view('agenda/index', [
            'inicio' => $inicio,
            'fim' => $fim,
            'semanaAnterior' => $semanaAnterior,
            'semanaProxima' => $semanaProxima,
            'porDia' => $porDia,
            'profissionais' => Professional::all(),
            'salas' => Room::all(),
            'filtroProf' => $filtroProf,
            'mensagem' => $mensagem,
            'erro' => $erro,
        ]);
    }

    // ── Helpers privados ─────────────────────────────────────────────────

    private function extractData(): array
    {
        $dataInicio = $_POST['data_inicio'] ?? '';
        $duracaoMin = (int) ($_POST['duracao'] ?? 60);

        // Calcula data_fim a partir de data_inicio + duração
        $dtInicio = \DateTime::createFromFormat('Y-m-d\TH:i', $dataInicio);
        $dtFim = $dtInicio ? (clone $dtInicio)->modify("+{$duracaoMin} minutes") : null;

        return [
            'profissional_id' => $_POST['profissional_id'] ?? '',
            'sala_id' => $_POST['sala_id'] ?? '',
            'paciente' => trim($_POST['paciente'] ?? ''),
            'servico_id' => $_POST['servico_id'] ?? '',
            'data_inicio' => $dtInicio ? $dtInicio->format('Y-m-d H:i:s') : '',
            'data_fim' => $dtFim ? $dtFim->format('Y-m-d H:i:s') : '',
            'duracao' => $duracaoMin,
            'status' => $_POST['status'] ?? 'Agendado',
            'observacoes' => trim($_POST['observacoes'] ?? ''),
        ];
    }

    private function handleCreate(string &$mensagem, string &$erro): void
    {
        $data = $this->extractData();
        if (!$data['profissional_id'] || !$data['paciente'] || !$data['data_inicio']) {
            $erro = 'Preencha profissional, paciente e data/hora.';
            return;
        }
        Appointment::create($data);
        $mensagem = 'Agendamento criado com sucesso.';
    }

    private function handleUpdate(string &$mensagem, string &$erro): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->extractData();
        if ($id <= 0 || !$data['profissional_id'] || !$data['paciente'] || !$data['data_inicio']) {
            $erro = 'Dados inválidos.';
            return;
        }
        Appointment::update($id, $data);
        $mensagem = 'Agendamento atualizado.';
    }
}
