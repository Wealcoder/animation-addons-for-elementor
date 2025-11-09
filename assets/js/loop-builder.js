(function ($) {
    $(document).on('click', '.aae-loop-builder__box-cta', function(e) {
        e.preventDefault();
        openCustomDialog();
    });

    function openCustomDialog() {
        if (!this.myCustomDialog) {
            this.myCustomDialog = elementorCommon.dialogsManager.createWidget('confirm', {
                id: 'my-custom-dialog',
                headerMessage: 'Save Changes',
                message: 'Would you like to save the changes you\'ve made?',
                position: {
                    my: 'center center',
                    at: 'center center'
                },
                strings: {
                    confirm: 'Save',
                    cancel: 'Cancel',
                },
                onConfirm: async () => {
                    await onConfirmCreateTemplate();
                }
            });
        }

        this.myCustomDialog.show();
    }

    async function onConfirmCreateTemplate() {
        const $e = elementorCommon.elements.$e;
        $e.internal('panel/state-loading');  // show spinner

        try {
            await $e.internal('document/save'); // save current edits
            console.log('Document saved successfully!');
        } finally {
            $e.internal('panel/state-ready'); // hide spinner
        }
        // $e.internal('panel/state-loading');
        // const templateID = await createAndSetTemplate();
        // this.afterAction('new', templateID);
        // $e.internal('panel/state-ready');
    }
    async function createAndSetTemplate() {
        const controlId = this.model.get('name'),
            newTemplateType = this.options.container.controls[controlId].actions.new.document_config.type,
            newTemplateSource = getTemplateSourceTypeValue(),
            newTemplate = await $e.data.create('library/templates', {
                type: newTemplateType,
                page_settings: {
                    source: newTemplateSource
                }
            }),
            templateID = parseInt(newTemplate.data.template_id);
        this.setValue(templateID);
        return templateID;
    }
    function getTemplateSourceTypeValue() {
        if ('repeater' === this.options?.container?.args?.type) {
            return this.options.container.renderer.args.settings.attributes._skin || undefined;
        }
        return this.options.container.controls._skin ? this.options.container.panel.getControlView('_skin').getControlValue() : undefined;
    }

})(jQuery);

