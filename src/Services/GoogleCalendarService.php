<?php

namespace Clinica\Services;

use Clinica\Core\Database;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
    private Client $client;
    private Calendar $service;
    private string $defaultCalendarId;

    public function __construct()
    {
        $this->defaultCalendarId = $_ENV['GOOGLE_CALENDAR_ID'] ?? 'primary';
        $this->client = $this->buildClient();
        $this->service = new Calendar($this->client);
    }

    // ── OAuth2 ──────────────────────────────────────────────────────────────

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function exchangeCode(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException('Erro ao trocar código OAuth: ' . $token['error_description']);
        }

        $this->saveToken($token);
    }

    public function isAuthorized(): bool
    {
        $token = $this->loadToken();
        return $token !== null;
    }

    // ── Eventos: App → Google ───────────────────────────────────────────────

    /**
     * @param array  $appointment  Dados do agendamento (incluindo 'id', 'paciente', etc.)
     * @param string|null $calendarId  ID da agenda Google da sala; usa o padrão se omitido.
     */
    public function createEvent(array $appointment, ?string $calendarId = null): string
    {
        $cal   = $calendarId ?: $this->defaultCalendarId;
        $event = $this->buildEvent($appointment);
        $created = $this->service->events->insert($cal, $event);
        return $created->getId();
    }

    /**
     * @param string      $googleEventId
     * @param array       $appointment
     * @param string|null $calendarId  ID da agenda Google da sala; usa o padrão se omitido.
     */
    public function updateEvent(string $googleEventId, array $appointment, ?string $calendarId = null): void
    {
        $cal   = $calendarId ?: $this->defaultCalendarId;
        $event = $this->buildEvent($appointment);
        $this->service->events->update($cal, $googleEventId, $event);
    }

    /**
     * @param string      $googleEventId
     * @param string|null $calendarId  ID da agenda Google da sala; usa o padrão se omitido.
     */
    public function cancelEvent(string $googleEventId, ?string $calendarId = null): void
    {
        $cal   = $calendarId ?: $this->defaultCalendarId;
        $event = $this->service->events->get($cal, $googleEventId);
        $event->setStatus('cancelled');
        $this->service->events->update($cal, $googleEventId, $event);
    }

    // ── Eventos: Google → App (polling) ─────────────────────────────────────

    /**
     * Lista eventos modificados desde $since em um calendário específico.
     * Retorna apenas eventos com a propriedade privada 'salas_id' (originados no sistema).
     *
     * @param string $calendarId  ID do calendário Google a consultar.
     * @param string $since       Timestamp RFC3339 (ex: '2025-01-01T00:00:00-03:00').
     */
    public function fetchUpdatedEvents(string $calendarId, string $since): array
    {
        $params = [
            'updatedMin'   => $since,
            'singleEvents' => true,
            'showDeleted'  => true,
            'maxResults'   => 250,
        ];

        $results = [];
        $pageToken = null;

        do {
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $eventsResult = $this->service->events->listEvents($calendarId, $params);

            foreach ($eventsResult->getItems() as $event) {
                $extProps = $event->getExtendedProperties();
                $private  = $extProps ? $extProps->getPrivate() : null;

                if ($private && isset($private['salas_id'])) {
                    $results[] = [
                        'google_event_id'   => $event->getId(),
                        'salas_id'          => (int) $private['salas_id'],
                        'profissional_id'   => (int) ($private['salas_profissional_id'] ?? 0),
                        'summary'           => $event->getSummary(),
                        'description'       => $event->getDescription(),
                        'status'            => $event->getStatus(), // 'confirmed' | 'cancelled'
                        'data_inicio'       => $this->parseEventDateTime($event->getStart()),
                        'data_fim'          => $this->parseEventDateTime($event->getEnd()),
                    ];
                }
            }

            $pageToken = $eventsResult->getNextPageToken();
        } while ($pageToken);

        return $results;
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');
        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $token = $this->loadToken();
        if ($token) {
            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired()) {
                $refreshed = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                if (!isset($refreshed['error'])) {
                    $this->saveToken($refreshed);
                }
            }
        }

        return $client;
    }

    private function buildEvent(array $appointment): Event
    {
        $event = new Event();
        $event->setSummary($appointment['paciente']);
        $event->setDescription($appointment['observacoes'] ?? '');
        $event->setStatus('confirmed');

        $start = new EventDateTime();
        $start->setDateTime($this->toRfc3339($appointment['data_inicio']));
        $start->setTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
        $event->setStart($start);

        $end = new EventDateTime();
        $end->setDateTime($this->toRfc3339($appointment['data_fim']));
        $end->setTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
        $event->setEnd($end);

        $extProps = new \Google\Service\Calendar\EventExtendedProperties();
        $extProps->setPrivate([
            'salas_id'              => (string) ($appointment['id'] ?? ''),
            'salas_profissional_id' => (string) ($appointment['profissional_id'] ?? ''),
        ]);
        $event->setExtendedProperties($extProps);

        return $event;
    }

    private function toRfc3339(string $datetime): string
    {
        $dt = new \DateTime($datetime, new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        return $dt->format(\DateTime::RFC3339);
    }

    private function parseEventDateTime(?\Google\Service\Calendar\EventDateTime $edt): string
    {
        if (!$edt) {
            return '';
        }
        $raw = $edt->getDateTime() ?: $edt->getDate();
        return (new \DateTime($raw))->format('Y-m-d H:i:s');
    }

    // ── Persistência do token ────────────────────────────────────────────────

    private function saveToken(array $token): void
    {
        $pdo  = Database::getInstance()->getConnection();
        $json = json_encode($token);

        $stmt = $pdo->prepare("
            INSERT INTO config (chave, valor) VALUES ('google_oauth_token', :valor)
            ON DUPLICATE KEY UPDATE valor = :valor2
        ");
        $stmt->execute([':valor' => $json, ':valor2' => $json]);
    }

    private function loadToken(): ?array
    {
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT valor FROM config WHERE chave = 'google_oauth_token'");
            $stmt->execute();
            $row = $stmt->fetch();

            if ($row && $row['valor']) {
                return json_decode($row['valor'], true);
            }
        } catch (\Throwable $e) {
            // Tabela config ainda não existe — primeira inicialização
        }

        return null;
    }
}
