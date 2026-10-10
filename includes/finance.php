<?php
/* Total reservado nos objetivos ativos (soma das contribuições) */
function getTotalReserved(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(c.value), 0)
         FROM contributions c
         JOIN goals g ON g.id_goals = c.goal_id
         WHERE g.user_id = :user_id AND g.status = \'active\''
    );
    $stmt->execute(['user_id' => $userId]);

    return round((float) $stmt->fetchColumn(), 2);
}

/* Saldo disponível = receitas - despesas - reservado nos objetivos ativos */
function getAvailableBalance(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(CASE WHEN type = \'income\' THEN value ELSE -value END), 0)
         FROM movements
         WHERE user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId]);
    $movementsBalance = (float) $stmt->fetchColumn();

    return round($movementsBalance - getTotalReserved($pdo, $userId), 2);
}
