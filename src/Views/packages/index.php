<?php
// src/Views/packages/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacotes - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="topbar">
            <h1>Gestão de Pacotes</h1>
            <?php if ($pacoteSelecionado): ?>
                <a href="index.php?route=pacotes" class="btn-secondary">Voltar para a Lista</a>
            <?php endif; ?>
        </div>

        <!-- Feedback -->
        <?php if ($mensagem): ?>
            <div class="msg-alerta" style="background-color:#d1fae5; border-color:#10b981; color:#065f46;">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="msg-alerta" style="background-color:#fee2e2; border-color:#ef4444; color:#991b1b;">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <!-- VIEW: DETALHES -->
        <?php if ($pacoteSelecionado): ?>
            <div class="card">
                <h2>Detalhes Pacote #
                    <?= $pacoteSelecionado['id'] ?>
                </h2>
                <p class="info">
                    <strong>Contratante:</strong>
                    <?= htmlspecialchars($pacoteSelecionado['contratante_nome']) ?><br>
                    <strong>Profissional:</strong>
                    <?= htmlspecialchars($pacoteSelecionado['profissional_relacionado'] ?: '-') ?> ·
                    <strong>Paciente:</strong>
                    <?= htmlspecialchars($pacoteSelecionado['paciente_relacionado'] ?: '-') ?><br>
                    <strong>Valor Total:</strong> R$
                    <?= \Clinica\Models\Package::formatMoney($pacoteSelecionado['valor_total']) ?>
                </p>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Utilizado?</th>
                            <th>Data</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelasPacote as $pp): ?>
                            <tr>
                                <td>
                                    <?= $pp['numero'] ?> (
                                    <?= htmlspecialchars($pp['descricao']) ?>)
                                </td>
                                <td>
                                    <?= $pp['utilizado'] ? '<span style="color:green;font-weight:bold;">Sim</span>' : '<span style="color:#999;">Não</span>' ?>
                                </td>
                                <td>
                                    <?= \Clinica\Helpers\DateHelper::formatBr($pp['data_utilizacao']) ?>
                                </td>
                                <td>
                                    <?php if (!$pp['utilizado']): ?>
                                        <form method="post" action="index.php?route=pacotes&id=<?= $pacoteSelecionado['id'] ?>"
                                            class="actions-form">
                                            <input type="hidden" name="action" value="usar_parcela">
                                            <input type="hidden" name="parcela_id" value="<?= $pp['id'] ?>">
                                            <button type="submit" class="btn-link">Marcar uso</button>
                                        </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- VIEW: LISTA & FORM -->
        <?php else: ?>
            <div class="card">
                <h2>Novo Pacote</h2>
                <form method="post" action="index.php?route=pacotes">
                    <input type="hidden" name="action" value="criar_pacote">

                    <div class="flex-row">
                        <div class="flex-col">
                            <label>Tipo</label>
                            <select name="tipo">
                                <option value="paciente">Paciente</option>
                                <option value="profissional">Profissional</option>
                            </select>
                        </div>
                        <div class="flex-col">
                            <label>Contratante (Nome)</label>
                            <input type="text" name="contratante_nome" required>
                        </div>
                    </div>

                    <div class="flex-row" style="margin-top:12px;">
                        <div class="flex-col">
                            <label>Profissional Relacionado</label>
                            <select name="profissional_relacionado">
                                <option value="">Selecione...</option>
                                <?php foreach ($profissionais as $p): ?>
                                    <option value="<?= htmlspecialchars($p['nome']) ?>">
                                        <?= htmlspecialchars($p['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex-col">
                            <label>Paciente Relacionado (Opcional)</label>
                            <input type="text" name="paciente_relacionado">
                        </div>
                    </div>

                    <div class="flex-row" style="margin-top:12px;">
                        <div class="flex-col">
                            <label>Qtd. Sessões/Horas</label>
                            <input type="number" name="quantidade" min="1" required>
                        </div>
                        <div class="flex-col">
                            <label>Valor Unitário (R$)</label>
                            <input type="text" name="valor_unitario" placeholder="Ex: 150,00">
                        </div>
                    </div>

                    <div class="flex-row" style="margin-top:12px;">
                        <div class="flex-col">
                            <label>Data Contrato</label>
                            <input type="date" name="data_contrato" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="flex-col">
                            <label>Data Pagamento</label>
                            <input type="date" name="data_pagamento">
                            <label style="margin-top:4px; font-weight:normal; font-size:0.85rem;">
                                <input type="checkbox" name="pagamento_ao_final" value="1"
                                    style="display:inline;width:auto;"> Pagamento ao Final
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:16px; text-align:right;">
                        <button type="submit" class="btn-primary">Criar Pacote</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Pacotes Existentes</h2>

                <form method="get" action="index.php" class="flex-row" style="margin-bottom: 20px; align-items: center; gap: 10px;">
                    <input type="hidden" name="route" value="pacotes">
                    <input type="text" name="busca" placeholder="Buscar por paciente, profissional ou data..." value="<?= htmlspecialchars($busca ?? '') ?>" style="flex: 1; margin: 0;">
                    <button type="submit" class="btn-primary" style="margin: 0;">Buscar</button>
                    <?php if (!empty($busca)): ?>
                        <a href="index.php?route=pacotes" class="btn-link">Limpar</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($pacotes)): ?>
                    <p>Nenhum pacote cadastrado.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Contratante</th>
                                    <th>Profissional</th>
                                    <th>Status Pag.</th>
                                    <th>Progresso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pacotes as $p): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($p['contratante_nome']) ?>
                                            </strong><br>
                                            <small>
                                                <?= \Clinica\Helpers\DateHelper::formatBr($p['data_contrato']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($p['profissional_relacionado'] ?: '-') ?>
                                        </td>
                                        <td>
                                            <?= \Clinica\Models\Package::formatStatusPayment($p) ?>
                                        </td>
                                        <td>
                                            <?= $p['usadas'] ?> /
                                            <?= $p['quantidade'] ?>
                                        </td>
                                        <td>
                                            <a href="index.php?route=pacotes&id=<?= $p['id'] ?>&pagina=<?= $paginaAtual ?? 1 ?>&busca=<?= urlencode($busca ?? '') ?>" class="btn-link">Ver/Usar</a>
                                            &nbsp;|&nbsp;
                                            <form method="post" action="index.php?route=pacotes" style="display:inline;"
                                                onsubmit="return confirm('Excluir?');">
                                                <input type="hidden" name="action" value="excluir_pacote">
                                                <input type="hidden" name="pacote_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn-link"
                                                    style="color:var(--text-muted);">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                        <div class="pagination" style="margin-top: 20px; display: flex; gap: 5px;">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="index.php?route=pacotes&pagina=<?= $i ?>&busca=<?= urlencode($busca ?? '') ?>" 
                                   style="padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; border-radius: 4px; <?= $i === ($paginaAtual ?? 1) ? 'background: #007bff; color: #fff; border-color: #007bff;' : 'background: #f9f9f9; color: #333;' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>