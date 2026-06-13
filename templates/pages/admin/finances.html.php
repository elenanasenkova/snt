<?php
require_once BASE_PATH . '/src/helpers/csrf.php';

$regularFeeTypes = array_values(array_filter($feeTypes, fn($ft) => mb_stripos($ft['name'], 'свет') === false));
$elecFeeTypes    = array_values(array_filter($feeTypes, fn($ft) => mb_stripos($ft['name'], 'свет') !== false));

$activeTab = $_GET['tab'] ?? 'fees';
if (!in_array($activeTab, ['fees', 'electricity', 'expenses'])) $activeTab = 'fees';

$PLOT_TOTAL = 61;
$plots = array_map('strval', range(1, $PLOT_TOTAL));

// Начисленная сумма участку с учётом адресности вида взноса.
// Для адресных видов (plot_scope='selected') начисление есть только у участков с записью fee_payments.
$reqFor = function(array $ft, $pay): float {
    if (is_array($pay) && isset($pay['required_amount'])) {
        return (float)$pay['required_amount'];
    }
    if (($ft['plot_scope'] ?? 'all') === 'selected') {
        return 0.0; // вид адресный — этому участку не начислен
    }
    return (float)$ft['amount']; // вид для всех участков
};

$regularCollected = 0;
$elecCollected    = 0;
foreach ($feeTypes as $ft) {
    $isElec = mb_stripos($ft['name'], 'свет') !== false;
    foreach ($plots as $pn) {
        $pay = $payMap[$ft['id']][$pn] ?? null;
        $amt = (float)($pay['paid_amount'] ?? 0);
        if ($isElec) $elecCollected += $amt; else $regularCollected += $amt;
    }
}
$totalExpenses = (float)($summary['total_expenses'] ?? 0);
$balance = $regularCollected - $totalExpenses;
$csrfToken = generateCsrfToken();

$statusLabel = fn(string $st): string => match($st) {
    'paid'    => 'Оплачено',
    'partial' => 'Частично',
    default   => 'Не оплачено',
};
$stBg = fn(string $st): string => match($st) {
    'paid'    => '#e6efd5',
    'partial' => '#fff3cd',
    default   => 'transparent',
};
$stColor = fn(string $st): string => match($st) {
    'paid'    => 'var(--good)',
    'partial' => '#c8860a',
    default   => 'var(--bad)',
};
?>
<div class="fade-up">

  <!-- Заголовок + год -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <div class="mono" style="color:var(--ink-mute);text-transform:uppercase;letter-spacing:.1em;font-size:11px;margin-bottom:4px">Администрирование</div>
      <h2 style="margin:0">Финансы — управление</h2>
    </div>
    <form method="GET" action="/admin/finances" style="display:flex;gap:8px;align-items:center">
      <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
      <select name="year" onchange="this.form.submit()"
        style="border:1px solid var(--line,#e5e7eb);border-radius:8px;padding:8px 14px;font-size:14px;color:var(--ink);background:var(--paper);cursor:pointer">
        <?php foreach ($years as $y): ?>
          <option value="<?= (int)$y ?>" <?= (int)$y === (int)$year ? 'selected' : '' ?>><?= (int)$y ?></option>
        <?php endforeach ?>
      </select>
    </form>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <!-- KPI -->
  <div class="grid-3 stagger" style="margin-bottom:24px">
    <div class="kpi card">
      <div class="label">Целевые взносы</div>
      <div class="value" id="kpi-reg-coll" style="color:var(--good);font-size:26px"><?= number_format($regularCollected, 0, '.', ' ') ?> ₽</div>
      <div class="delta">собрано за <?= (int)$year ?></div>
    </div>
    <div class="kpi card">
      <div class="label">Расходы СНТ</div>
      <div class="value" style="color:var(--bad);font-size:26px"><?= number_format($totalExpenses, 0, '.', ' ') ?> ₽</div>
      <div class="delta">за <?= (int)$year ?></div>
    </div>
    <div class="kpi card">
      <div class="label">Баланс</div>
      <div class="value" id="kpi-balance" style="color:<?= $balance >= 0 ? 'var(--accent)' : 'var(--bad)' ?>;font-size:26px">
        <?= ($balance >= 0 ? '+' : '') . number_format($balance, 0, '.', ' ') ?> ₽
      </div>
      <div class="delta" id="kpi-balance-delta"><?= $balance >= 0 ? 'профицит' : 'дефицит' ?></div>
    </div>
  </div>

  <!-- Главные табы -->
  <div class="glass" style="display:inline-flex;gap:4px;padding:6px;border-radius:999px;margin-bottom:28px">
    <?php foreach ([['fees','Взносы'],['electricity','⚡ Свет'],['expenses','Расходы']] as [$t,$lbl]): ?>
      <a href="/admin/finances?year=<?= (int)$year ?>&tab=<?= $t ?>"
         class="btn sm <?= $activeTab === $t ? 'primary' : 'ghost' ?>"
         style="<?= $activeTab !== $t ? 'border:none;box-shadow:none' : '' ?>;text-decoration:none">
        <?= $lbl ?>
      </a>
    <?php endforeach ?>
  </div>

  <!-- ══ ТАБ: ВЗНОСЫ ══════════════════════════════════════════════ -->
  <?php if ($activeTab === 'fees'): ?>

    <!-- Форма создания вида взноса -->
    <div class="card" style="padding:22px 24px;margin-bottom:22px;max-width:620px">
      <h3 style="margin:0 0 16px">Добавить вид взноса</h3>
      <form method="POST" action="/admin/finances?year=<?= (int)$year ?>&tab=fees">
        <input type="hidden" name="action" value="create_fee_type">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="grid-2" style="gap:14px;margin-bottom:12px">
          <div class="field">
            <label>Название</label>
            <input type="text" name="name" required placeholder="Членский взнос 2026">
          </div>
          <div class="field">
            <label>Сумма (₽)</label>
            <input type="number" name="amount" required min="1" step="0.01" placeholder="5000">
          </div>
        </div>
        <div class="field" style="margin-bottom:14px">
          <label>Описание</label>
          <input type="text" name="description" placeholder="Необязательно">
        </div>

        <!-- Выбор участков -->
        <div class="field" style="margin-bottom:14px">
          <label>Каким участкам начислить</label>
          <div style="display:flex;gap:18px;flex-wrap:wrap;margin:4px 0 8px">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400">
              <input type="radio" name="plot_scope" value="all" checked onchange="toggleScopePicker()"> Все участки (<?= $PLOT_TOTAL ?>)
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400">
              <input type="radio" name="plot_scope" value="selected" onchange="toggleScopePicker()"> Выбрать участки
            </label>
          </div>
          <div id="scope-picker" style="display:none;border:1px solid var(--line);border-radius:10px;padding:12px;background:var(--fog)">
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap">
              <button type="button" class="btn ghost sm" onclick="scopeAll(true)">Выделить все</button>
              <button type="button" class="btn ghost sm" onclick="scopeAll(false)">Снять все</button>
              <span id="scope-count" style="font-size:12px;color:var(--ink-mute)">выбрано: 0</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(60px,1fr));gap:5px;max-height:200px;overflow-y:auto">
              <?php foreach ($plots as $pn): ?>
              <label style="display:flex;align-items:center;gap:5px;background:var(--paper);border:1px solid var(--line-soft);border-radius:6px;padding:4px 6px;cursor:pointer;font-size:12px">
                <input type="checkbox" name="plots[]" value="<?= $pn ?>" class="scope-cb" onchange="updateScopeCount()">№<?= $pn ?>
              </label>
              <?php endforeach ?>
            </div>
          </div>
        </div>

        <button type="submit" class="btn primary">Создать</button>
      </form>
    </div>

    <?php if (empty($regularFeeTypes)): ?>
      <div class="card" style="padding:40px;text-align:center;color:var(--ink-mute)">
        <div style="font-size:36px;margin-bottom:12px">📋</div>
        <p>Видов взносов пока нет. Добавьте первый выше.</p>
      </div>
    <?php else: ?>

      <!-- Чипы управления видами -->
      <div class="glass panel" style="margin-bottom:18px;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <span style="font-size:12px;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.08em;margin-right:4px">Виды взносов <?= (int)$year ?>:</span>
        <?php foreach ($regularFeeTypes as $ft): ?>
        <div style="display:inline-flex;align-items:center;gap:6px;background:var(--fog);border:1px solid var(--line);border-radius:20px;padding:4px 10px 4px 12px;font-size:13px">
          <span style="font-weight:500"><?= e($ft['name']) ?></span>
          <span class="mono" style="color:var(--ink-mute);font-size:12px"><?= number_format((float)$ft['amount'], 0, '.', ' ') ?> ₽</span>
          <form method="POST" action="/admin/finances?year=<?= (int)$year ?>&tab=fees" style="display:inline"
                onsubmit="return confirm('Удалить «<?= e($ft['name']) ?>» и все платежи?')">
            <input type="hidden" name="action" value="delete_fee_type">
            <input type="hidden" name="fee_type_id" value="<?= (int)$ft['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <button type="submit" title="Удалить" style="width:18px;height:18px;border-radius:50%;border:none;background:transparent;cursor:pointer;color:var(--ink-mute);display:inline-flex;align-items:center;justify-content:center;padding:0;vertical-align:middle">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </form>
        </div>
        <?php endforeach ?>
      </div>

      <!-- Статистика по активным видам -->
      <?php
      $stPaid = 0; $stPartial = 0; $stTotal = 0; $subRequired = 0; $subCollected = 0;
      foreach ($plots as $pn) {
          $hasAppl = false; $allPaid = true; $anyPaid = false;
          foreach ($regularFeeTypes as $ft) {
              $pay = $payMap[$ft['id']][$pn] ?? null;
              $req = $reqFor($ft, $pay);
              if ($req <= 0) continue;
              $hasAppl = true;
              $st = $pay['status'] ?? 'unpaid';
              if ($st !== 'paid') $allPaid = false;
              if ($st === 'paid' || ($pay['paid_amount'] ?? 0) > 0) $anyPaid = true;
              $subRequired  += $req;
              $subCollected += (float)($pay['paid_amount'] ?? 0);
          }
          if (!$hasAppl) continue;
          $stTotal++;
          if ($allPaid) $stPaid++;
          elseif ($anyPaid) $stPartial++;
      }
      $stUnpaid = $stTotal - $stPaid - $stPartial;
      $pct = $stTotal > 0 ? round($stPaid / $stTotal * 100) : 0;
      ?>
      <div class="grid-4" style="margin-bottom:18px">
        <div class="kpi" style="border-color:#c9d8a5;background:#e6efd5">
          <div class="label">Оплатили полностью</div>
          <div class="value" id="kpi-st-paid" style="color:var(--good)"><?= $stPaid ?></div>
          <div class="delta" id="kpi-st-paid-delta">из <?= $stTotal ?> участков</div>
        </div>
        <div class="kpi" style="border-color:#ffe08a;background:#fff8e1">
          <div class="label">Частичная оплата</div>
          <div class="value" id="kpi-st-partial" style="color:#c8860a"><?= $stPartial ?></div>
          <div class="delta">участков</div>
        </div>
        <div class="kpi" style="border-color:#e6b3a4;background:#f5d5cc">
          <div class="label">Не оплатили</div>
          <div class="value" id="kpi-st-unpaid" style="color:var(--bad)"><?= $stUnpaid ?></div>
          <div class="delta">участков</div>
        </div>
        <div class="kpi">
          <div class="label">Собрано</div>
          <div class="value" id="kpi-coll" style="font-size:20px"><?= number_format($subCollected, 0, '.', ' ') ?></div>
          <div class="delta" id="kpi-coll-delta">из <?= number_format($subRequired, 0, '.', ' ') ?> ₽</div>
        </div>
      </div>

      <!-- Разбивка по видам взносов -->
      <?php if (!empty($regularFeeTypes)): ?>
      <div class="glass panel" style="margin-bottom:18px;padding:16px 20px">
        <div style="font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">
          По видам взносов — <?= (int)$year ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($regularFeeTypes as $ft):
            $ftCollected = 0; $ftRequired = 0;
            foreach ($plots as $pn) {
                $pay = $payMap[$ft['id']][$pn] ?? null;
                $req = $reqFor($ft, $pay);
                $ftRequired  += max(0, $req);
                $ftCollected += (float)($pay['paid_amount'] ?? 0);
            }
            $ftPct     = $ftRequired > 0 ? round($ftCollected / $ftRequired * 100) : 0;
            $ftSpent   = (float)($expensesByFeeType[(string)$ft['id']] ?? 0);
            $ftBalance = $ftCollected - $ftSpent;
          ?>
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="min-width:180px;font-size:13px;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($ft['name']) ?>">
              <?= e(mb_strlen($ft['name']) > 26 ? mb_substr($ft['name'],0,24).'…' : $ft['name']) ?>
            </div>
            <div style="flex:1;min-width:160px">
              <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--ink-mute);margin-bottom:3px">
                <span id="ft-st-<?= (int)$ft['id'] ?>"><?= number_format($ftCollected, 0, '.', ' ') ?> ₽ из <?= number_format($ftRequired, 0, '.', ' ') ?> ₽</span>
                <span class="mono" id="ft-pct-<?= (int)$ft['id'] ?>"><?= $ftPct ?>%</span>
              </div>
              <div style="height:5px;background:var(--line-soft);border-radius:99px;overflow:hidden">
                <div id="ft-bar-<?= (int)$ft['id'] ?>" style="width:<?= $ftPct ?>%;height:100%;background:<?= $ftPct >= 80 ? 'var(--good)' : ($ftPct < 50 ? 'var(--warn)' : 'var(--accent)') ?>;border-radius:99px;transition:width .3s"></div>
              </div>
            </div>
            <?php if ($ftSpent > 0): ?>
            <div style="font-size:12px;color:var(--ink-mute);white-space:nowrap">
              потрачено <span style="color:var(--bad);font-weight:600"><?= number_format($ftSpent, 0, '.', ' ') ?> ₽</span>
              · остаток <span style="color:<?= $ftBalance >= 0 ? 'var(--good)' : 'var(--bad)' ?>;font-weight:600"><?= number_format($ftBalance, 0, '.', ' ') ?> ₽</span>
            </div>
            <?php endif ?>
          </div>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>

      <!-- Прогресс-бар сбора -->
      <div class="glass" style="padding:14px 20px;border-radius:12px;margin-bottom:18px;display:flex;align-items:center;gap:16px">
        <span style="font-size:13px;color:var(--ink-soft);font-weight:600;white-space:nowrap">Сбор <?= (int)$year ?></span>
        <div style="flex:1;height:8px;background:var(--line);border-radius:99px;overflow:hidden">
          <div id="collect-bar" style="width:<?= $pct ?>%;height:100%;background:<?= $pct >= 80 ? 'var(--good)' : ($pct < 50 ? 'var(--warn)' : 'var(--accent)') ?>;border-radius:99px;transition:width .3s"></div>
        </div>
        <span class="mono" id="collect-txt" style="font-size:12px;color:var(--ink-mute);white-space:nowrap"><?= $stPaid ?> / <?= $stTotal ?> · <?= $pct ?>%</span>
      </div>

      <!-- СВОДНАЯ ТАБЛИЦА -->
      <div style="overflow-x:auto;border-radius:12px;border:1px solid var(--line);background:var(--paper)">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--fog);border-bottom:2px solid var(--line)">
              <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">No</th>
              <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;min-width:130px">Участник</th>
              <?php foreach ($regularFeeTypes as $ft): ?>
              <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.06em;min-width:170px">
                <div><?= e(mb_strlen($ft['name']) > 24 ? mb_substr($ft['name'], 0, 22).'…' : $ft['name']) ?></div>
                <div style="font-size:10px;font-weight:400;margin-top:2px"><?= number_format((float)$ft['amount'], 0, '.', ' ') ?> ₽</div>
              </th>
              <?php endforeach ?>
              <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;min-width:120px;white-space:nowrap">Итого</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($plots as $rowIdx => $pn):
              $rowReq = 0; $rowPaid = 0;
              foreach ($regularFeeTypes as $ft) {
                  $pay = $payMap[$ft['id']][$pn] ?? null;
                  $req = $reqFor($ft, $pay);
                  $rowReq  += max(0, $req);
                  $rowPaid += (float)($pay['paid_amount'] ?? 0);
              }
              // Итоговый статус строки
              $rAllPaid = true; $rAnyPaid = false; $rHas = false;
              foreach ($regularFeeTypes as $ft) {
                  $pay = $payMap[$ft['id']][$pn] ?? null;
                  $req = $reqFor($ft, $pay);
                  if ($req <= 0) continue;
                  $rHas = true;
                  $st = $pay['status'] ?? 'unpaid';
                  if ($st !== 'paid') $rAllPaid = false;
                  if ($st === 'paid' || ($pay['paid_amount'] ?? 0) > 0) $rAnyPaid = true;
              }
              $rowSt = $rHas ? ($rAllPaid ? 'paid' : ($rAnyPaid ? 'partial' : 'unpaid')) : 'unpaid';
              $rowClr = match($rowSt) { 'paid' => 'var(--good)', 'partial' => '#c8860a', default => 'var(--ink-mute)' };
            ?>
            <tr style="background:<?= $rowIdx % 2 === 0 ? 'transparent' : 'var(--fog)' ?>;border-bottom:1px solid var(--line-soft)">
              <td style="padding:8px 14px;vertical-align:middle">
                <span class="mono" style="font-weight:700;font-size:14px">No<?= e($pn) ?></span>
              </td>
              <td style="padding:8px 14px;vertical-align:middle">
                <span style="font-size:13px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:140px"><?= e($plotNames[$pn] ?? '—') ?></span>
              </td>
              <?php foreach ($regularFeeTypes as $ft):
                $ftId = (int)$ft['id'];
                $pay  = $payMap[$ftId][$pn] ?? null;
                $req  = $reqFor($ft, $pay);
                $paid = (float)($pay['paid_amount'] ?? 0);
                $st   = $pay['status'] ?? 'unpaid';
                $cellId = "cell-{$ftId}-{$pn}";
              ?>
              <td id="<?= $cellId ?>" style="padding:8px 12px;text-align:center;vertical-align:middle">
                <?php if ($req <= 0 && $st === 'unpaid'): /* адресный взнос не начислен этому участку */ ?>
                <span style="font-size:13px;color:var(--ink-mute);opacity:.5" title="Взнос не начислен этому участку">—</span>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                  <span class="cell-badge" style="font-size:11px;padding:2px 9px;border-radius:20px;background:<?= $stBg($st) ?>;color:<?= $stColor($st) ?>;font-weight:600;white-space:nowrap;border:1px solid <?= $st === 'unpaid' ? 'var(--line-soft)' : 'transparent' ?>">
                    <?= $statusLabel($st) ?>
                  </span>
                  <span class="cell-amount mono" style="font-size:11px;color:<?= $st === 'paid' ? 'var(--good)' : ($st === 'partial' ? '#c8860a' : 'var(--ink-mute)') ?>;white-space:nowrap">
                    <?php if ($st === 'partial'): ?>
                      <?= number_format($paid, 0, '.', ' ') ?> / <?= number_format($req, 0, '.', ' ') ?> ₽
                    <?php else: ?>
                      <?= number_format($req, 0, '.', ' ') ?> ₽
                    <?php endif ?>
                  </span>
                  <div style="display:flex;gap:4px;align-items:center;margin-top:2px">
                    <?php if ($st !== 'paid'): ?>
                    <button
                      onclick="openPartialModal(<?= $ftId ?>, '<?= e($pn) ?>', <?= $req ?>, <?= $paid ?>, '<?= e($ft['name']) ?>')"
                      class="btn ghost sm cell-partial-btn"
                      style="font-size:11px;padding:3px 8px;display:<?= $st !== 'paid' ? 'inline-flex' : 'none' ?>"
                      title="Частичное погашение">₽ частично</button>
                    <?php endif ?>
                    <button
                      onclick="togglePay(this, <?= $ftId ?>, '<?= e($pn) ?>', <?= $req ?>)"
                      class="btn sm cell-toggle-btn <?= $st === 'paid' ? 'primary' : 'ghost' ?>"
                      data-status="<?= $st ?>"
                      data-paid="<?= $paid ?>"
                      title="<?= $st === 'paid' ? 'Снять оплату' : 'Оплачено полностью' ?>"
                      style="width:28px;height:28px;padding:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                      <?php if ($st === 'paid'): ?>
                        <svg width="12" height="12" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      <?php else: ?>
                        <svg width="11" height="11" fill="none" stroke="var(--ink-mute)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                      <?php endif ?>
                    </button>
                  </div>
                </div>
                <?php endif ?>
              </td>
              <?php endforeach ?>
              <!-- Итого по строке -->
              <td id="rowtot-<?= e($pn) ?>" style="padding:8px 14px;text-align:right;vertical-align:middle;white-space:nowrap">
                <div style="font-weight:700;font-size:13px;color:<?= $rowClr ?>"><?= number_format($rowPaid, 0, '.', ' ') ?> ₽</div>
                <?php if ($rowReq > $rowPaid): ?>
                <div style="font-size:11px;color:var(--ink-mute)">из <?= number_format($rowReq, 0, '.', ' ') ?> ₽</div>
                <?php endif ?>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--fog);border-top:2px solid var(--line)">
              <td colspan="2" style="padding:10px 14px;font-weight:700;font-size:12px;color:var(--ink-mute)">ИТОГО · <?= $PLOT_TOTAL ?> участков</td>
              <?php
              $grandReq = 0; $grandPaid = 0;
              foreach ($regularFeeTypes as $ft):
                  $ftId = (int)$ft['id'];
                  $ftColl = 0; $ftReq = 0; $ftPaidN = 0;
                  foreach ($plots as $pn) {
                      $pay = $payMap[$ftId][$pn] ?? null;
                      $req = $reqFor($ft, $pay);
                      $p   = (float)($pay['paid_amount'] ?? 0);
                      $ftColl += $p;
                      $ftReq  += max(0, $req);
                      if (($pay['status'] ?? '') === 'paid') $ftPaidN++;
                  }
                  $grandReq  += $ftReq;
                  $grandPaid += $ftColl;
              ?>
              <td id="tfoot-ft-<?= $ftId ?>" style="padding:10px 12px;text-align:center">
                <div style="font-weight:700;font-size:13px"><?= number_format($ftColl, 0, '.', ' ') ?> ₽</div>
                <div style="font-size:11px;color:var(--ink-mute)">из <?= number_format($ftReq, 0, '.', ' ') ?> ₽</div>
                <div style="font-size:11px;color:var(--good);margin-top:2px"><?= $ftPaidN ?> оплатило</div>
              </td>
              <?php endforeach ?>
              <td id="tfoot-grand" style="padding:10px 14px;text-align:right">
                <div style="font-weight:700;font-size:13px"><?= number_format($grandPaid, 0, '.', ' ') ?> ₽</div>
                <div style="font-size:11px;color:var(--ink-mute)">из <?= number_format($grandReq, 0, '.', ' ') ?> ₽</div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Данные для real-time обновления виджетов взносов -->
      <script>
      window.regStats = {
        expenses: <?= json_encode(round($totalExpenses, 2)) ?>,
        elecCollected: <?= json_encode(round($elecCollected, 2)) ?>,
        pays: {
          <?php foreach ($regularFeeTypes as $ft): $ftId = (int)$ft['id']; ?>
          <?= $ftId ?>: {<?php foreach ($plots as $pn):
            $pay  = $payMap[$ftId][$pn] ?? null;
            $req  = $reqFor($ft, $pay);
            $paid = (float)($pay['paid_amount'] ?? 0);
            $st   = $pay['status'] ?? 'unpaid';
          ?>"<?= $pn ?>":{st:<?= json_encode($st) ?>,paid:<?= json_encode(round($paid,2)) ?>,req:<?= json_encode(round(max(0,$req),2)) ?>},<?php endforeach ?>},
          <?php endforeach ?>
        }
      };
      </script>
    <?php endif ?>

  <!-- ══ ТАБ: СВЕТ ════════════════════════════════════════════════ -->
  <?php elseif ($activeTab === 'electricity'): ?>

    <!-- Импорт из Excel -->
    <div class="card" style="padding:20px 24px;margin-bottom:20px;max-width:700px">
      <details>
        <summary style="font-weight:600;cursor:pointer;font-size:15px;color:var(--accent-deep);list-style:none;display:flex;align-items:center;gap:8px">
          <span style="font-size:18px">📊</span> Импортировать из файла начислений (.xlsx)
        </summary>
        <div style="margin-top:14px">
          <p style="font-size:13px;color:var(--ink-mute);margin:0 0 12px">
            Загрузите стандартный файл начислений за электроэнергию. Название счёта из строки 1, суммы из столбца «Итого к оплате». Участки не в файле = 0 руб.
          </p>
          <form method="POST" action="/admin/finances/elec-import" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="year" value="<?= (int)$year ?>">
            <div class="field" style="max-width:400px;margin-bottom:12px">
              <label>Файл Excel (.xlsx)</label>
              <input type="file" name="xlsx_file" accept=".xlsx" required
                     style="width:100%;border:1px solid var(--line,#d1d5db);border-radius:8px;padding:8px 10px;font-size:13px;background:var(--paper);color:var(--ink)">
            </div>
            <button type="submit" class="btn primary sm">⬆ Импортировать</button>
          </form>
        </div>
      </details>
    </div>

    <?php if (empty($elecFeeTypes)): ?>
      <div class="card" style="padding:40px;text-align:center;color:var(--ink-mute)">
        <div style="font-size:48px;margin-bottom:12px">⚡</div>
        <h3 style="color:var(--ink-soft);margin-bottom:8px">Счета за свет не загружены</h3>
        <p>Импортируйте файл начислений выше или создайте счёт вручную.</p>
      </div>
    <?php else: ?>

      <!-- ══ Виджет сводки по электроэнергии ══ -->
      <?php
      $wTotalReq = 0; $wTotalColl = 0; $wAllPaidPlots = 0; $wTotalAppl = 0;
      $monthRows = [];
      foreach ($elecFeeTypes as $mft) {
          $mId = (int)$mft['id'];
          $mLabel = trim(preg_replace('/свет\s*/iu', '', $mft['name'])) ?: $mft['name'];
          $mReq = 0; $mColl = 0; $mPaid = 0; $mPartial = 0; $mAppl = 0;
          foreach ($plots as $pn) {
              $pay = $payMap[$mId][$pn] ?? null;
              $req = (float)($pay['required_amount'] ?? 0);
              if ($req <= 0) continue;
              $mAppl++;
              $mReq  += $req;
              $mColl += (float)($pay['paid_amount'] ?? 0);
              $st = $pay['status'] ?? 'unpaid';
              if ($st === 'paid') $mPaid++;
              elseif ($st === 'partial') $mPartial++;
          }
          $mPct = $mReq  > 0 ? round($mColl  / $mReq  * 100) : 0;
          $wTotalReq   += $mReq;
          $wTotalColl  += $mColl;
          $wAllPaidPlots += $mPaid;
          $wTotalAppl  += $mAppl;
          $monthRows[] = compact('mId','mLabel','mReq','mColl','mPaid','mPartial','mAppl','mPct');
      }
      $wPct      = $wTotalReq  > 0 ? round($wTotalColl    / $wTotalReq  * 100) : 0;
      $wPctCount = $wTotalAppl > 0 ? round($wAllPaidPlots / $wTotalAppl * 100) : 0;
      ?>
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px">
        <div style="padding:14px 20px;background:linear-gradient(135deg,#fffbe6,#fff3cd);border-bottom:2px solid #ffe08a;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:22px">⚡</span>
            <div>
              <div style="font-weight:700;font-size:15px;color:var(--ink)">Электроэнергия <?= (int)$year ?></div>
              <div style="font-size:12px;color:var(--ink-mute);margin-top:1px">
                <?= count($elecFeeTypes) ?> месяца · начислено <?= number_format($wTotalReq, 0, '.', ' ') ?> ₽
              </div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="text-align:right">
              <div id="elec-w-coll" style="font-weight:700;font-size:18px;color:#c8860a"><?= number_format($wTotalColl, 0, '.', ' ') ?> ₽</div>
              <div style="font-size:11px;color:var(--ink-mute)">собрано из <?= number_format($wTotalReq, 0, '.', ' ') ?> ₽</div>
            </div>
            <div style="width:140px">
              <div style="background:var(--line-soft);border-radius:99px;height:8px;overflow:hidden">
                <div id="elec-w-pct-bar" style="width:<?= $wPct ?>%;height:100%;background:<?= $wPct >= 80 ? 'var(--good)' : '#c8860a' ?>;border-radius:99px;transition:width .3s"></div>
              </div>
              <div id="elec-w-pct-text" style="font-size:11px;color:var(--ink-mute);text-align:right;margin-top:2px" class="mono"><?= $wPct ?>% оплачено</div>
            </div>
          </div>
        </div>
        <!-- Таблица по месяцам -->
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--fog)">
              <th style="padding:8px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Месяц</th>
              <th style="padding:8px 12px;text-align:right;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Начислено</th>
              <th style="padding:8px 12px;text-align:right;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Собрано</th>
              <th style="padding:8px 12px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Оплатили</th>
              <th style="padding:8px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;min-width:120px">Прогресс</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($monthRows as $ri => $mr): ?>
            <tr id="elec-wr-<?= $mr['mId'] ?>"
                style="border-top:1px solid var(--line-soft);background:<?= $ri % 2 === 0 ? 'transparent' : 'var(--fog)' ?>;cursor:pointer"
                onclick="document.getElementById('elec-card-<?= $mr['mId'] ?>').scrollIntoView({behavior:'smooth',block:'start'});toggleElecCard(<?= $mr['mId'] ?>, true)"
                title="Перейти к <?= e($mr['mLabel']) ?>">
              <td style="padding:9px 16px;font-weight:600"><?= e($mr['mLabel']) ?></td>
              <td style="padding:9px 12px;text-align:right" class="mono"><?= number_format($mr['mReq'], 0, '.', ' ') ?> ₽</td>
              <td style="padding:9px 12px;text-align:right;color:<?= $mr['mColl'] > 0 ? '#c8860a' : 'var(--ink-mute)' ?>" class="mono">
                <?= $mr['mColl'] > 0 ? number_format($mr['mColl'], 0, '.', ' ') . ' ₽' : '—' ?>
              </td>
              <td style="padding:9px 12px;text-align:center;font-size:12px">
                <span style="color:var(--good);font-weight:600"><?= $mr['mPaid'] ?></span>
                <?php if ($mr['mPartial'] > 0): ?> + <span style="color:#c8860a"><?= $mr['mPartial'] ?></span><?php endif ?>
                <span style="color:var(--ink-mute)"> / <?= $mr['mAppl'] ?></span>
              </td>
              <td style="padding:9px 16px">
                <?php if ($mr['mAppl'] > 0): ?>
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="flex:1;background:var(--line-soft);border-radius:99px;height:5px;overflow:hidden">
                    <div style="width:<?= $mr['mPct'] ?>%;height:100%;background:<?= $mr['mPct'] >= 80 ? 'var(--good)' : '#c8860a' ?>;border-radius:99px"></div>
                  </div>
                  <span class="mono" style="font-size:11px;color:var(--ink-mute);white-space:nowrap"><?= $mr['mPct'] ?>%</span>
                </div>
                <?php else: ?>
                  <span style="font-size:11px;color:var(--warn)">не начислено</span>
                <?php endif ?>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
          <tfoot>
            <tr id="elec-wt" style="border-top:2px solid var(--line);background:var(--fog)">
              <td style="padding:9px 16px;font-weight:700;font-size:12px;color:var(--ink-mute)">ИТОГО</td>
              <td style="padding:9px 12px;text-align:right;font-weight:700" class="mono"><?= number_format($wTotalReq, 0, '.', ' ') ?> ₽</td>
              <td id="elec-wt-coll" style="padding:9px 12px;text-align:right;font-weight:700;color:#c8860a" class="mono"><?= number_format($wTotalColl, 0, '.', ' ') ?> ₽</td>
              <td id="elec-wt-paid" style="padding:9px 12px;text-align:center;font-size:12px;font-weight:700"><?= $wAllPaidPlots ?> / <?= $wTotalAppl ?></td>
              <td id="elec-wt-pct" style="padding:9px 16px">
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="flex:1;background:var(--line-soft);border-radius:99px;height:5px;overflow:hidden">
                    <div style="width:<?= $wPct ?>%;height:100%;background:<?= $wPct >= 80 ? 'var(--good)' : '#c8860a' ?>;border-radius:99px"></div>
                  </div>
                  <span class="mono" style="font-size:11px;font-weight:700;color:var(--ink-mute)"><?= $wPct ?>%</span>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Данные для real-time обновления виджета -->
      <script>
      window.elecStats = {
        <?php foreach ($monthRows as $mr): ?>
        <?= $mr['mId'] ?>: { req:<?= (int)$mr['mReq'] ?>, coll:<?= (int)$mr['mColl'] ?>, paid:<?= $mr['mPaid'] ?>, partial:<?= $mr['mPartial'] ?>, appl:<?= $mr['mAppl'] ?> },
        <?php endforeach ?>
      };
      </script>

      <?php foreach ($elecFeeTypes as $ft):
        $ftId       = (int)$ft['id'];
        $ftName     = $ft['name'];
        $monthLabel = trim(preg_replace('/свет\s*/iu', '', $ftName)) ?: $ftName;

        $paidCnt = 0; $partialCnt = 0; $applicable = 0; $collected = 0; $totalRequired = 0;
        $hasAmounts = false;
        foreach ($plots as $pn) {
            $pay = $payMap[$ftId][$pn] ?? null;
            $req = (float)($pay['required_amount'] ?? 0);
            if ($req <= 0) continue;
            $applicable++;
            $hasAmounts = true;
            $totalRequired += $req;
            $st = $pay['status'] ?? 'unpaid';
            $paid = (float)($pay['paid_amount'] ?? 0);
            if ($st === 'paid') { $paidCnt++; $collected += $paid; }
            elseif ($st === 'partial') { $partialCnt++; $collected += $paid; }
        }
        $pct     = $totalRequired > 0 ? round($collected / $totalRequired * 100) : 0;
        $allPaid = $applicable > 0 && $paidCnt === $applicable;
        $bcolor  = $allPaid ? 'var(--good)' : ($applicable > 0 ? '#c8860a' : 'var(--ink-mute)');
      ?>
      <div id="elec-card-<?= $ftId ?>" class="card" style="overflow:hidden;margin-bottom:24px;border-left:4px solid <?= $bcolor ?>">

        <!-- Шапка месяца — кликабельный аккордеон -->
        <div onclick="toggleElecCard(<?= $ftId ?>)"
             style="padding:16px 20px;background:var(--bg-warm);border-bottom:1px solid var(--line-soft);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;cursor:pointer;user-select:none"
             title="Развернуть / свернуть">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="font-size:22px"><?= $allPaid ? '✅' : '⚡' ?></div>
            <div>
              <div style="font-weight:700;font-size:16px;color:var(--ink)"><?= e($monthLabel) ?></div>
              <?php if ($applicable > 0): ?>
                <div style="font-size:12px;color:var(--ink-mute);margin-top:2px">
                  оплатили <?= $paidCnt ?>/<?= $applicable ?> ·
                  <?php if ($partialCnt > 0): ?> частично <?= $partialCnt ?> · <?php endif ?>
                  собрано <?= number_format($collected, 0, '.', ' ') ?> из <?= number_format($totalRequired, 0, '.', ' ') ?> ₽
                </div>
              <?php else: ?>
                <div style="font-size:12px;color:var(--warn);margin-top:2px">⚠ Суммы не введены</div>
              <?php endif ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px" onclick="event.stopPropagation()">
            <?php if ($applicable > 0): ?>
              <div style="width:120px">
                <div style="background:var(--line-soft);border-radius:999px;height:6px;overflow:hidden">
                  <div style="width:<?= $pct ?>%;height:100%;background:<?= $bcolor ?>;border-radius:999px"></div>
                </div>
                <div style="font-size:11px;color:var(--ink-mute);text-align:right;margin-top:2px" class="mono"><?= $pct ?>%</div>
              </div>
            <?php endif ?>
            <form method="POST" action="/admin/finances?year=<?= (int)$year ?>&tab=electricity"
                  onsubmit="return confirm('Удалить счёт «<?= e($ftName) ?>» и все данные?')" style="display:inline">
              <input type="hidden" name="action" value="delete_fee_type">
              <input type="hidden" name="fee_type_id" value="<?= $ftId ?>">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
              <button type="submit" class="btn danger sm">Удалить</button>
            </form>
            <!-- Шеврон -->
            <div id="elec-chevron-<?= $ftId ?>" style="width:28px;height:28px;border-radius:50%;background:var(--fog);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .25s;pointer-events:none">
              <svg id="elec-chv-svg-<?= $ftId ?>" width="12" height="12" fill="none" stroke="var(--ink-mute)" viewBox="0 0 24 24" style="transition:transform .25s;transform:rotate(0deg)">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Тело аккордеона (скрывается/показывается) -->
        <div id="elec-body-<?= $ftId ?>" style="display:<?= $hasAmounts ? 'none' : 'block' ?>">

        <!-- Ввод/редактирование сумм -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--line-soft);background:var(--fog)">
          <details <?= !$hasAmounts ? 'open' : '' ?>>
            <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--accent-deep);user-select:none">
              <?= $hasAmounts ? '✏ Редактировать суммы по участкам' : '📝 Ввести суммы по участкам' ?>
            </summary>
            <div style="margin-top:12px">
              <div style="font-size:12px;color:var(--ink-mute);margin-bottom:10px">
                Введите сумму для каждого участка. Без потребления — оставьте 0.
              </div>
              <div id="elec-grid-<?= $ftId ?>" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:6px;margin-bottom:10px">
                <?php foreach ($plots as $pn):
                  $pay = $payMap[$ftId][$pn] ?? null;
                  $req = (float)($pay['required_amount'] ?? 0);
                  $nm  = $plotNames[$pn] ?? '';
                ?>
                <div style="display:flex;align-items:center;gap:6px;background:var(--paper);border:1px solid var(--line-soft);border-radius:8px;padding:6px 8px">
                  <div style="font-size:12px;font-weight:600;color:var(--ink);min-width:24px">№<?= $pn ?></div>
                  <input type="number"
                    id="ea-<?= $ftId ?>-<?= $pn ?>"
                    value="<?= $req > 0 ? number_format($req, 2, '.', '') : '' ?>"
                    min="0" step="0.01" placeholder="0"
                    style="width:100%;border:1px solid var(--line);border-radius:6px;padding:4px 6px;font-size:12px;font-family:var(--font-mono,'JetBrains Mono',monospace)"
                    title="<?= e($nm) ?>">
                </div>
                <?php endforeach ?>
              </div>
              <div style="display:flex;align-items:center;gap:12px">
                <button class="btn primary sm" onclick="saveElecAmounts(<?= $ftId ?>)">Сохранить суммы</button>
                <span id="elec-msg-<?= $ftId ?>" style="font-size:13px;color:var(--ink-mute)"></span>
              </div>
            </div>
          </details>
        </div>

        <!-- Сводная таблица по месяцу -->
        <?php if ($hasAmounts): ?>
          <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
              <thead>
                <tr style="background:var(--fog);border-bottom:2px solid var(--line)">
                  <th style="padding:8px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">No</th>
                  <th style="padding:8px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;min-width:120px">Участник</th>
                  <th style="padding:8px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Статус</th>
                  <th style="padding:8px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Начислено</th>
                  <th style="padding:8px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em">Оплачено</th>
                  <th style="padding:8px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-mute);text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Действие</th>
                </tr>
              </thead>
              <tbody>
                <?php $rowE = 0; foreach ($plots as $pn):
                  $pay = $payMap[$ftId][$pn] ?? null;
                  $req = (float)($pay['required_amount'] ?? 0);
                  if ($req <= 0) continue;
                  $st   = $pay['status'] ?? 'unpaid';
                  $paid = (float)($pay['paid_amount'] ?? 0);
                  $nm   = $plotNames[$pn] ?? '—';
                  $cellId = "cell-{$ftId}-{$pn}";
                ?>
                <tr style="background:<?= $rowE++ % 2 === 0 ? 'transparent' : 'var(--fog)' ?>;border-bottom:1px solid var(--line-soft)">
                  <td style="padding:8px 14px;vertical-align:middle"><span class="mono" style="font-weight:700;font-size:14px">No<?= e($pn) ?></span></td>
                  <td style="padding:8px 14px;vertical-align:middle;font-size:13px;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($nm) ?></td>
                  <td id="<?= $cellId ?>" style="padding:8px 12px;text-align:center;vertical-align:middle">
                    <span class="cell-badge" style="font-size:11px;padding:2px 9px;border-radius:20px;background:<?= $stBg($st) ?>;color:<?= $stColor($st) ?>;font-weight:600;border:1px solid <?= $st === 'unpaid' ? 'var(--line-soft)' : 'transparent' ?>">
                      <?= $statusLabel($st) ?>
                    </span>
                  </td>
                  <td style="padding:8px 12px;text-align:center;vertical-align:middle">
                    <span class="mono" style="font-size:13px"><?= number_format($req, 2, '.', ' ') ?> ₽</span>
                  </td>
                  <td style="padding:8px 12px;text-align:center;vertical-align:middle">
                    <span class="mono" style="font-size:13px;color:<?= $st === 'partial' ? '#c8860a' : ($st === 'paid' ? 'var(--good)' : 'var(--ink-mute)') ?>">
                      <?= $st !== 'unpaid' ? number_format($paid, 2, '.', ' ') . ' ₽' : '—' ?>
                    </span>
                  </td>
                  <td style="padding:8px 12px;text-align:center;vertical-align:middle">
                    <div style="display:flex;gap:5px;align-items:center;justify-content:center">
                      <?php if ($st !== 'paid'): ?>
                      <button
                        onclick="openPartialModal(<?= $ftId ?>, '<?= e($pn) ?>', <?= $req ?>, <?= $paid ?>, '<?= e($ftName) ?> №<?= e($pn) ?>')"
                        class="btn ghost sm"
                        style="font-size:11px;padding:3px 8px"
                        title="Частичное погашение">₽ частично</button>
                      <?php endif ?>
                      <button
                        onclick="togglePay(this, <?= $ftId ?>, '<?= e($pn) ?>', <?= $req ?>)"
                        class="btn sm <?= $st === 'paid' ? 'primary' : 'ghost' ?>"
                        data-status="<?= $st ?>"
                        data-paid="<?= $paid ?>"
                        title="<?= $st === 'paid' ? 'Снять оплату' : 'Оплачено полностью' ?>"
                        style="width:28px;height:28px;padding:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                        <?php if ($st === 'paid'): ?>
                          <svg width="12" height="12" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <?php else: ?>
                          <svg width="11" height="11" fill="none" stroke="var(--ink-mute)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <?php endif ?>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div style="padding:24px;text-align:center;color:var(--ink-mute);font-size:13px">
            Сначала введите суммы по участкам ↑
          </div>
        <?php endif ?>
        </div><!-- /elec-body -->
      </div><!-- /card -->
      <?php endforeach ?>
    <?php endif ?>

  <!-- ══ ТАБ: РАСХОДЫ ═════════════════════════════════════════════ -->
  <?php elseif ($activeTab === 'expenses'): ?>

    <div class="card" style="padding:24px;margin-bottom:24px;max-width:620px">
      <h3 style="margin:0 0 16px">Добавить расход</h3>
      <div class="grid-2" style="gap:12px;margin-bottom:12px">
        <div class="field">
          <label>Описание</label>
          <input type="text" id="exp-desc" placeholder="На что потрачено">
        </div>
        <div class="field">
          <label>Сумма (₽)</label>
          <input type="number" id="exp-amount" min="1" step="0.01" placeholder="5000">
        </div>
      </div>
      <div class="field" style="margin-bottom:12px">
        <label>Категория взноса <span style="font-weight:400;color:var(--ink-mute)">(из какого фонда трата)</span></label>
        <select id="exp-fee-type" style="width:100%;border:1px solid var(--line);border-radius:8px;padding:8px 12px;font-size:14px;color:var(--ink);background:var(--paper)">
          <option value="">— Не привязана к категории</option>
          <?php
          $allRegularFeeTypes = array_values(array_filter($feeTypes, fn($ft) => mb_stripos($ft['name'], 'свет') === false));
          foreach ($allRegularFeeTypes as $ft):
          ?>
          <option value="<?= (int)$ft['id'] ?>"><?= e($ft['name']) ?> (<?= number_format((float)$ft['amount'], 0, '.', ' ') ?> ₽)</option>
          <?php endforeach ?>
        </select>
      </div>
      <!-- Прикрепить документ -->
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink-soft);margin-bottom:6px">
          Документ <span style="font-weight:400;color:var(--ink-mute)">(необязательно — квитанция, договор, фото, PDF…)</span>
        </label>
        <label id="exp-file-label" style="display:flex;align-items:center;gap:10px;border:2px dashed var(--line);border-radius:10px;padding:12px 16px;cursor:pointer;background:var(--fog);transition:border-color .2s"
               onmouseenter="this.style.borderColor='var(--accent)'" onmouseleave="this.style.borderColor='var(--line)'">
          <svg width="20" height="20" fill="none" stroke="var(--ink-mute)" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
          </svg>
          <span id="exp-file-text" style="font-size:13px;color:var(--ink-mute)">Нажмите или перетащите файл сюда</span>
          <input type="file" id="exp-attachment" name="attachment" style="display:none"
                 accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.jpg,.jpeg,.png,.gif,.webp,.heic,.bmp,.tiff"
                 onchange="onExpFileChange(this)">
        </label>
        <div id="exp-file-preview" style="display:none;margin-top:8px;padding:8px 12px;background:var(--paper);border:1px solid var(--line);border-radius:8px;display:none;align-items:center;gap:10px;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px;min-width:0">
            <span id="exp-file-icon" style="font-size:20px;flex-shrink:0">📎</span>
            <div style="min-width:0">
              <div id="exp-file-name" style="font-size:13px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px"></div>
              <div id="exp-file-size" style="font-size:11px;color:var(--ink-mute)"></div>
            </div>
          </div>
          <button type="button" onclick="clearExpFile()" style="border:none;background:transparent;cursor:pointer;color:var(--ink-mute);padding:2px 4px;flex-shrink:0" title="Удалить">✕</button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px">
        <button class="btn primary" onclick="addExpense()">Добавить расход</button>
        <span id="exp-msg" style="font-size:13px;color:var(--ink-mute)"></span>
      </div>
    </div>

    <?php if (empty($expenses)): ?>
      <div class="card" style="padding:40px;text-align:center;color:var(--ink-mute)">
        <div style="font-size:36px;margin-bottom:12px">📋</div>
        <p>Расходов за <?= (int)$year ?> год пока нет</p>
      </div>
    <?php else: ?>
      <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:14px 20px;background:var(--bg-warm);border-bottom:1px solid var(--line-soft);display:flex;justify-content:space-between;align-items:center">
          <h3 style="margin:0">Расходы <?= (int)$year ?></h3>
          <span style="font-size:14px;color:var(--bad);font-weight:600">Итого: <?= number_format($totalExpenses, 0, '.', ' ') ?> ₽</span>
        </div>
        <div id="expenses-list">
          <?php foreach ($expenses as $ex):
            $ext = $ex['attachment_name'] ? strtolower(pathinfo($ex['attachment_name'], PATHINFO_EXTENSION)) : '';
            $fileIcon = match(true) {
              in_array($ext, ['jpg','jpeg','png','gif','webp','heic','bmp','tiff']) => '🖼',
              in_array($ext, ['pdf'])                                               => '📕',
              in_array($ext, ['doc','docx','odt'])                                 => '📄',
              in_array($ext, ['xls','xlsx','ods'])                                 => '📊',
              $ext !== ''                                                           => '📎',
              default                                                               => '',
            };
          ?>
          <div style="padding:14px 20px;border-bottom:1px solid var(--line-soft);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:0">
              <div style="font-weight:500;color:var(--ink);font-size:14px"><?= e($ex['description'] ?? '—') ?></div>
              <div style="display:flex;align-items:center;gap:10px;margin-top:4px;flex-wrap:wrap">
                <span style="font-size:11px;color:var(--ink-mute)"><?= !empty($ex['created_at']) ? date('d.m.Y', strtotime($ex['created_at'])) : '' ?></span>
                <?php if (!empty($ex['fee_type_name'])): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;background:#fffbe6;border:1px solid #ffe08a;border-radius:6px;padding:1px 7px;color:#a06c00;font-weight:600">
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h4"/></svg>
                    <?= e($ex['fee_type_name']) ?>
                  </span>
                <?php endif ?>
                <?php if (!empty($ex['attachment_path'])): ?>
                  <a href="/download/expense?file=<?= e(basename($ex['attachment_path'])) ?>" target="_blank" download="<?= e($ex['attachment_name'] ?? 'document') ?>"
                     style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--accent-deep);text-decoration:none;background:var(--fog);border:1px solid var(--line);border-radius:6px;padding:2px 8px"
                     title="Открыть вложение">
                    <span><?= $fileIcon ?></span>
                    <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($ex['attachment_name']) ?></span>
                    <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                  </a>
                <?php endif ?>
              </div>
            </div>
            <div style="font-size:16px;font-weight:700;color:var(--bad);font-family:var(--font-serif,serif)">
              <?= number_format((float)$ex['amount'], 0, '.', ' ') ?> ₽
            </div>
          </div>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>

  <?php endif ?>
</div>

<!-- ══ Модалка частичного погашения ══════════════════════════════ -->
<div id="partial-overlay" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center" onclick="closePartialModal()">
  <div style="background:var(--paper);border-radius:16px;padding:28px;width:340px;box-shadow:0 8px 40px rgba(0,0,0,.18)" onclick="event.stopPropagation()">
    <div style="margin-bottom:16px">
      <div class="mono" id="pm-plot-label" style="color:var(--ink-mute);font-size:11px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Участок</div>
      <div id="pm-fee-name" style="font-weight:700;font-size:15px"></div>
      <div id="pm-required-label" style="color:var(--ink-mute);font-size:13px;margin-top:4px"></div>
    </div>
    <div class="field" style="margin-bottom:12px">
      <label>Сумма оплаты (₽)</label>
      <input type="number" id="pm-amount" min="1" step="0.01" placeholder="Введите сумму">
    </div>
    <div class="field" style="margin-bottom:20px">
      <label>Примечание (необязательно)</label>
      <input type="text" id="pm-notes" placeholder="Например: квитанция №123">
    </div>
    <div style="display:flex;gap:10px">
      <button class="btn primary" id="pm-submit" style="flex:1" onclick="submitPartialPay()">Сохранить</button>
      <button class="btn ghost" onclick="closePartialModal()">Отмена</button>
    </div>
    <div id="pm-error" style="font-size:13px;color:var(--bad);margin-top:10px;display:none"></div>
  </div>
</div>

<!-- ══ JavaScript ═════════════════════════════════════════════════ -->
<script>
const CSRF = <?= json_encode($csrfToken) ?>;

// ── Выбор участков при создании вида взноса ────────────────────
function toggleScopePicker() {
  const sel = document.querySelector('input[name="plot_scope"]:checked');
  const picker = document.getElementById('scope-picker');
  if (!picker || !sel) return;
  picker.style.display = sel.value === 'selected' ? 'block' : 'none';
  updateScopeCount();
}
function scopeAll(on) {
  document.querySelectorAll('.scope-cb').forEach(cb => cb.checked = on);
  updateScopeCount();
}
function updateScopeCount() {
  const n = document.querySelectorAll('.scope-cb:checked').length;
  const el = document.getElementById('scope-count');
  if (el) el.textContent = 'выбрано: ' + n;
}

// ── Состояние модалки частичного погашения ──────────────────────
let _pmFeeTypeId = null, _pmPlotNumber = null, _pmRequired = 0, _pmCurrentPaid = 0;

function openPartialModal(feeTypeId, plotNumber, required, current, feeName) {
  _pmFeeTypeId   = feeTypeId;
  _pmPlotNumber  = plotNumber;
  _pmRequired    = required;
  _pmCurrentPaid = current || 0;
  document.getElementById('pm-plot-label').textContent = 'Участок No' + plotNumber;
  document.getElementById('pm-fee-name').textContent = feeName;
  document.getElementById('pm-required-label').textContent =
    'Начислено: ' + required.toLocaleString('ru-RU', {maximumFractionDigits:2}) + ' ₽' +
    (current > 0 ? ' · уже оплачено: ' + current.toLocaleString('ru-RU', {maximumFractionDigits:2}) + ' ₽' : '');
  document.getElementById('pm-amount').value = current > 0 ? current : '';
  document.getElementById('pm-notes').value  = '';
  document.getElementById('pm-error').style.display = 'none';
  const overlay = document.getElementById('partial-overlay');
  overlay.style.display = 'flex';
  setTimeout(() => document.getElementById('pm-amount').focus(), 50);
}

function closePartialModal() {
  document.getElementById('partial-overlay').style.display = 'none';
}

async function submitPartialPay() {
  const amount = parseFloat(document.getElementById('pm-amount').value);
  const notes  = document.getElementById('pm-notes').value.trim();
  const errDiv = document.getElementById('pm-error');

  if (!amount || amount <= 0) {
    errDiv.textContent = 'Введите сумму больше нуля';
    errDiv.style.display = 'block';
    return;
  }

  const btn = document.getElementById('pm-submit');
  btn.disabled = true;
  btn.textContent = 'Сохраняем...';
  errDiv.style.display = 'none';

  try {
    const isFullPay = amount >= _pmRequired;
    const res = await fetch('/admin/finances/pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token:   CSRF,
        fee_type_id:  _pmFeeTypeId,
        plot_number:  _pmPlotNumber,
        status:       isFullPay ? 'paid' : 'partial',
        paid_amount:  amount,
        notes:        notes,
      }),
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Ошибка');

    const newStatus = isFullPay ? 'paid' : 'partial';
    refreshCell(_pmFeeTypeId, _pmPlotNumber, newStatus, amount, _pmRequired);
    refreshElecWidget(_pmFeeTypeId, _pmCurrentPaid > 0 ? 'partial' : 'unpaid', _pmCurrentPaid, newStatus, amount, _pmRequired);
    refreshRegularWidgets(_pmFeeTypeId, _pmPlotNumber, newStatus, amount);
    closePartialModal();
  } catch(e) {
    errDiv.textContent = 'Ошибка: ' + e.message;
    errDiv.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Сохранить';
  }
}

// ── Обновление ячейки таблицы после изменения статуса ──────────
function refreshCell(feeTypeId, plotNumber, newStatus, paidAmount, requiredAmount) {
  const cell = document.getElementById('cell-' + feeTypeId + '-' + plotNumber);
  if (!cell) return;

  const stLabelMap  = { paid: 'Оплачено', partial: 'Частично', unpaid: 'Не оплачено' };
  const stBgMap     = { paid: '#e6efd5',  partial: '#fff3cd',  unpaid: 'transparent' };
  const stColorMap  = { paid: 'var(--good)', partial: '#c8860a', unpaid: 'var(--bad)' };
  const stBorderMap = { paid: 'transparent', partial: 'transparent', unpaid: 'var(--line-soft)' };

  // Обновляем бейдж статуса
  const badge = cell.querySelector('.cell-badge');
  if (badge) {
    badge.textContent = stLabelMap[newStatus] || newStatus;
    badge.style.background  = stBgMap[newStatus]   || 'transparent';
    badge.style.color       = stColorMap[newStatus] || 'var(--ink)';
    badge.style.borderColor = stBorderMap[newStatus]|| 'var(--line-soft)';
  }

  // Обновляем сумму
  const amtSpan = cell.querySelector('.cell-amount');
  if (amtSpan) {
    if (newStatus === 'partial') {
      amtSpan.textContent = fmt(paidAmount) + ' / ' + fmt(requiredAmount) + ' ₽';
      amtSpan.style.color = '#c8860a';
    } else {
      amtSpan.textContent = fmt(requiredAmount) + ' ₽';
      amtSpan.style.color = newStatus === 'paid' ? 'var(--good)' : 'var(--ink-mute)';
    }
  }

  // Обновляем кнопки
  const toggleBtn = cell.querySelector('.cell-toggle-btn');
  if (toggleBtn) {
    toggleBtn.dataset.status = newStatus;
    if (newStatus === 'paid') {
      toggleBtn.className = 'btn sm cell-toggle-btn primary';
      toggleBtn.title = 'Снять оплату';
      toggleBtn.innerHTML = '<svg width="12" height="12" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
    } else {
      toggleBtn.className = 'btn sm cell-toggle-btn ghost';
      toggleBtn.title = 'Оплачено полностью';
      toggleBtn.innerHTML = '<svg width="11" height="11" fill="none" stroke="var(--ink-mute)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    }
  }

  // Кнопка «частично» — показываем только если не paid
  const partialBtn = cell.querySelector('.cell-partial-btn');
  if (partialBtn) {
    partialBtn.style.display = newStatus !== 'paid' ? 'inline-flex' : 'none';
  }

  // Обновляем data-paid на кнопке для следующего вызова
  if (toggleBtn) toggleBtn.dataset.paid = String(paidAmount);

  // Мигание фона
  const origBg = cell.style.background || '';
  cell.style.background = newStatus === 'paid' ? '#e6faf0' : '#fff3f3';
  setTimeout(() => { cell.style.background = origBg; }, 700);
}

function fmt(n) {
  return n.toLocaleString('ru-RU', { maximumFractionDigits: 0 });
}

// ── Real-time обновление виджетов взносов (KPI, разбивка, сбор) ──
function refreshRegularWidgets(ftId, plotNumber, newStatus, newPaid) {
  if (!window.regStats || !window.regStats.pays[ftId]) return;
  const pays = window.regStats.pays;

  // 1. Обновляем модель
  const entry = pays[ftId][plotNumber];
  if (!entry) return;
  entry.st   = newStatus;
  entry.paid = newPaid;

  const pctColor = p => p >= 80 ? 'var(--good)' : (p < 50 ? 'var(--warn)' : 'var(--accent)');

  // 2. Пересчёт по виду взноса → «По видам взносов» + tfoot
  let regularCollected = 0;
  for (const fid in pays) {
    let ftColl = 0, ftReq = 0, ftPaidN = 0;
    for (const pn in pays[fid]) {
      const p = pays[fid][pn];
      ftColl += p.paid;
      ftReq  += p.req;
      if (p.st === 'paid') ftPaidN++;
    }
    regularCollected += ftColl;
    const ftPct = ftReq > 0 ? Math.round(ftColl / ftReq * 100) : 0;

    const stEl = document.getElementById('ft-st-' + fid);
    if (stEl) stEl.textContent = fmt(ftColl) + ' ₽ из ' + fmt(ftReq) + ' ₽';
    const pctEl = document.getElementById('ft-pct-' + fid);
    if (pctEl) pctEl.textContent = ftPct + '%';
    const barEl = document.getElementById('ft-bar-' + fid);
    if (barEl) { barEl.style.width = Math.min(100, ftPct) + '%'; barEl.style.background = pctColor(ftPct); }

    const tfEl = document.getElementById('tfoot-ft-' + fid);
    if (tfEl) tfEl.innerHTML =
      '<div style="font-weight:700;font-size:13px">' + fmt(ftColl) + ' ₽</div>' +
      '<div style="font-size:11px;color:var(--ink-mute)">из ' + fmt(ftReq) + ' ₽</div>' +
      '<div style="font-size:11px;color:var(--good);margin-top:2px">' + ftPaidN + ' оплатило</div>';
  }

  // 3. Пересчёт по участкам → KPI-карточки + «Сбор» + строки «Итого»
  let stPaid = 0, stPartial = 0, stTotal = 0, subColl = 0, subReq = 0;
  const plotSet = {};
  for (const fid in pays) for (const pn in pays[fid]) plotSet[pn] = true;
  for (const pn in plotSet) {
    let hasAppl = false, allPaid = true, anyPaid = false, rowPaid = 0, rowReq = 0;
    for (const fid in pays) {
      const p = pays[fid][pn];
      if (!p || p.req <= 0) continue;
      hasAppl = true;
      rowPaid += p.paid;
      rowReq  += p.req;
      subColl += p.paid;
      subReq  += p.req;
      if (p.st !== 'paid') allPaid = false;
      if (p.st === 'paid' || p.paid > 0) anyPaid = true;
    }
    if (!hasAppl) continue;
    stTotal++;
    const rowSt = allPaid ? 'paid' : (anyPaid ? 'partial' : 'unpaid');
    if (rowSt === 'paid') stPaid++;
    else if (rowSt === 'partial') stPartial++;

    // Итого по строке участка
    const rowTot = document.getElementById('rowtot-' + pn);
    if (rowTot) {
      const rowClr = rowSt === 'paid' ? 'var(--good)' : (rowSt === 'partial' ? '#c8860a' : 'var(--ink-mute)');
      rowTot.innerHTML =
        '<div style="font-weight:700;font-size:13px;color:' + rowClr + '">' + fmt(rowPaid) + ' ₽</div>' +
        (rowReq > rowPaid ? '<div style="font-size:11px;color:var(--ink-mute)">из ' + fmt(rowReq) + ' ₽</div>' : '');
    }
  }
  const stUnpaid = stTotal - stPaid - stPartial;
  const pct = stTotal > 0 ? Math.round(stPaid / stTotal * 100) : 0;

  const set = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };
  set('kpi-st-paid', stPaid);
  set('kpi-st-paid-delta', 'из ' + stTotal + ' участков');
  set('kpi-st-partial', stPartial);
  set('kpi-st-unpaid', stUnpaid);
  set('kpi-coll', fmt(subColl));
  set('kpi-coll-delta', 'из ' + fmt(subReq) + ' ₽');
  set('collect-txt', stPaid + ' / ' + stTotal + ' · ' + pct + '%');
  const cBar = document.getElementById('collect-bar');
  if (cBar) { cBar.style.width = pct + '%'; cBar.style.background = pctColor(pct); }

  // 4. Общий итог таблицы
  const grand = document.getElementById('tfoot-grand');
  if (grand) grand.innerHTML =
    '<div style="font-weight:700;font-size:13px">' + fmt(subColl) + ' ₽</div>' +
    '<div style="font-size:11px;color:var(--ink-mute)">из ' + fmt(subReq) + ' ₽</div>';

  // 5. Верхние KPI: Целевые взносы + Баланс
  set('kpi-reg-coll', fmt(regularCollected) + ' ₽');
  const balance = regularCollected - window.regStats.expenses;
  const balEl = document.getElementById('kpi-balance');
  if (balEl) {
    balEl.textContent = (balance >= 0 ? '+' : '') + fmt(balance) + ' ₽';
    balEl.style.color = balance >= 0 ? 'var(--accent)' : 'var(--bad)';
  }
  set('kpi-balance-delta', balance >= 0 ? 'профицит' : 'дефицит');
}

// ── Real-time обновление виджета электроэнергии ────────────────
function refreshElecWidget(ftId, oldStatus, oldPaid, newStatus, newPaid, required) {
  if (!window.elecStats || !window.elecStats[ftId]) return;

  const s = window.elecStats[ftId];

  // Обновляем счётчики
  s.coll = Math.max(0, s.coll + newPaid - oldPaid);
  if (oldStatus === 'paid')    s.paid    = Math.max(0, s.paid - 1);
  if (oldStatus === 'partial') s.partial = Math.max(0, s.partial - 1);
  if (newStatus === 'paid')    s.paid++;
  if (newStatus === 'partial') s.partial++;

  const pct      = s.req  > 0 ? Math.round(s.coll / s.req  * 100) : 0;
  const pctColor = pct >= 80 ? 'var(--good)' : '#c8860a';

  // Обновляем строку месяца
  const row = document.getElementById('elec-wr-' + ftId);
  if (row) {
    const cells = row.querySelectorAll('td');
    // cells[2] = Собрано
    if (cells[2]) {
      cells[2].style.color = s.coll > 0 ? '#c8860a' : 'var(--ink-mute)';
      cells[2].textContent = s.coll > 0 ? fmt(s.coll) + ' ₽' : '—';
    }
    // cells[3] = Оплатили
    if (cells[3]) {
      cells[3].innerHTML =
        `<span style="color:var(--good);font-weight:600">${s.paid}</span>` +
        (s.partial > 0 ? ` + <span style="color:#c8860a">${s.partial}</span>` : '') +
        ` <span style="color:var(--ink-mute)">/ ${s.appl}</span>`;
    }
    // cells[4] = Прогресс
    if (cells[4]) {
      cells[4].innerHTML = s.appl > 0
        ? `<div style="display:flex;align-items:center;gap:8px">
             <div style="flex:1;background:var(--line-soft);border-radius:99px;height:5px;overflow:hidden">
               <div style="width:${pct}%;height:100%;background:${pctColor};border-radius:99px;transition:width .3s"></div>
             </div>
             <span class="mono" style="font-size:11px;color:var(--ink-mute);white-space:nowrap">${pct}%</span>
           </div>`
        : '<span style="font-size:11px;color:var(--warn)">не начислено</span>';
    }
  }

  // Пересчитываем итоги по всем месяцам
  let totalColl = 0, totalPaid = 0, totalAppl = 0, totalReq = 0;
  Object.values(window.elecStats).forEach(ms => {
    totalColl += ms.coll;
    totalPaid += ms.paid;
    totalAppl += ms.appl;
    totalReq  += ms.req;
  });
  const totalPct      = totalReq  > 0 ? Math.round(totalColl  / totalReq  * 100) : 0;
  const totalPctColor = totalPct >= 80 ? 'var(--good)' : '#c8860a';
  const totalPctCount      = totalAppl > 0 ? Math.round(totalPaid / totalAppl * 100) : 0;
  const totalPctCountColor = totalPctCount >= 80 ? 'var(--good)' : '#c8860a';

  // Итоговая строка
  const wtColl = document.getElementById('elec-wt-coll');
  if (wtColl) wtColl.textContent = fmt(totalColl) + ' ₽';
  const wtPaid = document.getElementById('elec-wt-paid');
  if (wtPaid) wtPaid.textContent = totalPaid + ' / ' + totalAppl;
  const wtPct = document.getElementById('elec-wt-pct');
  if (wtPct) {
    wtPct.innerHTML =
      `<div style="display:flex;align-items:center;gap:8px">
         <div style="flex:1;background:var(--line-soft);border-radius:99px;height:5px;overflow:hidden">
           <div style="width:${totalPct}%;height:100%;background:${totalPctColor};border-radius:99px;transition:width .3s"></div>
         </div>
         <span class="mono" style="font-size:11px;font-weight:700;color:var(--ink-mute)">${totalPct}%</span>
       </div>`;
  }

  // Шапка виджета
  const hColl = document.getElementById('elec-w-coll');
  if (hColl) hColl.textContent = fmt(totalColl) + ' ₽';
  const hBar = document.getElementById('elec-w-pct-bar');
  if (hBar) { hBar.style.width = totalPct + '%'; hBar.style.background = totalPctColor; }
  const hText = document.getElementById('elec-w-pct-text');
  if (hText) hText.textContent = totalPct + '% оплачено';
}

// ── Аккордеон карточки месяца ─────────────────────────────────
function toggleElecCard(ftId, forceOpen) {
  const body    = document.getElementById('elec-body-' + ftId);
  const svg     = document.getElementById('elec-chv-svg-' + ftId);
  if (!body) return;
  const isOpen  = body.style.display !== 'none';
  const open    = forceOpen !== undefined ? forceOpen : !isOpen;
  body.style.display = open ? 'block' : 'none';
  if (svg) svg.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}

// ── Полная оплата / сброс ──────────────────────────────────────
async function togglePay(btn, feeTypeId, plotNumber, feeAmount) {
  const currentStatus = btn.dataset.status || 'unpaid';
  const oldPaid       = parseFloat(btn.dataset.paid || 0);
  const newStatus     = currentStatus === 'paid' ? 'unpaid' : 'paid';
  const paidAmount    = newStatus === 'paid' ? feeAmount : 0;

  btn.disabled = true;
  btn.style.opacity = '.5';

  try {
    const res = await fetch('/admin/finances/pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        csrf_token:  CSRF,
        fee_type_id: feeTypeId,
        plot_number: plotNumber,
        status:      newStatus,
        paid_amount: paidAmount,
      }),
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Ошибка');

    refreshCell(feeTypeId, plotNumber, newStatus, paidAmount, feeAmount);
    refreshElecWidget(feeTypeId, currentStatus, oldPaid, newStatus, paidAmount, feeAmount);
    refreshRegularWidgets(feeTypeId, plotNumber, newStatus, paidAmount);
  } catch(e) {
    alert('Ошибка: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.style.opacity = '';
  }
}

// ── Вложение к расходу ─────────────────────────────────────────
function onExpFileChange(input) {
  const file    = input.files[0];
  const preview = document.getElementById('exp-file-preview');
  const nameEl  = document.getElementById('exp-file-name');
  const sizeEl  = document.getElementById('exp-file-size');
  const iconEl  = document.getElementById('exp-file-icon');
  const textEl  = document.getElementById('exp-file-text');
  if (!file) { clearExpFile(); return; }

  const ext = file.name.split('.').pop().toLowerCase();
  const iconMap = {
    pdf: '📕', doc: '📄', docx: '📄', odt: '📄',
    xls: '📊', xlsx: '📊', ods: '📊',
    jpg: '🖼', jpeg: '🖼', png: '🖼', gif: '🖼', webp: '🖼', heic: '🖼',
  };
  iconEl.textContent = iconMap[ext] || '📎';
  nameEl.textContent = file.name;
  sizeEl.textContent = file.size < 1024*1024
    ? (file.size / 1024).toFixed(0) + ' КБ'
    : (file.size / 1024 / 1024).toFixed(1) + ' МБ';
  preview.style.display = 'flex';
  textEl.textContent = '✓ Файл выбран';
  textEl.style.color = 'var(--good)';
}

function clearExpFile() {
  const input = document.getElementById('exp-attachment');
  input.value = '';
  document.getElementById('exp-file-preview').style.display = 'none';
  document.getElementById('exp-file-text').textContent = 'Нажмите или перетащите файл сюда';
  document.getElementById('exp-file-text').style.color = 'var(--ink-mute)';
}

// ── Добавить расход ────────────────────────────────────────────
async function addExpense() {
  const desc       = document.getElementById('exp-desc').value.trim();
  const amount     = parseFloat(document.getElementById('exp-amount').value);
  const file       = document.getElementById('exp-attachment').files[0];
  const feeTypeSel = document.getElementById('exp-fee-type');
  const feeTypeId  = feeTypeSel ? feeTypeSel.value : '';
  const msg        = document.getElementById('exp-msg');

  if (!desc || !amount || amount <= 0) {
    msg.style.color = 'var(--bad)';
    msg.textContent = 'Введите описание и сумму';
    return;
  }

  msg.style.color = 'var(--ink-mute)';
  msg.textContent = file ? 'Загружаем файл...' : 'Сохраняем...';

  const fd = new FormData();
  fd.append('csrf_token',  CSRF);
  fd.append('description', desc);
  fd.append('amount',      amount);
  if (feeTypeId) fd.append('fee_type_id', feeTypeId);
  if (file) fd.append('attachment', file);

  try {
    const res  = await fetch('/admin/finances/expense', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Ошибка');

    msg.style.color = 'var(--good)';
    msg.textContent = 'Сохранено!';
    document.getElementById('exp-desc').value   = '';
    document.getElementById('exp-amount').value = '';
    if (feeTypeSel) feeTypeSel.value = '';
    clearExpFile();

    const list = document.getElementById('expenses-list');
    if (!list) {
      // Первый расход: списка ещё нет в DOM (вместо него карточка-заглушка) — перерисовываем страницу
      msg.textContent = 'Сохранено! Обновляем...';
      location.reload();
      return;
    }
    if (list) {
      const today = new Date().toLocaleDateString('ru-RU');
      const ext   = file ? file.name.split('.').pop().toLowerCase() : '';
      const iconMap = {pdf:'📕',doc:'📄',docx:'📄',odt:'📄',xls:'📊',xlsx:'📊',ods:'📊',
                       jpg:'🖼',jpeg:'🖼',png:'🖼',gif:'🖼',webp:'🖼',heic:'🖼'};
      const fileHtml = data.attachment_path
        ? `<a href="/download/expense?file=${escHtml(data.attachment_path.replace(/^.*[\\/]/, ''))}" target="_blank" download="${escHtml(data.attachment_name || 'document')}"
              style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--accent-deep);text-decoration:none;background:var(--fog);border:1px solid var(--line);border-radius:6px;padding:2px 8px">
             <span>${iconMap[ext] || '📎'}</span>
             <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(data.attachment_name || '')}</span>
           </a>`
        : '';
      const categoryHtml = data.fee_type_name
        ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;background:#fffbe6;border:1px solid #ffe08a;border-radius:6px;padding:1px 7px;color:#a06c00;font-weight:600">
             <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h10M7 17h4"/></svg>
             ${escHtml(data.fee_type_name)}
           </span>`
        : '';
      const row = document.createElement('div');
      row.style.cssText = 'padding:14px 20px;border-bottom:1px solid var(--line-soft);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:#fff8f0';
      row.innerHTML = `
        <div style="flex:1;min-width:0">
          <div style="font-weight:500;color:var(--ink);font-size:14px">${escHtml(desc)}</div>
          <div style="display:flex;align-items:center;gap:10px;margin-top:4px;flex-wrap:wrap">
            <span style="font-size:11px;color:var(--ink-mute)">${today}</span>
            ${categoryHtml}
            ${fileHtml}
          </div>
        </div>
        <div style="font-size:16px;font-weight:700;color:var(--bad)">${amount.toLocaleString('ru-RU')} ₽</div>
      `;
      list.prepend(row);
      setTimeout(() => row.style.background = '', 1000);
    }
    setTimeout(() => msg.textContent = '', 3000);
  } catch(e) {
    msg.style.color = 'var(--bad)';
    msg.textContent = 'Ошибка: ' + e.message;
  }
}

// ── Сохранить суммы за свет ────────────────────────────────────
async function saveElecAmounts(feeTypeId) {
  const msg = document.getElementById('elec-msg-' + feeTypeId);
  msg.style.color = 'var(--ink-mute)';
  msg.textContent = 'Сохраняем...';

  const amounts = {};
  const grid = document.getElementById('elec-grid-' + feeTypeId);
  if (!grid) return;
  grid.querySelectorAll('input[type="number"]').forEach(inp => {
    const parts = inp.id.split('-');
    const plotNumber = parts[parts.length - 1];
    amounts[plotNumber] = parseFloat(inp.value) || 0;
  });

  try {
    const res = await fetch('/admin/finances/elec-amount', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_token: CSRF, fee_type_id: feeTypeId, amounts }),
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Ошибка');

    msg.style.color = 'var(--good)';
    msg.textContent = `Сохранено ${data.saved} участков`;
    setTimeout(() => { msg.textContent = ''; location.reload(); }, 1200);
  } catch(e) {
    msg.style.color = 'var(--bad)';
    msg.textContent = 'Ошибка: ' + e.message;
  }
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Закрытие модалки по Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closePartialModal();
});
</script>
