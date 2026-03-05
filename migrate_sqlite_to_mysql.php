<?php
/**
 * Script de Migração: SQLite → MySQL
 * 
 * Uso: php migrate_sqlite_to_mysql.php
 * 
 * Transfere todos os dados do arquivo clinica_salas.db para o MySQL (erp_saude).
 * As tabelas MySQL devem ser criadas antes (via config.php ou primeira execução do app).
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// ─── Conexão SQLite (origem) ───
$sqliteFile = __DIR__ . '/clinica_salas.db';
if (!file_exists($sqliteFile)) {
    die("❌ Arquivo SQLite não encontrado: {$sqliteFile}\n");
}

$sqlite = new PDO('sqlite:' . $sqliteFile);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ─── Conexão MySQL (destino) ───
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'erp_saude';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

try {
    $mysql = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}",
        $user,
        $pass
    );
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✅ Conectado ao MySQL ({$host}:{$port}/{$dbName})\n";
} catch (PDOException $e) {
    die("❌ Erro ao conectar ao MySQL: " . $e->getMessage() . "\n");
}

// ─── Criar tabelas no MySQL ───
echo "\n📦 Criando tabelas no MySQL...\n";
require __DIR__ . '/config.php';
echo "✅ Tabelas criadas/verificadas.\n";

// ─── Desabilitar FK checks para migração limpa ───
$mysql->exec("SET FOREIGN_KEY_CHECKS = 0");

// ─── Ordem de migração ───
$tabelasOrdem = [
    'usuarios',
    'usuarios_modulos',
    'profissionais',
    'salas',
    'registros',
    'pacotes',
    'pacotes_parcelas',
];

echo "\n🔄 Iniciando migração de dados...\n";
echo str_repeat('─', 50) . "\n";

$totalMigrado = 0;

foreach ($tabelasOrdem as $tabela) {
    // Verificar se a tabela existe no SQLite
    $check = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tabela}'")->fetch();
    if (!$check) {
        echo "⚠️  Tabela '{$tabela}' não existe no SQLite. Pulando.\n";
        continue;
    }

    // Detectar colunas reais da tabela SQLite dinamicamente
    $pragma = $sqlite->query("PRAGMA table_info({$tabela})")->fetchAll();
    $sqliteColumns = array_column($pragma, 'name');
    $columns = implode(', ', $sqliteColumns);

    // Montar INSERT dinâmico baseado nas colunas reais do SQLite
    $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $sqliteColumns));
    $insertSql = "INSERT INTO {$tabela} ({$columns}) VALUES ({$placeholders})";

    // Ler dados do SQLite
    $rows = $sqlite->query("SELECT {$columns} FROM {$tabela}")->fetchAll();
    $count = count($rows);

    if ($count === 0) {
        echo "📭 {$tabela}: 0 registros (vazio)\n";
        continue;
    }

    // Limpar tabela destino antes de inserir (evita duplicatas em re-execução)
    $mysql->exec("DELETE FROM {$tabela}");

    // Inserir no MySQL
    $stmtInsert = $mysql->prepare($insertSql);
    $errors = 0;
    $skippedOrphans = 0;

    foreach ($rows as $row) {
        // Para usuarios_modulos: pular registros órfãos (usuario_id sem usuario correspondente)
        if ($tabela === 'usuarios_modulos' && isset($row['usuario_id'])) {
            $checkUser = $sqlite->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id");
            $checkUser->execute([':id' => $row['usuario_id']]);
            if ((int) $checkUser->fetchColumn() === 0) {
                $skippedOrphans++;
                continue;
            }
        }

        try {
            $params = [];
            foreach ($sqliteColumns as $col) {
                $params[":{$col}"] = $row[$col];
            }
            $stmtInsert->execute($params);
        } catch (PDOException $e) {
            $errors++;
            if ($errors <= 3) {
                echo "  ⚠️  Erro ao migrar registro id={$row['id']} de '{$tabela}': {$e->getMessage()}\n";
            }
        }
    }

    $migrated = $count - $errors - $skippedOrphans;
    $totalMigrado += $migrated;
    $statusParts = [];
    if ($errors > 0)
        $statusParts[] = "{$errors} erros";
    if ($skippedOrphans > 0)
        $statusParts[] = "{$skippedOrphans} órfãos ignorados";
    $status = !empty($statusParts) ? '(' . implode(', ', $statusParts) . ')' : '';
    echo "✅ {$tabela}: {$migrated}/{$count} registros migrados {$status}\n";
}

// ─── Reabilitar FK checks ───
$mysql->exec("SET FOREIGN_KEY_CHECKS = 1");

// Ajustar AUTO_INCREMENT para continuar após o último ID
echo "\n🔧 Ajustando AUTO_INCREMENT...\n";
foreach ($tabelasOrdem as $tabela) {
    try {
        $result = $mysql->query("SELECT MAX(id) as max_id FROM {$tabela}")->fetch();
        $nextId = ($result['max_id'] ?? 0) + 1;
        $mysql->exec("ALTER TABLE {$tabela} AUTO_INCREMENT = {$nextId}");
    } catch (PDOException $e) {
        // Tabela pode não existir no MySQL ainda
    }
}

echo str_repeat('─', 50) . "\n";
echo "🎉 Migração concluída! Total: {$totalMigrado} registros migrados.\n";

// Verificação
echo "\n📊 Comparação de contagens:\n";
echo sprintf("%-25s %-10s %-10s\n", "Tabela", "SQLite", "MySQL");
echo str_repeat('─', 50) . "\n";

foreach ($tabelasOrdem as $tabela) {
    $check = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tabela}'")->fetch();
    $sqliteCount = $check
        ? (int) $sqlite->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn()
        : 0;

    try {
        $mysqlCount = (int) $mysql->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
    } catch (PDOException $e) {
        $mysqlCount = 0;
    }

    // Para usuarios_modulos, ajustar contagem SQLite para excluir órfãos
    $note = '';
    if ($tabela === 'usuarios_modulos' && $sqliteCount !== $mysqlCount) {
        $note = ' (órfãos removidos)';
    }

    $match = $sqliteCount === $mysqlCount ? '✅' : '⚠️' . $note;
    echo sprintf("%-25s %-10d %-10d %s\n", $tabela, $sqliteCount, $mysqlCount, $match);
}

echo "\n✅ Migração finalizada com sucesso!\n";
