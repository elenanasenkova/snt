<div style="max-width:900px;margin:0 auto">
  <div style="text-align:center;padding:60px 0 40px">
    <h1>СНТ «Берёзка»</h1>
    <p style="font-size:18px;margin-top:12px">Садоводческое некоммерческое товарищество · Сахалинская область</p>
    <div style="margin-top:32px;display:flex;gap:12px;justify-content:center">
      <a href="/login" class="btn primary">Войти</a>
      <a href="/register" class="btn">Подать заявку</a>
    </div>
  </div>
  <div class="grid-3 stagger" style="margin-bottom:48px">
    <div class="kpi"><div class="label">Участков</div><div class="value">61</div></div>
    <div class="kpi"><div class="label">Основано</div><div class="value">1990</div></div>
    <div class="kpi"><div class="label">Регион</div><div class="value" style="font-size:18px">Сахалин</div></div>
  </div>
  <?php if (!empty($meetings)): ?>
  <div class="card panel">
    <h3 style="margin-bottom:16px">Предстоящие собрания</h3>
    <?php foreach ($meetings as $m): ?>
    <div style="padding:12px 0;border-bottom:1px solid var(--line-soft)">
      <strong><?= e(date('d.m.Y', strtotime($m['meeting_date']))) ?></strong>
      — <?= e($m['topic']) ?>
      <?php if (!empty($m['location'])): ?><small> · <?= e($m['location']) ?></small><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
