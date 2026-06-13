<?php
require_once BASE_PATH . '/src/helpers/csrf.php';

$userVotes = $userVotes ?? [];
$canManage = $canManage ?? false;
$tab       = in_array($tab ?? 'all', ['all','announcements','events','votes'], true) ? $tab : 'all';

// Категории объявлений
$catMap = [
    'sale'    => ['label' => 'Продаю',     'pill' => 'good', 'color' => 'var(--good)'],
    'buy'     => ['label' => 'Куплю',       'pill' => 'warn', 'color' => 'var(--warn)'],
    'wanted'  => ['label' => 'Разыскиваю',  'pill' => 'bad',  'color' => 'var(--bad)'],
    'service' => ['label' => 'Услуги',      'pill' => '',     'color' => 'var(--accent)'],
    'info'    => ['label' => 'Информация',  'pill' => '',     'color' => 'var(--ink-mute)'],
];
$initials = function (string $name): string {
    $p = preg_split('/\s+/', trim($name));
    $o = mb_strtoupper(mb_substr($p[0] ?? 'У', 0, 1));
    if (!empty($p[1])) $o .= mb_strtoupper(mb_substr($p[1], 0, 1));
    return $o;
};

$cntA = count($announcements ?? []);
$cntE = count($events ?? []);
$cntV = count($votes ?? []);
?>
<div class="fade-up">

  <!-- Шапка -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px">
    <div>
      <h2 style="margin:0">📣 Лента сообщества</h2>
      <p style="margin:4px 0 0;font-size:13px;color:var(--ink-mute)">Объявления, события и голосования СНТ «Берёзка» — в одном месте</p>
    </div>
    <button class="btn primary" onclick="toggleFeedForm(this)">+ Создать</button>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:18px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- ====== ЕДИНАЯ ФОРМА СОЗДАНИЯ ====== -->
  <div id="feed-form" style="display:none;margin-bottom:26px">
    <div class="card" style="padding:26px">
      <h3 style="margin:0 0 18px;font-size:18px">Новая запись</h3>

      <!-- Выбор типа -->
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        <button type="button" class="btn sm type-btn type-active" data-type="announcement" onclick="selectType('announcement')">📋 Объявление</button>
        <?php if ($canManage): ?>
          <button type="button" class="btn sm ghost type-btn" data-type="event" onclick="selectType('event')">📅 Событие</button>
          <button type="button" class="btn sm ghost type-btn" data-type="vote" onclick="selectType('vote')">🗳 Голосование</button>
        <?php endif ?>
      </div>

      <form method="POST" action="/feed" style="display:flex;flex-direction:column;gap:16px">
        <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
        <input type="hidden" name="action" id="feed-action" value="create_announcement">

        <!-- ── Поля: Объявление ── -->
        <div class="type-fields" data-type="announcement">
          <div style="margin-bottom:14px">
            <div style="font-size:13px;font-weight:600;color:var(--ink-soft);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em">Категория</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <?php foreach ($catMap as $k => $c): ?>
                <label style="cursor:pointer">
                  <input type="radio" name="category" value="<?= $k ?>" style="display:none" <?= $k === 'info' ? 'checked' : '' ?>
                    onchange="document.querySelectorAll('.cat-pill').forEach(el=>el.classList.remove('cat-active'));this.closest('label').querySelector('.cat-pill').classList.add('cat-active');">
                  <span class="pill cat-pill <?= $c['pill'] ?> <?= $k === 'info' ? 'cat-active' : '' ?>" style="cursor:pointer;font-size:13px;padding:6px 14px"><?= $c['label'] ?></span>
                </label>
              <?php endforeach ?>
            </div>
          </div>
          <div class="field"><label>Заголовок <span style="color:var(--bad)">*</span></label>
            <input type="text" name="title" maxlength="255" placeholder="Кратко: что предлагаете или ищете"></div>
          <div class="field"><label>Описание <span style="color:var(--bad)">*</span></label>
            <textarea name="content" rows="4" maxlength="2000" placeholder="Подробности, цена, контакт..."></textarea></div>
        </div>

        <?php if ($canManage): ?>
        <!-- ── Поля: Событие ── -->
        <div class="type-fields" data-type="event" style="display:none">
          <div class="field"><label>Тема события <span style="color:var(--bad)">*</span></label>
            <input type="text" name="topic" maxlength="255" placeholder="Например: Общее собрание членов СНТ" disabled></div>
          <div style="display:flex;gap:14px;flex-wrap:wrap">
            <div class="field" style="flex:1;min-width:160px"><label>Дата <span style="color:var(--bad)">*</span></label>
              <input type="date" name="meeting_date" disabled></div>
            <div class="field" style="flex:2;min-width:200px"><label>Место</label>
              <input type="text" name="location" maxlength="255" placeholder="Правление / онлайн / участок" disabled></div>
          </div>
        </div>

        <!-- ── Поля: Голосование ── -->
        <div class="type-fields" data-type="vote" style="display:none">
          <div class="field"><label>Вопрос <span style="color:var(--bad)">*</span></label>
            <input type="text" name="title" maxlength="255" placeholder="Например: Утвердить смету на 2026 год?" disabled></div>
          <div class="field"><label>Пояснение</label>
            <textarea name="description" rows="2" maxlength="1000" placeholder="Необязательное описание" disabled></textarea></div>
          <div class="field"><label>Варианты ответа <span style="color:var(--bad)">*</span> <span style="font-weight:400;color:var(--ink-mute)">— по одному в строке, минимум два</span></label>
            <textarea name="options" rows="4" placeholder="За&#10;Против&#10;Воздержался" disabled></textarea></div>
        </div>
        <?php endif ?>

        <div style="display:flex;gap:10px">
          <button type="submit" class="btn primary" id="feed-submit">Опубликовать</button>
          <button type="button" class="btn ghost" onclick="toggleFeedForm(document.querySelector('.btn.primary[onclick*=toggleFeedForm]'))">Отмена</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ====== ФИЛЬТР-ЧИПЫ ====== -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px" id="feed-chips">
    <button type="button" class="btn sm chip" data-filter="all"           onclick="filterFeed('all')">Все</button>
    <button type="button" class="btn sm chip ghost" data-filter="announcements" onclick="filterFeed('announcements')">📋 Объявления <?= $cntA ? "($cntA)" : '' ?></button>
    <button type="button" class="btn sm chip ghost" data-filter="events"  onclick="filterFeed('events')">📅 События <?= $cntE ? "($cntE)" : '' ?></button>
    <button type="button" class="btn sm chip ghost" data-filter="votes"   onclick="filterFeed('votes')">🗳 Голосования <?= $cntV ? "($cntV)" : '' ?></button>
  </div>

  <?php if ($cntA + $cntE + $cntV === 0): ?>
    <div class="card" style="padding:56px 32px;text-align:center">
      <div style="font-size:48px;margin-bottom:16px">📭</div>
      <h3 style="color:var(--ink-soft);margin-bottom:8px">В ленте пока пусто</h3>
      <p style="color:var(--ink-mute)">Нажмите «+ Создать», чтобы добавить первую запись</p>
    </div>
  <?php endif ?>

  <!-- ====== СОБЫТИЯ ====== -->
  <section class="feed-section" data-section="events" <?= $cntE === 0 ? 'style=display:none' : '' ?>>
    <?php foreach (($events ?? []) as $ev):
      $isPast = !empty($ev['meeting_date']) && strtotime($ev['meeting_date']) < strtotime(date('Y-m-d'));
    ?>
      <div class="card" style="overflow:hidden;border-left:4px solid var(--accent);margin-bottom:14px;<?= $isPast ? 'opacity:.6' : '' ?>">
        <div style="padding:16px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
          <div style="text-align:center;flex-shrink:0;min-width:56px">
            <div style="font-size:22px;font-weight:800;color:var(--accent);line-height:1"><?= !empty($ev['meeting_date']) ? date('d', strtotime($ev['meeting_date'])) : '—' ?></div>
            <div style="font-size:11px;color:var(--ink-mute);text-transform:uppercase"><?= !empty($ev['meeting_date']) ? date('m.Y', strtotime($ev['meeting_date'])) : '' ?></div>
          </div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <span class="pill" style="font-size:11px">📅 Событие</span>
              <?php if ($isPast): ?><span class="pill" style="font-size:11px;opacity:.7">Прошло</span><?php endif ?>
            </div>
            <div style="font-weight:700;font-size:15px;color:var(--ink);margin-top:6px"><?= e($ev['topic'] ?? '') ?></div>
            <?php if (!empty($ev['location'])): ?>
              <div style="font-size:12px;color:var(--ink-mute);margin-top:3px">📍 <?= e($ev['location']) ?></div>
            <?php endif ?>
          </div>
          <?php if ($canManage): ?>
            <form method="POST" action="/feed" style="margin:0" onsubmit="return confirm('Удалить событие?')">
              <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
              <input type="hidden" name="action" value="delete_event">
              <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
              <button type="submit" class="btn sm" style="background:var(--bad);color:#fff;border:none;font-size:11px;padding:4px 10px;border-radius:6px">Удалить</button>
            </form>
          <?php endif ?>
        </div>
      </div>
    <?php endforeach ?>
  </section>

  <!-- ====== ГОЛОСОВАНИЯ ====== -->
  <section class="feed-section" data-section="votes" <?= $cntV === 0 ? 'style=display:none' : '' ?>>
    <?php foreach (($votes ?? []) as $vote):
      $voteId   = (int)($vote['id'] ?? 0);
      $hasVoted = isset($userVotes[$voteId]);
      $options  = $vote['options'] ?? [];
      $total    = array_sum(array_column($options, 'vote_count'));
    ?>
      <div class="card" style="padding:22px;border-left:4px solid var(--warn);margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px">
          <div style="flex:1;min-width:0">
            <span class="pill warn" style="font-size:11px">🗳 Голосование</span>
            <h3 style="margin:8px 0 4px;font-size:16px"><?= e($vote['title']) ?></h3>
            <?php if (!empty($vote['description'])): ?>
              <p style="color:var(--ink-soft);margin:0;line-height:1.55;font-size:13px"><?= nl2br(e($vote['description'])) ?></p>
            <?php endif ?>
          </div>
          <?php if ($canManage): ?>
            <form method="POST" action="/feed" style="margin:0" onsubmit="return confirm('Закрыть голосование?')">
              <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
              <input type="hidden" name="action" value="close_vote">
              <input type="hidden" name="id" value="<?= $voteId ?>">
              <button type="submit" class="btn sm ghost" style="font-size:11px;padding:4px 10px">Закрыть</button>
            </form>
          <?php endif ?>
        </div>

        <?php if ($hasVoted): ?>
          <div style="font-size:12px;color:var(--ink-soft);margin-bottom:10px;font-weight:500">Вы проголосовали. Результаты:</div>
          <?php foreach ($options as $opt):
            $c = (int)($opt['vote_count'] ?? 0);
            $pct = $total > 0 ? round($c / $total * 100) : 0;
          ?>
            <div style="margin-bottom:10px">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px">
                <span><?= e($opt['option_text']) ?></span>
                <span class="mono" style="color:var(--ink-soft)"><?= $c ?> (<?= $pct ?>%)</span>
              </div>
              <div style="height:7px;background:var(--bg-warm);border-radius:4px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:var(--accent);border-radius:4px;transition:width .4s"></div>
              </div>
            </div>
          <?php endforeach ?>
          <div style="font-size:11px;color:var(--ink-mute);margin-top:6px">Всего голосов: <?= $total ?></div>
        <?php else: ?>
          <form method="POST" action="/feed">
            <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
            <input type="hidden" name="action" value="cast_vote">
            <input type="hidden" name="vote_id" value="<?= $voteId ?>">
            <div style="display:flex;flex-direction:column;gap:8px;margin:12px 0">
              <?php foreach ($options as $opt): ?>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:9px 13px;border-radius:8px;border:1px solid var(--line)"
                       onmouseover="this.style.background='var(--bg-warm)'" onmouseout="this.style.background=''">
                  <input type="radio" name="option_id" value="<?= (int)$opt['id'] ?>" required>
                  <span><?= e($opt['option_text']) ?></span>
                </label>
              <?php endforeach ?>
            </div>
            <button type="submit" class="btn primary sm">Проголосовать</button>
          </form>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </section>

  <!-- ====== ОБЪЯВЛЕНИЯ ====== -->
  <section class="feed-section" data-section="announcements" <?= $cntA === 0 ? 'style=display:none' : '' ?>>
    <?php foreach (($announcements ?? []) as $ann):
      $cat  = $ann['category'] ?? 'info';
      $cm   = $catMap[$cat] ?? $catMap['info'];
      $plot = plotFromAddress($ann['author_address'] ?? '');
      $ini  = $initials($ann['author_name'] ?? 'Участник');
      $prev = $ann['content'] ?? '';
      if (mb_strlen($prev) > 160) $prev = mb_substr($prev, 0, 160) . '…';
      $dateStr = !empty($ann['created_at']) ? date('d.m.Y', strtotime($ann['created_at'])) : '';
    ?>
      <div class="card" style="overflow:hidden;border-left:4px solid <?= $cm['color'] ?>;margin-bottom:14px">
        <div style="padding:16px 20px 12px">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:8px">
            <div style="flex:1;min-width:0">
              <?php if (!empty($ann['is_pinned'])): ?>
                <span style="font-size:11px;color:var(--accent);font-weight:700;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:4px">📌 Закреплено</span>
              <?php endif ?>
              <div style="font-weight:700;font-size:15px;color:var(--ink);overflow-wrap:break-word;line-height:1.35"><?= e($ann['title']) ?></div>
            </div>
            <div style="flex-shrink:0;text-align:right">
              <span class="pill <?= $cm['pill'] ?>" style="font-size:12px"><?= $cm['label'] ?></span>
              <div style="font-size:11px;color:var(--ink-mute);margin-top:5px;white-space:nowrap"><?= $dateStr ?></div>
            </div>
          </div>
          <div style="color:var(--ink-soft);font-size:13px;line-height:1.6;margin-bottom:12px"><?= nl2br(e($prev)) ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:10px;border-top:1px solid var(--line-soft)">
            <div style="display:flex;align-items:center;gap:9px;min-width:0">
              <div style="width:28px;height:28px;border-radius:50%;background:<?= $cm['color'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:11px;color:#fff"><?= e($ini) ?></div>
              <div style="min-width:0">
                <div style="font-size:13px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($ann['author_name'] ?? 'Участник') ?></div>
                <div style="font-size:11px;color:var(--ink-mute)"><?= $plot ? 'Участок №' . e($plot) : 'Правление' ?></div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-shrink:0">
              <?php if ((int)($ann['views_count'] ?? 0) > 0): ?>
                <div style="font-size:11px;color:var(--ink-mute)">👁 <?= (int)$ann['views_count'] ?></div>
              <?php endif ?>
              <?php if ((int)$ann['author_id'] === (int)$userId): ?>
                <form method="POST" action="/feed" style="margin:0" onsubmit="return confirm('Удалить объявление?')">
                  <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                  <input type="hidden" name="action" value="delete_announcement">
                  <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                  <button type="submit" class="btn sm" style="background:var(--bad);color:#fff;border:none;padding:4px 10px;font-size:11px;border-radius:6px">Удалить</button>
                </form>
              <?php endif ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  </section>

</div>

<style>
  .cat-pill { opacity:.55; }
  .cat-pill.cat-active { opacity:1; outline:2px solid currentColor; outline-offset:2px; }
  .type-btn.type-active { background:var(--accent); color:#fff; }
  .chip.chip-active { background:var(--accent); color:#fff; }
</style>

<script>
  function toggleFeedForm(btn){
    var f = document.getElementById('feed-form');
    var open = f.style.display === 'none' || !f.style.display;
    f.style.display = open ? 'block' : 'none';
    btn.textContent = open ? '✕ Закрыть' : '+ Создать';
  }

  // Переключение типа создаваемой записи. Скрытые поля отключаем (disabled),
  // чтобы они не участвовали в валидации и не отправлялись.
  function selectType(type){
    document.querySelectorAll('.type-btn').forEach(function(b){
      var on = b.dataset.type === type;
      b.classList.toggle('type-active', on);
      b.classList.toggle('ghost', !on);
    });
    document.querySelectorAll('.type-fields').forEach(function(box){
      var on = box.dataset.type === type;
      box.style.display = on ? 'block' : 'none';
      box.querySelectorAll('input,textarea,select').forEach(function(el){ el.disabled = !on; });
    });
    var actions = {announcement:'create_announcement', event:'create_event', vote:'create_vote'};
    document.getElementById('feed-action').value = actions[type];
    var labels = {announcement:'Опубликовать', event:'Создать событие', vote:'Запустить голосование'};
    document.getElementById('feed-submit').textContent = labels[type];
  }

  function filterFeed(f){
    document.querySelectorAll('#feed-chips .chip').forEach(function(c){
      var on = c.dataset.filter === f;
      c.classList.toggle('chip-active', on);
      c.classList.toggle('ghost', !on);
    });
    document.querySelectorAll('.feed-section').forEach(function(s){
      s.style.display = (f === 'all' || s.dataset.section === f) ? '' : 'none';
    });
  }

  // Инициализация типа (объявление по умолчанию) и активного фильтра из ?tab
  selectType('announcement');
  filterFeed(<?= json_encode($tab) ?>);
</script>
