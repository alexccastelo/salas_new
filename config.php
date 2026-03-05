<?php
// config.php
// Configurações e recursos compartilhados da aplicação

// Garantir que o autoloader e .env estejam carregados (para arquivos legados)
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Fuso horário
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza');

// Conexão PDO via classe Database (suporta MySQL e SQLite)
$pdo = \Clinica\Core\Database::getInstance()->getConnection();

/*
 |---------------------------------------------------------
 | Criação de tabelas (se não existirem)
 |---------------------------------------------------------
*/

// Tabela de registros de uso das salas
$pdo->exec("
CREATE TABLE IF NOT EXISTS registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT DEFAULT NULL,
    sala_id INT DEFAULT NULL,
    profissional VARCHAR(255) DEFAULT NULL,
    sala VARCHAR(255) DEFAULT NULL,
    data DATE NOT NULL,
    hora_checkin VARCHAR(5) NOT NULL,
    hora_checkout VARCHAR(5) DEFAULT NULL,
    total_horas DECIMAL(10,2) DEFAULT NULL,
    mensagem TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de pacotes de horas/sessões (pacientes ou profissionais)
$pdo->exec("
CREATE TABLE IF NOT EXISTS pacotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL COMMENT 'paciente ou profissional',
    contratante_nome VARCHAR(255) NOT NULL,
    profissional_relacionado VARCHAR(255) DEFAULT NULL,
    paciente_relacionado VARCHAR(255) DEFAULT NULL,
    data_contrato DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    pagamento_ao_final TINYINT(1) NOT NULL DEFAULT 0,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de "parcelas" / usos de cada pacote
$pdo->exec("
CREATE TABLE IF NOT EXISTS pacotes_parcelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pacote_id INT NOT NULL,
    numero INT NOT NULL,
    total INT NOT NULL,
    descricao VARCHAR(50) NOT NULL,
    utilizado TINYINT(1) NOT NULL DEFAULT 0,
    data_utilizacao DATE DEFAULT NULL,
    registro_id INT DEFAULT NULL,
    FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de profissionais
$pdo->exec("
CREATE TABLE IF NOT EXISTS profissionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    celular VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    chave_pix VARCHAR(255) DEFAULT NULL,
    profissao VARCHAR(100) DEFAULT NULL,
    especialidade VARCHAR(100) DEFAULT NULL,
    conselho VARCHAR(50) DEFAULT NULL,
    numero_conselho VARCHAR(50) DEFAULT NULL,
    tipo_contrato ENUM('PJ','CLT','Eventual') DEFAULT NULL,
    percentual_repasse DECIMAL(5,2) DEFAULT NULL,
    cor_agenda VARCHAR(7) DEFAULT '#3B82F6',
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Adicionar colunas caso a tabela já exista (migração incremental)
$alterColumns = [
    'celular' => "ALTER TABLE profissionais ADD COLUMN celular VARCHAR(20) DEFAULT NULL",
    'email' => "ALTER TABLE profissionais ADD COLUMN email VARCHAR(255) DEFAULT NULL",
    'chave_pix' => "ALTER TABLE profissionais ADD COLUMN chave_pix VARCHAR(255) DEFAULT NULL",
    'profissao' => "ALTER TABLE profissionais ADD COLUMN profissao VARCHAR(100) DEFAULT NULL",
    'especialidade' => "ALTER TABLE profissionais ADD COLUMN especialidade VARCHAR(100) DEFAULT NULL",
    'conselho' => "ALTER TABLE profissionais ADD COLUMN conselho VARCHAR(50) DEFAULT NULL",
    'numero_conselho' => "ALTER TABLE profissionais ADD COLUMN numero_conselho VARCHAR(50) DEFAULT NULL",
    'tipo_contrato' => "ALTER TABLE profissionais ADD COLUMN tipo_contrato ENUM('PJ','CLT','Eventual') DEFAULT NULL",
    'percentual_repasse' => "ALTER TABLE profissionais ADD COLUMN percentual_repasse DECIMAL(5,2) DEFAULT NULL",
    'cor_agenda' => "ALTER TABLE profissionais ADD COLUMN cor_agenda VARCHAR(7) DEFAULT '#3B82F6'",
];
foreach ($alterColumns as $col => $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Coluna já existe, ignorar
    }
}

// Tabela de serviços (novo módulo)
$pdo->exec("
CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT DEFAULT NULL,
    especialidade VARCHAR(100) DEFAULT NULL,
    tempo_atendimento INT NOT NULL DEFAULT 60 COMMENT 'minutos',
    quantidade_sessoes INT DEFAULT NULL,
    servico_vinculado_id INT DEFAULT NULL,
    valor_base DECIMAL(10,2) DEFAULT NULL,
    valor_avista DECIMAL(10,2) DEFAULT NULL,
    valor_prazo DECIMAL(10,2) DEFAULT NULL,
    qtd_parcelas INT DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (servico_vinculado_id) REFERENCES servicos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de salas
$pdo->exec("
CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT DEFAULT NULL,
    capacidade INT DEFAULT NULL,
    cor_agenda VARCHAR(7) DEFAULT '#10B981',
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Adicionar colunas de salas caso já exista
$alterSalas = [
    'descricao' => "ALTER TABLE salas ADD COLUMN descricao TEXT DEFAULT NULL",
    'capacidade' => "ALTER TABLE salas ADD COLUMN capacidade INT DEFAULT NULL",
    'cor_agenda' => "ALTER TABLE salas ADD COLUMN cor_agenda VARCHAR(7) DEFAULT '#10B981'",
];
foreach ($alterSalas as $col => $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Coluna já existe
    }
}

// Tabela pivot salas_servicos (N:N)
$pdo->exec("
CREATE TABLE IF NOT EXISTS salas_servicos (
    sala_id INT NOT NULL,
    servico_id INT NOT NULL,
    PRIMARY KEY (sala_id, servico_id),
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de usuários (acesso ao admin)
$pdo->exec("
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Tabela de modulos usuários (acesso ao admin)
$pdo->exec("
CREATE TABLE IF NOT EXISTS usuarios_modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


/*
 |---------------------------------------------------------
 | Usuário administrador padrão
 |---------------------------------------------------------
 | Criado apenas se ainda não existir nenhum usuário.
 | E-mail: admin@clinica.local
 | Senha:  Clinica@2024!
*/
$checkUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
if ((int) $checkUsuarios === 0) {
    $emailDefault = $_ENV['ADMIN_EMAIL'] ?? 'admin@clinica.local';
    $senhaDefault = $_ENV['ADMIN_PASS'] ?? 'Clinica@2024!';
    $hashDefault = password_hash($senhaDefault, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha_hash) VALUES (:email, :senha_hash)");
    $stmt->execute([
        ':email' => $emailDefault,
        ':senha_hash' => $hashDefault
    ]);
}
