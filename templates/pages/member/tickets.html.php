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

  <?php if ($ticket): /* ───── Просмотр переписки ───── */ ?>
    <a href="/tickets" class="btn sm ghost" style="margin-bottom:16px">← К списку обращений</a>

    <div class="card panel" style="margin-bottom:18px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0"><?= e($ticket['subject']) ?></h2>
        <span class="pill <?= $statusPill[$ticket['status']] ?? 'mute' ?>"><?= e(Ticket::STATUS_LABELS[$ticket['status']] ?? $ticket['status']) ?></span>
      </div>
      <div style="font-size:11px;color:var(--ink-mute);margin-top:6px">Обращение № <?= (int)$ticket['id'] ?> · от <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></div>
      <div style="margin-top:12px;color:var(--ink-soft);white-space:pre-wrap"><?= e($ticket['body']) ?></div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px">
      <?php foreach ($replies as $r):
        $isBoard = in_array((int)$r['author_role'], [1, 2, 3, 4], true);
      ?>
        <div class="card" style="padding:14px 18px;<?= $isBoard ? 'border-left:3px solid var(--accent)' : '' ?>">
          <div style="font-weight:600;color:var(--ink);font-size:13px">
            <?= $isBoard ? '🛡 Правление' : e($r['author_name'] ?? 'Член СНТ') ?>
            <span style="font-weight:400;color:var(--ink-mute);font-size:11px">· <?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></span>
          </div>
          <div style="margin-top:6px;color:var(--ink-soft);white-space:pre-wrap"><?= e($r['body']) ?></div>
        </div>
      <?php endforeach ?>
      <?php if (empty($replies)): ?>
        <div style="font-size:13px;color:var(--ink-mute)">Ответов пока нет — правление скоро рассмотрит обращение.</div>
      <?php endif ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
      <form method="POST" action="/tickets" class="card panel">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
        <label style="font-weight:600;display:block;margin-bottom:8px">Добавить сообщение</label>
        <textarea name="body" rows="3" required style="width:100%" placeholder="Уточнение или дополнение…"></textarea>
        <button type="submit" class="btn" style="margin-top:10px">Отправить</button>
      </form>
    <?php else: ?>
      <div class="card panel" style="text-align:center;color:var(--ink-mute)">Обращение закрыто.</div>
    <?php endif ?>

  <?php else: /* ───── Список + форма создания ───── */ ?>
    <h2 style="margin-bottom:8px">Обращения в правление</h2>
    <p style="color:var(--ink-mute);margin-bottom:20px;font-size:14px">Задайте вопрос правлению или сообщите о проблеме. Ответ придёт уведомлением 🔔.</p>

    <form method="POST" action="/tickets" class="card panel" style="margin-bottom:24px">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <input type="hidden" name="action" value="create">
      <label style="font-weight:600;display:block;margin-bottom:6px">Тема</label>
      <input type="text" name="subject" maxlength="200" required style="width:100%;margin-bottom:12px" placeholder="Коротко о сути обращения">
      <label style="font-weight:600;display:block;margin-bottom:6px">Сообщение</label>
      <textarea name="body" rows="4" required style="width:100%" placeholder="Опишите вопрос подробно…"></textarea>
      <button type="submit" class="btn" style="margin-top:12px">Отправить обращение</button>
    </form>

    <h3 style="margin-bottom:12px">Мои обращения</h3>
    <?php if (empty($tickets)): ?>
      <div class="card panel" style="text-align:center;color:var(--ink-mute)">У вас пока нет обращений.</div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($tickets as $t): ?>
          <a href="/tickets?id=<?= (int)$t['id'] ?>" class="card" style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;text-decoration:none">
            <div style="min-width:0">
              <div style="font-weight:600;color:var(--ink)"><?= e($t['subject']) ?></div>
              <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">№ <?= (int)$t['id'] ?> · обновлено <?= date('d.m.Y H:i', strtotime($t['updated_at'])) ?></div>
            </div>
            <span class="pill <?= $statusPill[$t['status']] ?? 'mute' ?>" style="flex-shrink:0"><?= e(Ticket::STATUS_LABELS[$t['status']] ?? $t['status']) ?></span>
          </a>
        <?php endforeach ?>
      </div>
    <?php endif ?>

  <?php endif ?>
</div>
