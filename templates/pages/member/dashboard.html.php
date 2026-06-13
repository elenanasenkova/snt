<?php
$categoryLabel = [
    'sale'    => 'Продаю',
    'buy'     => 'Куплю',
    'service' => 'Услуги',
    'info'    => 'Информация',
    'wanted'  => 'Разыскиваю',
];
$categoryPill = [
    'sale'    => 'good',
    'buy'     => 'warn',
    'wanted'  => 'bad',
    'service' => '',
    'info'    => '',
];

// Ближайшее собрание
$nextMeeting = '—';
$upcoming = array_filter($meetings ?? [], fn($m) => strtotime($m['meeting_date']) >= strtotime('today'));
usort($upcoming, fn($a, $b) => strtotime($a['meeting_date']) - strtotime($b['meeting_date']));
if ($upcoming) {
    $nextMeeting = date('d.m.Y', strtotime(reset($upcoming)['meeting_date']));
}

// Неоплаченные взносы
$unpaid = count(array_filter($fees ?? [], fn($f) => ($f['status'] ?? '') !== 'paid'));

$year = (int)date('Y');
$memberSince = $user['created_at'] ? (int)date('Y', strtotime($user['created_at'])) : '—';
?>

<!-- Hero-панель -->
<div class="glass-deep panel stagger" style="display:grid;grid-template-columns:1.4fr 1fr;gap:28px;align-items:center;margin-bottom:28px">

  <!-- Слева: приветствие + кнопки -->
  <div>
    <div class="mono" style="color:var(--ink-mute);text-transform:uppercase;letter-spacing:.12em">
      Личный кабинет<?= $plotNumber ? ' · Участок №' . e($plotNumber) : '' ?>
    </div>
    <h2 style="margin:10px 0 8px"><?= e($user['full_name']) ?></h2>
    <p style="color:var(--ink-soft);margin:0 0 18px">
      <?php if ($unpaid === 0): ?>
        Все взносы за <?= $year ?> год оплачены.
      <?php else: ?>
        Есть неоплаченные взносы.
      <?php endif; ?>
      <?php if ($nextMeeting !== '—'): ?>
        Следующее собрание — <?= e($nextMeeting) ?>.
      <?php endif; ?>
    </p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="/finances" class="btn primary">История платежей</a>
      <a href="/classifieds" class="btn">Объявления</a>
      <a href="/votes" class="btn ghost">Голосования</a>
    </div>
  </div>

  <!-- Справа: KPI-карточки -->
  <div class="grid-2" style="gap:12px">

    <div class="card" style="padding:16px">
      <div class="label" style="font-size:12px;color:var(--ink-mute);margin-bottom:4px">Задолженность</div>
      <div class="value mono" style="font-size:22px;font-weight:700;color:<?= ($debtAmount ?? 0) > 0 ? 'var(--bad)' : 'var(--good)' ?>">
        <?= ($debtAmount ?? 0) > 0 ? number_format($debtAmount, 0, '.', ' ') . ' ₽' : '0 ₽' ?>
      </div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">
        <?= ($debtAmount ?? 0) > 0 ? 'Оплатить до 01.07' : 'Нет долгов' ?>
      </div>
    </div>

    <div class="card" style="padding:16px">
      <div class="label" style="font-size:12px;color:var(--ink-mute);margin-bottom:4px">Оплачено в <?= $year ?></div>
      <div class="value mono" style="font-size:22px;font-weight:700">
        <?= number_format($totalPaid ?? 0, 0, '.', ' ') ?> ₽
      </div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">Членский + целевые</div>
    </div>

    <div class="card" style="padding:16px">
      <div class="label" style="font-size:12px;color:var(--ink-mute);margin-bottom:4px">Участок с</div>
      <div class="value mono" style="font-size:22px;font-weight:700"><?= e($memberSince) ?></div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">Год вступления</div>
    </div>

    <div class="card" style="padding:16px">
      <div class="label" style="font-size:12px;color:var(--ink-mute);margin-bottom:4px">Всего внесено</div>
      <div class="value mono" style="font-size:22px;font-weight:700">
        <?= number_format($totalPaid ?? 0, 0, '.', ' ') ?> ₽
      </div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">За всё время</div>
    </div>

  </div>
</div>

<!-- Предстоящие собрания -->
<?php if (!empty($upcoming)): ?>
<div class="card" style="margin-bottom:24px">
  <div style="padding:18px 20px;border-bottom:1px solid var(--line-soft)">
    <h3 style="margin:0">Предстоящие собрания</h3>
  </div>
  <?php foreach (array_slice($upcoming, 0, 3) as $m): ?>
  <div style="padding:14px 20px;border-bottom:1px solid var(--line-soft);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <div style="min-width:0">
      <div style="font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= e($m['topic'] ?? 'Без темы') ?>
      </div>
      <?php if (!empty($m['location'])): ?>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px"><?= e($m['location']) ?></div>
      <?php endif; ?>
    </div>
    <span class="pill" style="flex-shrink:0"><?= date('d.m.Y', strtotime($m['meeting_date'])) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Последние объявления -->
<?php if (!empty($announcements)): ?>
<?php
$annCatColor = ['sale'=>'var(--good)','buy'=>'var(--warn)','wanted'=>'var(--bad)','service'=>'var(--accent)','info'=>'var(--ink-mute)'];
?>
<div class="card" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--line-soft);display:flex;justify-content:space-between;align-items:center">
    <h3 style="margin:0">Последние объявления</h3>
    <a href="/classifieds" style="font-size:13px;color:var(--accent-deep)">Все →</a>
  </div>
  <?php foreach (array_slice($announcements, 0, 5) as $ann):
    $cat   = $ann['category'] ?? 'info';
    $pill  = $categoryPill[$cat] ?? '';
    $label = $categoryLabel[$cat] ?? $cat;
    $color = $annCatColor[$cat] ?? 'var(--ink-mute)';
    $plot  = plotFromAddress($ann['author_address'] ?? '');
  ?>
  <div style="padding:14px 20px;border-bottom:1px solid var(--line-soft);border-left:3px solid <?= $color ?>">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:6px">
      <div style="font-weight:600;color:var(--ink);min-width:0;overflow-wrap:break-word;flex:1;font-size:14px">
        <?= e($ann['title']) ?>
      </div>
      <span style="font-size:11px;color:var(--ink-mute);white-space:nowrap;flex-shrink:0">
        <?= date('d.m.Y', strtotime($ann['created_at'])) ?>
      </span>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <span class="pill <?= $pill ?>" style="font-size:11px"><?= e($label) ?></span>
      <span style="font-size:12px;color:var(--ink-mute)">
        <?= e($ann['author_name'] ?? '') ?>
        <?= $plot ? ' · №' . e($plot) : '' ?>
      </span>
    </div>
  </div>
  <?php endforeach; ?>
  <div style="padding:10px 20px;text-align:center">
    <a href="/classifieds" class="btn sm ghost" style="font-size:12px">Открыть доску объявлений</a>
  </div>
</div>
<?php endif; ?>
