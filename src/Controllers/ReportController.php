<?php

namespace Clinica\Controllers;

use Clinica\Core\Controller;
use Clinica\Core\Auth;
use Clinica\Models\Professional;
use Clinica\Models\Room;
use Clinica\Models\Registry;
use DateTime;

class ReportController extends Controller
{
    public function index()
    {
        Auth::requirePermission('relatorios'); // Assuming 'relatorios' permission exists/is mapped

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

        // Names for display/filtering
        $profNome = null;
        if ($profissional_id > 0) {
            $p = Professional::find($profissional_id);
            if ($p)
                $profNome = $p['nome'];
        }

        $salaNome = null;
        if ($sala_id > 0) {
            $s = Room::find($sala_id);
            if ($s)
                $salaNome = $s['nome'];
        }

        $filters = [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'profissional' => $profNome,
            'sala' => $salaNome
        ];

        // Fetch Data using Model
        $resumoDia = Registry::getSummaryByDay($filters);
        $resumoProf = Registry::getSummaryByProfessional($filters);
        $resumoSala = Registry::getSummaryByRoom($filters);
        $totalGeral = Registry::getTotalHours($filters);
        $detalhes = Registry::search($filters);

        $this->view('reports/index', [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'profNome' => $profNome,
            'salaNome' => $salaNome,
            'resumoDia' => $resumoDia,
            'resumoProf' => $resumoProf,
            'resumoSala' => $resumoSala,
            'totalGeral' => $totalGeral,
            'detalhes' => $detalhes,

            // Should optionaly pass query string for 'back' link to dashboard with same filters?
            // Actually dashboard handles its own state via GET params so we can just link back.
        ]);
    }
}
