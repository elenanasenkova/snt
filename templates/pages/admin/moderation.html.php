<?php require_once BASE_PATH . '/src/helpers/csrf.php'; ?>
<div class="fade-up">
  <h2 style="margin-bottom:24px">Заявки на вступление</h2>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px">
      <?= e($flash['msg']) ?>
    </div>
  <?php endif ?>

  <?php if (empty($registrations)): ?>
    <div class="card panel" style="text-align:center">
      <div style="font-size:40px;margin-bottom:12px">✅</div>
      <div style="color:var(--ink-soft);font-size:16px">Новых заявок нет</div>
      <div style="color:var(--ink-mute);font-size:13px;margin-top:6px">Все заявки рассмотрены</div>
    </div>
  <?php else: ?>
    <div class="card" style="overflow:hidden">
      <table class="t">
        <thead>
          <tr>
            <th>ФИО</th>
            <th>Email</th>
            <th>Телефон</th>
            <th>Участок</th>
            <th>Сообщение</th>
            <th>Дата</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registrations as $reg): ?>
            <tr>
              <td style="font-weight:500;white-space:nowrap"><?= e($reg['full_name'] ?? '—') ?></td>
              <td class="mono" style="color:var(--ink-soft)"><?= e($reg['email'] ?? '') ?></td>
              <td class="mono" style="color:var(--ink-soft);white-space:nowrap"><?= e($reg['phone'] ?? '—') ?></td>
              <td><?= e($reg['address'] ?? '—') ?></td>
              <td style="max-width:200px">
                <?php if (!empty($reg['message'])): ?>
                  <span title="<?= e($reg['message']) ?>"
                        style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;color:var(--ink-soft)">
                    <?= e($reg['message']) ?>
                  </span>
                <?php else: ?>
                  <span style="color:var(--ink-mute)">—</span>
                <?php endif ?>
              </td>
              <td style="white-space:nowrap;font-size:13px;color:var(--ink-mute)">
                <?= !empty($reg['created_at']) ? date('d.m.Y', strtotime($reg['created_at'])) : '—' ?>
              </td>
              <td>
                <div style="display:flex;gap:6px">
                  <form method="POST" action="/admin/moderation">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="id" value="<?= (int)$reg['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <button type="submit" class="btn sm primary">Одобрить</button>
                  </form>
                  <form method="POST" action="/admin/moderation"
                    onsubmit="return confirm('Отклонить заявку от <?= e(addslashes($reg['full_name'] ?? '')) ?>?')">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" value="<?= (int)$reg['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <button type="submit" class="btn sm danger">Отклонить</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
