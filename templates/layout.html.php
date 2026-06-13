<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>СНТ «Берёзка»</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,500;0,600;1,400&family=Manrope:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <?php include BASE_PATH . '/templates/partials/navigation.html.php'; ?>
  <main class="shell fade-up">
    <?php
    // Используем flash, если контроллер уже передал его через extract(); иначе читаем из сессии.
    if (empty($flash)) { $flash = getFlash(); }
    if ($flash): ?>
      <div class="flash-bar <?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>">
        <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php
    // Сбрасываем, чтобы шаблон страницы не показал дублирующий блок
    $flash = null;
    endif; ?>
    <?php include BASE_PATH . '/templates/pages/' . $currentPage . '.html.php'; ?>
  </main>
  <?php include BASE_PATH . '/templates/partials/footer.html.php'; ?>
  <script src="/js/main.js"></script>
</body>
</html>
