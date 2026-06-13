<?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
<div class="fade-up">
  <h2 style="margin-bottom:24px">Собрания</h2>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px">
      <?= e($flash['msg']) ?>
    </div>
  <?php endif ?>

  <!-- Форма создания -->
  <div class="card panel" style="max-width:540px;margin-bottom:32px">
    <h3 style="margin:0 0 20px">Запланировать собрание</h3>
    <form method="POST" action="/admin/meetings" style="display:flex;flex-direction:column;gap:16px">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <div class="field">
        <label>Дата проведения</label>
        <input type="date" name="meeting_date" required>
      </div>
      <div class="field">
        <label>Тема / повестка</label>
        <input type="text" name="topic" required placeholder="Годовое общее собрание">
      </div>
      <div class="field">
        <label>Место проведения</label>
        <input type="text" name="location" placeholder="Правление СНТ">
      </div>
      <div>
        <button type="submit" class="btn primary">Добавить</button>
      </div>
    </form>
  </div>

  <!-- Список -->
  <h3 style="margin-bottom:16px">Все собрания</h3>
  <?php if (empty($meetings)): ?>
    <div class="card panel" style="text-align:center;color:var(--ink-mute)">Нет запланированных собраний</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($meetings as $m): ?>
        <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
          <div style="display:flex;align-items:center;gap:16px">
            <div style="min-width:60px;text-align:center;background:var(--accent-soft);border-radius:10px;padding:8px 6px;flex-shrink:0">
              <div style="font-family:'Fraunces',serif;font-size:22px;color:var(--accent-deep)"><?= date('d', strtotime($m['meeting_date'])) ?></div>
              <div style="font-size:11px;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.05em"><?= date('M Y', strtotime($m['meeting_date'])) ?></div>
            </div>
            <div>
              <div style="font-weight:600;color:var(--ink)"><?= e($m['topic']) ?></div>
              <?php if (!empty($m['location'])): ?>
                <div style="font-size:13px;color:var(--ink-mute);margin-top:3px">📍 <?= e($m['location']) ?></div>
              <?php endif ?>
            </div>
          </div>
          <form method="POST" action="/admin/meetings"
            onsubmit="return confirm('Удалить собрание «<?= e(addslashes($m['topic'])) ?>»?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <button type="submit" class="btn sm danger">Удалить</button>
          </form>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
