<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../format.php';
require_once __DIR__ . '/../finance.php';
require_once __DIR__ . '/../category_icons.php';

$userId = (int) $_SESSION['user_id'];

/* Saldos */
$totalBalance     = getTotalBalance($pdo, $userId);
$totalReserved    = getTotalReserved($pdo, $userId);
$availableBalance = getAvailableBalance($pdo, $userId);

/* Últimos 6 meses (incluindo o atual): a lista começa com tudo a zero */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("first day of -$i months"));
    $months[$key] = ['income' => 0.0, 'expense' => 0.0];
}

/* Totais por mês e por tipo, desde o primeiro mês da lista */
$stmt = $pdo->prepare(
    'SELECT DATE_FORMAT(date, \'%Y-%m\') AS ym, type, SUM(value) AS total
     FROM movements
     WHERE user_id = :user_id AND date >= :start
     GROUP BY ym, type'
);
$stmt->execute([
    'user_id' => $userId,
    'start'   => array_key_first($months) . '-01',
]);

foreach ($stmt->fetchAll() as $row) {
    if (isset($months[$row['ym']])) {
        $months[$row['ym']][$row['type']] = round((float) $row['total'], 2);
    }
}

/* O mês atual é o último da lista */
$currentMonth  = array_key_last($months);
$monthIncome   = $months[$currentMonth]['income'];
$monthExpenses = $months[$currentMonth]['expense'];

/* Dados do gráfico (o JavaScript só os desenha) */
$monthNames = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

$chartLabels   = [];
$chartIncome   = [];
$chartExpenses = [];

foreach ($months as $key => $values) {
    $chartLabels[]   = $monthNames[(int) substr($key, 5, 2) - 1];
    $chartIncome[]   = $values['income'];
    $chartExpenses[] = $values['expense'];
}

/* Os 3 objetivos ativos mais recentes, com o valor reservado */
$stmt = $pdo->prepare(
    'SELECT g.id_goals, g.name, g.target, COALESCE(SUM(c.value), 0) AS reserved
     FROM goals g
     LEFT JOIN contributions c ON c.goal_id = g.id_goals
     WHERE g.user_id = :user_id AND g.status = \'active\'
     GROUP BY g.id_goals, g.name, g.target
     ORDER BY g.id_goals DESC
     LIMIT 3'
);
$stmt->execute(['user_id' => $userId]);
$summaryGoals = $stmt->fetchAll();

/* Os 5 movimentos mais recentes */
$stmt = $pdo->prepare(
    'SELECT m.date, m.value, m.description, m.type,
            c.name AS category_name, c.icon AS category_icon
     FROM movements m
     JOIN categories c ON c.id_categories = m.category_id
     WHERE m.user_id = :user_id
     ORDER BY m.date DESC, m.id_movements DESC
     LIMIT 5'
);
$stmt->execute(['user_id' => $userId]);
$recentMovements = $stmt->fetchAll();


?>

<section class="summary-section">
    <h1>Resumo</h1>

    <!-- Indicadores -->
    <div class="summary-cards">
        <div class="summary-card summary-card-main">
            <span class="summary-label">Saldo total</span>
            <span class="summary-value <?= $totalBalance < 0 ? 'summary-value-expense' : '' ?>">
                <?= formatMoney($totalBalance) ?>
            </span>
            <div class="summary-breakdown">
                <span>Disponível <strong><?= formatMoney($availableBalance) ?></strong></span>
                <span>Reservado <strong><?= formatMoney($totalReserved) ?></strong></span>
            </div>
        </div>

        <div class="summary-card">
            <span class="summary-label">Receitas do mês</span>
            <span class="summary-value summary-value-income"><?= formatMoney($monthIncome) ?></span>
        </div>

        <div class="summary-card">
            <span class="summary-label">Despesas do mês</span>
            <span class="summary-value summary-value-expense"><?= formatMoney($monthExpenses) ?></span>
        </div>
    </div>

    <!-- Gráfico -->
    <h2>Receitas e despesas</h2>
    <div class="summary-chart">
        <canvas id="chart-income-expenses" role="img" aria-label="Gráfico de receitas e despesas dos últimos 6 meses"></canvas>
    </div>

    <!-- Objetivos -->
    <h2>Objetivos</h2>

    <?php if (empty($summaryGoals)): ?>
        <p>
            Ainda não tens objetivos ativos.
            <a href="dashboard.php?section=goals" class="link-on-light">Cria o primeiro</a>.
        </p>
    <?php else: ?>
        <ul class="summary-list">
            <?php foreach ($summaryGoals as $goal): ?>
                <?php
                $reserved = (float) $goal['reserved'];
                $target   = (float) $goal['target'];
                $percent  = $target > 0 ? min(100, (int) round($reserved / $target * 100)) : 0;
                ?>
                <li class="summary-goal">
                    <div class="summary-goal-header">
                        <span class="summary-goal-name"><?= htmlspecialchars($goal['name']) ?></span>
                        <span class="summary-goal-amounts"><?= formatMoney($reserved) ?> de <?= formatMoney($target) ?></span>
                    </div>

                    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
                        <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="dashboard.php?section=goals" class="link-on-light summary-link">Ver todos os objetivos</a>
    <?php endif; ?>

    <!-- Últimos movimentos -->
    <h2>Últimos movimentos</h2>

    <?php if (empty($recentMovements)): ?>
        <p>
            Ainda não registaste nenhum movimento.
            <a href="dashboard.php?section=movements" class="link-on-light">Regista o primeiro</a>.
        </p>
    <?php else: ?>
        <ul class="summary-list">
            <?php foreach ($recentMovements as $movement): ?>
                <li class="summary-item">
                    <span class="summary-item-icon"><?= htmlspecialchars($movement['category_icon'] ?? DEFAULT_CATEGORY_ICON) ?></span>

                    <div class="summary-item-info">
                        <span class="summary-item-description">
                            <?= htmlspecialchars($movement['description'] ?: $movement['category_name']) ?>
                        </span>
                        <span class="summary-item-meta">
                            <?= htmlspecialchars($movement['category_name']) ?> · <?= formatDate($movement['date']) ?>
                        </span>
                    </div>

                    <span class="summary-item-value <?= $movement['type'] === 'income' ? 'summary-value-income' : 'summary-value-expense' ?>">
                        <?= $movement['type'] === 'income' ? '+' : '−' ?> <?= formatMoney($movement['value']) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="dashboard.php?section=movements" class="link-on-light summary-link">Ver todos os movimentos</a>
    <?php endif; ?>
</section>

<script type="application/json" id="chart-data">
    <?= json_encode([
        'labels'   => $chartLabels,
        'income'   => $chartIncome,
        'expenses' => $chartExpenses,
    ], JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.js"></script>
<script src="assets/js/summary-chart.js"></script>