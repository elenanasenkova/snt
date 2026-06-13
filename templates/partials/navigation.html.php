<?php
$user = getCurrentUser();
require_once BASE_PATH . '/src/helpers/csrf.php';
?>
<div class="topbar-sticky">
<header class="glass topbar">

  <a href="/" class="brand" style="text-decoration:none;flex-shrink:0">
    <div class="mark">Б</div>
    <div>
      <div class="name">СНТ «Берёзка»</div>
      <div class="sub">Сахалинская область</div>
    </div>
  </a>

  <?php if ($user): ?>
    <nav class="hnav" style="margin-top:0;flex:1;min-width:0;display:flex;align-items:center">
      <?php if (isBoard()): ?>
        <?php
          // Подпись роли в панели правления
          $roleLabels = [1 => 'Администратор', 2 => 'Председатель', 3 => 'Казначей', 4 => 'Секретарь'];
          $roleLabel  = $roleLabels[userRole()] ?? 'Правление';
        ?>
        <div class="mono" style="font-size:11px;color:var(--ink-mute);padding:0 10px 0 4px;text-transform:uppercase;letter-spacing:.1em;white-space:nowrap;flex-shrink:0">
          <?= e($_SESSION['user']['full_name'] ?? $roleLabel) ?> · <?= e($roleLabel) ?>
        </div>
        <?php /* Группа 1: Дашборд (все члены правления) */ ?>
        <a href="/admin"            class="hnav-item <?= $currentPage === 'admin/dashboard' ? 'active' : '' ?>">🏠 Дашборд</a>
        <?php /* Разделитель 1 */ ?>
        <span style="display:inline-block;width:1px;background:var(--line-soft);height:18px;margin:0 2px;align-self:center;flex-shrink:0"></span>
        <?php /* Группа 2: Пользователи/реестр (правление 1,2,4), Финансы (казначей), Лента */ ?>
        <?php if (canMeetings()): ?>
          <a href="/admin/users"      class="hnav-item <?= $currentPage === 'admin/users'      ? 'active' : '' ?>">👥 Пользователи</a>
        <?php endif ?>
        <?php if (canFinance()): ?>
          <a href="/admin/finances"   class="hnav-item <?= $currentPage === 'admin/finances'   ? 'active' : '' ?>">💰 Финансы</a>
        <?php endif ?>
        <a href="/feed"             class="hnav-item <?= $currentPage === 'member/feed' ? 'active' : '' ?>">📣 Лента</a>
        <?php /* Разделитель 2 */ ?>
        <span style="display:inline-block;width:1px;background:var(--line-soft);height:18px;margin:0 2px;align-self:center;flex-shrink:0"></span>
        <?php /* Группа 3: Канцелярия (Документы+Обращения), Модерация (админ). Реестр → «Пользователи». */ ?>
        <?php if (canMeetings()): ?>
          <?php $openTickets = isAdmin() ? \models\Ticket::openCount() : 0; ?>
          <a href="/office" class="hnav-item <?= $currentPage === 'member/office' ? 'active' : '' ?>" style="position:relative">🗂 Канцелярия<?php if ($openTickets > 0): ?> <span style="background:var(--bad);color:#fff;font-size:10px;font-weight:700;border-radius:8px;padding:1px 5px"><?= $openTickets > 99 ? '99+' : $openTickets ?></span><?php endif ?></a>
        <?php endif ?>
        <?php if (isAdmin()): ?>
          <a href="/admin/moderation" class="hnav-item <?= $currentPage === 'admin/moderation' ? 'active' : '' ?>">📋 Модерация</a>
        <?php endif ?>
      <?php else: ?>
        <a href="/dashboard"    class="hnav-item <?= $currentPage === 'member/dashboard'    ? 'active' : '' ?>">🏠 Главная</a>
        <a href="/feed"         class="hnav-item <?= $currentPage === 'member/feed'         ? 'active' : '' ?>">📣 Лента</a>
        <a href="/finances"     class="hnav-item <?= $currentPage === 'member/finances'     ? 'active' : '' ?>">💰 Взносы</a>
        <a href="/reports"      class="hnav-item <?= $currentPage === 'member/reports'      ? 'active' : '' ?>">📊 Отчёты</a>
        <a href="/office"       class="hnav-item <?= $currentPage === 'member/office'       ? 'active' : '' ?>">🗂 Канцелярия</a>
        <a href="/discussions"  class="hnav-item <?= $currentPage === 'member/discussions'  ? 'active' : '' ?>">💬 Обсуждения</a>
      <?php endif; ?>
    </nav>

    <?php $unread = \models\Notification::unreadCount((int)($_SESSION['user']['id'] ?? 0)); ?>
    <a href="/notifications" class="hnav-item <?= $currentPage === 'member/notifications' ? 'active' : '' ?>" style="margin-left:auto;flex-shrink:0;position:relative;font-size:17px" title="Уведомления">🔔<?php if ($unread > 0): ?><span style="position:absolute;top:-3px;right:-5px;background:var(--bad);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;line-height:16px;text-align:center;border-radius:8px;padding:0 3px"><?= $unread > 99 ? '99+' : $unread ?></span><?php endif ?></a>
    <form method="POST" action="/logout" style="flex-shrink:0">
      <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
      <button type="submit" class="btn sm ghost">Выйти</button>
    </form>

  <?php else: ?>
    <nav class="hnav" style="margin-top:0;margin-left:auto">
      <a href="/login"    class="hnav-item <?= $currentPage === 'auth/login'    ? 'active' : '' ?>">Войти</a>
      <a href="/register" class="hnav-item <?= $currentPage === 'auth/register' ? 'active' : '' ?>">Регистрация</a>
    </nav>
  <?php endif; ?>

</header>
</div>
