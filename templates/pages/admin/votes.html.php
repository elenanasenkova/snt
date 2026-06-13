<?php /** @var array $votes @var array|null $flash */ ?>

<div class="fade-up">
  <h2 style="margin-bottom:24px;color:var(--ink)">Голосования</h2>

  <?php if (!empty($flash)): ?>
    <div class="card" style="padding:12px 20px;margin-bottom:20px;background:<?= $flash['type'] === 'success' ? 'var(--good-soft,#f0fdf4)' : 'var(--bad-soft,#fef2f2)' ?>;border:1px solid <?= $flash['type'] === 'success' ? '#22c55e' : '#ef4444' ?>">
      <span style="color:<?= $flash['type'] === 'success' ? '#15803d' : '#b91c1c' ?>;font-weight:500">
        <?= e($flash['msg']) ?>
      </span>
    </div>
  <?php endif ?>

  <!-- Create vote form -->
  <div class="card" style="padding:28px;max-width:600px;margin-bottom:36px">
    <h3 style="margin:0 0 20px;color:var(--ink);font-size:16px">Создать голосование</h3>
    <form method="POST" action="/admin/votes">
      <?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <div class="field" style="margin-bottom:16px">
        <label style="display:block;font-size:13px;color:var(--ink-soft);margin-bottom:6px;font-weight:500">Заголовок</label>
        <input type="text" name="title" required placeholder="Утверждение сметы на 2026 год"
          style="width:100%;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:10px 12px;font-size:14px;color:var(--ink);background:var(--paper,#fff);box-sizing:border-box">
      </div>
      <div class="field" style="margin-bottom:16px">
        <label style="display:block;font-size:13px;color:var(--ink-soft);margin-bottom:6px;font-weight:500">Описание</label>
        <textarea name="description" rows="3" placeholder="Подробное описание вопроса голосования..."
          style="width:100%;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:10px 12px;font-size:14px;color:var(--ink);background:var(--paper,#fff);resize:vertical;box-sizing:border-box"></textarea>
      </div>
      <div class="field" style="margin-bottom:24px">
        <label style="display:block;font-size:13px;color:var(--ink-soft);margin-bottom:6px;font-weight:500">Варианты ответов <span style="color:var(--ink-mute);font-weight:400">(по одному на строку)</span></label>
        <textarea name="options" rows="4" required placeholder="За&#10;Против&#10;Воздержался"
          style="width:100%;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:10px 12px;font-size:14px;color:var(--ink);background:var(--paper,#fff);resize:vertical;box-sizing:border-box;font-family:inherit"></textarea>
        <div style="font-size:12px;color:var(--ink-mute);margin-top:6px">Минимум 2 варианта</div>
      </div>
      <button type="submit" class="btn primary">Создать голосование</button>
    </form>
  </div>

  <!-- All votes list -->
  <h3 style="margin-bottom:16px;color:var(--ink)">Все голосования</h3>
  <?php if (empty($votes)): ?>
    <div class="card" style="padding:32px;text-align:center;color:var(--ink-mute)">Нет голосований</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($votes as $v): ?>
        <?php
          $options    = $v['options'] ?? [];
          $totalVotes = array_sum(array_column($options, 'vote_count'));
          $isActive   = ($v['status'] ?? '') === 'active';
        ?>
        <div class="card" style="padding:24px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px">
            <div>
              <div style="font-weight:600;color:var(--ink);font-size:16px"><?= htmlspecialchars($v['title']) ?></div>
              <?php if (!empty($v['description'])): ?>
                <div style="font-size:13px;color:var(--ink-mute);margin-top:4px"><?= htmlspecialchars($v['description']) ?></div>
              <?php endif ?>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
              <?php if ($isActive): ?>
                <span class="pill good">Активно</span>
              <?php else: ?>
                <span class="pill bad">Закрыто</span>
              <?php endif ?>
            </div>
          </div>

          <?php if (!empty($options)): ?>
            <div style="display:flex;flex-direction:column;gap:10px">
              <?php foreach ($options as $opt): ?>
                <?php
                  $count = (int)($opt['vote_count'] ?? 0);
                  $pct   = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
                ?>
                <div>
                  <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:14px;color:var(--ink)"><?= htmlspecialchars($opt['text'] ?? $opt['option_text'] ?? '') ?></span>
                    <span style="font-size:13px;color:var(--ink-soft)" class="mono"><?= $count ?> голос<?= $count === 1 ? '' : ($count >= 2 && $count <= 4 ? 'а' : 'ов') ?> (<?= $pct ?>%)</span>
                  </div>
                  <div style="background:var(--bg-warm,#f3f4f6);border-radius:999px;height:8px;overflow:hidden">
                    <div style="width:<?= $pct ?>%;height:100%;border-radius:999px;background:var(--accent);transition:width .3s"></div>
                  </div>
                </div>
              <?php endforeach ?>
            </div>
            <div style="margin-top:12px;font-size:13px;color:var(--ink-mute)">Всего голосов: <strong><?= $totalVotes ?></strong></div>
          <?php else: ?>
            <div style="font-size:13px;color:var(--ink-mute)">Ещё нет голосов</div>
          <?php endif ?>

          <!-- Action buttons -->
          <div style="display:flex;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border,#e5e7eb)">
            <a href="/admin/votes/export?id=<?= (int)$v['id'] ?>" class="btn sm ghost">⬇ Выгрузить итоги</a>
            <?php if ($isActive): ?>
              <form method="POST" action="/admin/votes/close" style="display:inline">
                <?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                <button type="submit" class="btn sm ghost">Закрыть</button>
              </form>
            <?php endif ?>
            <form method="POST" action="/admin/votes/delete" style="display:inline"
                  onsubmit="return confirm('Удалить голосование «<?= htmlspecialchars(addslashes($v['title']), ENT_QUOTES) ?>»? Это действие необратимо.')">
              <?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
              <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
              <button type="submit" class="btn sm danger">Удалить</button>
            </form>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
