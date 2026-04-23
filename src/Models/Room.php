<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Room
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM salas WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function allIncludingInactive()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM salas ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM salas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO salas (nome, descricao, capacidade, cor_agenda, google_calendar_id, ativo)
            VALUES (:nome, :descricao, :capacidade, :cor_agenda, :google_calendar_id, 1)
        ");
        $stmt->execute([
            ':nome'               => $data['nome'],
            ':descricao'          => $data['descricao'] ?: null,
            ':capacidade'         => $data['capacidade'] ?: null,
            ':cor_agenda'         => $data['cor_agenda'] ?: '#10B981',
            ':google_calendar_id' => trim($data['google_calendar_id'] ?? '') ?: null,
        ]);
        $salaId = $pdo->lastInsertId();

        // Vincular serviços
        if (!empty($data['servicos'])) {
            self::syncServicos($salaId, $data['servicos']);
        }

        return $salaId;
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE salas SET
                nome                = :nome,
                descricao           = :descricao,
                capacidade          = :capacidade,
                cor_agenda          = :cor_agenda,
                google_calendar_id  = :google_calendar_id,
                ativo               = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome'               => $data['nome'],
            ':descricao'          => $data['descricao'] ?: null,
            ':capacidade'         => $data['capacidade'] ?: null,
            ':cor_agenda'         => $data['cor_agenda'] ?: '#10B981',
            ':google_calendar_id' => trim($data['google_calendar_id'] ?? '') ?: null,
            ':ativo'              => $data['ativo'] ?? 1,
            ':id'                 => $id,
        ]);

        // Atualizar vínculo com serviços
        self::syncServicos($id, $data['servicos'] ?? []);

        return true;
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE salas SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Retorna todos os google_calendar_id distintos configurados nas salas,
     * mais o calendário padrão do .env, sem duplicatas e sem valores nulos.
     */
    public static function allGoogleCalendarIds(): array
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT DISTINCT google_calendar_id FROM salas WHERE google_calendar_id IS NOT NULL AND google_calendar_id != ''");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $default = $_ENV['GOOGLE_CALENDAR_ID'] ?? 'primary';
        if (!in_array($default, $ids, true)) {
            $ids[] = $default;
        }

        return $ids;
    }

    // Serviços vinculados
    public static function getServicos($salaId)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT s.* FROM servicos s
            INNER JOIN salas_servicos ss ON ss.servico_id = s.id
            WHERE ss.sala_id = :sala_id
            ORDER BY s.nome
        ");
        $stmt->execute([':sala_id' => $salaId]);
        return $stmt->fetchAll();
    }

    public static function getServicoIds($salaId)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT servico_id FROM salas_servicos WHERE sala_id = :sala_id");
        $stmt->execute([':sala_id' => $salaId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function syncServicos($salaId, $servicoIds)
    {
        $pdo = Database::getInstance()->getConnection();

        // Remover vínculos antigos
        $stmtDel = $pdo->prepare("DELETE FROM salas_servicos WHERE sala_id = :sala_id");
        $stmtDel->execute([':sala_id' => $salaId]);

        // Inserir novos
        if (!empty($servicoIds)) {
            $stmtIns = $pdo->prepare("INSERT INTO salas_servicos (sala_id, servico_id) VALUES (:sala_id, :servico_id)");
            foreach ($servicoIds as $servicoId) {
                $stmtIns->execute([
                    ':sala_id' => $salaId,
                    ':servico_id' => (int) $servicoId,
                ]);
            }
        }
    }
}
