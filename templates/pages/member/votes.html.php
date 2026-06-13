<?php
require_once BASE_PATH . '/src/helpers/csrf.php';
$userVotes = $userVotes ?? [];
?>
<div class="fade-up">
  <h2 style="margin-bottom:24px">Голосования</h2>

  <?php if (!empty($flash)): ?>
    <div class="flash-bar <?= e($flash['type']) ?>" style="margin-bottom:20px"><?= e($flash['msg']) ?></div>
  <?php endif ?>

  <?php if (empty($votes)): ?>
    <div style="text-align:center;padding:64px 0;color:var(--ink-mute)">Нет активных голосований.</div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:24px">
      <?php foreach ($votes as $vote):
        $voteId     = (int)($vote['id'] ?? 0);
        $hasVoted   = isset($userVotes[$voteId]);
        $options    = $vote['options'] ?? [];
        $totalVotes = array_sum(array_column($options, 'vote_count'));
      ?>
        <div class="card" style="padding:24px">
          <div style="margin-bottom:12px">
            <h3 style="margin:0 0 6px"><?= e($vote['title']) ?></h3>
            <?php if (!empty($vote['description'])): ?>
              <p style="color:var(--ink-soft);margin:0;line-height:1.6"><?= nl2br(e($vote['description'])) ?></p>
            <?php endif ?>
          </div>

          <?php if (!empty($vote['ended_at'])): ?>
            <div style="margin-bottom:16px">
              <span class="pill">До <?= date('d.m.Y', strtotime($vote['ended_at'])) ?></span>
            </div>
          <?php endif ?>

          <?php if ($hasVoted): ?>
            <div>
              <div style="font-size:13px;color:var(--ink-soft);margin-bottom:12px;font-weight:500">Вы уже проголосовали. Результаты:</div>
              <?php foreach ($options as $opt):
                $count = (int)($opt['vote_count'] ?? 0);
                $pct   = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
              ?>
                <div style="margin-bottom:12px">
                  <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:14px">
                    <span><?= e($opt['option_text']) ?></span>
                    <span class="mono" style="color:var(--ink-soft)"><?= $count ?> (<?= $pct ?>%)</span>
                  </div>
                  <div style="height:8px;background:var(--bg-warm);border-radius:4px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:var(--accent);border-radius:4px;transition:width .4s"></div>
                  </div>
                </div>
              <?php endforeach ?>
              <div style="font-size:12px;color:var(--ink-mute);margin-top:8px">Всего голосов: <?= $totalVotes ?></div>
            </div>
          <?php else: ?>
            <form method="POST" action="/votes">
              <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
              <input type="hidden" name="vote_id" value="<?= $voteId ?>">
              <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
                <?php foreach ($options as $opt): ?>
                  <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 14px;border-radius:8px;border:1px solid var(--line);transition:background .15s"
                         onmouseover="this.style.background='var(--bg-warm)'" onmouseout="this.style.background=''">
                    <input type="radio" name="option_id" value="<?= (int)$opt['id'] ?>" required>
                    <span><?= e($opt['option_text']) ?></span>
                  </label>
                <?php endforeach ?>
              </div>
              <button type="submit" class="btn primary">Проголосовать</button>
            </form>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
