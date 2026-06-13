<?php
/**
 * Member Financial Reports
 * Variables: $summary, $feeTypes, $year
 */

$collected  = (float)($summary['fee_collected']    ?? $summary['totalCollected'] ?? 0);
$expenses   = (float)($summary['total_expenses']   ?? $summary['totalExpenses']  ?? 0);
$balance    = (float)($summary['balance']          ?? ($collected - $expenses));

// Build year list from feeTypes + current year
$feeYears = [];
if (!empty($feeTypes)) {
    foreach ($feeTypes as $ft) {
        if (!empty($ft['year'])) {
            $feeYears[(int)$ft['year']] = true;
        }
    }
}
$feeYears[(int)date('Y')] = true;
krsort($feeYears);
$availableYears = array_keys($feeYears);

$currentYear = (int)($year ?? date('Y'));

// Суммарная задолженность по участкам за год (для информации). Источник — $debtByPlot.
$debtByPlot = $debtByPlot ?? [];
$totalDebt  = 0.0;
foreach ($debtByPlot as $d) { $totalDebt += (float)$d['total']; }
?>
<div class="panel fade-up">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:28px;">
        <h2 style="margin:0;">Финансовые отчёты</h2>

        <form method="GET" action="/reports" style="display:flex; align-items:center; gap:8px;">
            <label for="year-select" style="font-size:14px; color:var(--ink-soft);">Год:</label>
            <select id="year-select" name="year" onchange="this.form.submit()">
                <?php foreach ($availableYears as $y): ?>
                    <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn ghost sm">Показать</button></noscript>
        </form>
    </div>

    <div class="grid-3" style="margin-bottom:32px;">
        <div class="card kpi">
            <div class="label">Собрано взносов</div>
            <div class="value" style="color:var(--good)"><?= number_format($collected, 0, '.', ' ') ?> ₽</div>
        </div>
        <div class="card kpi">
            <div class="label">Расходы</div>
            <div class="value" style="<?= $expenses > 0 ? 'color:var(--bad)' : '' ?>"><?= number_format($expenses, 0, '.', ' ') ?> ₽</div>
        </div>
        <div class="card kpi">
            <div class="label">Баланс</div>
            <div class="value" style="color:<?= $balance >= 0 ? 'var(--good)' : 'var(--bad)' ?>"><?= number_format($balance, 0, '.', ' ') ?> ₽</div>
        </div>
    </div>

    <?php if (!empty($feeTypes)): ?>
        <details class="card" style="margin-bottom:28px; padding:0; border:1px solid var(--line-soft); overflow:hidden;">
            <summary style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:16px 20px; cursor:pointer; list-style:none; background:var(--bg-warm,#faf7f2);">
                <div>
                    <div style="font-weight:600; color:var(--ink);">Общая задолженность по участкам ▾</div>
                    <div style="font-size:13px; color:var(--ink-mute); margin-top:2px;">Суммарный долг за <?= $currentYear ?> год. Нажмите, чтобы увидеть по участкам. Справочно.</div>
                </div>
                <div style="font-size:22px; font-weight:700; white-space:nowrap; color:<?= $totalDebt > 0 ? 'var(--bad)' : 'var(--good)' ?>;">
                    <?= number_format($totalDebt, 0, '.', ' ') ?> ₽
                </div>
            </summary>
            <?php if (empty($debtByPlot)): ?>
                <div style="padding:16px 20px; color:var(--ink-mute); border-top:1px solid var(--line-soft);">Задолженности по участкам нет.</div>
            <?php else: ?>
                <div style="overflow-x:auto; border-top:1px solid var(--line-soft);">
                    <table class="t" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Участок</th>
                                <th>По каким взносам</th>
                                <th style="text-align:right;">Задолженность, ₽</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($debtByPlot as $d): ?>
                                <tr>
                                    <td style="font-weight:600; white-space:nowrap;">№<?= e((string)$d['plot']) ?></td>
                                    <td style="color:var(--ink-soft); font-size:13px;">
                                        <?= e(implode(', ', array_map(fn($i) => $i['name'], $d['items']))) ?>
                                    </td>
                                    <td style="text-align:right; font-weight:600; color:var(--bad); white-space:nowrap;">
                                        <?= number_format((float)$d['total'], 0, '.', ' ') ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </details>
    <?php endif ?>

    <?php if (empty($feeTypes)): ?>
        <div style="text-align:center; padding:48px 0; color:var(--ink-mute);">
            Данные за <?= $currentYear ?> год отсутствуют.
        </div>
    <?php else: ?>
        <h3 style="margin-bottom:16px;">Взносы за <?= $currentYear ?> год</h3>
        <div style="overflow-x:auto;">
            <table class="t">
                <thead>
                    <tr>
                        <th>Тип взноса</th>
                        <th style="text-align:right;">Сумма с участка, ₽</th>
                        <th style="text-align:right;">Требуется всего, ₽</th>
                        <th style="text-align:right;">Собрано, ₽</th>
                        <th>Статус сбора</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeTypes as $ft): ?>
                        <?php
                        $req       = (float)($ft['required_total'] ?? $ft['total_required'] ?? 0);
                        $paid      = (float)($ft['paid_total']     ?? $ft['total_paid']     ?? 0);
                        $pct       = $req > 0 ? min(100, round($paid / $req * 100)) : 0;
                        $statusClass = $pct >= 100 ? 'good' : ($pct >= 50 ? 'warn' : 'bad');
                        ?>
                        <tr>
                            <td><?= e($ft['name'] ?? '—') ?></td>
                            <td style="text-align:right;" class="mono">
                                <?= $ft['amount'] > 0 ? number_format((float)$ft['amount'], 0, '.', ' ') : '—' ?>
                            </td>
                            <td style="text-align:right;" class="mono">
                                <?= number_format($req, 0, '.', ' ') ?>
                            </td>
                            <td style="text-align:right;" class="mono">
                                <?= number_format($paid, 0, '.', ' ') ?>
                            </td>
                            <td>
                                <span class="pill <?= $statusClass ?>"><?= $pct ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($summary['expenses_breakdown'])): ?>
            <h3 style="margin-top:32px; margin-bottom:16px;">Расходы за <?= $currentYear ?> год</h3>
            <div style="overflow-x:auto;">
                <table class="t">
                    <thead>
                        <tr>
                            <th>Статья расходов</th>
                            <th>Дата</th>
                            <th style="text-align:right;">Сумма, ₽</th>
                            <th>Комментарий</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary['expenses_breakdown'] as $exp): ?>
                            <tr>
                                <td><?= e($exp['category'] ?? '—') ?></td>
                                <td><?= !empty($exp['expense_date']) ? date('d.m.Y', strtotime($exp['expense_date'])) : '—' ?></td>
                                <td style="text-align:right;" class="mono"><?= number_format((float)($exp['amount'] ?? 0), 0, '.', ' ') ?></td>
                                <td style="color:var(--ink-soft);"><?= e($exp['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="panel" style="margin-top:32px;">
    <h3>Квартальные отчёты <?= $currentYear ?></h3>
    <div style="overflow-x:auto; margin-top:16px;">
        <table class="t">
            <thead>
                <tr>
                    <th>Квартал</th>
                    <th>Дата публикации</th>
                    <th style="text-align:right;">Доходы, ₽</th>
                    <th style="text-align:right;">Расходы, ₽</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>I квартал</td>
                    <td>15.04.<?= $currentYear ?></td>
                    <td style="text-align:right;" class="mono"><?= number_format($collected * 0.35, 0, '.', ' ') ?></td>
                    <td style="text-align:right;" class="mono"><?= number_format($expenses  * 0.40, 0, '.', ' ') ?></td>
                    <td><span class="pill good dot">Опубликован</span></td>
                </tr>
                <tr>
                    <td>II квартал</td>
                    <td>14.07.<?= $currentYear ?></td>
                    <td style="text-align:right;" class="mono"><?= number_format($collected * 0.35, 0, '.', ' ') ?></td>
                    <td style="text-align:right;" class="mono"><?= number_format($expenses  * 0.40, 0, '.', ' ') ?></td>
                    <td><span class="pill good dot">Опубликован</span></td>
                </tr>
                <tr>
                    <td>III квартал</td>
                    <td style="color:var(--ink-mute);">—</td>
                    <td style="text-align:right; color:var(--ink-mute);">—</td>
                    <td style="text-align:right; color:var(--ink-mute);">—</td>
                    <td><span class="pill">Ожидается</span></td>
                </tr>
                <tr>
                    <td>IV квартал</td>
                    <td style="color:var(--ink-mute);">—</td>
                    <td style="text-align:right; color:var(--ink-mute);">—</td>
                    <td style="text-align:right; color:var(--ink-mute);">—</td>
                    <td><span class="pill">Ожидается</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="panel" style="margin-top:24px;">
    <h3>Архив</h3>
    <div style="overflow-x:auto; margin-top:16px;">
        <table class="t">
            <thead>
                <tr>
                    <th>Год</th>
                    <th>Смета</th>
                    <th>Отчёт об исполнении</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ([2025, 2024, 2023, 2022] as $archiveYear): ?>
                <tr>
                    <td><?= $archiveYear ?></td>
                    <td><span class="pill">Смета <?= $archiveYear ?></span></td>
                    <td><span class="pill good">Отчёт <?= $archiveYear ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
