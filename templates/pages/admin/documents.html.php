<?php
require_once BASE_PATH . '/src/helpers/csrf.php';
$catLabels = ['устав' => 'Устав', 'протокол' => 'Протокол', 'положение' => 'Положение', 'смета' => 'Смета', 'другое' => 'Другое'];
$fmtSize = function ($b) {
    $b = (int)$b;
    if ($b <= 0) return '';
    if ($b < 1024 * 1024) return round($b / 1024) . ' КБ';
    return round($b / 1048576, 1) . ' МБ';
};
?>
<div class="fade-up">
  <h2 style="margin-bottom:8px">Документы СНТ</h2>
  <p style="color:var(--ink-mute);margin:0 0 24px;font-size:14px">Устав, протоколы собраний, сметы и положения. Публичные документы видны всем членам в разделе «Документы».</p>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- Загрузка документа -->
  <div class="card panel" style="max-width:640px;margin-bottom:32px">
    <h3 style="margin:0 0 20px">Добавить документ</h3>
    <form method="POST" action="/admin/documents" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <div class="field">
        <label>Название *</label>
        <input type="text" name="title" required placeholder="Например: Устав СНТ «Берёзка» 2026">
      </div>
      <div class="grid-2">
        <div class="field">
          <label>Категория</label>
          <select name="category" class="btn ghost" style="width:100%;cursor:pointer;text-align:left">
            <?php foreach ($catLabels as $val => $lbl): ?>
              <option value="<?= e($val) ?>"><?= e($lbl) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="field">
          <label>Файл * <span style="color:var(--ink-mute);font-weight:400">(pdf, doc, xls, jpg — до 20 МБ)</span></label>
          <input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.odt,.ods">
        </div>
      </div>
      <div class="field">
        <label>Описание</label>
        <input type="text" name="description" placeholder="Краткое описание (необязательно)">
      </div>
      <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
        <input type="checkbox" name="is_public" checked> Виден членам СНТ (публичный)
      </label>
      <div><button type="submit" class="btn primary">Загрузить</button></div>
    </form>
  </div>

  <!-- Список документов -->
  <h3 style="margin-bottom:16px">Все документы (<?= count($documents) ?>)</h3>
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
                <?php if ((int)($doc['is_public'] ?? 1) === 0): ?> · <span style="color:var(--bad)">только правление</span><?php endif ?>
              </div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
            <a class="btn ghost sm" href="/uploads/documents/<?= e(rawurlencode($fileName)) ?>" target="_blank" rel="noopener" download>⬇ Скачать</a>
            <form method="POST" action="/admin/documents" onsubmit="return confirm('Удалить документ «<?= e(addslashes($doc['title'] ?? $fileName)) ?>»?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
              <button type="submit" class="btn sm danger">Удалить</button>
            </form>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
