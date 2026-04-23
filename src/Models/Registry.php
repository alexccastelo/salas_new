<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Registry
{
    public static function create($data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO registros (profissional_id, sala_id, data, hora_checkin)
            VALUES (:profissional_id, :sala_id, :data, :hora_checkin)
        ");
        return $stmt->execute([
            ':profissional_id' => $data['profissional_id'],
            ':sala_id' => $data['sala_id'],
            ':data' => $data['data'],
            ':hora_checkin' => $data['hora_checkin'],
        ]);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        // JOIN to get names
        $stmt = $pdo->prepare("
            SELECT r.*, p.nome as profissional, s.nome as sala
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE r.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function checkout($id, $horaCheckout, $totalHoras)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE registros
            SET hora_checkout = :hora_checkout,
                total_horas   = :total_horas
            WHERE id = :id
        ");
        return $stmt->execute([
            ':hora_checkout' => $horaCheckout,
            ':total_horas' => $totalHoras,
            ':id' => $id,
        ]);
    }

    public static function getOpen()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("
            SELECT r.*, p.nome as profissional, s.nome as sala
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE r.hora_checkout IS NULL
            ORDER BY r.data DESC, r.hora_checkin DESC
        ");
        return $stmt->fetchAll();
    }

    public static function getFinished($limit, $offset, $busca = '')
    {
        $pdo = Database::getInstance()->getConnection();
        
        $where = "r.hora_checkout IS NOT NULL";
        $params = [];
        
        if ($busca !== '') {
            $where .= " AND (
                p.nome LIKE :busca OR
                s.nome LIKE :busca OR
                DATE_FORMAT(r.data, '%d/%m/%Y') LIKE :busca OR
                r.data LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql = "
            SELECT r.*, p.nome as profissional, s.nome as sala
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
            ORDER BY r.data DESC, r.hora_checkin DESC
            LIMIT :limite OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countFinished($busca = '')
    {
        $pdo = Database::getInstance()->getConnection();
        
        $where = "r.hora_checkout IS NOT NULL";
        $params = [];
        
        if ($busca !== '') {
            $where .= " AND (
                p.nome LIKE :busca OR
                s.nome LIKE :busca OR
                DATE_FORMAT(r.data, '%d/%m/%Y') LIKE :busca OR
                r.data LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sql = "
            SELECT COUNT(*) 
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // --- Reporting / Dashboard Methods ---

    public static function search($filters = [])
    {
        $pdo = Database::getInstance()->getConnection();
        list($where, $params) = self::buildWhere($filters);

        $sql = "
            SELECT r.*, p.nome as profissional, s.nome as sala
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
            ORDER BY r.data, profissional, sala, r.hora_checkin
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getSummaryByDay($filters = [])
    {
        $pdo = Database::getInstance()->getConnection();
        list($where, $params) = self::buildWhere($filters);
        // Ensure filters map properly for WHERE clause with alias

        $stmt = $pdo->prepare("
            SELECT r.data, SUM(IFNULL(r.total_horas, 0)) AS horas_total
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
            GROUP BY r.data ORDER BY r.data
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getSummaryByProfessional($filters = [])
    {
        $pdo = Database::getInstance()->getConnection();
        list($where, $params) = self::buildWhere($filters);

        $stmt = $pdo->prepare("
            SELECT p.nome as profissional, SUM(IFNULL(r.total_horas, 0)) AS horas_total
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
            GROUP BY p.nome ORDER BY p.nome
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getSummaryByRoom($filters = [])
    {
        $pdo = Database::getInstance()->getConnection();
        list($where, $params) = self::buildWhere($filters);

        $stmt = $pdo->prepare("
            SELECT s.nome as sala, SUM(IFNULL(r.total_horas, 0)) AS horas_total
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
            GROUP BY s.nome ORDER BY s.nome
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getTotalHours($filters = [])
    {
        $pdo = Database::getInstance()->getConnection();
        list($where, $params) = self::buildWhere($filters);

        $stmt = $pdo->prepare("
            SELECT SUM(IFNULL(r.total_horas, 0)) AS horas_total 
            FROM registros r
            LEFT JOIN profissionais p ON r.profissional_id = p.id
            LEFT JOIN salas s ON r.sala_id = s.id
            WHERE $where
        ");
        $stmt->execute($params);
        $res = $stmt->fetch();
        return $res ? (float) $res['horas_total'] : 0.0;
    }

    private static function buildWhere($filters)
    {
        // Using 'r.' prefix for common columns to avoid ambiguity
        $where = "r.hora_checkout IS NOT NULL";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND r.data BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        }

        // Filters come in as NAMES (from dropdowns which might still pass names, or IDs. 
        // Dashboard passes names because it looks up IDs then passes names string. 
        // Let's support both or stick to what Dashboard does.
        // DashboardController: if ($p) $profNomeFiltro = $p['nome']; ... 'profissional' => $profNomeFiltro
        // So filters are NAMES.

        if (!empty($filters['profissional'])) {
            $where .= " AND p.nome = :profissional";
            $params[':profissional'] = $filters['profissional'];
        }

        if (!empty($filters['sala'])) {
            $where .= " AND s.nome = :sala";
            $params[':sala'] = $filters['sala'];
        }

        return [$where, $params];
    }
}
