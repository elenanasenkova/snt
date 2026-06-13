<div class="splash">
  <div class="card panel">
    <div style="text-align:center; margin-bottom:24px">
      <div class="brand" style="justify-content:center; margin-bottom:8px">
        <div class="mark">Б</div>
        <div><div class="name">СНТ «Берёзка»</div></div>
      </div>
      <h2>Вход в систему</h2>
    </div>
    <form method="POST" action="/login" style="display:flex;flex-direction:column;gap:16px">
      <?php require_once BASE_PATH.'/src/helpers/csrf.php'; echo csrfField(); ?>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" required autofocus placeholder="your@email.ru">
      </div>
      <div class="field">
        <label>Пароль</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn primary" style="width:100%;justify-content:center">Войти</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:13px">
      Ещё нет доступа? <a href="/register">Подать заявку</a>
    </p>
  </div>
</div>
