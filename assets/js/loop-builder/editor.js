/**
 * AAE Loop Builder - Editor JavaScript
 * Enhanced implementation for template management
 */
(function($) {
    'use strict';

    class AAE_LoopBuilderEditor {
        constructor() {
            this.init();
            this.templateCache = new Map();
        }

        init() {
            $(window).on('elementor:init', () => {
                this.onElementorInit();
            });
        }

        onElementorInit() {
            this.bindEvents();
            this.initializePreviewSystem();
            
            // Register with Elementor's hook system
            elementor.hooks.addAction('panel/open_editor/widget/aae--loop-grid', (panel, model, view) => {
                this.onWidgetPanelOpen(panel, model, view);
            });

            elementor.hooks.addAction('panel/open_editor/widget/aae--loop-carousel', (panel, model, view) => {
                this.onWidgetPanelOpen(panel, model, view);
            });
        }

        bindEvents() {
            // Listen for template ID changes
            $(document).on('change', '.elementor-control-template_id select', (event) => {
                this.handleTemplateChange(event);
            });

            // Listen for Elementor's setting changes
            elementor.channels.data.on('change', this.onElementorSettingChange.bind(this));
        }

        onWidgetPanelOpen(panel, model, view) {
            // Initialize widget-specific functionality when panel opens
            setTimeout(() => {
                this.initializeWidgetControls(model);
            }, 100);
        }

        onElementorSettingChange(changedModel) {
            if (changedModel.get('widgetType') === 'aae--loop-grid' || 
                changedModel.get('widgetType') === 'aae--loop-carousel') {
                this.handleWidgetSettingChange(changedModel);
            }
        }

        handleWidgetSettingChange(model) {
            const settings = model.get('settings');
            const templateId = settings.get('template_id');
            
            if (templateId) {
                // Refresh preview when settings change
                this.refreshWidgetPreview(model);
            }
        }

        initializePreviewSystem() {
            // Enhanced preview system
            if (typeof elementor !== 'undefined' && elementor.modules && elementor.modules.layouts) {
                this.setupPreviewHooks();
            }
        }

        setupPreviewHooks() {
            // Add preview hooks for better integration
            elementor.on('preview:loaded', () => {
                this.onPreviewLoaded();
            });

            elementor.channels.editor.on('change:preview:mode', (mode) => {
                if (mode === 'preview') {
                    this.refreshAllLoopWidgets();
                }
            });
        }

        onPreviewLoaded() {
            // Initialize loop widgets in preview
            const $preview = $('#elementor-preview-iframe').contents();
            $preview.find('.custom-loop-container').each((index, element) => {
                this.initializeLoopWidget($(element));
            });
        }

        initializeWidgetControls(model) {
            const widgetType = model.get('widgetType');
            
            if (widgetType === 'aae--loop-grid' || widgetType === 'aae--loop-carousel') {
                // Add template management buttons
                this.addTemplateManagementUI(model);
            }
        }

        addTemplateManagementUI(model) {
            const $templateControl = $('.elementor-control-template_id');
            
            if ($templateControl.length && !$templateControl.find('.aae-template-actions').length) {
                const $actions = $(`
                    <div class="aae-template-actions" style="margin-top: 10px;">
                        <button type="button" class="elementor-button elementor-button-default aae-create-template">
                            <i class="eicon-plus"></i> Create Template
                        </button>
                        <button type="button" class="elementor-button elementor-button-default aae-edit-template" style="display: none;">
                            <i class="eicon-edit"></i> Edit Template
                        </button>
                    </div>
                `);

                $templateControl.append($actions);
                this.bindTemplateActions($actions, model);
            }
        }

        bindTemplateActions($actions, model) {
            $actions.find('.aae-create-template').on('click', () => {
                this.createNewTemplate(model);
            });

            $actions.find('.aae-edit-template').on('click', () => {
                const templateId = model.get('settings').get('template_id');
                this.editTemplate(templateId);
            });
        }

        createNewTemplate(model) {
            // Show Elementor-style modal for template creation
            const modal = elementorCommon.dialogsManager.createWidget('confirm', {
                id: 'aae-create-template-modal',
                headerMessage: 'Create Loop Template',
                message: 'This will save your current work and create a new loop template. Continue?',
                position: {
                    my: 'center center',
                    at: 'center center'
                },
                strings: {
                    confirm: 'Create & Edit',
                    cancel: 'Cancel'
                },
                onConfirm: () => {
                    this.processTemplateCreation(model);
                }
            });

            modal.show();
        }

        processTemplateCreation(model) {
            // Save current document, create template, then enter template mode
            Promise.resolve()
                .then(() => {
                    if (elementor && elementor.saver && elementor.saver.save) {
                        return elementor.saver.save();
                    }
                })
                .then(() => this.doCreateTemplate())
                .then((response) => {
                    if (response.success) {
                        // Update widget setting
                        model.get('settings').set('template_id', response.data.template_id);
                        
                        // Enter template editing mode
                        const baseId = elementor.config.document.id;
                        $(document).trigger('clb:enter-template-mode', {
                            templateId: response.data.template_id,
                            baseId: baseId
                        });
                    }
                })
                .catch((error) => {
                    console.error('Template creation failed:', error);
                    elementor.notifications.showToast({
                        message: 'Failed to create template',
                        type: 'error'
                    });
                });
        }

        doCreateTemplate() {
            const ajaxUrl = typeof aaeLoopBuilderTemplateQuery !== 'undefined' ? aaeLoopBuilderTemplateQuery.ajax_url : '/wp-admin/admin-ajax.php';
            const nonce = typeof aaeLoopBuilderTemplateQuery !== 'undefined' ? aaeLoopBuilderTemplateQuery.nonce : '';
            
            return $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'create_loop_template',
                    template_name: 'New Loop Template',
                    source_type: 'post',
                    template_type: 'loop-builder',
                    nonce: nonce
                }
            });
        }

        editTemplate(templateId) {
            if (!templateId) return;
            
            // Save current work, then enter template mode
            Promise.resolve()
                .then(() => {
                    if (elementor && elementor.saver && elementor.saver.save) {
                        return elementor.saver.save();
                    }
                })
                .finally(() => {
                    const baseId = elementor.config.document.id;
                    $(document).trigger('clb:enter-template-mode', {
                        templateId: templateId,
                        baseId: baseId
                    });
                });
        }

        refreshWidgetPreview(model) {
            const view = elementor.getPreviewView().getChildViewContainer(model);
            if (view) {
                view.renderHTML();
            }
        }

        refreshAllLoopWidgets() {
            // Refresh all loop widgets in preview
            const $preview = $('#elementor-preview-iframe').contents();
            $preview.find('.custom-loop-container').each((index, element) => {
                const $widget = $(element).closest('.elementor-widget');
                if ($widget.length) {
                    const widgetId = $widget.data('id');
                    const model = elementor.getContainer(widgetId);
                    if (model) {
                        this.refreshWidgetPreview(model);
                    }
                }
            });
        }

        initializeLoopWidget($container) {
            // Initialize individual loop widget functionality
            const settings = $container.data('settings') || {};
            
            if (settings.template_id) {
                this.loadTemplatePreview(settings.template_id, $container);
            }
        }

        handleTemplateChange(event) {
            const $select = $(event.target);
            const templateId = $select.val();
            
            // Show/hide edit button based on selection
            const $editButton = $('.aae-edit-template');
            if (templateId) {
                $editButton.show();
                this.loadTemplatePreview(templateId);
            } else {
                $editButton.hide();
            }
        }

        loadTemplatePreview(templateId, $container = null) {
            // Check cache first
            if (this.templateCache.has(templateId)) {
                const cachedData = this.templateCache.get(templateId);
                this.displayTemplatePreview(cachedData, $container);
                return Promise.resolve(cachedData);
            }

            const $previewArea = $container || this.getPreviewArea();
            $previewArea.html('<div class="loading">Loading preview...</div>');

            const ajaxUrl = typeof aaeLoopBuilderTemplateQuery !== 'undefined' ? aaeLoopBuilderTemplateQuery.ajax_url : '/wp-admin/admin-ajax.php';
            const nonce = typeof aaeLoopBuilderTemplateQuery !== 'undefined' ? aaeLoopBuilderTemplateQuery.nonce : '';

            // Request template preview via AJAX
            return $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clb_get_template_preview',
                    template_id: templateId,
                    nonce: nonce
                }
            }).done((response) => {
                if (response.success) {
                    this.templateCache.set(templateId, response.data);
                    this.displayTemplatePreview(response.data, $container);
                } else {
                    $previewArea.html('<div class="error">Preview not available</div>');
                }
            }).fail(() => {
                $previewArea.html('<div class="error">Failed to load preview</div>');
            });
        }

        displayTemplatePreview(data, $container = null) {
            const $previewArea = $container || this.getPreviewArea();
            
            let previewHtml = '<div class="template-preview">';
            previewHtml += '<h4><i class="eicon-document-file"></i> Template Preview</h4>';
            
            if (data.template_data && Array.isArray(data.template_data)) {
                previewHtml += '<div class="widget-list">';
                data.template_data.forEach(element => {
                    if (element.widgetType) {
                        const widgetIcon = this.getWidgetIcon(element.widgetType);
                        previewHtml += `<div class="widget-preview">
                            <i class="${widgetIcon}"></i> ${this.getWidgetLabel(element.widgetType)}
                        </div>`;
                    }
                });
                previewHtml += '</div>';
            } else {
                previewHtml += '<div class="empty-template">Empty template - add some widgets to get started</div>';
            }
            
            previewHtml += '</div>';
            
            $previewArea.html(previewHtml);
        }

        getWidgetIcon(widgetType) {
            const iconMap = {
                'heading': 'eicon-heading',
                'text-editor': 'eicon-text',
                'image': 'eicon-image',
                'button': 'eicon-button',
                'divider': 'eicon-divider',
                'spacer': 'eicon-spacer',
                'icon': 'eicon-star',
                'icon-list': 'eicon-bullet-list',
            };
            
            return iconMap[widgetType] || 'eicon-apps';
        }

        getWidgetLabel(widgetType) {
            const labelMap = {
                'heading': 'Heading',
                'text-editor': 'Text Editor',
                'image': 'Image',
                'button': 'Button',
            };
            
            return labelMap[widgetType] || widgetType.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        getPreviewArea() {
            let $previewArea = $('.template-preview-container');
            
            if (!$previewArea.length) {
                $previewArea = $('<div class="template-preview-container"></div>');
                $('.elementor-control-template_id').after($previewArea);
            }
            
            return $previewArea;
        }

        clearTemplateCache(templateId = null) {
            if (templateId) {
                this.templateCache.delete(templateId);
            } else {
                this.templateCache.clear();
            }
        }
    }

    // Initialize
    $(document).ready(() => {
        if (typeof elementor !== 'undefined') {
            new AAE_LoopBuilderEditor();
        } else {
            $(window).on('elementor:init', () => {
                new AAE_LoopBuilderEditor();
            });
        }
    });

})(jQuery);


