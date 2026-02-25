<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Professional;
use Clinica\Models\Room;
use Clinica\Helpers\DateHelper;
use Clinica\Core\Database;
use PDO;
use DateTime;

class DashboardController extends Controller
{
    public function index()
    {
        Auth::check();

        $pdo = Database::getInstance()->getConnection();

        $data_inicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : '';
        $data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';
        $profissional_id = isset($_GET['profissional_id']) ? (int) $_GET['profissional_id'] : 0;
        $sala_id = isset($_GET['sala_id']) ? (int) $_GET['sala_id'] : 0;

        // Defaults
        if ($data_inicio === '' && $data_fim === '') {
            $data_fim = date('Y-m-d');
            $data_inicio = date('Y-m-d', strtotime('-6 days'));
        } elseif ($data_inicio === '' && $data_fim !== '') {
            $data_inicio = $data_fim;
        } elseif ($data_inicio !== '' && $data_fim === '') {
            $data_fim = $data_inicio;
        }

        // Filters Labels
        $profNomeFiltro = null;
        if ($profissional_id > 0) {
            $p = Professional::find($profissional_id);
            if ($p)
                $profNomeFiltro = $p['nome'];
        }

        $salaNomeFiltro = null;
        if ($sala_id > 0) {
            $s = Room::find($sala_id);
            if ($s)
                $salaNomeFiltro = $s['nome'];
        }

        $filters = [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'profissional' => $profNomeFiltro,
            'sala' => $salaNomeFiltro
        ];

        // CSV Export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv($filters);
            return;
        }

        // Summaries
        $resumoDia = \Clinica\Models\Registry::getSummaryByDay($filters);
        $resumoProf = \Clinica\Models\Registry::getSummaryByProfessional($filters);
        $resumoSala = \Clinica\Models\Registry::getSummaryByRoom($filters);
        $totalGeral = \Clinica\Models\Registry::getTotalHours($filters);

        $this->view('dashboard/index', [
            'profissionais' => Professional::all(),
            'salas' => Room::all(),
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'profissional_id' => $profissional_id,
            'sala_id' => $sala_id,
            'resumoDia' => $resumoDia,
            'resumoProf' => $resumoProf,
            'resumoSala' => $resumoSala,
            'totalGeral' => $totalGeral
        ]);
    }

    private function exportCsv($filters)
    {
        $rows = \Clinica\Models\Registry::search($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio_salas_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

        fputcsv($output, ['Data', 'Profissional', 'Sala', 'Check-in', 'Check-out', 'Horas'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                DateHelper::formatBr($row['data']),
                $row['profissional'],
                $row['sala'],
                $row['hora_checkin'],
                $row['hora_checkout'],
                number_format((float) $row['total_horas'], 2, ',', '')
            ], ';');
        }
        fclose($output);
        exit;
    }
}
