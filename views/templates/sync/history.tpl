<?php
use oat\tao\helpers\Template;
?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('styles.css') ?>" />

<div id="sync-history" class="data-container-wrapper flex-container-full">
    <div class="sync-history-headings">
        <header>
            <h2><?= __('Synchronization History') ?></h2>
        </header>
    </div>
    <div class="grid-row">
        <div class="col-12">
            <div class="sync-history-grid"></div>
        </div>
    </div>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>

<script>
    requirejs.config({
        config: {
            'taoSync/controller/SynchronizationHistory/index' : <?= json_encode(get_data('config')) ?>
        }
    });
</script>