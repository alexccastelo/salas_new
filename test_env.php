<?php
// Tenta forçar a falta de output
ob_start();

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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['teste'] = 'OK';
    echo "Sessão iniciada e escrita: OK!<br>";
    echo "ID da Sessão: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "Erro de Sessão: " . $e->getMessage() . "<br>";
}

// Check para ver oq já foi enviado antes (bom pra achar BOM)
$sent = headers_sent($file, $line);
if ($sent) {
    echo "<br><br><span style='color:red;'><strong>ATENÇÃO:</strong> Headers já foram enviados no arquivo <strong>{$file}</strong> na linha <strong>{$line}</strong>! Isso causa o erro 500 nologin.</span>";
} else {
    echo "<br><br><span style='color:green;'>Headers OK (Não foram enviados antes da hora).</span>";
}

ob_end_flush();
