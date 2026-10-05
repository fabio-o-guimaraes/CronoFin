<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../category_icons.php';

$userId = (int) $_SESSION['user_id'];

/* Categorias predefinidas */
$stmt = $pdo->query(
    'SELECT id_categories, name, icon FROM categories WHERE user_id IS NULL ORDER BY name'
);
$defaultCategories = $stmt->fetchAll();

/* Categorias do utilizador, com o número de movimentos de cada uma */
$stmt = $pdo->prepare(
    'SELECT c.id_categories, c.name, c.icon, c.status, COUNT(m.id_movements) AS movements_count
     FROM categories c
     LEFT JOIN movements m ON m.category_id = c.id_categories
     WHERE c.user_id = :id
     GROUP BY c.id_categories, c.name, c.icon, c.status
     ORDER BY c.name'
);
$stmt->execute(['id' => $userId]);
$userCategories = $stmt->fetchAll();

/* Separa as ativas das inativas */
$activeCategories   = array_filter($userCategories, fn($c) => $c['status'] === 'active');
$inactiveCategories = array_filter($userCategories, fn($c) => $c['status'] === 'inactive');

/* Modo de edição: ?edit=ID */
$editId = (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit']))
    ? (int) $_GET['edit']
    : 0;

$editCategory = null;
foreach ($userCategories as $category) {
    if ((int) $category['id_categories'] === $editId) {
        $editCategory = $category;
        break;
    }
}

/* Se houve erro no envio anterior, repõe o que o utilizador tinha escrito */
$formData = $_SESSION['category_form_data'] ?? [];
unset($_SESSION['category_form_data']);

$nameValue = $formData['name'] ?? ($editCategory['name'] ?? '');
$iconValue = $formData['icon'] ?? ($editCategory['icon'] ?? DEFAULT_CATEGORY_ICON);

/* Mensagens de erro e de sucesso */
$errorMessages = [
    'empty'    => 'O nome da categoria é obrigatório.',
    'exists'   => 'Já existe uma categoria com esse nome.',
    'icon'     => 'O ícone escolhido não é válido.',
    'notfound' => 'A categoria não foi encontrada.',
    'length' => 'O nome não pode ter mais de 100 caracteres.',
];

$successMessages = [
    'created'     => 'Categoria criada com sucesso.',
    'updated'     => 'Categoria atualizada com sucesso.',
    'deleted'     => 'Categoria eliminada com sucesso.',
    'deactivated' => 'A categoria tem movimentos associados, por isso foi desativada em vez de eliminada.',
    'reactivated' => 'Categoria reativada com sucesso.',
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

<section class="categories-section">
    <h1>Categorias</h1>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- Formulário: criar ou editar -->
    <form action="actions/<?= $editCategory ? 'category_update' : 'category_create' ?>.php" method="POST" class="category-form">
        <h2><?= $editCategory ? 'Editar categoria' : 'Nova categoria' ?></h2>

        <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?= (int) $editCategory['id_categories'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" maxlength="100" required value="<?= htmlspecialchars($nameValue) ?>">
        </div>

        <fieldset class="form-group icon-picker">
            <legend>Ícone</legend>
            <?php foreach (getCategoryIcons() as $icon): ?>
                <label class="icon-option">
                    <input type="radio" name="icon" value="<?= htmlspecialchars($icon) ?>" <?= $icon === $iconValue ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($icon) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit" class="btn btn-primary">
            <?= $editCategory ? 'Guardar alterações' : 'Criar categoria' ?>
        </button>

        <?php if ($editCategory): ?>
            <a href="dashboard.php?section=categories" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>

    <!-- Categorias do utilizador: ativas -->
    <h2>As minhas categorias</h2>

    <?php if (empty($activeCategories)): ?>
        <p>Ainda não criaste nenhuma categoria.</p>
    <?php else: ?>
        <ul class="category-list">
            <?php foreach ($activeCategories as $category): ?>
                <li class="category-item">
                    <span class="category-icon"><?= htmlspecialchars($category['icon'] ?? DEFAULT_CATEGORY_ICON) ?></span>
                    <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>

                    <div class="category-actions">
                        <a href="dashboard.php?section=categories&edit=<?= (int) $category['id_categories'] ?>" class="btn btn-secondary">Editar</a>

                        <form action="actions/category_delete.php" method="POST">
                            <input type="hidden" name="id" value="<?= (int) $category['id_categories'] ?>">
                            <button type="submit" class="btn btn-danger">
                                <?= (int) $category['movements_count'] === 0 ? 'Eliminar' : 'Desativar' ?>
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Categorias do utilizador: inativas -->
    <?php if (!empty($inactiveCategories)): ?>
        <h2>Categorias inativas</h2>
        <ul class="category-list">
            <?php foreach ($inactiveCategories as $category): ?>
                <li class="category-item category-item-inactive">
                    <span class="category-icon"><?= htmlspecialchars($category['icon'] ?? DEFAULT_CATEGORY_ICON) ?></span>
                    <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>

                    <div class="category-actions">
                        <form action="actions/category_reactivate.php" method="POST">
                            <input type="hidden" name="id" value="<?= (int) $category['id_categories'] ?>">
                            <button type="submit" class="btn btn-secondary">Reativar</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Categorias predefinidas -->
    <h2>Categorias predefinidas</h2>
    <ul class="category-list">
        <?php foreach ($defaultCategories as $category): ?>
            <li class="category-item">
                <span class="category-icon"><?= htmlspecialchars($category['icon'] ?? DEFAULT_CATEGORY_ICON) ?></span>
                <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>