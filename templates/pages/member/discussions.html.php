<?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
<div class="fade-up">
  <h2 style="margin-bottom:24px">Обсуждения</h2>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- Форма нового сообщения -->
  <div class="card" style="margin-bottom:28px;padding:24px">
    <h3 style="margin:0 0 16px">Новое сообщение</h3>
    <form method="POST" action="/discussions" style="display:flex;flex-direction:column;gap:16px">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <div class="field">
        <label for="post-title">Тема</label>
        <input id="post-title" type="text" name="title" required placeholder="Тема обсуждения">
      </div>
      <div class="field">
        <label for="post-content">Сообщение</label>
        <textarea id="post-content" name="content" rows="4" required placeholder="Напишите сообщение..."></textarea>
      </div>
      <div>
        <button type="submit" class="btn primary">Опубликовать</button>
      </div>
    </form>
  </div>

  <!-- Список -->
  <?php if (empty($posts)): ?>
    <div style="text-align:center;padding:48px 0;color:var(--ink-mute)">Обсуждений пока нет. Начните первым!</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($posts as $post): ?>
        <div class="card" style="padding:20px">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px">
            <div style="font-weight:600;font-size:16px;flex:1;min-width:0;overflow-wrap:break-word">
              <?= e($post['title'] ?? 'Без темы') ?>
            </div>
            <span style="color:var(--ink-mute);font-size:13px;white-space:nowrap;flex-shrink:0">
              <?= !empty($post['created_at']) ? date('d.m.Y H:i', strtotime($post['created_at'])) : '' ?>
            </span>
          </div>
          <div style="font-size:13px;color:var(--ink-soft);margin-bottom:10px">
            <?= e($post['author_name'] ?? 'Участник') ?>
            <?php if (!empty($post['plot_number'])): ?>
              &nbsp;&middot;&nbsp;Участок №<?= e($post['plot_number']) ?>
            <?php endif ?>
          </div>
          <div style="color:var(--ink);line-height:1.7"><?= nl2br(e($post['content'] ?? '')) ?></div>
          <?php if (!empty($post['replies_count']) && $post['replies_count'] > 0): ?>
            <div style="margin-top:12px;font-size:13px;color:var(--ink-mute)">
              Ответов: <?= (int)$post['replies_count'] ?>
            </div>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
