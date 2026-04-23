#!/usr/bin/env php
<?php
/**
 * Script de polling: sincroniza eventos do Google Calendar → agenda local.
 *
 * Uso:
 *   php src/Commands/SyncGoogleCalendar.php
 *
 * Cron (a cada 10 minutos):
 *   */10 * * * * php /caminho/para/app/src/Commands/SyncGoogleCalendar.php >> /var/log/salas_gcal_sync.log 2>&1
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

use Clinica\Core\Database;
use Clinica\Models\Appointment;
use Clinica\Models\Room;
use Clinica\Services\GoogleCalendarService;

$pdo = Database::getInstance()->getConnection();

// Garante que as tabelas existam antes de consultar
require_once __DIR__ . '/../../config.php';

// ── Verifica autorização ─────────────────────────────────────────────────────
$gcal = new GoogleCalendarService();
if (!$gcal->isAuthorized()) {
    echo "[" . date('Y-m-d H:i:s') . "] Google Calendar não autorizado. Acesse /google/auth para configurar.\n";
    exit(0);
}

// ── Recupera timestamp da última sincronização ───────────────────────────────
$stmt = $pdo->prepare("SELECT valor FROM config WHERE chave = 'google_last_sync'");
$stmt->execute();
$row = $stmt->fetch();

// Usa 24h atrás como fallback na primeira execução
$since = $row ? $row['valor'] : (new DateTime('-24 hours'))->format(DateTime::RFC3339);
$now   = (new DateTime())->format(DateTime::RFC3339);

echo "[" . date('Y-m-d H:i:s') . "] Buscando eventos atualizados desde: {$since}\n";

// ── Descobre todos os calendários a consultar ────────────────────────────────
$calendarIds = Room::allGoogleCalendarIds();
echo "[" . date('Y-m-d H:i:s') . "] Consultando " . count($calendarIds) . " calendário(s): " . implode(', ', $calendarIds) . "\n";

// ── Busca eventos em todos os calendários ────────────────────────────────────
$events = [];
foreach ($calendarIds as $calendarId) {
    try {
        $found   = $gcal->fetchUpdatedEvents($calendarId, $since);
        $events  = array_merge($events, $found);
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO ao buscar calendário '{$calendarId}': " . $e->getMessage() . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] " . count($events) . " evento(s) encontrado(s) no total.\n";

// ── Processa cada evento ─────────────────────────────────────────────────────
$atualizados  = 0;
$ignorados    = 0;

foreach ($events as $ev) {
    $salasId = $ev['salas_id'];

    if ($salasId <= 0) {
        $ignorados++;
        continue;
    }

    $appointment = Appointment::find($salasId);
    if (!$appointment) {
        $ignorados++;
        continue;
    }

    // Evento cancelado no Google → cancela no sistema
    if ($ev['status'] === 'cancelled' && $appointment['status'] !== 'Cancelado') {
        Appointment::cancel($salasId);
        echo "[" . date('Y-m-d H:i:s') . "] Agendamento #{$salasId} cancelado via Google Calendar.\n";
        $atualizados++;
        continue;
    }

    // Evento editado no Google → atualiza horários no sistema
    if ($ev['status'] === 'confirmed' && $ev['data_inicio'] && $ev['data_fim']) {
        $novosDados = array_merge($appointment, [
            'data_inicio' => $ev['data_inicio'],
            'data_fim'    => $ev['data_fim'],
            'paciente'    => $ev['summary'] ?: $appointment['paciente'],
            'observacoes' => $ev['description'] ?: $appointment['observacoes'],
        ]);
        Appointment::update($salasId, $novosDados);
        echo "[" . date('Y-m-d H:i:s') . "] Agendamento #{$salasId} atualizado via Google Calendar.\n";
        $atualizados++;
    }
}

// ── Atualiza timestamp da última sincronização ───────────────────────────────
$upsert = $pdo->prepare("
    INSERT INTO config (chave, valor) VALUES ('google_last_sync', :valor)
    ON DUPLICATE KEY UPDATE valor = :valor2
");
$upsert->execute([':valor' => $now, ':valor2' => $now]);

echo "[" . date('Y-m-d H:i:s') . "] Concluído. Atualizados: {$atualizados} | Ignorados: {$ignorados}\n";
