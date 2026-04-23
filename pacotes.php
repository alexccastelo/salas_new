<?php
require_once 'config.php';
require_once 'auth.php';

exigirPermissao('pacotes');

$mensagem = '';
$erro     = '';

// Converte "1.200,50" ou "1200,50" em float 1200.50
function parseValorMonetario(string $valor): float
{
    $v = trim($valor);
    if ($v === '') {
        return 0.0;
    }
    // remove separador de milhar
    $v = str_replace(['.', ' '], ['', ''], $v);
    // troca vírgula por ponto
    $v = str_replace(',', '.', $v);
    return (float)$v;
}

/*
 |---------------------------------------------------------
 | PROCESSAMENTO DOS FORMULÁRIOS
 |---------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CRIAR NOVO PACOTE
    if ($action === 'criar_pacote') {
        $tipo                   = $_POST['tipo'] ?? 'paciente'; // 'paciente' ou 'profissional'
        $contratanteNome        = trim($_POST['contratante_nome'] ?? '');
        $profissionalRelacionado= trim($_POST['profissional_relacionado'] ?? '');
        $pacienteRelacionado    = trim($_POST['paciente_relacionado'] ?? '');

        $dataContrato           = $_POST['data_contrato'] ?? date('Y-m-d');
        $dataPagamento          = $_POST['data_pagamento'] ?? '';
        $pagamentoAoFinal       = isset($_POST['pagamento_ao_final']) ? 1 : 0;

        $quantidade             = (int)($_POST['quantidade'] ?? 0);
        $valorUnitarioStr       = $_POST['valor_unitario'] ?? '0';
        $valorUnitario          = parseValorMonetario($valorUnitarioStr);

        if (!in_array($tipo, ['paciente', 'profissional'], true)) {
            $tipo = 'paciente';
        }

        if ($contratanteNome === '') {
            $erro = 'Informe o nome de quem contratou o pacote.';
        } elseif ($quantidade <= 0) {
            $erro = 'Informe a quantidade de horas/sessões do pacote.';
        } elseif ($valorUnitario <= 0) {
            $erro = 'Informe o valor unitário maior que zero.';
        } else {
            $valorTotal = $quantidade * $valorUnitario;

            // Se marcado pagamento ao final, mantemos data_pagamento vazia
            if ($pagamentoAoFinal) {
                $dataPagamento = null;
            } elseif ($dataPagamento === '') {
                $dataPagamento = null;
            }

            // Insere o pacote
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
                ':tipo'               => $tipo,
                ':contratante_nome'   => $contratanteNome,
                ':prof_rel'           => $profissionalRelacionado !== '' ? $profissionalRelacionado : null,
                ':pac_rel'            => $pacienteRelacionado !== '' ? $pacienteRelacionado : null,
                ':data_contrato'      => $dataContrato,
                ':data_pagamento'     => $dataPagamento,
                ':pagamento_ao_final' => $pagamentoAoFinal,
                ':quantidade'         => $quantidade,
                ':valor_unitario'     => $valorUnitario,
                ':valor_total'        => $valorTotal,
            ]);

            $pacoteId = (int)$pdo->lastInsertId();

            // Gera as "parcelas" 1/4, 2/4, etc.
            $stmtParc = $pdo->prepare("
                INSERT INTO pacotes_parcelas (pacote_id, numero, total, descricao, utilizado, data_utilizacao, registro_id)
                VALUES (:pacote_id, :numero, :total, :descricao, 0, NULL, NULL)
            ");

            for ($i = 1; $i <= $quantidade; $i++) {
                $descricao = "{$i}/{$quantidade}";
                $stmtParc->execute([
                    ':pacote_id' => $pacoteId,
                    ':numero'    => $i,
                    ':total'     => $quantidade,
                    ':descricao' => $descricao,
                ]);
            }

            $mensagem = 'Pacote criado com sucesso e parcelas geradas.';
        }

    // MARCAR UMA PARCELA COMO UTILIZADA
    } elseif ($action === 'usar_parcela') {
        $parcelaId      = (int)($_POST['parcela_id'] ?? 0);
        $dataUtilizacao = $_POST['data_utilizacao'] ?? date('Y-m-d');

        if ($parcelaId <= 0) {
            $erro = 'Parcela inválida.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE pacotes_parcelas
                SET utilizado = 1,
                    data_utilizacao = :data_utilizacao
                WHERE id = :id
            ");
            $stmt->execute([
                ':data_utilizacao' => $dataUtilizacao,
                ':id'              => $parcelaId,
            ]);
            $mensagem = 'Parcela marcada como utilizada.';
        }

    // EXCLUIR PACOTE (remove também as parcelas pelo ON DELETE CASCADE)
    } elseif ($action === 'excluir_pacote') {
        $pacoteId = (int)($_POST['pacote_id'] ?? 0);

        if ($pacoteId <= 0) {
            $erro = 'Pacote inválido para exclusão.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM pacotes WHERE id = :id");
            $stmt->execute([':id' => $pacoteId]);
            $mensagem = 'Pacote excluído com sucesso.';
        }
    }
}

/*
 |---------------------------------------------------------
 | BUSCAS PARA A TELA
 |---------------------------------------------------------
*/

// Profissionais (para dropdown de "profissional relacionado")
$stmt = $pdo->query("SELECT id, nome FROM profissionais WHERE ativo = 1 ORDER BY nome ASC");
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca e paginação
$busca = trim($_GET['busca'] ?? '');
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($paginaAtual < 1) $paginaAtual = 1;
$itensPorPagina = 5;
$offset = ($paginaAtual - 1) * $itensPorPagina;

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

// Contar o total para paginação
$sqlCount = "SELECT COUNT(DISTINCT p.id) FROM pacotes p $where";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalItens = (int)$stmtCount->fetchColumn();
$totalPaginas = ceil($totalItens / $itensPorPagina);

// Lista de pacotes com quantidade usada (com paginação)
$sqlPacotes = "
    SELECT
        p.*,
        COALESCE(SUM(CASE WHEN pp.utilizado = 1 THEN 1 ELSE 0 END), 0) AS usadas
    FROM pacotes p
    LEFT JOIN pacotes_parcelas pp ON pp.pacote_id = p.id
    $where
    GROUP BY p.id
    ORDER BY p.data_contrato DESC, p.id DESC
    LIMIT $itensPorPagina OFFSET $offset
";
$stmt = $pdo->prepare($sqlPacotes);
$stmt->execute($params);
$pacotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pacote selecionado para ver detalhes/parcelas
$pacoteSelecionado = null;
$parcelasPacote    = [];
$pacoteIdDetalhe   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pacoteIdDetalhe > 0) {
    $stmt = $pdo->prepare("SELECT * FROM pacotes WHERE id = :id");
    $stmt->execute([':id' => $pacoteIdDetalhe]);
    $pacoteSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pacoteSelecionado) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM pacotes_parcelas
            WHERE pacote_id = :id
            ORDER BY numero ASC
        ");
        $stmt->execute([':id' => $pacoteIdDetalhe]);
        $parcelasPacote = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Função de formatação de data para BR
function formatarDataBR(?string $data): string
{
    if (!$data) return '';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacotes de horas/sessões - Espaço Vital Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="container">
    <h1>Gestão de Pacotes de Horas / Sessões</h1>

    <?php include 'user_info.php'; ?>

    <?php if ($mensagem): ?>
        <div class="msg-alerta" style="background-color:#ecfdf3;border-color:#22c55e;color:#166534;">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="msg-alerta">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <!-- NOVO PACOTE -->
    <div class="card">
        <h2>Novo pacote</h2>
        <p class="info">
            Use este formulário tanto para pacotes contratados por <strong>pacientes</strong> (sessões de terapia),
            quanto por <strong>profissionais</strong> (horas/slots de uso das salas).
        </p>

        <form method="post" class="flex-row">
            <input type="hidden" name="action" value="criar_pacote">

            <div class="flex-col">
                <label>Tipo de pacote</label>
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:8px;">
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="radio" name="tipo" value="paciente" checked>
                        Paciente
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;">
                        <input type="radio" name="tipo" value="profissional">
                        Profissional
                    </label>
                </div>

                <label for="contratante_nome">Nome de quem contratou</label>
                <input type="text" id="contratante_nome" name="contratante_nome" required>

                <label for="profissional_relacionado">
                    Profissional relacionado
                    <span class="small">(se o pacote é de um paciente, informe o profissional responsável; se é de um profissional, informe o próprio ou deixe em branco)</span>
                </label>
                <select id="profissional_relacionado" name="profissional_relacionado">
                    <option value="">(opcional)</option>
                    <?php foreach ($profissionais as $p): ?>
                        <option value="<?= htmlspecialchars($p['nome']) ?>">
                            <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="paciente_relacionado">
                    Paciente relacionado
                    <span class="small">(opcional, caso o pacote de um profissional seja destinado a um paciente específico)</span>
                </label>
                <input type="text" id="paciente_relacionado" name="paciente_relacionado">
            </div>

            <div class="flex-col">
                <label for="data_contrato">Data da contratação</label>
                <input type="date" id="data_contrato" name="data_contrato" value="<?= date('Y-m-d'); ?>">

                <label for="data_pagamento">Data do pagamento</label>
                <input type="date" id="data_pagamento" name="data_pagamento">

                <label style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                    <input type="checkbox" name="pagamento_ao_final" value="1">
                    Pagamento ao final do pacote
                </label>

                <label for="quantidade">Quantidade de horas/sessões</label>
                <input type="number" id="quantidade" name="quantidade" min="1" required>

                <label for="valor_unitario">Valor unitário (por hora/sessão)</label>
                <input type="text" id="valor_unitario" name="valor_unitario" placeholder="Ex.: 150,00" required>

                <button type="submit" style="margin-top:16px;">Cadastrar pacote</button>
            </div>
        </form>
    </div>

    <!-- LISTA DE PACOTES -->
    <div class="card">
        <h2>Pacotes cadastrados</h2>

        <form method="get" class="flex-row" style="margin-bottom: 20px; align-items: center; gap: 10px;">
            <input type="text" name="busca" placeholder="Buscar por paciente, profissional ou data..." value="<?= htmlspecialchars($busca) ?>" style="flex: 1; margin: 0;">
            <button type="submit" style="margin: 0;">Buscar</button>
            <?php if ($busca !== ''): ?>
                <a href="pacotes.php" class="btn-link">Limpar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($pacotes)): ?>
            <p>Nenhum pacote encontrado.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Contratante</th>
                    <th>Profissional relacionado</th>
                    <th>Paciente relacionado</th>
                    <th>Data contrato</th>
                    <th>Pagamento</th>
                    <th>Qtd</th>
                    <th>Valor unit.</th>
                    <th>Valor total</th>
                    <th>Usadas</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pacotes as $p): ?>
                    <?php
                    $tipoLabel = $p['tipo'] === 'profissional' ? 'Profissional' : 'Paciente';
                    $statusPagamento = '';
                    if ((int)$p['pagamento_ao_final'] === 1) {
                        $statusPagamento = 'Ao final';
                    } elseif (!empty($p['data_pagamento'])) {
                        $statusPagamento = 'Pago em ' . formatarDataBR($p['data_pagamento']);
                    } else {
                        $statusPagamento = 'Pendente';
                    }
                    ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars($tipoLabel) ?></td>
                        <td><?= htmlspecialchars($p['contratante_nome']) ?></td>
                        <td><?= htmlspecialchars($p['profissional_relacionado'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['paciente_relacionado'] ?? '') ?></td>
                        <td><?= htmlspecialchars(formatarDataBR($p['data_contrato'])) ?></td>
                        <td><?= htmlspecialchars($statusPagamento) ?></td>
                        <td><?= (int)$p['quantidade'] ?></td>
                        <td><?= htmlspecialchars(number_format((float)$p['valor_unitario'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars(number_format((float)$p['valor_total'], 2, ',', '.')) ?></td>
                        <td><?= (int)$p['usadas'] . '/' . (int)$p['quantidade'] ?></td>
                        <td>
                            <a class="btn-link" href="pacotes.php?id=<?= (int)$p['id'] ?>&pagina=<?= $paginaAtual ?>&busca=<?= urlencode($busca) ?>">Detalhes</a>
                            |
                            <form method="post" class="actions-form"
                                  onsubmit="return confirm('Tem certeza que deseja excluir este pacote?');">
                                <input type="hidden" name="action" value="excluir_pacote">
                                <input type="hidden" name="pacote_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPaginas > 1): ?>
                <div class="pagination" style="margin-top: 20px; display: flex; gap: 5px;">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>" 
                           style="padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px; <?= $i === $paginaAtual ? 'background: #007bff; color: #fff; border-color: #007bff;' : 'background: #f9f9f9; color: #333;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- DETALHES E PARCELAS DO PACOTE SELECIONADO -->
    <?php if ($pacoteSelecionado): ?>
        <div class="card">
            <h2>Detalhes do pacote #<?= (int)$pacoteSelecionado['id'] ?></h2>

            <p class="info">
                <strong>Tipo:</strong> <?= $pacoteSelecionado['tipo'] === 'profissional' ? 'Profissional' : 'Paciente'; ?> ·
                <strong>Contratante:</strong> <?= htmlspecialchars($pacoteSelecionado['contratante_nome']); ?> ·
                <strong>Profissional:</strong> <?= htmlspecialchars($pacoteSelecionado['profissional_relacionado'] ?? ''); ?> ·
                <strong>Paciente:</strong> <?= htmlspecialchars($pacoteSelecionado['paciente_relacionado'] ?? ''); ?><br>
                <strong>Data contrato:</strong> <?= formatarDataBR($pacoteSelecionado['data_contrato']); ?> ·
                <strong>Qtd:</strong> <?= (int)$pacoteSelecionado['quantidade']; ?> ·
                <strong>Valor unit.:</strong> <?= number_format((float)$pacoteSelecionado['valor_unitario'], 2, ',', '.'); ?> ·
                <strong>Valor total:</strong> <?= number_format((float)$pacoteSelecionado['valor_total'], 2, ',', '.'); ?>
            </p>

            <?php if (empty($parcelasPacote)): ?>
                <p>Não há parcelas geradas para este pacote.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Descrição</th>
                        <th>Utilizada?</th>
                        <th>Data de utilização</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parcelasPacote as $parc): ?>
                        <tr>
                            <td><?= (int)$parc['numero'] ?></td>
                            <td><?= htmlspecialchars($parc['descricao']) ?></td>
                            <td><?= (int)$parc['utilizado'] === 1 ? 'Sim' : 'Não' ?></td>
                            <td><?= htmlspecialchars(formatarDataBR($parc['data_utilizacao'] ?? null)) ?></td>
                            <td>
                                <?php if ((int)$parc['utilizado'] === 0): ?>
                                    <form method="post" class="actions-form">
                                        <input type="hidden" name="action" value="usar_parcela">
                                        <input type="hidden" name="parcela_id" value="<?= (int)$parc['id'] ?>">
                                        <input type="date" name="data_utilizacao" value="<?= date('Y-m-d'); ?>">
                                        <button type="submit">Marcar utilizada</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
