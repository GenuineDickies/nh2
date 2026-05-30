<?php

use App\Core\View;

/** @var array $documents */
/** @var string|null $emptyMessage */
?>
<?php if (!$documents): ?>
    <p class="muted"><?= View::e($emptyMessage ?? 'No generated document records yet.') ?></p>
<?php else: ?>
    <div class="record-list">
        <?php foreach ($documents as $document): ?>
            <?php
                $version = (int) ($document['version'] ?? 1);
                $isCurrent = empty($document['superseded_at']);
                $versionLabel = 'v' . $version . ($isCurrent ? ' - Current' : '');
                $supersededNote = $isCurrent ? '' : ' - Superseded ' . $document['superseded_at'];
                $meta = ucwords(str_replace('_', ' ', (string) ($document['document_type'] ?? $document['status'] ?? '')))
                    . ' at ' . ($document['generated_at'] ?? '');
            ?>
            <div class="record-row document-row<?= $isCurrent ? '' : ' document-row-superseded' ?>">
                <?php if (!empty($document['file_path'])): ?>
                    <a class="document-link" href="/documents/<?= (int) $document['id'] ?>/download" target="_blank" rel="noopener">
                        <strong><?= View::e($document['document_number'] . ' - ' . $document['title']) ?></strong>
                        <span><?= View::e($versionLabel . $supersededNote) ?></span>
                        <span class="muted"><?= View::e($meta) ?></span>
                    </a>
                    <?php if ($isCurrent): ?>
                        <form class="document-actions" method="post" action="/documents/<?= (int) $document['id'] ?>/regenerate">
                            <button class="secondary-action" type="submit">Regenerate</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="document-link">
                        <strong><?= View::e($document['document_number'] . ' - ' . $document['title']) ?></strong>
                        <span><?= View::e($versionLabel . ' - ' . ($document['status'] ?? 'placeholder')) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
