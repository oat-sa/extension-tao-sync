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

    <div class="action-area">
        <div class="action-block terminate-action">

        </div>

        <form class="action-block sync-action sync-form" action="<?= get_data('form-action'); ?>">
                <section class="intro">
                    <p>
                        <?= __('Depending on the workload this process may take a while.') ?>
                    </p>
                </section>

                <section class="fb-container viewport-hidden">
                    <div class="feedback-info status-history">
                        <span class="icon-info"></span>
                        <div class="messages">
                            <p>
                                <span><?= __('Last synchronization has been completed at %s', '<time class="complete-time"></time>') ?>.</span>
                                <span> <?= __('Go to') ?> <a href="<?= get_data('dashboard-url'); ?>"><?= __('Synchronization History') ?></a>.</span>
                            </p>

                            <?php Template::inc(get_data('includeTemplate'), get_data('includeExtension')); ?>

                        </div>
                    </div>

                    <div class="feedback-error status-active">
                        <span class="icon-error"></span>
                        <div class="messages">
                            <p>
                                <?=  __('The data synchronization cannot be completed because there are active assessments in progress.') ?>
                            </p>
                        </div>
                    </div>

                    <div class="feedback-info status-progress">
                        <span class="icon-loop"></span>
                        <div class="messages">
                            <p>
                                <?= __('Synchronization in progress.') ?>
                                <br/>
                                <em><?= __('Note that all time data refer to the server time.') ?></em>
                            </p>
                            <p class="msg" data-type="enqueued">
                                <?= __('Task has been enqueued at %s', '<time class="enqueue-time"></time>') ?>
                            </p>
                            <p class="msg" data-type="updated">
                                <?= __('Last status check at %s', '<time class="update-time"></time>') ?>
                            </p>
                        </div>
                    </div>

                    <div class="feedback-success status-success">
                        <span class="icon-result-ok"></span>
                        <div class="messages">
                            <p>
                                <span><?= __('Success! The synchronization process has been finished.') ?></span>
                                <span> <?= __('Go to') ?> <a href="<?= get_data('dashboard-url'); ?>"><?= __('Synchronization History') ?></a>.</span>
                            </p>
                        </div>
                    </div>

                    <div class="feedback-error status-error">
                        <span class="icon-error"></span>
                        <div class="messages">
                            <p>
                                <span><?= __('The synchronization process has failed. Please contact the administrator.') ?></span>
                                <span> <?= __('Go to') ?> <a href="<?= get_data('dashboard-url'); ?>"><?= __('Synchronization History') ?></a>.</span>
                            </p>
                        </div>
                    </div>
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

                <button type="submit" class="btn-info" data-control="launch" disabled>
                    <span class="icon-loop"></span>
                    <?= __('Synchronize Data') ?>
                </button>

                <div id="dashboard-container"></div>

                <button class="btn-info" data-control="close">
                    <span class="icon-end-attempt"></span>
                    <?= __('Done') ?>
                </button>

            </form>
    </div>
</div>
