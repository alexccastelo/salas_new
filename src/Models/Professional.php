<?php

namespace Clinica\Models;

use Clinica\Core\Database;
use PDO;

class Professional
{
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM profissionais WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function allIncludingInactive()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM profissionais ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO profissionais 
            (nome, celular, email, chave_pix, profissao, especialidade, conselho, numero_conselho, tipo_contrato, percentual_repasse, cor_agenda, ativo)
            VALUES 
            (:nome, :celular, :email, :chave_pix, :profissao, :especialidade, :conselho, :numero_conselho, :tipo_contrato, :percentual_repasse, :cor_agenda, 1)
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':celular' => $data['celular'] ?: null,
            ':email' => $data['email'] ?: null,
            ':chave_pix' => $data['chave_pix'] ?: null,
            ':profissao' => $data['profissao'] ?: null,
            ':especialidade' => $data['especialidade'] ?: null,
            ':conselho' => $data['conselho'] ?: null,
            ':numero_conselho' => $data['numero_conselho'] ?: null,
            ':tipo_contrato' => $data['tipo_contrato'] ?: null,
            ':percentual_repasse' => $data['percentual_repasse'] !== '' ? $data['percentual_repasse'] : null,
            ':cor_agenda' => $data['cor_agenda'] ?: '#3B82F6',
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            UPDATE profissionais SET 
                nome = :nome,
                celular = :celular,
                email = :email,
                chave_pix = :chave_pix,
                profissao = :profissao,
                especialidade = :especialidade,
                conselho = :conselho,
                numero_conselho = :numero_conselho,
                tipo_contrato = :tipo_contrato,
                percentual_repasse = :percentual_repasse,
                cor_agenda = :cor_agenda,
                ativo = :ativo
            WHERE id = :id
        ");
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':celular' => $data['celular'] ?: null,
            ':email' => $data['email'] ?: null,
            ':chave_pix' => $data['chave_pix'] ?: null,
            ':profissao' => $data['profissao'] ?: null,
            ':especialidade' => $data['especialidade'] ?: null,
            ':conselho' => $data['conselho'] ?: null,
            ':numero_conselho' => $data['numero_conselho'] ?: null,
            ':tipo_contrato' => $data['tipo_contrato'] ?: null,
            ':percentual_repasse' => $data['percentual_repasse'] !== '' ? $data['percentual_repasse'] : null,
            ':cor_agenda' => $data['cor_agenda'] ?: '#3B82F6',
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
    }

    public static function delete($id)
    {
        $prof = self::find($id);
        if ($prof) {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE profissionais SET ativo = 0 WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        }
        return false;
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
