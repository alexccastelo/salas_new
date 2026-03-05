<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Appointment
{
    /**
     * Retorna agendamentos entre duas datas (para grade semanal).
     */
    public static function findByRange(string $inicio, string $fim, ?int $profissionalId = null): array
    {
        $pdo = Database::getInstance()->getConnection();
        $sql = "
            SELECT a.*,
                   p.nome AS profissional_nome,
                   p.cor_agenda AS profissional_cor,
                   s.nome AS sala_nome
            FROM   agenda a
            LEFT JOIN profissionais p ON p.id = a.profissional_id
            LEFT JOIN salas         s ON s.id = a.sala_id
            WHERE  a.data_inicio >= :inicio
              AND  a.data_inicio <  :fim
              AND  a.status != 'Cancelado'
        ";
        if ($profissionalId) {
            $sql .= " AND a.profissional_id = :prof_id";
        }
        $sql .= " ORDER BY a.data_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':inicio', $inicio);
        $stmt->bindValue(':fim', $fim);
        if ($profissionalId) {
            $stmt->bindValue(':prof_id', $profissionalId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*,
                   p.nome AS profissional_nome,
                   s.nome AS sala_nome
            FROM   agenda a
            LEFT JOIN profissionais p ON p.id = a.profissional_id
            LEFT JOIN salas         s ON s.id = a.sala_id
            WHERE  a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO agenda
                (profissional_id, sala_id, paciente, servico_id, data_inicio, data_fim, status, observacoes)
            VALUES
                (:profissional_id, :sala_id, :paciente, :servico_id, :data_inicio, :data_fim, :status, :observacoes)
        ");
        $stmt->execute([
            ':profissional_id' => (int) $data['profissional_id'],
            ':sala_id' => $data['sala_id'] ?: null,
            ':paciente' => trim($data['paciente']),
            ':servico_id' => $data['servico_id'] ?: null,
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => $data['data_fim'],
            ':status' => $data['status'] ?? 'Agendado',
            ':observacoes' => $data['observacoes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE agenda SET
                profissional_id = :profissional_id,
                sala_id         = :sala_id,
                paciente        = :paciente,
                servico_id      = :servico_id,
                data_inicio     = :data_inicio,
                data_fim        = :data_fim,
                status          = :status,
                observacoes     = :observacoes
            WHERE id = :id
        ");
        return $stmt->execute([
            ':profissional_id' => (int) $data['profissional_id'],
            ':sala_id' => $data['sala_id'] ?: null,
            ':paciente' => trim($data['paciente']),
            ':servico_id' => $data['servico_id'] ?: null,
            ':data_inicio' => $data['data_inicio'],
            ':data_fim' => $data['data_fim'],
            ':status' => $data['status'] ?? 'Agendado',
            ':observacoes' => $data['observacoes'] ?: null,
            ':id' => $id,
        ]);
    }

    public static function cancel(int $id): bool
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE agenda SET status = 'Cancelado' WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Conta agendamentos por status nesta semana */
    public static function statsByWeek(string $inicio, string $fim): array
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as total
            FROM   agenda
            WHERE  data_inicio >= :inicio AND data_inicio < :fim
            GROUP  BY status
        ");
        $stmt->execute([':inicio' => $inicio, ':fim' => $fim]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['total'];
        }
        return $out;
    }
}
