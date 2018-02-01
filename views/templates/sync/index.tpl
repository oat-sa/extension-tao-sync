<?php
use oat\tao\helpers\Template;
?>
<link rel="stylesheet" href="<?= Template::css('styles.css', 'taoSync') ?>"/>
<div class="tao-sync-container">
  <header class="section-header flex-container-full">
    <h2><?=__('Data Synchronization')?></h2>
  </header>
  <section class="button-bg">
    <p><?=__('Note, that this can be a lengthy process, please be patient!')?></p>
    <button type="button" class="btn-info">
      <span class="icon-loop"></span>
      <span class="initial"><?=__('Start Synchronization')?></span>
      <div class="progress">
        <p><?=__('Synchronization in Progress')?></p>
        <p><span><?=__('Process started at ')?><span> <span id="start-time"></span></p>
        <span><?=__('Last attempt ')?></span>
                    <span id="last-attempt"></span>
        </p>
      </div>
    </button>
  </section>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>
