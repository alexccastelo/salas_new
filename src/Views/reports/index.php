<?php
// src/Views/reports/index.php
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Uso - Espaço Vital</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Print Specifics to override some 'premium' styles for clarity on paper */
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="card">
        <div class="topbar">
            <h1>Relatório de Uso de Salas</h1>
            <button type="button" class="btn-primary no-print" onclick="window.print();">Imprimir / Salvar PDF</button>
        </div>

        <div class="no-print info" style="margin-bottom:16px;">
            Este relatório é otimizado para impressão. Utilize a função de impressão do seu navegador.
        </div>

        <p class="info">
            <strong>Período:</strong> <?= \Clinica\Helpers\DateHelper::formatBr($data_inicio) ?> a <?= \Clinica\Helpers\DateHelper::formatBr($data_fim) ?><br>
            <strong>Profissional:</strong> <?= $profNome ? htmlspecialchars($profNome) : 'Todos' ?> · 
            <strong>Sala:</strong> <?= $salaNome ? htmlspecialchars($salaNome) : 'Todas' ?>
        </p>

        <h2 style="margin-top:20px; color:var(--primary-color);">Total Geral: <?= number_format($totalGeral, 2, ',', '') ?> horas</h2>
    </div>

    <!-- Summaries Grid -->
    <div class="grid-3">
        <div class="card">
            <h3>Por Dia</h3>
            <?php if (empty($resumoDia)): ?>
                <p class="info">Sem dados.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Data</th><th>Horas</th></tr></thead>
                    <tbody>
                        <?php foreach ($resumoDia as $r): ?>
                            <tr>
                                <td><?= \Clinica\Helpers\DateHelper::formatBr($r['data']) ?></td>
                                <td><?= number_format((float)$r['horas_total'], 2, ',', '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Por Profissional</h3>
            <?php if (empty($resumoProf)): ?>
                <p class="info">Sem dados.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Nome</th><th>Horas</th></tr></thead>
                    <tbody>
                        <?php foreach ($resumoProf as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['profissional']) ?></td>
                                <td><?= number_format((float)$r['horas_total'], 2, ',', '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Por Sala</h3>
            <?php if (empty($resumoSala)): ?>
                <p class="info">Sem dados.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Sala</th><th>Horas</th></tr></thead>
                    <tbody>
                        <?php foreach ($resumoSala as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['sala']) ?></td>
                                <td><?= number_format((float)$r['horas_total'], 2, ',', '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed List -->
    <div class="card">
        <h3>Detalhamento dos Registros</h3>
        <?php if (empty($detalhes)): ?>
            <p class="info">Nenhum registro encontrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Profissional</th>
                        <th>Sala</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalhes as $row): ?>
                        <tr>
                            <td><?= \Clinica\Helpers\DateHelper::formatBr($row['data']) ?></td>
                            <td><?= htmlspecialchars($row['profissional']) ?></td>
                            <td><?= htmlspecialchars($row['sala']) ?></td>
                            <td><?= htmlspecialchars($row['hora_checkin']) ?></td>
                            <td><?= htmlspecialchars($row['hora_checkout']) ?></td>
                            <td>
                                <?= $row['total_horas'] !== null ? number_format((float)$row['total_horas'], 2, ',', '') : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="no-print" style="margin-top:20px; text-align:center;">
        <a href="\/dashboard" class="btn-secondary">Voltar ao Dashboard</a>
    </div>
</main>
</body>
</html>
