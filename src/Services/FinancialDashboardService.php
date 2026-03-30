<?php

namespace App\Services;

use DateTimeImmutable;

class FinancialDashboardService
{
    public static function monthNames(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Marco',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }

    public static function shortMonthNames(): array
    {
        return [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];
    }

    public static function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public function buildAnalysis(array $seriesBySource, array $selectedSources, DateTimeImmutable $today): array
    {
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('n');
        $currentDay = (int) $today->format('j');
        $daysInMonth = (int) $today->format('t');

        $yearsMap = [];
        $matrix = [];

        foreach ($selectedSources as $sourceKey) {
            foreach (($seriesBySource[$sourceKey] ?? []) as $year => $months) {
                $year = (int) $year;
                if ($year <= 0) {
                    continue;
                }

                $yearsMap[$year] = $year;
                $matrix[$year] = $matrix[$year] ?? [];

                foreach ($months as $month => $amount) {
                    $month = (int) $month;
                    if ($month < 1 || $month > 12) {
                        continue;
                    }

                    $matrix[$year][$month] = (float) ($matrix[$year][$month] ?? 0) + (float) $amount;
                }
            }
        }

        ksort($yearsMap);
        $years = array_values($yearsMap);
        $monthNames = self::monthNames();
        $shortMonthNames = self::shortMonthNames();
        $heatValues = [];
        $rows = [];
        $yearTotals = [];
        $promotionCandidates = [];

        foreach ($years as $year) {
            $yearTotals[$year] = 0.0;
        }

        for ($month = 1; $month <= 12; $month++) {
            $rowValues = [];
            $averageBase = [];

            foreach ($years as $year) {
                $amount = (float) ($matrix[$year][$month] ?? 0);
                $isFutureCell = $year === $currentYear && $month > $currentMonth;
                $displayValue = $isFutureCell ? null : $amount;

                if ($displayValue !== null) {
                    $yearTotals[$year] += $displayValue;
                    $heatValues[] = $displayValue;
                }

                $rowValues[] = [
                    'year' => $year,
                    'value' => $displayValue,
                ];

                if ($displayValue === null) {
                    continue;
                }

                if ($year === $currentYear && $month === $currentMonth) {
                    continue;
                }

                if ($amount > 0) {
                    $averageBase[] = $amount;
                }
            }

            $averageRaw = empty($averageBase) ? 0.0 : array_sum($averageBase) / count($averageBase);
            $averageDisplay = $month === $currentMonth
                ? ($daysInMonth > 0 ? ($averageRaw / $daysInMonth) * $currentDay : 0.0)
                : $averageRaw;

            if ($averageDisplay > 0) {
                $promotionCandidates[] = [
                    'month' => $month,
                    'label' => $monthNames[$month],
                    'average' => $averageDisplay,
                ];
            }

            $rows[] = [
                'month' => $month,
                'month_label' => $monthNames[$month],
                'values' => $rowValues,
                'average' => $averageDisplay,
            ];
        }

        usort($promotionCandidates, static fn (array $a, array $b): int => $a['average'] <=> $b['average']);
        $lowest = array_slice($promotionCandidates, 0, 3);
        $highest = array_slice(array_reverse($promotionCandidates), 0, 3);

        $currentMonthTotal = (float) ($matrix[$currentYear][$currentMonth] ?? 0);
        $expectedValue = (float) ($rows[$currentMonth - 1]['average'] ?? 0);

        return [
            'selected_sources' => $selectedSources,
            'years' => $years,
            'rows' => $rows,
            'year_totals' => $yearTotals,
            'heat_min' => empty($heatValues) ? 0.0 : min($heatValues),
            'heat_max' => empty($heatValues) ? 0.0 : max($heatValues),
            'current_month_total' => $currentMonthTotal,
            'expected_value' => $expectedValue,
            'indicator' => $currentMonthTotal - $expectedValue,
            'chart_labels' => array_values($shortMonthNames),
            'chart_values' => array_map(static fn (array $row): float => (float) $row['average'], $rows),
            'lowest_months' => $lowest,
            'highest_months' => $highest,
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
            'current_day' => $currentDay,
            'days_in_month' => $daysInMonth,
        ];
    }
}
