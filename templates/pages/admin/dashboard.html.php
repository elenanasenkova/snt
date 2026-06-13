<?php /** @var array $stats @var array $meetings @var array $feeTypes @var array $pendingRegistrations @var int $year @var array $summary @var array $debtors */ ?>
<?php
  // Процент сбора взносов
  $feeCollected = (float)($summary['fee_collected'] ?? 0);
  $totalRequired = 0;
  foreach ($feeTypes as $ft) {
      $totalRequired += (float)($ft['required_total'] ?? 0);
  }
  $collectionPct = ($totalRequired > 0) ? round($feeCollected / $totalRequired * 100) : 0;

  // Имя председателя (текущий пользователь)
  $currentUser = getCurrentUser();
  $chairName = e($currentUser['full_name'] ?? 'Председатель');
?>

<div class="fade-up">

  <?php if (!empty($pendingRegistrations)): ?>
    <div class="card" style="background:var(--warn-soft,#fff8e1);border:1px solid #f59e0b;margin-bottom:24px;padding:16px 20px;display:flex;align-items:center;gap:12px">
      <span style="font-size:20px">⚠️</span>
      <span style="color:var(--ink);font-weight:500">
        <?= count($pendingRegistrations) ?> новых заявок на вступление
      </span>
      <a href="/admin/moderation" class="btn sm" style="margin-left:auto">Рассмотреть</a>
    </div>
  <?php endif ?>

  <!-- Hero панель -->
  <div class="glass-deep panel" style="margin-bottom:28px">
    <!-- Заголовок + CTA -->
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:20px">
      <div>
        <div class="mono" style="font-size:12px;color:var(--ink-mute);margin-bottom:10px;letter-spacing:.06em">
          Админ-панель · <?= $chairName ?>
        </div>
        <h2 style="margin:0 0 10px;color:var(--ink);font-size:26px">Панель управления</h2>
        <p style="color:var(--ink-soft);margin:0;font-size:14px;line-height:1.6">
          Управление взносами, голосованиями и собраниями СНТ «Берёзка». Участков: 61.
        </p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if (canFinance()): ?><a href="/admin/finances" class="btn primary sm">Финансы</a><?php endif ?>
        <a href="/classifieds" class="btn sm">Объявления</a>
        <?php if (canMeetings()): ?><a href="/admin/votes" class="btn ghost sm">Голосования</a><?php endif ?>
        <?php if (canFinance()): ?>
        <form method="POST" action="/admin/remind-fees" style="margin:0" onsubmit="return confirm('Разослать всем членам напоминание об оплате взносов?')">
          <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
          <button type="submit" class="btn ghost sm">🔔 Напомнить о взносах</button>
        </form>
        <?php endif ?>
      </div>
    </div>
    <!-- KPI в одну строку -->
    <div style="display:flex;gap:14px;flex-wrap:wrap">
      <!-- Должников -->
      <div class="card" style="flex:1;min-width:140px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div class="kpi label" style="font-size:12px;color:var(--ink-mute)">Должников</div>
        <div class="kpi value" style="font-size:22px;font-weight:700;color:var(--ink)">
          <?php if ($debtors['count'] > 0): ?>
            <span class="pill bad" style="font-size:16px;padding:3px 10px"><?= $debtors['count'] ?></span>
          <?php else: ?>
            <span class="pill good" style="font-size:14px;padding:3px 10px">Нет</span>
          <?php endif ?>
        </div>
      </div>
      <!-- Долг всего -->
      <div class="card" style="flex:1;min-width:140px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div class="kpi label" style="font-size:12px;color:var(--ink-mute)">Долг всего</div>
        <div class="kpi value" style="font-size:18px;font-weight:700;white-space:nowrap;color:<?= $debtors['total'] > 0 ? 'var(--bad)' : 'var(--good)' ?>">
          <?= number_format($debtors['total'], 0, '.', ' ') ?> ₽
        </div>
      </div>
      <!-- Сбор взносов -->
      <div class="card" style="flex:1;min-width:140px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div class="kpi label" style="font-size:12px;color:var(--ink-mute)">Сбор взносов</div>
        <div class="kpi value" style="font-size:22px;font-weight:700;color:<?= $collectionPct >= 80 ? 'var(--good)' : ($collectionPct >= 50 ? 'var(--warn)' : 'var(--bad)') ?>">
          <?= $collectionPct ?>%
        </div>
      </div>
      <!-- Предстоящих ОС -->
      <div class="card" style="flex:1;min-width:140px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div class="kpi label" style="font-size:12px;color:var(--ink-mute)">Предстоящих ОС</div>
        <div class="kpi value" style="font-size:22px;font-weight:700;color:var(--accent)">
          <?= (int)($stats['upcoming_meetings'] ?? 0) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick links -->
  <h3 style="margin-bottom:16px;color:var(--ink-soft);font-size:14px;text-transform:uppercase;letter-spacing:.08em">Разделы управления</h3>
  <div class="grid-3" style="margin-bottom:36px">
    <?php if (isAdmin()): ?>
    <a href="/admin/users" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:box-shadow .15s">
      <span style="font-size:28px">👥</span>
      <div>
        <div style="font-weight:600;color:var(--ink)">Пользователи</div>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">Управление участниками и ролями</div>
      </div>
    </a>
    <?php endif ?>
    <?php if (canFinance()): ?>
    <a href="/admin/finances" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:center;gap:14px">
      <span style="font-size:28px">💰</span>
      <div>
        <div style="font-weight:600;color:var(--ink)">Взносы и финансы</div>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">Типы взносов, сборы, расходы</div>
      </div>
    </a>
    <?php endif ?>
    <?php if (canMeetings()): ?>
    <a href="/admin/meetings" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:center;gap:14px">
      <span style="font-size:28px">📅</span>
      <div>
        <div style="font-weight:600;color:var(--ink)">Собрания</div>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">Создание и управление собраниями</div>
      </div>
    </a>
    <a href="/admin/votes" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:center;gap:14px">
      <span style="font-size:28px">🗳️</span>
      <div>
        <div style="font-weight:600;color:var(--ink)">Голосования</div>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">Создание и просмотр результатов</div>
      </div>
    </a>
    <?php endif ?>
    <?php if (isAdmin()): ?>
    <a href="/admin/moderation" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:center;gap:14px">
      <span style="font-size:28px">📋</span>
      <div>
        <div style="font-weight:600;color:var(--ink)">Модерация</div>
        <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">
          Заявки на вступление
          <?php if (!empty($pendingRegistrations)): ?>
            <span class="pill warn"><?= count($pendingRegistrations) ?></span>
          <?php endif ?>
        </div>
      </div>
    </a>
    <?php endif ?>
  </div>

  <!-- Recent announcements -->
  <?php if (!empty($announcements)): ?>
  <h3 style="margin-bottom:16px;color:var(--ink)">Последние объявления</h3>
  <?php
    $annCatColor = ['sale'=>'var(--good)','buy'=>'var(--warn)','wanted'=>'var(--bad)','service'=>'var(--accent)','info'=>'var(--ink-mute)'];
    $annCatLabel = ['sale'=>'Продаю','buy'=>'Куплю','wanted'=>'Разыскиваю','service'=>'Услуги','info'=>'Информация'];
    $annCatPill  = ['sale'=>'good','buy'=>'warn','wanted'=>'bad','service'=>'','info'=>''];
  ?>
  <div class="card" style="padding:0;overflow:hidden;margin-bottom:32px">
    <?php foreach (array_slice($announcements, 0, 5) as $i => $ann):
      $cat   = $ann['category'] ?? 'info';
      $color = $annCatColor[$cat] ?? 'var(--ink-mute)';
      $label = $annCatLabel[$cat] ?? $cat;
      $pill  = $annCatPill[$cat]  ?? '';
      $plot  = plotFromAddress($ann['author_address'] ?? '');
    ?>
      <div style="padding:14px 20px;display:flex;align-items:center;gap:14px;border-left:3px solid <?= $color ?>;<?= $i > 0 ? 'border-top:1px solid var(--line-soft)' : '' ?>">
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($ann['title']) ?></div>
          <div style="font-size:12px;color:var(--ink-mute);margin-top:2px">
            <?= e($ann['author_name'] ?? '—') ?>
            <?= $plot ? ' · Участок №' . e($plot) : '' ?>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
          <span class="pill <?= $pill ?>" style="font-size:11px"><?= $label ?></span>
          <span style="font-size:11px;color:var(--ink-mute)"><?= date('d.m.Y', strtotime($ann['created_at'])) ?></span>
        </div>
      </div>
    <?php endforeach ?>
    <div style="padding:10px 20px;border-top:1px solid var(--line-soft);text-align:right">
      <a href="/classifieds" style="font-size:13px;color:var(--accent-deep)">Все объявления →</a>
    </div>
  </div>
  <?php endif ?>

  <!-- Upcoming meetings -->
  <h3 style="margin-bottom:16px;color:var(--ink)">Предстоящие собрания</h3>
  <?php if (empty($meetings)): ?>
    <div class="card" style="padding:24px;color:var(--ink-mute);text-align:center">Нет запланированных собраний</div>
  <?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
      <?php foreach ($meetings as $i => $m): ?>
        <div style="padding:16px 24px;display:flex;align-items:center;gap:16px;<?= $i > 0 ? 'border-top:1px solid var(--border,#e5e7eb)' : '' ?>">
          <div style="min-width:80px;text-align:center;background:var(--accent-soft,#eff6ff);border-radius:8px;padding:8px 4px">
            <div style="font-size:22px;font-weight:700;color:var(--accent)"><?= htmlspecialchars(date('d', strtotime($m['meeting_date']))) ?></div>
            <div style="font-size:11px;color:var(--ink-soft);text-transform:uppercase"><?= htmlspecialchars(date('M', strtotime($m['meeting_date']))) ?></div>
          </div>
          <div>
            <div style="font-weight:600;color:var(--ink)"><?= htmlspecialchars($m['topic']) ?></div>
            <?php if (!empty($m['location'])): ?>
              <div style="font-size:13px;color:var(--ink-mute);margin-top:2px">📍 <?= htmlspecialchars($m['location']) ?></div>
            <?php endif ?>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
