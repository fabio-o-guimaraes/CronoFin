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
