<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../format.php';
require_once __DIR__ . '/../finance.php';
require_once __DIR__ . '/../category_icons.php';

$userId = (int) $_SESSION['user_id'];

/* Saldo disponível e total reservado */
$availableBalance = getAvailableBalance($pdo, $userId);
$totalReserved    = getTotalReserved($pdo, $userId);

/* Objetivos ativos, com o valor reservado de cada um */
$stmt = $pdo->prepare(
    'SELECT g.id_goals, g.name, g.target, COALESCE(SUM(c.value), 0) AS reserved
     FROM goals g
     LEFT JOIN contributions c ON c.goal_id = g.id_goals
     WHERE g.user_id = :user_id AND g.status = \'active\'
     GROUP BY g.id_goals, g.name, g.target
     ORDER BY g.id_goals DESC'
);
$stmt->execute(['user_id' => $userId]);
$activeGoals = $stmt->fetchAll();

/* Modo de edição: ?edit=ID (só objetivos ativos do utilizador) */
$editId = (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit']))
    ? (int) $_GET['edit']
    : 0;

$editGoal = null;
foreach ($activeGoals as $candidate) {
    if ((int) $candidate['id_goals'] === $editId) {
        $editGoal = $candidate;
        break;
    }
}

/* Modo de aquisição: ?acquire=ID (só objetivos ativos do utilizador, e nunca ao mesmo tempo que a edição) */
$acquireId = (isset($_GET['acquire']) && is_string($_GET['acquire']) && ctype_digit($_GET['acquire']))
    ? (int) $_GET['acquire']
    : 0;

$acquireGoal = null;
if ($editGoal === null) {
    foreach ($activeGoals as $candidate) {
        if ((int) $candidate['id_goals'] === $acquireId) {
            $acquireGoal = $candidate;
            break;
        }
    }
}

/* Categorias disponíveis para a despesa (só no modo de aquisição) */
$acquireCategories = [];
if ($acquireGoal !== null) {
    $stmt = $pdo->prepare(
        'SELECT id_categories, name, icon
         FROM categories
         WHERE status = \'active\' AND (user_id IS NULL OR user_id = :user_id)
         ORDER BY name'
    );
    $stmt->execute(['user_id' => $userId]);
    $acquireCategories = $stmt->fetchAll();
}

/* Se houve erro no envio anterior, repõe o que o utilizador tinha escrito */
$acquireData = $_SESSION['goal_acquire_form_data'] ?? [];
unset($_SESSION['goal_acquire_form_data']);

$spentValue = $acquireData['spent']
    ?? (($acquireGoal !== null && (float) $acquireGoal['reserved'] > 0)
        ? number_format((float) $acquireGoal['reserved'], 2, ',', '')
        : '');
$acquireDate        = $acquireData['date']         ?? date('Y-m-d');
$acquireCategory    = (int) ($acquireData['category'] ?? 0);
$acquireNewCategory = $acquireData['new_category'] ?? '';
$acquireDescription = $acquireData['description']  ?? ($acquireGoal['name'] ?? '');

/* Objetivos adquiridos, com a despesa que foi registada ao adquirir */
$stmt = $pdo->prepare(
    'SELECT g.id_goals, g.name, m.value AS spent, m.date AS spent_date
     FROM goals g
     LEFT JOIN movements m ON m.goal_id = g.id_goals
     WHERE g.user_id = :user_id AND g.status = \'acquired\'
     ORDER BY m.date DESC, g.id_goals DESC'
);
$stmt->execute(['user_id' => $userId]);
$acquiredGoals = $stmt->fetchAll();

/* Se houve erro no envio anterior, repõe o que o utilizador tinha escrito */
$formData = $_SESSION['goal_form_data'] ?? [];
unset($_SESSION['goal_form_data']);

$nameValue   = $formData['name']   ?? ($editGoal['name'] ?? '');
$targetValue = $formData['target'] ?? ($editGoal ? number_format((float) $editGoal['target'], 2, ',', '') : '');

/* Mensagens de erro e de sucesso */
$errorMessages = [
    'name'           => 'O nome do objetivo é obrigatório.',
    'namelength'     => 'O nome não pode ter mais de 100 caracteres.',
    'target'         => 'Indica uma meta válida, maior que zero.',
    'toolarge'       => 'O valor não pode ser superior a 99 999 999,99 €.',
    'amount'         => 'Indica um valor válido, maior que zero.',
    'nobalance'      => 'Não tens saldo disponível suficiente para reservar esse valor.',
    'exceedtarget'   => 'O valor ultrapassa a meta do objetivo.',
    'exceedreserved' => 'Não podes retirar mais do que o valor reservado.',
    'belowreserved'  => 'A meta não pode ser inferior ao valor já reservado.',
    'notfound'       => 'O objetivo não foi encontrado.',
    'date'       => 'A data não é válida.',
    'future'     => 'A data não pode ser futura.',
    'category'   => 'Escolhe uma categoria ou cria uma nova.',
    'catexists'  => 'Já existe uma categoria com esse nome. Escolhe-a na lista.',
    'catlength'  => 'O nome da categoria não pode ter mais de 100 caracteres.',
    'desclength' => 'A descrição não pode ter mais de 100 caracteres.',
];

$successMessages = [
    'created'     => 'Objetivo criado com sucesso.',
    'contributed' => 'Valor reservado com sucesso.',
    'withdrawn'   => 'Valor retirado com sucesso.',
    'updated'     => 'Objetivo atualizado com sucesso.',
    'acquired'    => 'Objetivo marcado como adquirido.',
    'deleted'     => 'Objetivo eliminado com sucesso.',
];

$errorMessage = null;
if (isset($_GET['error'])) {
    $code = is_string($_GET['error']) ? $_GET['error'] : '';
    $errorMessage = $errorMessages[$code] ?? 'Ocorreu um erro. Tenta novamente.';
}

$successMessage = null;
if (isset($_GET['success']) && is_string($_GET['success'])) {
    $successMessage = $successMessages[$_GET['success']] ?? null;
}

?>

<section class="goals-section">
    <h1>Objetivos</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- Saldo disponível e reservado -->
    <div class="goal-summary">
        <div class="goal-summary-item">
            <span class="goal-summary-label">Saldo disponível</span>
            <span class="goal-summary-value"><?= formatMoney($availableBalance) ?></span>
        </div>
        <div class="goal-summary-item">
            <span class="goal-summary-label">Reservado em objetivos</span>
            <span class="goal-summary-value"><?= formatMoney($totalReserved) ?></span>
        </div>
    </div>

    <?php if ($acquireGoal): ?>

        <!-- Formulário: adquirir objetivo -->
        <form action="actions/goal_acquire.php" method="POST" class="goal-form">
            <h2>Adquirir objetivo</h2>

            <input type="hidden" name="id" value="<?= (int) $acquireGoal['id_goals'] ?>">

            <p class="goal-form-hint">
                <?= htmlspecialchars($acquireGoal['name']) ?>: tens <?= formatMoney($acquireGoal['reserved']) ?> reservado.
                Indica o valor realmente gasto. Se for menor, a diferença volta ao saldo disponível; se for maior, o excesso sai do saldo.
            </p>

            <div class="form-group">
                <label for="spent">Valor gasto (€)</label>
                <input type="text" id="spent" name="spent" inputmode="decimal" placeholder="0,00" maxlength="12" required value="<?= htmlspecialchars($spentValue) ?>">
            </div>

            <div class="form-group">
                <label for="spent-date">Data</label>
                <input type="date" id="spent-date" name="date" max="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($acquireDate) ?>">
            </div>

            <div class="form-group">
                <label for="acquire-category">Categoria</label>
                <select id="acquire-category" name="category">
                    <option value="">Escolhe uma categoria</option>
                    <?php foreach ($acquireCategories as $category): ?>
                        <option value="<?= (int) $category['id_categories'] ?>" <?= (int) $category['id_categories'] === $acquireCategory ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($category['icon'] ?? DEFAULT_CATEGORY_ICON) . ' ' . $category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="new-category">Ou cria uma nova categoria (opcional)</label>
                <input type="text" id="new-category" name="new_category" maxlength="100" value="<?= htmlspecialchars($acquireNewCategory) ?>">
            </div>

            <div class="form-group">
                <label for="acquire-description">Descrição</label>
                <input type="text" id="acquire-description" name="description" maxlength="100" value="<?= htmlspecialchars($acquireDescription) ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Confirmar aquisição</button>
                <a href="dashboard.php?section=goals" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>

    <?php else: ?>

        <!-- Formulário: novo objetivo / editar objetivo -->
        <form action="actions/<?= $editGoal ? 'goal_update' : 'goal_create' ?>.php" method="POST" class="goal-form">
            <h2><?= $editGoal ? 'Editar objetivo' : 'Novo objetivo' ?></h2>

            <?php if ($editGoal): ?>
                <input type="hidden" name="id" value="<?= (int) $editGoal['id_goals'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" maxlength="100" required value="<?= htmlspecialchars($nameValue) ?>">
            </div>

            <div class="form-group">
                <label for="target">Meta (€)</label>
                <input type="text" id="target" name="target" inputmode="decimal" placeholder="0,00" maxlength="12" required value="<?= htmlspecialchars($targetValue) ?>">
            </div>

            <?php if ($editGoal): ?>
                <p class="goal-form-hint">
                    Já tens <?= formatMoney($editGoal['reserved']) ?> reservado, por isso a meta não pode ser inferior a este valor.
                </p>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editGoal ? 'Guardar alterações' : 'Criar objetivo' ?>
                </button>

                <?php if ($editGoal): ?>
                    <a href="dashboard.php?section=goals" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>

    <?php endif; ?>

    <!-- Objetivos ativos -->
    <h2>Os meus objetivos</h2>

    <?php if (empty($activeGoals)): ?>
        <p>Ainda não tens objetivos ativos.</p>
    <?php else: ?>
        <ul class="goal-list">
            <?php foreach ($activeGoals as $goal): ?>
                <?php
                $reserved = (float) $goal['reserved'];
                $target   = (float) $goal['target'];
                $percent  = $target > 0 ? min(100, (int) round($reserved / $target * 100)) : 0;
                ?>
                <li class="goal-card">
                    <h3 class="goal-name"><?= htmlspecialchars($goal['name']) ?></h3>

                    <div class="goal-amounts">
                        <span><?= formatMoney($reserved) ?> de <?= formatMoney($target) ?></span>
                        <span><?= $percent ?>%</span>
                    </div>

                    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
                        <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                    </div>

                    <p class="goal-remaining">
                        <?= $reserved >= $target
                            ? 'Meta atingida!'
                            : 'Faltam ' . formatMoney($target - $reserved) ?>
                    </p>

                    <form action="actions/goal_contribute.php" method="POST" class="goal-contribute">
                        <input type="hidden" name="id" value="<?= (int) $goal['id_goals'] ?>">

                        <input type="text" name="amount" inputmode="decimal" placeholder="0,00" maxlength="12" required
                            aria-label="Valor para o objetivo <?= htmlspecialchars($goal['name']) ?>">

                        <button type="submit" name="action" value="add" class="btn btn-primary"
                            <?= $reserved >= $target ? 'disabled' : '' ?>>Adicionar</button>

                        <button type="submit" name="action" value="withdraw" class="btn btn-secondary"
                            <?= $reserved <= 0 ? 'disabled' : '' ?>>Retirar</button>
                    </form>

                    <div class="goal-actions">
                        <a href="dashboard.php?section=goals&edit=<?= (int) $goal['id_goals'] ?>" class="btn btn-secondary">Editar</a>
                        <a href="dashboard.php?section=goals&acquire=<?= (int) $goal['id_goals'] ?>" class="btn btn-primary">Adquirido</a>

                        <form action="actions/goal_delete.php" method="POST" onsubmit="return confirm('Eliminar este objetivo? O valor reservado volta ao saldo disponível.');">
                            <input type="hidden" name="id" value="<?= (int) $goal['id_goals'] ?>">
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Objetivos adquiridos -->
    <?php if (!empty($acquiredGoals)): ?>
        <h2>Adquiridos</h2>
        <ul class="goal-done-list">
            <?php foreach ($acquiredGoals as $goal): ?>
                <li class="goal-done-item">
                    <span class="goal-done-name"><?= htmlspecialchars($goal['name']) ?></span>
                    <?php if ($goal['spent'] !== null): ?>
                        <span class="goal-done-info">
                            <?= formatMoney($goal['spent']) ?> · <?= formatDate($goal['spent_date']) ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>