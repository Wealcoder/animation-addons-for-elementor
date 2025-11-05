/**
 * AAE Loop Builder - Template Query Control JavaScript
 */
(function($) {
    'use strict';

    class AAE_TemplateQueryControl extends elementor.modules.controls.Select2 {
        
        getDefaultSettings() {
            const settings = super.getDefaultSettings();
            
            return {
                ...settings,
                select2Options: {
                    ...settings.select2Options,
                    placeholder: 'Select Template',
                    allowClear: true
                }
            };
        }

        getSelect2Placeholder() {
            return {
                id: '',
                text: 'Select Template'
            };
        }

        onReady() {
            super.onReady();
            this.initTemplateActions();
            this.updateActionButtons();
        }

        initTemplateActions() {
            const $actions = this.$el.find('.elementor-control-template-query-actions');
            
            // Handle new template button
            $actions.on('click', '[data-action="new"]', (e) => {
                e.preventDefault();
                this.createNewTemplate();
            });

            // Handle edit template button
            $actions.on('click', '[data-action="edit"]', (e) => {
                e.preventDefault();
                this.editTemplate();
            });
        }

        onBaseInputChange() {
            super.onBaseInputChange(...arguments);
            this.updateActionButtons();
        }

        updateActionButtons() {
            const templateId = this.getControlValue();
            const $actions = this.$el.find('.elementor-control-template-query-actions');
            const $editButton = $actions.find('[data-action="edit"]');
            const $newButton = $actions.find('[data-action="new"]');
            const $emptyState = this.$el.find('.elementor-control-template_query-empty-state');

            if (templateId) {
                $editButton.show();
                $newButton.hide();
                $emptyState.hide();
            } else {
                $editButton.hide();
                $newButton.show();
                $emptyState.show();
            }
        }

        createNewTemplate() {
            // Show confirmation dialog
            const dialog = this.getCreateTemplateDialog();
            dialog.show();
        }

        getCreateTemplateDialog() {
            if (!this.createTemplateDialog) {
                this.createTemplateDialog = elementorCommon.dialogsManager.createWidget('confirm', {
                    id: 'aae-loop-builder-create-template',
                    headerMessage: 'Create Template',
                    message: 'Creating a new template will save your current work and switch to the template editor. Continue?',
                    position: {
                        my: 'center center',
                        at: 'center center'
                    },
                    strings: {
                        confirm: 'Create & Switch',
                        cancel: 'Cancel'
                    },
                    onConfirm: () => {
                        this.saveAndCreateTemplate();
                    }
                });
            }
            return this.createTemplateDialog;
        }

        saveAndCreateTemplate() {
            // Show loading state
            const $newButton = this.$el.find('[data-action="new"]');
            const originalText = $newButton.html();
            $newButton.html('<i class="eicon-loading eicon-animation-spin"></i> Creating...').prop('disabled', true);

            // First save the current page
            this.saveCurrentPage()
                .then(() => {
                    // Then create the template
                    return this.doCreateTemplate();
                })
                .catch((error) => {
                    console.error('Save and create failed:', error);
                    elementor.notifications.showToast({
                        message: 'Failed to save current work',
                        type: 'error'
                    });
                })
                .finally(() => {
                    $newButton.html(originalText).prop('disabled', false);
                });
        }

        saveCurrentPage() {
            return new Promise((resolve, reject) => {
                // Check if there are unsaved changes
                if (!this.hasUnsavedChanges()) {
                    resolve();
                    return;
                }

                // Save the current page
                if (typeof $e !== 'undefined' && $e.components) {
                    const documentSave = $e.components.get('document/save');
                    if (documentSave && documentSave.saveDraft) {
                        documentSave.saveDraft()
                            .then(() => {
                                elementor.notifications.showToast({
                                    message: 'Current work saved successfully!',
                                    type: 'success'
                                });
                                resolve();
                            })
                            .catch((error) => {
                                console.error('Save failed:', error);
                                reject(error);
                            });
                        return;
                    }
                }
                
                // Fallback to legacy API
                if (typeof elementor !== 'undefined' && elementor.saver && elementor.saver.saveDraft) {
                    elementor.saver.saveDraft()
                        .then(() => {
                            elementor.notifications.showToast({
                                message: 'Current work saved successfully!',
                                type: 'success'
                            });
                            resolve();
                        })
                        .catch((error) => {
                            console.error('Save failed:', error);
                            reject(error);
                        });
                } else {
                    console.warn('No save method available');
                    resolve();
                }
            });
        }

        hasUnsavedChanges() {
            // Check if Elementor has unsaved changes
            if (typeof $e !== 'undefined' && $e.components) {
                const documentSave = $e.components.get('document/save');
                if (documentSave && documentSave.isEditorChanged) {
                    return documentSave.isEditorChanged();
                }
            }
            
            // Fallback to legacy API
            if (typeof elementor !== 'undefined' && elementor.saver) {
                if (elementor.saver.isEditorChanged) {
                    return elementor.saver.isEditorChanged();
                }
            }
            
            return false;
        }

        doCreateTemplate() {
            // Check if required variables are available
            if (typeof aaeLoopBuilderTemplateQuery === 'undefined' || !aaeLoopBuilderTemplateQuery.ajax_url) {
                console.error('AAE Loop Builder Template Query variables not available');
                elementor.notifications.showToast({
                    message: 'Editor not properly initialized',
                    type: 'error'
                });
                return Promise.reject('Variables not available');
            }

            return new Promise((resolve, reject) => {
                // Get the source type
                const sourceType = this.getSourceType();
                
                // Create template via AJAX
                $.ajax({
                    url: aaeLoopBuilderTemplateQuery.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'create_loop_template',
                        nonce: aaeLoopBuilderTemplateQuery.nonce,
                        template_name: 'New Loop Template',
                        source_type: sourceType,
                        template_type: 'loop-builder'
                    }
                }).done((response) => {
                    if (response.success) {
                        // Add new template to select2 dropdown
                        const $select = this.$el.find('select');
                        const newOption = new Option(
                            response.data.template_name,
                            response.data.template_id,
                            true,
                            true
                        );
                        $select.append(newOption).trigger('change');
                        
                        // Set the new template ID in widget
                        this.setValue(response.data.template_id);
                        
                        // Update UI elements
                        this.updateActionButtons();
                        
                        // Show success message
                        if (elementor && elementor.notifications) {
                            elementor.notifications.showToast({
                                message: 'Template created successfully!',
                                type: 'success'
                            });
                        }
                        
                        resolve(response.data);
                    } else {
                        const errorMessage = response.data || 'Template creation failed';
                        console.error('Template creation error:', errorMessage);
                        elementor.notifications.showToast({
                            message: errorMessage,
                            type: 'error'
                        });
                        reject(errorMessage);
                    }
                }).fail((error) => {
                    console.error('Template creation failed:', error);
                    const errorMessage = error.responseJSON && error.responseJSON.data 
                        ? error.responseJSON.data 
                        : 'Failed to create template. Please try again.';
                    elementor.notifications.showToast({
                        message: errorMessage,
                        type: 'error'
                    });
                    reject(error);
                });
            });
        }

        editTemplate() {
            const templateId = this.getControlValue();
            if (templateId) {
                // Show loading indicator
                const $editButton = this.$el.find('[data-action="edit"]');
                const originalText = $editButton.html();
                $editButton.html('<i class="eicon-loading eicon-animation-spin"></i> Loading...').prop('disabled', true);
                
                // Save current document, then switch to template
                this.saveCurrentDocument()
                    .then(() => {
                        this.switchToTemplateDocumentWithoutReload(templateId);
                    })
                    .catch((error) => {
                        console.error('Save failed:', error);
                        // Still try to switch even if save fails
                        this.switchToTemplateDocumentWithoutReload(templateId);
                    })
                    .finally(() => {
                        setTimeout(() => {
                            $editButton.html(originalText).prop('disabled', false);
                        }, 500);
                    });
            }
        }

        switchToTemplateDocumentWithoutReload(templateId) {
            // Show notification
            if (elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: 'Switching to template editor...',
                    type: 'info'
                });
            }

            // Get current base document ID
            const baseId = elementor.config.document ? elementor.config.document.id : null;
            
            // Try to switch without reload
            this.performDocumentSwitch(templateId)
                .then(() => {
                    // Add active-document to URL
                    const url = new URL(window.location.href);
                    url.searchParams.set('active-document', String(templateId));
                    window.history.replaceState({}, '', url.toString());
                    
                    // Trigger template mode activation
                    $(document).trigger('clb:enter-template-mode', {
                        templateId: templateId,
                        baseId: baseId
                    });
                    
                    // Show success
                    if (elementor && elementor.notifications) {
                        elementor.notifications.showToast({
                            message: 'Template editor loaded',
                            type: 'success'
                        });
                    }
                })
                .catch((error) => {
                    console.error('No-reload switch failed, using page reload:', error);
                    // Fallback to page reload
                    this.openTemplateEditorWithReload(templateId);
                });
        }

        performDocumentSwitch(templateId) {
            return new Promise((resolve, reject) => {
                try {
                    // Use $e.run command - the modern Elementor way
                    if (window.$e && $e.run) {
                        
                        // Switch to template document
                        $e.run('editor/documents/switch', {
                            id: parseInt(templateId)
                        }).then(() => {
                            resolve();
                        }).catch((error) => {
                            reject(error);
                        });
                    } else {
                        reject('Modern API not available');
                    }
                } catch (error) {
                    console.error('Perform switch error:', error);
                    reject(error);
                }
            });
        }

        openTemplateEditorWithReload(templateId) {
            // Fallback: Use active-document parameter with page reload
            const currentPostId = elementor.config.document.id;
            const editUrl = `${window.location.origin}/wp-admin/post.php?post=${currentPostId}&action=elementor&active-document=${templateId}`;
            
            setTimeout(() => {
                window.location.href = editUrl;
            }, 300);
        }

        saveCurrentDocument() {
            return new Promise((resolve, reject) => {
                try {
                    // Method 1: Use modern Elementor API
                    if (window.$e && $e.components) {
                        const documentSave = $e.components.get('document/save');
                        if (documentSave && documentSave.save) {
                            documentSave.save({ status: 'publish' })
                                .then(() => {
                                    resolve();
                                })
                                .catch((error) => {
                                    resolve(); // Resolve anyway to continue.
                                });
                            return;
                        }
                    }
                    
                    // Method 2: Use legacy Elementor API
                    if (elementor && elementor.saver && elementor.saver.save) {
                        Promise.resolve(elementor.saver.save({ status: 'publish' }))
                            .then(() => {
                                resolve();
                            })
                            .catch((error) => {
                                resolve(); // Resolve anyway to continue.
                            });
                        return;
                    }
                    
                    // No save method available
                    console.warn('No save method available, continuing anyway');
                    resolve();
                    
                } catch (error) {
                    console.error('Save document error:', error);
                    resolve(); // Resolve anyway to continue
                }
            });
        }


        getSourceType() {
            // Get from current widget's settings
            try {
                if (this.container && this.container.model) {
                    const widgetSettings = this.container.model.get('settings');
                    if (widgetSettings && widgetSettings.source) {
                        return widgetSettings.source;
                    }
                }
                
                return 'post'; // Default fallback
            } catch (error) {
                console.warn('Could not determine source type, using default:', error);
                return 'post';
            }
        }

        applySavedValue() {
            super.applySavedValue();
            this.updateActionButtons();
        }
    }

    // Register the control
    if (typeof elementor !== 'undefined') {
        elementor.addControlView('template_query', AAE_TemplateQueryControl);
    } else {
        $(window).on('elementor:init', () => {
            elementor.addControlView('template_query', AAE_TemplateQueryControl);
        });
    }

})(jQuery);

