<?php
require_once BASE_PATH . '/src/helpers/csrf.php';
use models\Ticket;

$statusPill = [
    'new'         => 'warn',
    'in_progress' => 'dot',
    'answered'    => 'good',
    'closed'      => '',
];
?>
<div class="fade-up">

  <?php if ($ticket): /* ───── Просмотр и работа с обращением ───── */ ?>
    <a href="/admin/tickets" class="btn sm ghost" style="margin-bottom:16px">← Ко всем обращениям</a>

    <div class="card panel" style="margin-bottom:18px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0"><?= e($ticket['subject']) ?></h2>
        <span class="pill <?= $statusPill[$ticket['status']] ?? '' ?>"><?= e(Ticket::STATUS_LABELS[$ticket['status']] ?? $ticket['status']) ?></span>
      </div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:6px">
        Обращение № <?= (int)$ticket['id'] ?> · автор: <strong><?= e($ticket['author_name'] ?? 'Член СНТ') ?></strong> · <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
      </div>
      <div style="margin-top:12px;color:var(--ink-soft);white-space:pre-wrap"><?= e($ticket['body']) ?></div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px">
      <?php foreach ($replies as $r):
        $isBoard = in_array((int)$r['author_role'], [1, 2, 3, 4], true);
      ?>
        <div class="card" style="padding:14px 18px;<?= $isBoard ? 'border-left:3px solid var(--accent)' : '' ?>">
          <div style="font-weight:600;color:var(--ink);font-size:13px">
            <?= $isBoard ? '🛡 Правление (' . e($r['author_name'] ?? '') . ')' : e($r['author_name'] ?? 'Член СНТ') ?>
            <span style="font-weight:400;color:var(--ink-mute);font-size:11px">· <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></span>
          </div>
          <div style="margin-top:6px;color:var(--ink-soft);white-space:pre-wrap"><?= e($r['body']) ?></div>
        </div>
      <?php endforeach ?>
      <?php if (empty($replies)): ?>
        <div style="font-size:13px;color:var(--ink-mute)">Ответов ещё нет.</div>
      <?php endif ?>
    </div>

    <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-start">
      <form method="POST" action="/admin/tickets" class="card panel" style="flex:1;min-width:280px">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <label style="font-weight:600;display:block;margin-bottom:8px">Ответ члену</label>
        <textarea name="body" rows="3" required style="width:100%" placeholder="Текст ответа…"></textarea>
        <button type="submit" class="btn" style="margin-top:10px">Ответить и уведомить</button>
      </form>

      <form method="POST" action="/admin/tickets" class="card panel" style="min-width:220px">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <label style="font-weight:600;display:block;margin-bottom:8px">Статус</label>
        <select name="status" style="width:100%">
          <?php foreach (Ticket::STATUS_LABELS as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= $ticket['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach ?>
        </select>
        <button type="submit" class="btn ghost" style="margin-top:10px">Сменить статус</button>
      </form>
    </div>

  <?php else: /* ───── Список всех обращений ───── */ ?>
    <h2 style="margin-bottom:16px">Обращения в правление</h2>

    <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
      <a href="/admin/tickets" class="btn sm <?= $filter === '' ? '' : 'ghost' ?>">Все</a>
      <?php foreach (Ticket::STATUS_LABELS as $val => $label): ?>
        <a href="/admin/tickets?status=<?= e($val) ?>" class="btn sm <?= $filter === $val ? '' : 'ghost' ?>"><?= e($label) ?></a>
      <?php endforeach ?>
    </div>

    <?php if (empty($tickets)): ?>
      <div class="card panel" style="text-align:center;color:var(--ink-mute)">Обращений нет.</div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($tickets as $t): ?>
          <a href="/admin/tickets?id=<?= (int)$t['id'] ?>" class="card" style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;text-decoration:none">
            <div style="min-width:0">
              <div style="font-weight:600;color:var(--ink)"><?= e($t['subject']) ?></div>
              <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">№ <?= (int)$t['id'] ?> · <?= e($t['author_name'] ?? 'Член СНТ') ?> · обновлено <?= date('d.m.Y H:i', strtotime($t['updated_at'])) ?></div>
            </div>
            <span class="pill <?= $statusPill[$t['status']] ?? '' ?>" style="flex-shrink:0"><?= e(Ticket::STATUS_LABELS[$t['status']] ?? $t['status']) ?></span>
          </a>
        <?php endforeach ?>
      </div>
    <?php endif ?>

  <?php endif ?>
</div>
