<?php
require_once BASE_PATH . '/src/helpers/csrf.php';

// Карта категорий: ярлык, CSS-класс пилюли, акцентный цвет
$catMap = [
    'sale'    => ['label' => 'Продаю',      'pill' => 'good',   'color' => 'var(--good)'],
    'buy'     => ['label' => 'Куплю',        'pill' => 'warn',   'color' => 'var(--warn)'],
    'wanted'  => ['label' => 'Разыскиваю',  'pill' => 'bad',    'color' => 'var(--bad)'],
    'service' => ['label' => 'Услуги',       'pill' => '',       'color' => 'var(--accent)'],
    'info'    => ['label' => 'Информация',   'pill' => '',       'color' => 'var(--ink-mute)'],
];

// Initials helper (inline function)
$initials = function(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = mb_strtoupper(mb_substr($parts[0] ?? 'У', 0, 1));
    if (!empty($parts[1])) $out .= mb_strtoupper(mb_substr($parts[1], 0, 1));
    return $out;
};
?>
<div class="fade-up">

  <!-- Заголовок + кнопка -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px">
    <div>
      <h2 style="margin:0">Доска объявлений</h2>
      <p style="margin:4px 0 0;font-size:13px;color:var(--ink-mute)">Объявления участников СНТ «Берёзка»</p>
    </div>
    <button class="btn primary" onclick="
      var f=document.getElementById('ann-form');
      f.style.display = f.style.display==='none' ? 'block' : 'none';
      this.textContent = f.style.display==='none' ? '+ Создать объявление' : '✕ Закрыть';
    ">+ Создать объявление</button>
  </div>

  <!-- Flash -->
  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- Форма создания -->
  <div id="ann-form" style="display:none;margin-bottom:32px">
    <div class="card" style="padding:28px">
      <h3 style="margin:0 0 20px;font-size:18px">Новое объявление</h3>
      <form method="POST" action="/classifieds" style="display:flex;flex-direction:column;gap:18px">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">

        <!-- Выбор категории — пилюли -->
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--ink-soft);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em">Категория</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap" id="cat-pills">
            <?php foreach ($catMap as $catKey => $catDef): ?>
              <label style="cursor:pointer">
                <input type="radio" name="category" value="<?= $catKey ?>" style="display:none"
                  <?= $catKey === 'info' ? 'checked' : '' ?>
                  onchange="
                    document.querySelectorAll('.cat-pill').forEach(el=>el.classList.remove('cat-active'));
                    this.closest('label').querySelector('.cat-pill').classList.add('cat-active');
                  ">
                <span class="pill cat-pill <?= $catDef['pill'] ?> <?= $catKey === 'info' ? 'cat-active' : '' ?>"
                      style="cursor:pointer;transition:opacity .15s;font-size:13px;padding:6px 14px">
                  <?= $catDef['label'] ?>
                </span>
              </label>
            <?php endforeach ?>
          </div>
          <style>
            .cat-pill { opacity:.55; }
            .cat-pill.cat-active { opacity:1; outline:2px solid currentColor; outline-offset:2px; }
          </style>
        </div>

        <div class="field">
          <label>Заголовок <span style="color:var(--bad)">*</span></label>
          <input type="text" name="title" required maxlength="255" placeholder="Кратко: что предлагаете или ищете">
        </div>

        <div class="field">
          <label>Описание <span style="color:var(--bad)">*</span></label>
          <textarea name="content" required rows="5" maxlength="2000" id="ann-content"
            placeholder="Подробности, цена, контакт..."
            oninput="document.getElementById('ann-chars').textContent=this.value.length"></textarea>
          <div style="font-size:11px;color:var(--ink-mute);text-align:right;margin-top:4px">
            <span id="ann-chars">0</span> / 2000
          </div>
        </div>

        <div style="display:flex;gap:10px">
          <button type="submit" class="btn primary">Опубликовать</button>
          <button type="button" class="btn ghost"
            onclick="document.getElementById('ann-form').style.display='none';
                     document.querySelector('button[onclick*=ann-form]').textContent='+ Создать объявление'">
            Отмена
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Список объявлений -->
  <?php if (empty($announcements)): ?>
    <div class="card" style="padding:56px 32px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">📋</div>
      <h3 style="color:var(--ink-soft);margin-bottom:8px">Объявлений пока нет</h3>
      <p style="color:var(--ink-mute)">Станьте первым — опубликуйте объявление</p>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px">
      <?php foreach ($announcements as $ann):
        $cat   = $ann['category'] ?? 'info';
        $cm    = $catMap[$cat] ?? $catMap['info'];
        $plot  = plotFromAddress($ann['author_address'] ?? '');
        $ini   = $initials($ann['author_name'] ?? 'Участник');
        $views = (int)($ann['views_count'] ?? 0);

        // Превью текста — 160 символов
        $preview = $ann['content'] ?? '';
        if (mb_strlen($preview) > 160) $preview = mb_substr($preview, 0, 160) . '…';

        // Дата
        $dateStr = !empty($ann['created_at']) ? date('d.m.Y', strtotime($ann['created_at'])) : '';

        // Закреплено?
        $pinned = !empty($ann['is_pinned']);
      ?>
        <div class="card" style="overflow:hidden;border-left:4px solid <?= $cm['color'] ?>">
          <div style="padding:18px 20px 14px">

            <!-- Верхняя строка: заголовок + дата -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:10px">
              <div style="flex:1;min-width:0">
                <?php if ($pinned): ?>
                  <span style="font-size:11px;color:var(--accent);font-weight:700;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:4px">📌 Закреплено</span>
                <?php endif ?>
                <div style="font-weight:700;font-size:15px;color:var(--ink);overflow-wrap:break-word;line-height:1.35">
                  <?= e($ann['title']) ?>
                </div>
              </div>
              <div style="flex-shrink:0;text-align:right">
                <span class="pill <?= $cm['pill'] ?>" style="font-size:12px"><?= $cm['label'] ?></span>
                <div style="font-size:11px;color:var(--ink-mute);margin-top:5px;white-space:nowrap"><?= $dateStr ?></div>
              </div>
            </div>

            <!-- Превью текста -->
            <div style="color:var(--ink-soft);font-size:13px;line-height:1.65;margin-bottom:14px">
              <?= nl2br(e($preview)) ?>
            </div>

            <!-- Футер: автор + участок + просмотры -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:12px;border-top:1px solid var(--line-soft)">
              <div style="display:flex;align-items:center;gap:9px;min-width:0">
                <!-- Аватар -->
                <div style="width:30px;height:30px;border-radius:50%;background:<?= $cm['color'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:11px;color:#fff;letter-spacing:.02em">
                  <?= e($ini) ?>
                </div>
                <div style="min-width:0">
                  <div style="font-size:13px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= e($ann['author_name'] ?? 'Участник') ?>
                  </div>
                  <?php if ($plot): ?>
                    <div style="font-size:11px;color:var(--ink-mute)">Участок №<?= e($plot) ?></div>
                  <?php else: ?>
                    <div style="font-size:11px;color:var(--ink-mute)">Правление</div>
                  <?php endif ?>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
                <?php if ($views > 0): ?>
                  <div style="font-size:11px;color:var(--ink-mute)">👁 <?= $views ?></div>
                <?php endif ?>
                <?php if ((int)$ann['author_id'] === (int)($_SESSION['user']['id'] ?? 0)): ?>
                  <form method="post" action="/classifieds/delete" style="margin:0"
                        onsubmit="return confirm('Удалить объявление «<?= e(addslashes($ann['title'])) ?>»?')">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                    <button type="submit" class="btn sm" style="background:var(--bad);color:#fff;border:none;padding:4px 10px;font-size:11px;border-radius:6px;cursor:pointer">Удалить</button>
                  </form>
                <?php endif ?>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach ?>
    </div>

    <!-- Пагинация -->
    <?php if (($totalPages ?? 1) > 1): ?>
      <div style="display:flex;justify-content:center;align-items:center;gap:8px;margin-top:28px;flex-wrap:wrap">
        <?php if ($page > 1): ?>
          <a class="btn sm ghost" href="?page=<?= $page - 1 ?>">← Назад</a>
        <?php endif ?>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
          <a class="btn sm <?= $p === $page ? 'primary' : 'ghost' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
        <?php endfor ?>
        <?php if ($page < $totalPages): ?>
          <a class="btn sm ghost" href="?page=<?= $page + 1 ?>">Вперёд →</a>
        <?php endif ?>
      </div>
    <?php endif ?>
  <?php endif ?>
</div>
