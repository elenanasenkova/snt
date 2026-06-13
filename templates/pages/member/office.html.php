<?php
require_once BASE_PATH . '/src/helpers/csrf.php';
use models\Ticket;

$manageDocs    = $manageDocs ?? false;
$manageTickets = $manageTickets ?? false;
$showTickets   = $showTickets ?? false;
$thread        = $thread ?? null;
$replies       = $replies ?? [];
$filter        = $filter ?? '';
$tab           = $tab ?? 'docs';

$catLabels  = ['устав' => 'Устав', 'протокол' => 'Протокол', 'положение' => 'Положение', 'смета' => 'Смета', 'другое' => 'Другое'];
$statusPill = ['new' => 'warn', 'in_progress' => 'dot', 'answered' => 'good', 'closed' => ''];
$fmtSize = function ($b) {
    $b = (int)$b;
    if ($b <= 0) return '';
    if ($b < 1048576) return round($b / 1024) . ' КБ';
    return round($b / 1048576, 1) . ' МБ';
};
?>
<div class="fade-up">

<?php if ($thread): /* ═══════════ ПРОСМОТР ОБРАЩЕНИЯ ═══════════ */ ?>
  <a href="/office?tab=tickets" class="btn sm ghost" style="margin-bottom:16px">← К списку обращений</a>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:18px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <div class="card panel" style="margin-bottom:18px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
      <h2 style="margin:0"><?= e($thread['subject']) ?></h2>
      <span class="pill <?= $statusPill[$thread['status']] ?? 'mute' ?>"><?= e(Ticket::STATUS_LABELS[$thread['status']] ?? $thread['status']) ?></span>
    </div>
    <div style="font-size:11px;color:var(--ink-mute);margin-top:6px">
      Обращение № <?= (int)$thread['id'] ?> · автор: <?= e($thread['author_name'] ?? 'Член СНТ') ?> · <?= date('d.m.Y H:i', strtotime($thread['created_at'])) ?>
    </div>
    <div style="margin-top:12px;color:var(--ink-soft);white-space:pre-wrap"><?= e($thread['body']) ?></div>
  </div>

  <?php if ($manageTickets): /* правление: смена статуса */ ?>
    <form method="POST" action="/office" class="card" style="padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <input type="hidden" name="action" value="ticket_status">
      <input type="hidden" name="ticket_id" value="<?= (int)$thread['id'] ?>">
      <span style="font-size:13px;color:var(--ink-soft);font-weight:600">Статус:</span>
      <select name="status" class="btn ghost sm" style="cursor:pointer">
        <?php foreach (Ticket::STATUS_LABELS as $sv => $sl): ?>
          <option value="<?= e($sv) ?>" <?= $thread['status'] === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
        <?php endforeach ?>
      </select>
      <button type="submit" class="btn sm">Применить</button>
    </form>
  <?php endif ?>

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
      <div style="font-size:13px;color:var(--ink-mute)">Ответов пока нет.</div>
    <?php endif ?>
  </div>

  <?php if ($thread['status'] !== 'closed'): ?>
    <form method="POST" action="/office" class="card panel">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <input type="hidden" name="action" value="reply_ticket">
      <input type="hidden" name="ticket_id" value="<?= (int)$thread['id'] ?>">
      <label style="font-weight:600;display:block;margin-bottom:8px"><?= $manageTickets ? 'Ответ правления' : 'Добавить сообщение' ?></label>
      <textarea name="body" rows="3" required style="width:100%" placeholder="Текст сообщения…"></textarea>
      <button type="submit" class="btn primary" style="margin-top:10px">Отправить</button>
    </form>
  <?php else: ?>
    <div class="card panel" style="text-align:center;color:var(--ink-mute)">Обращение закрыто.</div>
  <?php endif ?>

<?php else: /* ═══════════ ОБЫЧНЫЙ ВИД: вкладки ═══════════ */ ?>

  <!-- Шапка -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px">
    <div>
      <h2 style="margin:0">🗂 Канцелярия</h2>
      <p style="margin:4px 0 0;font-size:13px;color:var(--ink-mute)">
        <?= $manageDocs ? 'Документы СНТ и обращения членов' : 'Документы СНТ и ваши обращения в правление' ?>
      </p>
    </div>
    <button class="btn primary" onclick="toggleOfficeForm(this)">
      <?= $manageDocs ? '+ Добавить документ' : '+ Новое обращение' ?>
    </button>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:18px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- ЕДИНАЯ ФОРМА СОЗДАНИЯ (по роли) -->
  <div id="office-form" style="display:none;margin-bottom:26px">
    <div class="card panel" style="max-width:660px">
      <?php if ($manageDocs): /* правление → документ */ ?>
        <h3 style="margin:0 0 18px">Добавить документ</h3>
        <form method="POST" action="/office" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px">
          <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
          <input type="hidden" name="action" value="create_document">
          <div class="field"><label>Название <span style="color:var(--bad)">*</span></label>
            <input type="text" name="title" required placeholder="Например: Устав СНТ «Берёзка» 2026"></div>
          <div class="grid-2">
            <div class="field"><label>Категория</label>
              <select name="category" class="btn ghost" style="width:100%;cursor:pointer;text-align:left">
                <?php foreach ($catLabels as $v => $l): ?><option value="<?= e($v) ?>"><?= e($l) ?></option><?php endforeach ?>
              </select></div>
            <div class="field"><label>Файл <span style="color:var(--bad)">*</span> <span style="color:var(--ink-mute);font-weight:400">(pdf, doc, xls, jpg — до 20 МБ)</span></label>
              <input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.odt,.ods"></div>
          </div>
          <div class="field"><label>Описание</label>
            <input type="text" name="description" placeholder="Краткое описание (необязательно)"></div>
          <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
            <input type="checkbox" name="is_public" checked> Виден членам СНТ (публичный)</label>
          <div style="display:flex;gap:10px">
            <button type="submit" class="btn primary">Загрузить</button>
            <button type="button" class="btn ghost" onclick="toggleOfficeForm(document.querySelector('.btn.primary[onclick*=toggleOfficeForm]'))">Отмена</button>
          </div>
        </form>
      <?php else: /* член → обращение */ ?>
        <h3 style="margin:0 0 18px">Новое обращение в правление</h3>
        <p style="color:var(--ink-mute);margin:0 0 16px;font-size:13px">Ответ придёт уведомлением 🔔.</p>
        <form method="POST" action="/office" style="display:flex;flex-direction:column;gap:14px">
          <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
          <input type="hidden" name="action" value="create_ticket">
          <div class="field"><label>Тема <span style="color:var(--bad)">*</span></label>
            <input type="text" name="subject" maxlength="200" required placeholder="Коротко о сути обращения"></div>
          <div class="field"><label>Сообщение <span style="color:var(--bad)">*</span></label>
            <textarea name="body" rows="4" required placeholder="Опишите вопрос подробно…"></textarea></div>
          <div style="display:flex;gap:10px">
            <button type="submit" class="btn primary">Отправить обращение</button>
            <button type="button" class="btn ghost" onclick="toggleOfficeForm(document.querySelector('.btn.primary[onclick*=toggleOfficeForm]'))">Отмена</button>
          </div>
        </form>
      <?php endif ?>
    </div>
  </div>

  <!-- ВКЛАДКИ -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px" id="office-tabs">
    <button type="button" class="btn sm tabchip" data-tab="docs" onclick="officeTab('docs')">📁 Документы (<?= count($documents ?? []) ?>)</button>
    <?php if ($showTickets): ?>
      <button type="button" class="btn sm tabchip ghost" data-tab="tickets" onclick="officeTab('tickets')">✉️ Обращения (<?= count($tickets ?? []) ?>)</button>
    <?php endif ?>
  </div>

  <!-- ─── ДОКУМЕНТЫ ─── -->
  <section class="office-section" data-section="docs">
    <?php if (empty($documents)): ?>
      <div class="card panel" style="text-align:center;color:var(--ink-mute)">Документов пока нет</div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($documents as $doc):
          $fileName = basename($doc['file_path'] ?? '');
          $cat = $doc['category'] ?? 'другое';
        ?>
          <div class="card" style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0">
              <span style="font-size:26px;flex-shrink:0">📄</span>
              <div style="min-width:0">
                <div style="font-weight:600;color:var(--ink)"><?= e($doc['title'] ?? $fileName) ?></div>
                <div style="font-size:12px;color:var(--ink-mute);margin-top:3px">
                  <span class="pill" style="margin-right:6px"><?= e($catLabels[$cat] ?? $cat) ?></span>
                  <?= !empty($doc['created_at']) ? date('d.m.Y', strtotime($doc['created_at'])) : '' ?>
                  <?php if (!empty($doc['file_size'])): ?> · <?= $fmtSize($doc['file_size']) ?><?php endif ?>
                  <?php if ($manageDocs && (int)($doc['is_public'] ?? 1) === 0): ?> · <span style="color:var(--bad)">только правление</span><?php endif ?>
                </div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
              <a class="btn ghost sm" href="/uploads/documents/<?= e(rawurlencode($fileName)) ?>" target="_blank" rel="noopener" download>⬇ Скачать</a>
              <?php if ($manageDocs): ?>
                <form method="POST" action="/office" onsubmit="return confirm('Удалить документ?')">
                  <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                  <input type="hidden" name="action" value="delete_document">
                  <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                  <button type="submit" class="btn sm danger">Удалить</button>
                </form>
              <?php endif ?>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </section>

  <!-- ─── ОБРАЩЕНИЯ ─── -->
  <?php if ($showTickets): ?>
    <section class="office-section" data-section="tickets" style="display:none">
      <?php if ($manageTickets): /* правление: фильтр по статусу */ ?>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
          <a href="/office?tab=tickets" class="btn sm <?= $filter === '' ? 'primary' : 'ghost' ?>">Все</a>
          <?php foreach (Ticket::STATUS_LABELS as $sv => $sl): ?>
            <a href="/office?tab=tickets&status=<?= e($sv) ?>" class="btn sm <?= $filter === $sv ? 'primary' : 'ghost' ?>"><?= e($sl) ?></a>
          <?php endforeach ?>
        </div>
      <?php endif ?>

      <?php if (empty($tickets)): ?>
        <div class="card panel" style="text-align:center;color:var(--ink-mute)"><?= $manageTickets ? 'Обращений нет' : 'У вас пока нет обращений' ?></div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($tickets as $t): ?>
            <a href="/office?tab=tickets&id=<?= (int)$t['id'] ?>" class="card" style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;text-decoration:none">
              <div style="min-width:0">
                <div style="font-weight:600;color:var(--ink)"><?= e($t['subject']) ?></div>
                <div style="font-size:11px;color:var(--ink-mute);margin-top:3px">
                  № <?= (int)$t['id'] ?>
                  <?php if ($manageTickets && !empty($t['author_name'])): ?> · <?= e($t['author_name']) ?><?php endif ?>
                  · обновлено <?= date('d.m.Y H:i', strtotime($t['updated_at'])) ?>
                </div>
              </div>
              <span class="pill <?= $statusPill[$t['status']] ?? 'mute' ?>" style="flex-shrink:0"><?= e(Ticket::STATUS_LABELS[$t['status']] ?? $t['status']) ?></span>
            </a>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </section>
  <?php endif ?>

<?php endif /* thread vs tabs */ ?>
</div>

<style>
  .tabchip.tab-active { background:var(--accent); color:#fff; }
</style>

<script>
  function toggleOfficeForm(btn){
    var f = document.getElementById('office-form');
    var open = f.style.display === 'none' || !f.style.display;
    f.style.display = open ? 'block' : 'none';
  }
  function officeTab(t){
    document.querySelectorAll('#office-tabs .tabchip').forEach(function(c){
      var on = c.dataset.tab === t;
      c.classList.toggle('tab-active', on);
      c.classList.toggle('ghost', !on);
    });
    document.querySelectorAll('.office-section').forEach(function(s){
      s.style.display = s.dataset.section === t ? '' : 'none';
    });
  }
  <?php if (!$thread): ?>officeTab(<?= json_encode($tab) ?>);<?php endif ?>
</script>
