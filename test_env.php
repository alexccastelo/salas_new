<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Hospedagem - Salas New</h1>";

$dir = __DIR__;
echo "Diretório Atual: {$dir}<br>";
echo "Permissões da pasta Atual: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br><br>";

// 1. Testar conexão SQLite
$dbFile = $dir . '/clinica_salas.db';
echo "Testando SQLite...<br>";
if (file_exists($dbFile)) {
    echo "Arquivo DB existente: SIM<br>";
    echo "Pode ler: " . (is_readable($dbFile) ? 'SIM' : 'NÃO') . "<br>";
    echo "Pode escrever: " . (is_writable($dbFile) ? 'SIM' : 'NÃO') . "<br>";
} else {
    echo "Arquivo DB existente: NÃO<br>";
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão PDO SQLite: OK!<br>";
} catch (Exception $e) {
    echo "Erro PDO SQLite: " . $e->getMessage() . "<br>";
}

echo "<br>";

// 2. Testar sessões
echo "Testando Sessão...<br>";
try {
    session_start();
    $_SESSION['teste'] = 'OK';
    echo "Sessão iniciada e escrita: OK!<br>";
    echo "ID da Sessão: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "Erro de Sessão: " . $e->getMessage() . "<br>";
}
