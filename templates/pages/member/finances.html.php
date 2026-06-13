<?php
/**
 * Member Finances
 * Variables: $fees, $plotNumber, $years, $user
 */

$statusPill = [
    'paid'    => ['class' => 'good', 'label' => 'Оплачено'],
    'partial' => ['class' => 'warn', 'label' => 'Частично'],
    'unpaid'  => ['class' => 'bad',  'label' => 'Не оплачено'],
];

$totalRequired = 0;
$totalPaid     = 0;
if (!empty($fees)) {
    foreach ($fees as $fee) {
        $totalRequired += (float)($fee['required_amount'] ?? 0);
        $totalPaid     += (float)($fee['paid_amount'] ?? 0);
    }
}
$totalBalance = $totalRequired - $totalPaid;
?>
<div class="panel fade-up">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
        <h2 style="margin:0;">Мои взносы</h2>
        <button class="btn ghost sm" disabled title="Скоро" style="opacity:.55;cursor:not-allowed;">
            ⬇ Выписка PDF
        </button>
    </div>

    <div class="grid-3" style="margin-bottom:24px;">
        <div class="card" style="padding:18px 20px">
            <div style="font-size:12px;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">К оплате</div>
            <div class="mono" style="font-size:28px;font-weight:700;color:<?= $totalBalance > 0 ? 'var(--bad)' : 'var(--good)' ?>">
                <?= number_format($totalBalance, 0, '.', ' ') ?> ₽
            </div>
            <?php if ($totalBalance > 0): ?>
                <span class="pill bad" style="margin-top:8px;display:inline-block">Есть задолженность</span>
            <?php else: ?>
                <span class="pill good" style="margin-top:8px;display:inline-block">Всё оплачено</span>
            <?php endif; ?>
        </div>
        <div class="card" style="padding:18px 20px">
            <div style="font-size:12px;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Оплачено</div>
            <div class="mono" style="font-size:28px;font-weight:700;color:var(--good)">
                <?= number_format($totalPaid, 0, '.', ' ') ?> ₽
            </div>
        </div>
        <div class="card" style="padding:18px 20px">
            <div style="font-size:12px;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Начислено</div>
            <div class="mono" style="font-size:28px;font-weight:700;color:var(--ink)">
                <?= number_format($totalRequired, 0, '.', ' ') ?> ₽
            </div>
        </div>
    </div>

    <?php if (!$plotNumber): ?>
        <div class="card" style="border-left:4px solid var(--accent); padding:16px 20px; color:var(--ink-soft);">
            Номер участка не указан в профиле. Обратитесь к председателю для привязки участка.
        </div>
    <?php else: ?>
        <div style="margin-bottom:20px;">
            <span class="pill">Участок №<?= e($plotNumber) ?></span>
        </div>

        <?php if (empty($fees)): ?>
            <div style="text-align:center; padding:48px 0; color:var(--ink-mute);">
                Данные о взносах отсутствуют.
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="t">
                    <thead>
                        <tr>
                            <th>Взнос</th>
                            <th>Год</th>
                            <th style="text-align:right;">Требуется, ₽</th>
                            <th style="text-align:right;">Оплачено, ₽</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fees as $fee): ?>
                            <?php
                            $status = $fee['status'] ?? 'unpaid';
                            $pill   = $statusPill[$status] ?? $statusPill['unpaid'];
                            ?>
                            <tr>
                                <td><?= e($fee['fee_name'] ?? $fee['name'] ?? '—') ?></td>
                                <td><?= e($fee['year'] ?? '—') ?></td>
                                <td style="text-align:right;" class="mono">
                                    <?= number_format((float)($fee['required_amount'] ?? 0), 0, '.', ' ') ?>
                                </td>
                                <td style="text-align:right;" class="mono">
                                    <?= number_format((float)($fee['paid_amount'] ?? 0), 0, '.', ' ') ?>
                                </td>
                                <td><span class="pill <?= $pill['class'] ?>"><?= $pill['label'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700;">
                            <td colspan="2">Итого</td>
                            <td style="text-align:right;" class="mono">
                                <?= number_format($totalRequired, 0, '.', ' ') ?>
                            </td>
                            <td style="text-align:right;" class="mono">
                                <?= number_format($totalPaid, 0, '.', ' ') ?>
                            </td>
                            <td>
                                <?php if ($totalBalance <= 0): ?>
                                    <span class="pill good">Нет долга</span>
                                <?php else: ?>
                                    <span class="pill bad">Долг: <?= number_format($totalBalance, 0, '.', ' ') ?> ₽</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
