<section id="<?=get_data('id')?>-container">
    <header>
        <h1><?=get_data('title')?></h1>
    </header>
    <div>
        <div id="<?=get_data('id')?>-tree"></div>
    </div>
    <footer>
        <button id="saver-action-<?=get_data('id')?>" type="button" class="btn-info small"><span class="icon-save"></span> <?=__('Save')?></button>
    </footer>
</section>
<script>
    require(['jquery', 'i18n', 'generis.tree.select', 'ui/dialog'], function($, __, GenerisTreeSelectClass, dialog) {
        new GenerisTreeSelectClass('#<?=get_data('id')?>-tree', <?=json_encode(get_data('dataUrl'))?>, {
            actionId: <?=json_encode(get_data('id'))?>,
            saveUrl: <?=json_encode(get_data('saveUrl'))?>,
            saveData: {
                resourceUri: <?=json_encode(get_data('resourceUri'))?>,
                propertyUri: <?=json_encode(get_data('propertyUri'))?>,
            },
            checkedNodes: <?=json_encode(tao_helpers_Uri::encodeArray(get_data('values')))?>,
            hiddenNodes: <?=json_encode(get_data('hiddenNodes'))?>,
            serverParameters: {
                openNodes: <?=json_encode(get_data('openNodes'))?>,
                rootNode: <?=json_encode(get_data('rootNode'))?>,
            },
            saveCallback: function(toSend) {
                this.saveData[<?=json_encode(get_data('saveForcedParam'))?>] = 0;
            },
            saveErrorCallback: function(respopnse, instance) {
                if (respopnse.needApprove) {
                    dialog({
                        message: __("This user already assign to another test center"),
                        content: __("This user already assign to next test centers: <strong>"
                            +  respopnse.testCenters
                            + "</strong>. <p>Do you wont reassign him ? </p>"),
                        buttons: "yes,no",
                        autoRender: true,
                        autoDestroy: true,
                        onYesBtn: function() {
                            instance.options.saveData[<?=json_encode(get_data('saveForcedParam'))?>] = 1;
                            instance.saveData();
                        }
                    });
                    return true;
                }
            },
            paginate: 10
        });
    });
</script>
