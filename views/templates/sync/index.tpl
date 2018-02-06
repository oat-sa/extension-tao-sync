<?php

use oat\tao\helpers\Template;
use Jig\Utils\Element;

$formFields = get_data('form-fields');
?>
<link rel="stylesheet" href="<?= Template::css('styles.css', 'taoSync') ?>"/>
<div id="tao-sync-container">
  <header class="section-header flex-container-full">
    <h2><?= __('Data Synchronization') ?></h2>
  </header>

  <section class="action-area">
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

    <button type="button" class="btn-info" disabled>
      <span class="icon-loop"></span>
        <?= __('Synchronize Data') ?>
    </button>

    <section class="messages viewport-hidden">
      <div class="feedback-info">
        <span class="icon-loop spinner-icon"></span>
        <div class="messages">
          <p><?= __('The synchronization task been enqueued at ') ?><time id="start-time"></time>.</p>
          <p><?= __('Last server communication ') ?>
            <time id="last-attempt"></time> <?= __('seconds ago.') ?></p>
        </div>
      </div>

      <div class="feedback-success">
        <span class="icon-result-ok"></span>
        <div class="messages">
          <p><?= __('The synchronization process has been finished. You can now close the browser window.') ?></p>
        </div>
      </div>

      <div class="feedback-error error-failure">
        <span class="icon-error"></span>
        <div class="messages">
          <p><?= __('The synchronization process has failed. Please contact the administrator.') ?></p>
        </div>
      </div>

      <div class="feedback-error error-timeout">
        <span class="icon-error"></span>
        <div class="messages">
          <p><?= __('Failed to connect to the server. Please try again later.') ?></p>
        </div>
      </div>
    </section>


  </section>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>

