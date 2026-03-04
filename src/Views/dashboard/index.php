<?php
// src/Views/dashboard/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <main class="container">
        <h1>Dashboard</h1>

        <!-- FILTROS -->
        <div class="card">
            <h2>Filtros</h2>
            <form method="get" action="index.php">
                <input type="hidden" name="route" value="dashboard">

                <div class="flex-row">
                    <div class="flex-col">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
                    </div>
                    <div class="flex-col">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
                    </div>
                    <div class="flex-col">
                        <label>Profissional</label>
                        <select name="profissional_id">
                            <option value="0">(Todos)</option>
                            <?php foreach ($profissionais as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $profissional_id == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex-col">
                        <label>Sala</label>
                        <select name="sala_id">
                            <option value="0">(Todas)</option>
                            <?php foreach ($salas as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $sala_id == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:16px; display:flex; gap:10px;">
                    <button type="submit" class="btn-primary">Filtrar</button>
                    <button type="submit" name="export" value="csv" class="btn-secondary">Exportar CSV</button>
                </div>
            </form>
        </div>

        <!-- TOTAL GERAL -->
        <div class="card">
            <h2>Resumo Geral</h2>
            <p class="info">Total de horas no período filtrado.</p>
            <p class="kpi-value" style="font-size: 2rem; color: var(--primary-color);">
                <?= number_format($totalGeral, 2, ',', '') ?> h
            </p>
        </div>

        <div class="grid-3">
            <!-- POR DIA -->
            <div class="card">
                <h2>Por Dia</h2>
                <?php if (empty($resumoDia)): ?>
                    <p>Sem dados.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>H</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumoDia as $r): ?>
                                <tr>
                                    <td><?= \Clinica\Helpers\DateHelper::formatBr($r['data']) ?></td>
                                    <td><?= number_format((float) $r['horas_total'], 2, ',', '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- POR PROFISSIONAL -->
            <div class="card">
                <h2>Por Profissional</h2>
                <?php if (empty($resumoProf)): ?>
                    <p>Sem dados.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>H</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumoProf as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['profissional']) ?></td>
                                    <td><?= number_format((float) $r['horas_total'], 2, ',', '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- POR SALA -->
            <div class="card">
                <h2>Por Sala</h2>
                <?php if (empty($resumoSala)): ?>
                    <p>Sem dados.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sala</th>
                                <th>H</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumoSala as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['sala']) ?></td>
                                    <td><?= number_format((float) $r['horas_total'], 2, ',', '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>

</html>