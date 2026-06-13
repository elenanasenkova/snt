<?php
require_once BASE_PATH . '/src/helpers/csrf.php';
$roles = $roles ?? [];
$canEditRole = isAdmin(); // роль и статус правят только админ/председатель
$statusPill = ['active' => 'good', 'pending' => 'warn', 'inactive' => 'bad'];
$statusRu   = ['active' => 'Активен', 'pending' => 'Ожидает', 'inactive' => 'Неактивен'];
// Сортировка по номеру участка
usort($users, fn($a, $b) => plotFromAddress($a['address'] ?? '') <=> plotFromAddress($b['address'] ?? ''));
$inp = 'width:100%;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:8px 10px;font-size:13px;color:var(--ink);background:var(--paper,#fff);box-sizing:border-box';

$totalUsers   = count($users);
$activeUsers  = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'active'));
$pendingUsers = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'pending'));
?>
<div class="fade-up">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px">
    <h2 style="margin:0">Пользователи · реестр членов СНТ</h2>
    <a href="/admin/users/export" class="btn primary sm">⬇ Выгрузить в Excel (CSV)</a>
  </div>
  <p style="color:var(--ink-mute);margin:0 0 20px;font-size:14px">Всего записей: <strong><?= $totalUsers ?></strong>. Реестр ведётся в соответствии с ФЗ-217 «О ведении гражданами садоводства…».</p>

  <div class="grid-3" style="margin-bottom:20px">
    <div class="card kpi">
      <div class="label">Всего участников</div>
      <div class="value"><?= $totalUsers ?></div>
    </div>
    <div class="card kpi">
      <div class="label">Активных</div>
      <div class="value" style="color:var(--good)"><?= $activeUsers ?></div>
    </div>
    <div class="card kpi">
      <div class="label">На рассмотрении</div>
      <div class="value" style="color:var(--warn)"><?= $pendingUsers ?></div>
    </div>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px">
      <?= e($flash['msg']) ?>
    </div>
  <?php endif ?>

  <div class="card panel" style="overflow-x:auto;padding:0">
    <table style="width:100%;border-collapse:collapse;font-size:14px">
      <thead>
        <tr style="text-align:left;background:var(--bg-warm,#faf7f2);color:var(--ink-mute)">
          <th style="padding:12px 14px;font-weight:600;white-space:nowrap">№ уч.</th>
          <th style="padding:12px 14px;font-weight:600">ФИО</th>
          <th style="padding:12px 14px;font-weight:600;white-space:nowrap">Телефон</th>
          <th style="padding:12px 14px;font-weight:600">Email</th>
          <th style="padding:12px 14px;font-weight:600">Роль</th>
          <th style="padding:12px 14px;font-weight:600">Статус</th>
          <th style="padding:12px 14px;font-weight:600">Действие</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--ink-mute)">Записей нет</td></tr>
        <?php else: ?>
          <?php foreach ($users as $u):
            $plot = plotFromAddress($u['address'] ?? '');
            $st   = $u['status'] ?? '';
          ?>
          <tr style="border-top:1px solid var(--line-soft)">
            <td style="padding:11px 14px;font-weight:600;color:var(--accent-deep);white-space:nowrap"><?= $plot ? '№' . $plot : '—' ?></td>
            <td style="padding:11px 14px"><?= e($u['full_name'] ?? '') ?></td>
            <td style="padding:11px 14px;white-space:nowrap"><?= e($u['phone'] ?? '') ?: '<span style="color:var(--ink-mute)">—</span>' ?></td>
            <td style="padding:11px 14px;color:var(--ink-soft)"><?= e($u['email'] ?? '') ?></td>
            <td style="padding:11px 14px;white-space:nowrap"><?= e($u['role_name'] ?? '') ?></td>
            <td style="padding:11px 14px"><span class="pill <?= $statusPill[$st] ?? '' ?>"><?= e($statusRu[$st] ?? $st) ?></span></td>
            <td style="padding:11px 14px">
              <details>
                <summary style="cursor:pointer;color:var(--accent-deep);font-weight:500;font-size:13px">Изменить</summary>
                <form method="POST" action="/admin/users" style="margin-top:12px;display:grid;gap:10px;min-width:260px">
                  <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <label style="font-size:12px;color:var(--ink-soft)">ФИО
                    <input type="text" name="full_name" value="<?= e($u['full_name'] ?? '') ?>" required style="<?= $inp ?>"></label>
                  <label style="font-size:12px;color:var(--ink-soft)">Адрес / участок
                    <input type="text" name="address" value="<?= e($u['address'] ?? '') ?>" placeholder="Участок №…" style="<?= $inp ?>"></label>
                  <label style="font-size:12px;color:var(--ink-soft)">Телефон
                    <input type="text" name="phone" value="<?= e($u['phone'] ?? '') ?>" style="<?= $inp ?>"></label>
                  <label style="font-size:12px;color:var(--ink-soft)">Email
                    <input type="email" name="email" value="<?= e($u['email'] ?? '') ?>" required style="<?= $inp ?>"></label>
                  <?php if ($canEditRole): ?>
                    <?php if (!empty($roles)): ?>
                    <label style="font-size:12px;color:var(--ink-soft)">Роль
                      <select name="role_id" style="<?= $inp ?>">
                        <?php foreach ($roles as $r): ?>
                          <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)($u['role_id'] ?? 0) ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                        <?php endforeach ?>
                      </select></label>
                    <?php endif ?>
                    <label style="font-size:12px;color:var(--ink-soft)">Статус
                      <select name="status" style="<?= $inp ?>">
                        <?php foreach (['active' => 'Активен', 'inactive' => 'Неактивен', 'pending' => 'Ожидает'] as $sv => $sl): ?>
                          <option value="<?= $sv ?>" <?= $sv === $st ? 'selected' : '' ?>><?= $sl ?></option>
                        <?php endforeach ?>
                      </select></label>
                  <?php else: ?>
                    <p style="font-size:11px;color:var(--ink-mute);margin:0">Роль и статус меняет администратор или председатель.</p>
                  <?php endif ?>
                  <button type="submit" class="btn primary sm">Сохранить</button>
                </form>
              </details>
            </td>
          </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>
