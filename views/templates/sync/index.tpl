<?php

use oat\tao\helpers\Template;
use Jig\Utils\Element;
use oat\taoSync\controller\Synchronizer;

$formFields = get_data('form-fields');
?>
<link rel="stylesheet" href="<?= Template::css('styles.css', Synchronizer::EXTENSION_ID) ?>"/>
<div id="tao-sync-container">
  <header class="section-header flex-container-full">
    <h2><?= __('Data Synchronization') ?></h2>
  </header>

  <form class="action-area" action="<?= get_data('form-action'); ?>">
    <section class="intro">
      <p><?= __('Depending on the workload this process may take a while.') ?><br/><?= __('Please do not close or reload the browser window before you receive a notification that the process has been completed!') ?></p>
    </section>

      <?php if($formFields): ?>
        <fieldset class="custom-fields">
            <?php foreach($formFields as $formField): ?>
              <div class="custom-field">
                  <?php if(!empty($formField['label'])): ?>
                    <label for="<?= $formField['attributes']['id'] ?>"><?= $formField['label'] ?>
                        <?php if(!empty($formField['attributes']['required'])): ?>
                          <abbr title="<?= __('Required field') ?>">*</abbr>
                        <?php endif; ?>
                    </label>
                  <?php endif; ?>
                  <?= Element::{$formField['element']}($formField['attributes']) ?>
              </div>
            <?php endforeach; ?>
        </fieldset>
      <?php endif; ?>

    <button type="submit" class="btn-info" disabled>
      <span class="icon-loop"></span>
        <?= __('Synchronize Data') ?>
    </button>

    <section class="fb-container viewport-hidden">
      <div class="feedback-info">
        <span class="icon-loop"></span>
        <div class="messages">
          <p class="msg" data-type="progress"><?= __('Synchronization in progress') ?>.
            <em><?= __('Note that all time data refer to the server time') ?></em>.</p>
          <p class="msg" data-type="enqueued"><?= __('Task has been enqueued at ') ?><time id="enqueue-time"></time>.</p>
          <p class="msg" data-type="updated"><?= __('Last status check at ') ?><time id="update-time"></time></p>
        </div>
      </div>

      <div class="feedback-success">
        <span class="icon-result-ok"></span>
        <div class="messages">
          <p><?= __('The synchronization process has been finished.') ?></p>
        </div>
      </div>

      <div class="feedback-error">
        <span class="icon-error"></span>
        <div class="messages">
          <p><?= __('The synchronization process has failed. Please contact the administrator.') ?></p>
        </div>
      </div>
    </section>


  </form>
</div>

<?php Template::inc('footer.tpl', 'tao');
