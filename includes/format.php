<?php
/* 1234.5 -> "1 234,50 €" */
function formatMoney(float|string $value): string
{
    return number_format((float) $value, 2, ',', "\u{00A0}") . "\u{00A0}€";
}

/* "2026-10-08" -> "08/10/2026" */
function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

/* Valor máximo que cabe nas colunas DECIMAL(10,2) */
const MAX_AMOUNT = 99999999.99;

/* "12,50" ou "12.50" -> 12.5. Devolve null se não for um valor válido */
function parseAmount(string $input): ?float
{
    $normalized = str_replace(',', '.', str_replace([' ', "\u{00A0}"], '', trim($input)));

    if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
        return null;
    }

    return (float) $normalized;
}
