<?php
// config.php
// Configurações e recursos compartilhados da aplicação

// Fuso horário
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Fortaleza');

// Caminho do banco SQLite
$dbName = $_ENV['DB_DATABASE'] ?? 'clinica_salas.db';
$dbFile = __DIR__ . '/' . ltrim($dbName, '/');

// Conexão PDO com SQLite
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . htmlspecialchars($e->getMessage()));
}

/*
 |---------------------------------------------------------
 | Criação de tabelas (se não existirem)
 |---------------------------------------------------------
*/

// Tabela de registros de uso das salas

// Tabela de pacotes de horas/sessões (pacientes ou profissionais)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS pacotes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo TEXT NOT NULL, -- 'paciente' ou 'profissional'
        contratante_nome TEXT NOT NULL,
        profissional_relacionado TEXT, -- profissional responsável (se paciente contratou) ou próprio profissional
        paciente_relacionado TEXT,     -- paciente específico (se o pacote for atrelado)
        data_contrato TEXT NOT NULL,   -- formato YYYY-MM-DD
        data_pagamento TEXT,           -- pode ser NULL
        pagamento_ao_final INTEGER NOT NULL DEFAULT 0, -- 0 = não, 1 = sim
        quantidade INTEGER NOT NULL,   -- quantidade de horas/sessões
        valor_unitario REAL NOT NULL,
        valor_total REAL NOT NULL
    );
");

// Tabela de "parcelas" / usos de cada pacote
$pdo->exec("
    CREATE TABLE IF NOT EXISTS pacotes_parcelas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pacote_id INTEGER NOT NULL,
        numero INTEGER NOT NULL,   -- número da parcela (1,2,3,...)
        total  INTEGER NOT NULL,   -- total de parcelas (ex: 4)
        descricao TEXT NOT NULL,   -- ex: '1/4', '2/4'
        utilizado INTEGER NOT NULL DEFAULT 0, -- 0 = não usado, 1 = usado
        data_utilizacao TEXT,      -- YYYY-MM-DD
        registro_id INTEGER,       -- opcional, para amarrar com registros de sala no futuro
        FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE
    );
");


$pdo->exec("
CREATE TABLE IF NOT EXISTS registros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profissional TEXT NOT NULL,
    sala TEXT NOT NULL,
    data TEXT NOT NULL,            -- YYYY-MM-DD
    hora_checkin TEXT NOT NULL,    -- HH:MM
    hora_checkout TEXT,            -- HH:MM
    total_horas REAL,              -- horas em decimal
    mensagem TEXT                  -- mensagem gerada para WhatsApp
);
");

// Tabela de profissionais
$pdo->exec("
CREATE TABLE IF NOT EXISTS profissionais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL UNIQUE,
    ativo INTEGER NOT NULL DEFAULT 1
);
");

// Tabela de salas
$pdo->exec("
CREATE TABLE IF NOT EXISTS salas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL UNIQUE,
    ativo INTEGER NOT NULL DEFAULT 1
);
");

// Tabela de usuários (acesso ao admin)
$pdo->exec("
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL
);
");
// Tabela de modulos usuários (acesso ao admin)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios_modulos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL,
        modulo TEXT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    );
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
