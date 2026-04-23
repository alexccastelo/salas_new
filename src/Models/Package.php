<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;
use DateTime;

class Package
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("
            SELECT
                p.*,
                COALESCE(SUM(CASE WHEN pp.utilizado = 1 THEN 1 ELSE 0 END), 0) AS usadas
            FROM pacotes p
            LEFT JOIN pacotes_parcelas pp ON pp.pacote_id = p.id
            GROUP BY p.id
            ORDER BY p.data_contrato DESC, p.id DESC
        ");
        return $stmt->fetchAll();
    }

    public static function getPaginatedAndFiltered($busca, $limit, $offset)
    {
        $pdo = Database::getInstance()->getConnection();
        
        $where = "WHERE 1=1";
        $params = [];

        if ($busca !== '') {
            $where .= " AND (
                p.contratante_nome LIKE :busca OR
                p.profissional_relacionado LIKE :busca OR
                p.paciente_relacionado LIKE :busca OR
                DATE_FORMAT(p.data_contrato, '%d/%m/%Y') LIKE :busca OR
                p.data_contrato LIKE :busca OR
                DATE_FORMAT(p.data_pagamento, '%d/%m/%Y') LIKE :busca OR
                p.data_pagamento LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sqlPacotes = "
            SELECT
                p.*,
                COALESCE(SUM(CASE WHEN pp.utilizado = 1 THEN 1 ELSE 0 END), 0) AS usadas
            FROM pacotes p
            LEFT JOIN pacotes_parcelas pp ON pp.pacote_id = p.id
            $where
            GROUP BY p.id
            ORDER BY p.data_contrato DESC, p.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $pdo->prepare($sqlPacotes);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countFiltered($busca)
    {
        $pdo = Database::getInstance()->getConnection();
        $where = "WHERE 1=1";
        $params = [];

        if ($busca !== '') {
            $where .= " AND (
                p.contratante_nome LIKE :busca OR
                p.profissional_relacionado LIKE :busca OR
                p.paciente_relacionado LIKE :busca OR
                DATE_FORMAT(p.data_contrato, '%d/%m/%Y') LIKE :busca OR
                p.data_contrato LIKE :busca OR
                DATE_FORMAT(p.data_pagamento, '%d/%m/%Y') LIKE :busca OR
                p.data_pagamento LIKE :busca
            )";
            $params[':busca'] = '%' . $busca . '%';
        }

        $sqlCount = "SELECT COUNT(DISTINCT p.id) FROM pacotes p $where";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        return (int)$stmtCount->fetchColumn();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function getInstallments($packageId)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            SELECT *
            FROM pacotes_parcelas
            WHERE pacote_id = :id
            ORDER BY numero ASC
        ");
        $stmt->execute([':id' => $packageId]);
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance()->getConnection();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pacotes
                (tipo, contratante_nome, profissional_relacionado, paciente_relacionado,
                 data_contrato, data_pagamento, pagamento_ao_final,
                 quantidade, valor_unitario, valor_total)
                VALUES
                (:tipo, :contratante_nome, :prof_rel, :pac_rel,
                 :data_contrato, :data_pagamento, :pagamento_ao_final,
                 :quantidade, :valor_unitario, :valor_total)
            ");
            $stmt->execute([
                ':tipo' => $data['tipo'],
                ':contratante_nome' => $data['contratante_nome'],
                ':prof_rel' => $data['profissional_relacionado'] ?: null,
                ':pac_rel' => $data['paciente_relacionado'] ?: null,
                ':data_contrato' => $data['data_contrato'],
                ':data_pagamento' => $data['data_pagamento'] ?: null,
                ':pagamento_ao_final' => $data['pagamento_ao_final'],
                ':quantidade' => $data['quantidade'],
                ':valor_unitario' => $data['valor_unitario'],
                ':valor_total' => $data['valor_total'],
            ]);

            $pacoteId = (int) $pdo->lastInsertId();

            $stmtParc = $pdo->prepare("
                INSERT INTO pacotes_parcelas (pacote_id, numero, total, descricao, utilizado, data_utilizacao, registro_id)
                VALUES (:pacote_id, :numero, :total, :descricao, 0, NULL, NULL)
            ");

            for ($i = 1; $i <= $data['quantidade']; $i++) {
                $descricao = "{$i}/{$data['quantidade']}";
                $stmtParc->execute([
                    ':pacote_id' => $pacoteId,
                    ':numero' => $i,
                    ':total' => $data['quantidade'],
                    ':descricao' => $descricao,
                ]);
            }

            $pdo->commit();
            return $pacoteId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function markInstallmentUsed($parcelaId, $dataUtilizacao)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE pacotes_parcelas
            SET utilizado = 1,
                data_utilizacao = :data_utilizacao
            WHERE id = :id
        ");
        return $stmt->execute([
            ':data_utilizacao' => $dataUtilizacao,
            ':id' => $parcelaId,
        ]);
    }

    public static function updateInstallment($parcelaId, $dataUtilizacao, $utilizado)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE pacotes_parcelas
            SET utilizado = :utilizado,
                data_utilizacao = :data_utilizacao
            WHERE id = :id
        ");
        return $stmt->execute([
            ':utilizado' => $utilizado,
            ':data_utilizacao' => $dataUtilizacao,
            ':id' => $parcelaId,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("DELETE FROM pacotes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Formatters
    public static function formatMoney($val)
    {
        return number_format((float) $val, 2, ',', '.');
    }

    public static function formatStatusPayment($p)
    {
        if ((int) $p['pagamento_ao_final'] === 1) {
            return 'Ao final';
        } elseif (!empty($p['data_pagamento'])) {
            $d = DateTime::createFromFormat('Y-m-d', $p['data_pagamento']);
            return 'Pago em ' . ($d ? $d->format('d/m/Y') : $p['data_pagamento']);
        } else {
            return 'Pendente';
        }
    }
}
