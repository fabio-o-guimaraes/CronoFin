<?php

/**
 * @var array<string, string> $sections  Lista de secções (definida em dashboard.php)
 * @var string $section                  Secção ativa (definida em dashboard.php)
 */
?>

<nav class="app-sidebar" id="app-sidebar" aria-label="Navegação principal">
    <ul class="sidebar-nav">
        <?php foreach ($sections as $key => $label): ?>
            <li>
                <a href="dashboard.php?section=<?= $key ?>"
                    class="sidebar-link <?= $key === $section ? 'sidebar-link-active' : '' ?>"
                    <?= $key === $section ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>