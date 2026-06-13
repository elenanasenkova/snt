<div class="splash" style="align-items:flex-start;padding-top:60px">
  <div class="card panel" style="max-width:500px;margin:0 auto;width:100%">
    <h2 style="margin-bottom:8px">Заявка на вступление</h2>
    <p style="margin-bottom:24px;font-size:14px">Заявка будет рассмотрена председателем СНТ</p>
    <form method="POST" action="/register" style="display:flex;flex-direction:column;gap:16px">
      <?php require_once BASE_PATH.'/src/helpers/csrf.php'; echo csrfField(); ?>
      <div class="field"><label>ФИО *</label><input type="text" name="full_name" required placeholder="Иванов Иван Иванович"></div>
      <div class="field"><label>Email *</label><input type="email" name="email" required placeholder="your@email.ru"></div>
      <div class="field"><label>Телефон</label><input type="tel" name="phone" placeholder="+7 900 000-00-00"></div>
      <div class="field"><label>Номер участка</label><input type="text" name="address" placeholder="Участок №47"></div>
      <div class="field"><label>Сообщение</label><textarea name="message" placeholder="Расскажите о себе..."></textarea></div>
      <button type="submit" class="btn primary" style="width:100%;justify-content:center">Отправить заявку</button>
    </form>
    <p style="text-align:center;margin-top:16px;font-size:13px"><a href="/login">← Назад к входу</a></p>
  </div>
</div>
