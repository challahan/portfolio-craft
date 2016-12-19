<?php

use craft\app\debug\DeprecatedPanel;
use craft\app\models\DeprecationError;

/** @var $panel DeprecatedPanel */
/** @var $caption string */
/** @var $logs DeprecationError[] */
?>

<?php if (!empty($caption)): ?>
    <h3><?= $caption ?></h3>
<?php endif; ?>

<?php if (empty($logs)): ?>
    <p>No deprecation errors were logged.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-condensed table-bordered table-striped table-hover" style="table-layout: fixed;">
            <thead>
            <tr>
                <th style="nowrap">Error Message</th>
                <th>Origin</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlentities($log->message, null, 'UTF-8') ?></td>
                    <td><?= htmlentities($log->getOrigin(), null, 'UTF-8') ?> – <a href="<?= $panel->getUrl().'&trace='.$log->id ?>">StackTrace</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
