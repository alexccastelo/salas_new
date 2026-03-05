<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Service
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function allIncludingInactive()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("
            SELECT s.*, sv.nome AS servico_vinculado_nome
            FROM servicos s
            LEFT JOIN servicos sv ON s.servico_vinculado_id = sv.id
            ORDER BY s.nome ASC
        ");
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO servicos 
            (nome, descricao, especialidade, tempo_atendimento, quantidade_sessoes,
             servico_vinculado_id, valor_base, valor_avista, valor_prazo, qtd_parcelas, ativo)
            VALUES 
            (:nome, :descricao, :especialidade, :tempo_atendimento, :quantidade_sessoes,
             :servico_vinculado_id, :valor_base, :valor_avista, :valor_prazo, :qtd_parcelas, 1)
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':especialidade' => $data['especialidade'] ?: null,
            ':tempo_atendimento' => $data['tempo_atendimento'] ?: 60,
            ':quantidade_sessoes' => $data['quantidade_sessoes'] ?: null,
            ':servico_vinculado_id' => $data['servico_vinculado_id'] ?: null,
            ':valor_base' => $data['valor_base'] !== '' ? $data['valor_base'] : null,
            ':valor_avista' => $data['valor_avista'] !== '' ? $data['valor_avista'] : null,
            ':valor_prazo' => $data['valor_prazo'] !== '' ? $data['valor_prazo'] : null,
            ':qtd_parcelas' => $data['qtd_parcelas'] ?: null,
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE servicos SET
                nome = :nome,
                descricao = :descricao,
                especialidade = :especialidade,
                tempo_atendimento = :tempo_atendimento,
                quantidade_sessoes = :quantidade_sessoes,
                servico_vinculado_id = :servico_vinculado_id,
                valor_base = :valor_base,
                valor_avista = :valor_avista,
                valor_prazo = :valor_prazo,
                qtd_parcelas = :qtd_parcelas,
                ativo = :ativo
            WHERE id = :id
        ");
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?: null,
            ':especialidade' => $data['especialidade'] ?: null,
            ':tempo_atendimento' => $data['tempo_atendimento'] ?: 60,
            ':quantidade_sessoes' => $data['quantidade_sessoes'] ?: null,
            ':servico_vinculado_id' => $data['servico_vinculado_id'] ?: null,
            ':valor_base' => $data['valor_base'] !== '' ? $data['valor_base'] : null,
            ':valor_avista' => $data['valor_avista'] !== '' ? $data['valor_avista'] : null,
            ':valor_prazo' => $data['valor_prazo'] !== '' ? $data['valor_prazo'] : null,
            ':qtd_parcelas' => $data['qtd_parcelas'] ?: null,
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE servicos SET ativo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function formatMoney($val)
    {
        if ($val === null || $val === '')
            return '—';
        return 'R$ ' . number_format((float) $val, 2, ',', '.');
    }
}
