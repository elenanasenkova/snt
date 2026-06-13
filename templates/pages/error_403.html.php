<div class="splash">
  <div style="text-align:center">
    <h1 style="font-size:80px;opacity:.3">403</h1>
    <h2>Доступ запрещён</h2>
    <p style="margin:12px 0 24px">У вас недостаточно прав для просмотра этой страницы.</p>
    <?php if (!empty($message)): ?>
      <p style="margin:0 0 24px;opacity:.6"><?= e($message) ?></p>
    <?php endif; ?>
    <a href="/" class="btn">← Вернуться на главную</a>
  </div>
</div>
