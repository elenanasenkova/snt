<?php
/**
 * Member Protocols & Documents
 * Variables: $documents
 */
?>
<div class="panel fade-up">
    <h2 style="margin-top:0; margin-bottom:24px;">Протоколы и документы</h2>

    <?php if (empty($documents)): ?>
        <div style="text-align:center; padding:48px 0 32px;">
            <div style="margin-bottom:12px;">
                <span class="pill warn">Раздел в наполнении</span>
            </div>
            <p style="color:var(--ink-mute); margin:0;">Протоколы собраний и решения правления будут опубликованы администрацией. Следите за объявлениями.</p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($documents as $doc): ?>
                <?php
                $fileName = basename($doc['file_path'] ?? '');
                $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $isPdf    = $ext === 'pdf';
                $icon     = $isPdf ? '📄' : '📎';
                $docDate  = !empty($doc['created_at'])
                    ? date('d.m.Y', strtotime($doc['created_at']))
                    : '—';
                ?>
                <div class="card doc" style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:0;">
                        <div style="font-size:28px; flex-shrink:0;" aria-hidden="true"><?= $icon ?></div>
                        <div style="min-width:0;">
                            <div style="font-weight:600; margin-bottom:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= e($doc['title'] ?? $fileName ?: 'Документ') ?>
                            </div>
                            <div style="font-size:13px; color:var(--ink-mute);">
                                <?php if (!empty($doc['category'])): ?>
                                    <span class="pill" style="margin-right:6px;"><?= e($doc['category']) ?></span>
                                <?php endif; ?>
                                <?= $docDate ?>
                                <?php if ($isPdf): ?>
                                    &nbsp;&middot;&nbsp;<span style="color:var(--ink-mute);">PDF</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($doc['file_path'])): ?>
                        <a class="btn ghost sm"
                           href="/uploads/documents/<?= e(rawurlencode($fileName)) ?>"
                           download
                           title="Скачать">
                            &#8595; Скачать
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
