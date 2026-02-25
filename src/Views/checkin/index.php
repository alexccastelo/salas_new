<?php
// src/Views/checkin/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in - Espaço Vital</title>
    <!-- We can link the old style for base or a new one. Let's use the existing one but we will improve it later. -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <div class="topbar">
            <h1>Controle de Check-in / Check-out</h1>
            <div class="topbar-actions">
                <!-- Actions if needed -->
            </div>
        </div>

        <!-- Feedback Messages -->
        <?php if (!empty($mensagemSistema)): ?>
            <div class="msg-alerta" style="background-color:#d1fae5; border-color:#10b981; color:#065f46;">
                <?= htmlspecialchars($mensagemSistema) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="msg-alerta" style="background-color:#fee2e2; border-color:#ef4444; color:#991b1b;">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagemWhatsApp)): ?>
            <div class="card">
                <h2>Mensagem para WhatsApp</h2>
                <p class="info">Copie a mensagem abaixo:</p>
                <div style="position:relative;">
                    <textarea id="whatsappMsg" readonly rows="5"
                        style="background:#f9fafb;"><?= htmlspecialchars($mensagemWhatsApp) ?></textarea>
                    <button type="button" class="btn-secondary"
                        onclick="navigator.clipboard.writeText(document.getElementById('whatsappMsg').value); alert('Copiado!');"
                        style="position:absolute; bottom:10px; right:10px;">Copiar</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- NOVO CHECK-IN -->
            <div class="card">
                <h2>Novo Check-in</h2>
                <form method="post" action="index.php?route=checkin">
                    <input type="hidden" name="action" value="novo_checkin">

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label for="profissional_id">Profissional</label>
                        <select id="profissional_id" name="profissional_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($profissionais as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex-col" style="margin-bottom:12px;">
                        <label for="sala_id">Sala</label>
                        <select id="sala_id" name="sala_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($salas as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex-row">
                        <div class="flex-col">
                            <label for="data">Data</label>
                            <input type="date" id="data" name="data" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="flex-col">
                            <label for="hora_checkin">Hora</label>
                            <input type="time" id="hora_checkin" name="hora_checkin" value="<?= date('H:i') ?>">
                        </div>
                    </div>

                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" class="btn-primary">Registrar Check-in</button>
                    </div>
                </form>
            </div>

            <!-- CHECK-INS EM ABERTO -->
            <div class="card">
                <h2>Check-ins em Aberto</h2>
                <?php if (empty($abertos)): ?>
                    <p class="info">Nenhum uso em andamento no momento.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Profissional / Sala</th>
                                    <th>Início</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abertos as $a): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($a['profissional']) ?></strong><br>
                                            <small><?= htmlspecialchars($a['sala']) ?></small>
                                        </td>
                                        <td>
                                            <?= \Clinica\Helpers\DateHelper::formatBr($a['data']) ?><br>
                                            <?= htmlspecialchars($a['hora_checkin']) ?>
                                        </td>
                                        <td>
                                            <form method="post" action="index.php?route=checkin"
                                                style="display:flex; align-items:center; gap:8px;">
                                                <input type="hidden" name="action" value="checkout">
                                                <input type="hidden" name="registro_id" value="<?= $a['id'] ?>">

                                                <input type="time" name="hora_checkout" value="<?= date('H:i') ?>" required
                                                    style="padding:4px; border:1px solid #ccc; border-radius:4px;">

                                                <button type="submit" class="btn-link"
                                                    onclick="return confirm('Confirmar check-out?');">Check-out</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FINALIZADOS -->
        <div class="card" style="margin-top:16px;">
            <h2>Últimos Finalizados</h2>
            <?php if (empty($finalizados)): ?>
                <p class="info">Nenhum registro finalizado.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Profissional</th>
                                <th>Sala</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Total (h)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finalizados as $r): ?>
                                <tr>
                                    <td><?= \Clinica\Helpers\DateHelper::formatBr($r['data']) ?></td>
                                    <td><?= htmlspecialchars($r['profissional']) ?></td>
                                    <td><?= htmlspecialchars($r['sala']) ?></td>
                                    <td><?= htmlspecialchars($r['hora_checkin']) ?></td>
                                    <td><?= htmlspecialchars($r['hora_checkout']) ?></td>
                                    <td>
                                        <?= $r['total_horas'] !== null
                                            ? number_format((float) $r['total_horas'], 2, ',', '')
                                            : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPaginas > 1): ?>
                    <div class="pagination">
                        <?php if ($paginaAtual > 1): ?>
                            <a href="index.php?route=checkin&page=<?= $paginaAtual - 1 ?>">&laquo; Anterior</a>
                        <?php endif; ?>

                        <span>Página <?= $paginaAtual ?> de <?= $totalPaginas ?></span>

                        <?php if ($paginaAtual < $totalPaginas): ?>
                            <a href="index.php?route=checkin&page=<?= $paginaAtual + 1 ?>">Próximo &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

</body>

</html>