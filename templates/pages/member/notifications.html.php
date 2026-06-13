<?php
$icon = ['meeting' => '📅', 'vote' => '🗳️', 'reminder' => '🔔', 'info' => '📢'];
?>
<div class="fade-up">
  <h2 style="margin-bottom:24px">Уведомления</h2>

  <?php if (empty($items)): ?>
    <div class="card panel" style="text-align:center;color:var(--ink-mute)">Уведомлений пока нет</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($items as $n):
        $isUnread = (int)$n['is_read'] === 0;
      ?>
        <div class="card" style="padding:14px 18px;display:flex;gap:14px;align-items:flex-start;<?= $isUnread ? 'border-left:3px solid var(--accent)' : '' ?>">
          <span style="font-size:22px;flex-shrink:0"><?= $icon[$n['type']] ?? '📢' ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-weight:600;color:var(--ink)">
              <?= e($n['title']) ?>
              <?php if ($isUnread): ?> <span class="pill warn" style="font-size:10px">новое</span><?php endif ?>
            </div>
            <?php if (!empty($n['body'])): ?>
              <div style="font-size:13px;color:var(--ink-soft);margin-top:3px"><?= e($n['body']) ?></div>
            <?php endif ?>
            <div style="font-size:11px;color:var(--ink-mute);margin-top:4px">
              <?= date('d.m.Y H:i', strtotime($n['created_at'])) ?>
              <?php if (!empty($n['link'])): ?> · <a href="<?= e($n['link']) ?>" style="color:var(--accent-deep)">Перейти →</a><?php endif ?>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
