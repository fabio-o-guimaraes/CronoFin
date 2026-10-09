<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../category_icons.php';
require_once __DIR__ . '/../format.php';

$userId = (int) $_SESSION['user_id'];

/* Categorias que o utilizador pode ver: predefinidas + próprias */
$stmt = $pdo->prepare(
    'SELECT id_categories, name, icon, status
     FROM categories
     WHERE user_id IS NULL OR user_id = :id
     ORDER BY name'
);
$stmt->execute(['id' => $userId]);
$allCategories = $stmt->fetchAll();

/* Modo de edição: ?edit=ID */
$editId = (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit']))
    ? (int) $_GET['edit']
    : 0;

$editMovement = null;

if ($editId !== 0) {
    $stmt = $pdo->prepare(
        'SELECT id_movements, date, value, description, type, category_id
         FROM movements
         WHERE id_movements = :id AND user_id = :user_id'
    );
    $stmt->execute(['id' => $editId, 'user_id' => $userId]);
    $editMovement = $stmt->fetch() ?: null;
}

/* Para registar ou editar, só as ativas (na edição, mantém-se a categoria atual do movimento, mesmo inativa) */
$formCategories = array_filter(
    $allCategories,
    fn($c) =>
    $c['status'] === 'active'
        || ($editMovement !== null && (int) $c['id_categories'] === (int) $editMovement['category_id'])
);

/* Filtros recebidos por GET (cada um é validado, e se for inválido fica vazio) */
$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';

$typeFilter = isset($_GET['type']) && in_array($_GET['type'], ['income', 'expense'], true)
    ? $_GET['type']
    : '';

$categoryFilter = isset($_GET['category']) && is_string($_GET['category']) && ctype_digit($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$monthFilter = isset($_GET['month']) && is_string($_GET['month']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['month'])
    ? $_GET['month']
    : '';

/* Monta a consulta: começa só com o utilizador e acrescenta uma condição por cada filtro ativo */
$where  = ['m.user_id = :user_id'];
$params = ['user_id' => $userId];

if ($search !== '') {
    $where[] = 'm.description LIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($typeFilter !== '') {
    $where[] = 'm.type = :type';
    $params['type'] = $typeFilter;
}

if ($categoryFilter !== 0) {
    $where[] = 'm.category_id = :category';
    $params['category'] = $categoryFilter;
}

if ($monthFilter !== '') {
    $where[] = "DATE_FORMAT(m.date, '%Y-%m') = :month";
    $params['month'] = $monthFilter;
}

$stmt = $pdo->prepare(
    'SELECT m.id_movements, m.date, m.value, m.description, m.type, m.category_id,
            c.name AS category_name, c.icon AS category_icon
     FROM movements m
     JOIN categories c ON c.id_categories = m.category_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY m.date DESC, m.id_movements DESC'
);
$stmt->execute($params);
$movements = $stmt->fetchAll();

/* Serve para mostrar o botão "Limpar" e a mensagem certa quando a lista está vazia */
$hasFilters = $search !== '' || $typeFilter !== '' || $categoryFilter !== 0 || $monthFilter !== '';

/* Se houve erro no envio anterior, repõe o que o utilizador tinha escrito */
$formData = $_SESSION['movement_form_data'] ?? [];
unset($_SESSION['movement_form_data']);

$typeValue        = $formData['type']        ?? ($editMovement['type'] ?? 'expense');
$valueValue       = $formData['value']       ?? ($editMovement ? number_format((float) $editMovement['value'], 2, ',', '') : '');
$dateValue        = $formData['date']        ?? ($editMovement['date'] ?? date('Y-m-d'));
$categoryValue    = (int) ($formData['category'] ?? ($editMovement['category_id'] ?? 0));
$descriptionValue = $formData['description'] ?? ($editMovement['description'] ?? '');

/* Mensagens de erro e de sucesso */
$errorMessages = [
    'type'        => 'O tipo de movimento não é válido.',
    'value'       => 'Indica um valor válido, maior que zero.',
    'date'        => 'A data não é válida.',
    'future'      => 'A data não pode ser futura.',
    'category'    => 'Escolhe uma categoria válida.',
    'length'      => 'A descrição não pode ter mais de 100 caracteres.',
    'notfound'    => 'O movimento não foi encontrado.',
];

$successMessages = [
    'created' => 'Movimento registado com sucesso.',
    'updated' => 'Movimento atualizado com sucesso.',
    'deleted' => 'Movimento eliminado com sucesso.',
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

<section class="movements-section">
    <h1>Movimentos</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- Formulário: novo movimento / editar movimento -->
    <form action="actions/<?= $editMovement ? 'movement_update' : 'movement_create' ?>.php" method="POST" class="movement-form">
        <h2><?= $editMovement ? 'Editar movimento' : 'Novo movimento' ?></h2>

        <?php if ($editMovement): ?>
            <input type="hidden" name="id" value="<?= (int) $editMovement['id_movements'] ?>">
        <?php endif; ?>

        <fieldset class="form-group type-picker">

            <legend>Tipo</legend>

            <label class="type-option">
                <input type="radio" name="type" value="expense" <?= $typeValue === 'expense' ? 'checked' : '' ?>>
                <span>Despesa</span>
            </label>

            <label class="type-option">
                <input type="radio" name="type" value="income" <?= $typeValue === 'income' ? 'checked' : '' ?>>
                <span>Receita</span>
            </label>
        </fieldset>

        <div class="form-group">
            <label for="value">Valor (€)</label>
            <input type="text" id="value" name="value" inputmode="decimal" placeholder="0,00" required value="<?= htmlspecialchars($valueValue) ?>">
        </div>

        <div class="form-group">
            <label for="date">Data</label>
            <input type="date" id="date" name="date" max="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($dateValue) ?>">
        </div>

        <div class="form-group">
            <label for="category">Categoria</label>
            <select id="category" name="category" required>
                <option value="">Escolhe uma categoria</option>
                <?php foreach ($formCategories as $category): ?>
                    <option value="<?= (int) $category['id_categories'] ?>" <?= (int) $category['id_categories'] === $categoryValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($category['icon'] ?? DEFAULT_CATEGORY_ICON) . ' ' . $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Descrição (opcional)</label>
            <input type="text" id="description" name="description" maxlength="100" value="<?= htmlspecialchars($descriptionValue) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $editMovement ? 'Guardar alterações' : 'Registar movimento' ?>
            </button>

            <?php if ($editMovement): ?>
                <a href="dashboard.php?section=movements" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Filtros -->
    <form action="dashboard.php" method="GET" class="movement-filters">
        <input type="hidden" name="section" value="movements">

        <h2>Pesquisar e filtrar</h2>

        <div class="form-group">
            <label for="search">Descrição</label>
            <input type="text" id="search" name="search" placeholder="Descrição..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="form-group">
            <label for="filter-type">Tipo</label>
            <select id="filter-type" name="type">
                <option value="">Todos</option>
                <option value="income" <?= $typeFilter === 'income' ? 'selected' : '' ?>>Receitas</option>
                <option value="expense" <?= $typeFilter === 'expense' ? 'selected' : '' ?>>Despesas</option>
            </select>
        </div>

        <div class="form-group">
            <label for="filter-category">Categoria</label>
            <select id="filter-category" name="category">
                <option value="">Todas</option>
                <?php foreach ($allCategories as $category): ?>
                    <option value="<?= (int) $category['id_categories'] ?>" <?= (int) $category['id_categories'] === $categoryFilter ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($category['icon'] ?? DEFAULT_CATEGORY_ICON) . ' ' . $category['name']) ?><?= $category['status'] === 'inactive' ? ' (inativa)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="filter-month">Mês</label>
            <input type="month" id="filter-month" name="month" value="<?= htmlspecialchars($monthFilter) ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <?php if ($hasFilters): ?>
                <a href="dashboard.php?section=movements" class="btn btn-secondary">Limpar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Lista de movimentos -->
    <h2>Os meus movimentos</h2>

    <?php if (empty($movements)): ?>
        <p>
            <?= $hasFilters
                ? 'Nenhum movimento corresponde aos filtros escolhidos.'
                : 'Ainda não registaste nenhum movimento.' ?>
        </p>
    <?php else: ?>
        <ul class="movement-list">
            <?php foreach ($movements as $movement): ?>
                <li class="movement-item">
                    <span class="movement-icon"><?= htmlspecialchars($movement['category_icon'] ?? DEFAULT_CATEGORY_ICON) ?></span>

                    <div class="movement-info">
                        <span class="movement-description">
                            <?= htmlspecialchars($movement['description'] ?: $movement['category_name']) ?>
                        </span>
                        <span class="movement-meta">
                            <?= htmlspecialchars($movement['category_name']) ?> · <?= formatDate($movement['date']) ?>
                        </span>
                    </div>

                    <span class="movement-value movement-value-<?= htmlspecialchars($movement['type']) ?>">
                        <?= $movement['type'] === 'income' ? '+' : '−' ?> <?= formatMoney($movement['value']) ?>
                    </span>

                    <div class="movement-actions">
                        <a href="dashboard.php?section=movements&edit=<?= (int) $movement['id_movements'] ?>" class="btn btn-secondary">Editar</a>

                        <form action="actions/movement_delete.php" method="POST" onsubmit="return confirm('Eliminar este movimento? Esta ação não pode ser desfeita.');">
                            <input type="hidden" name="id" value="<?= (int) $movement['id_movements'] ?>">
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>