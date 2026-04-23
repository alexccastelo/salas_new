<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Appointment;
use Clinica\Models\Professional;
use Clinica\Models\Room;
use Clinica\Services\GoogleCalendarService;

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
                    $existing = Appointment::find($id);
                    Appointment::cancel($id);
                    $this->syncCancel($existing);
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
        $id = Appointment::create($data);
        $data['id'] = $id;
        $this->syncCreate($id, $data);
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
        $existing = Appointment::find($id);
        $this->syncUpdate($existing);
        $mensagem = 'Agendamento atualizado.';
    }

    // ── Sincronização Google Calendar ────────────────────────────────────────

    /** Resolve o google_calendar_id da sala do agendamento, ou retorna null para usar o padrão. */
    private function resolveCalendarId(array $data): ?string
    {
        $salaId = (int) ($data['sala_id'] ?? 0);
        if ($salaId > 0) {
            $sala = Room::find($salaId);
            if ($sala && !empty($sala['google_calendar_id'])) {
                return $sala['google_calendar_id'];
            }
        }
        return null; // GoogleCalendarService usará GOOGLE_CALENDAR_ID do .env
    }

    private function syncCreate(int $id, array $data): void
    {
        try {
            $gcal = new GoogleCalendarService();
            if (!$gcal->isAuthorized()) {
                return;
            }
            $calendarId    = $this->resolveCalendarId($data);
            $googleEventId = $gcal->createEvent($data, $calendarId);
            Appointment::updateGoogleEventId($id, $googleEventId);
        } catch (\Throwable $e) {
            error_log('[GoogleCalendar] Erro ao criar evento: ' . $e->getMessage());
        }
    }

    private function syncUpdate(array|false $appointment): void
    {
        if (!$appointment || empty($appointment['google_event_id'])) {
            return;
        }
        try {
            $gcal = new GoogleCalendarService();
            if (!$gcal->isAuthorized()) {
                return;
            }
            $calendarId = $this->resolveCalendarId($appointment);
            $gcal->updateEvent($appointment['google_event_id'], $appointment, $calendarId);
            Appointment::updateGoogleEventId((int) $appointment['id'], $appointment['google_event_id']);
        } catch (\Throwable $e) {
            error_log('[GoogleCalendar] Erro ao atualizar evento: ' . $e->getMessage());
        }
    }

    private function syncCancel(array|false $appointment): void
    {
        if (!$appointment || empty($appointment['google_event_id'])) {
            return;
        }
        try {
            $gcal = new GoogleCalendarService();
            if (!$gcal->isAuthorized()) {
                return;
            }
            $calendarId = $this->resolveCalendarId($appointment);
            $gcal->cancelEvent($appointment['google_event_id'], $calendarId);
        } catch (\Throwable $e) {
            error_log('[GoogleCalendar] Erro ao cancelar evento: ' . $e->getMessage());
        }
    }
}
