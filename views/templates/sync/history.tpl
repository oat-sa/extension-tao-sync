<?php
use oat\tao\helpers\Template;
?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('styles.css') ?>" />
<div class="sync-history-headings flex-container-full">
    <header>
        <h2><?= __('Synchronization History') ?></h2>
    </header>
</div>

<div id="sync-history" class="flex-container-full">
<div class="grid-row">
    <div class="col-12">
        <div class="sync-history-grid"></div>
    </div>
</div>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>