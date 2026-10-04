/**
 * AAE Loop Builder - Active Document Handler
 * Handles in-place template editing behavior
 */
(function($) {
    'use strict';

    class AAE_ActiveDocumentHandler {
        constructor() {
            this.activeDocumentId = null;
            this.baseDocumentId = null;
            this.isTemplateMode = false;
            this.init();
        }

        init() {
            // Listen for programmatic entry (no page reload)
            $(document).on('clb:enter-template-mode', (e, data) => {
                if (!data || !data.templateId) return;
                this.enterTemplateMode(data.templateId, data.baseId);
            });

            // Check if URL already has active-document param
            const urlParams = new URLSearchParams(window.location.search);
            const activeDocId = urlParams.get('active-document');

            if (activeDocId) {
                const baseId = parseInt(urlParams.get('post'));
                this.waitForElementor(() => {
                    this.enterTemplateMode(parseInt(activeDocId), baseId);
                });
            }
        }

        waitForElementor(callback) {
            if (typeof elementor !== 'undefined' && elementor.config) {
                callback();
            } else {
                setTimeout(() => this.waitForElementor(callback), 100);
            }
        }

        enterTemplateMode(templateId, baseId) {
            this.activeDocumentId = templateId;
            this.baseDocumentId = baseId || (elementor.config.document ? elementor.config.document.id : null);
            this.isTemplateMode = true;

            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.set('active-document', String(templateId));
            window.history.replaceState({}, '', url.toString());

            // Add compact editor indicator with controls
            this.addEditorIndicatorWithControls();

            // Lock base document in preview
            this.lockBaseDocument();

            // Switch to template document
            this.switchToTemplateDocument();
        }

        addEditorIndicatorWithControls() {
            // Remove any existing indicator (both in parent and inside iframe)
            $('.aae-template-mode-bar').remove();

            const checkAndAdd = () => {
                const iframe = document.querySelector('#elementor-preview-iframe');
                if (!iframe || !iframe.contentWindow || !iframe.contentDocument) {
                    setTimeout(checkAndAdd, 300);
                    return;
                }

                const $iframeDoc = jQuery(iframe.contentDocument);

                const activeDocument = new URLSearchParams(window.location.search).get('active-document') || null;

                if( null == activeDocument ) {
                    setTimeout(checkAndAdd, 300);
                    return;
                }
                // Ensure Elementor preview has loaded widgets
                const $loopGrid = $iframeDoc.find('.custom-loop-container-' + activeDocument );
                if (!$loopGrid.length) {
                    setTimeout(checkAndAdd, 300);
                    return;
                }

                // Add a dashed border and inline "Save & Back" button inside the first loop item.
                const $firstItem = $loopGrid.find('.aae-loop-item:first-child');
                if ($firstItem.length && !$firstItem.find('.aae-inline-back-btn').length) {
                    $firstItem.css({
                        'border': '1px dashed rgba(241, 37, 41, 1)',
                        'position': 'relative',
                    });

                    const $inlineBtn = jQuery(`<button class="aae-inline-back-btn">← Save & Back</button>`).css({
                        'position': 'absolute',
                        'top': '-27px',
                        'left': '50%',
                        'transform': 'translateX(-50%)',
                        'background': 'linear-gradient(225deg, #F12529 10.04%, #FFA030 90.11%)',
                        'color': '#fff',
                        'border': '0',
                        'border-radius': '4px',
                        'padding': '4px 8px',
                        'font-size': '12px',
                        'cursor': 'pointer',
                        'z-index': '10',
                    });

                    $inlineBtn.on('click', () => this.saveAndExitTemplateMode());
                    $firstItem.append($inlineBtn);
                }

                // Add class to body (optional)
                $('body').addClass('aae-template-editing-mode');
            };

            checkAndAdd();
        }


        lockBaseDocument() {
            // Lock base page elements in the preview iframe - keep it clean and simple
            const $previewFrame = $('#elementor-preview-iframe');
            if (!$previewFrame.length) return;

            const previewDoc = $previewFrame[0].contentDocument;
            if (!previewDoc) return;

            // Inject CSS to lock non-template elements (no visual changes, just disable clicks)
            const styleId = 'aae-lock-base-style';
            if (previewDoc.getElementById(styleId)) return;
            const activeDocument = new URLSearchParams(window.location.search).get('active-document') || null;
            const style = previewDoc.createElement('style');
            style.id = styleId;
            style.textContent = `
                /* Lock base page elements - no visual effects, just disable interaction */
                .elementor-element:not(.e-loop-item):not(.e-loop-item *) {
                    pointer-events: none !important;
                }
                
                /* Keep template area fully interactive */
                .e-loop-item,
                .e-loop-item *,
                .e-loop-item .elementor-element {
                    pointer-events: auto !important;
                }
                
                /* Enable Add Section buttons inside loop container */
                .custom-loop-container-${activeDocument} .elementor-add-section, .custom-loop-container-${activeDocument} .elementor-empty-view, .custom-loop-container-${activeDocument} .elementor-widget-empty {
                    display: block !important;
                }
                .custom-loop-container-${activeDocument} .elementor-element-editable .elementor-element-overlay {
                    display: block !important;
                }
                .custom-loop-container-${activeDocument} .elementor-element.elementor-element-edit-mode:hover .elementor-element-overlay {
                    display: block !important;
                    pointer-events: none !important;
                }
            `;
            previewDoc.head.appendChild(style);
        }

	switchToTemplateDocument() {
		// For active-document mode, the URL parameter handles document loading
		// Just ensure the panel opens to elements view after load
		setTimeout(() => {
			try {
				// Method 1: Use panel view directly (most reliable)
				if (elementor && elementor.getPanelView) {
					const panelView = elementor.getPanelView();
					if (panelView && panelView.setPage) {
						panelView.setPage('elements');
					}
				}
				// Method 2: Use panels.currentView (legacy)
				else if (elementor && elementor.panels && elementor.panels.currentView) {
					elementor.panels.currentView.setPage('elements');
				}
				// Method 3: Try direct navigation to panel (alternative)
				else if (window.$e && window.$e.route) {
					$e.route('panel/editor');
				}
			} catch (error) {
				console.warn('Could not open elements panel:', error);
				// Silent fail is okay - panel will just show whatever was last open
			}
		}, 500);
	}

        saveAndExitTemplateMode() {
            // Show saving notification
            if (elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: 'Saving template...',
                    type: 'info'
                });
            }

            // Save using modern or legacy API
            this.saveDocument()
                .then(() => {
                    if (elementor && elementor.notifications) {
                        elementor.notifications.showToast({
                            message: 'Template saved successfully!',
                            type: 'success'
                        });
                    }
                    this.exitTemplateMode();
                })
                .catch((error) => {
                    console.error('Save failed:', error);
                    // Exit anyway even if save fails
                    this.exitTemplateMode();
                });
        }

        saveDocument() {
            return new Promise((resolve, reject) => {
                try {
                    // Method 1: Use modern Elementor API
                    if (typeof $e !== 'undefined' && $e.components) {
                        const documentSave = $e.components.get('document/save');
                        if (documentSave && documentSave.save) {
                            documentSave.save({ status: 'publish' })
                                .then(resolve)
                                .catch(reject);
                            return;
                        }
                    }

                    // Method 2: Use legacy Elementor API
                    if (typeof elementor !== 'undefined' && elementor.saver) {
                        if (elementor.saver.save) {
                            Promise.resolve(elementor.saver.save({ status: 'publish' }))
                                .then(resolve)
                                .catch(reject);
                            return;
                        }
                    }

                    // No save method available
                    console.warn('No save method available, resolving anyway');
                    resolve();

                } catch (error) {
                    console.error('Save document error:', error);
                    reject(error);
                }
            });
        }

        exitTemplateMode() {
            // Try to switch without reload first
            const baseDocId = this.baseDocumentId ||
                (elementor.config.initial_document ? elementor.config.initial_document.id : null);

            if (!baseDocId) {
                console.error('Base document ID not found, using fallback');
                this.fallbackExit();
                return;
            }

            // Show notification
            if (elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: 'Returning to page editor...',
                    type: 'info'
                });
            }

            // Try switching without reload using document manager
            this.switchDocumentWithoutReload(baseDocId)
                .catch((error) => {
                    console.warn('Document switch failed, using page reload:', error);
                    
                    // Show user-friendly message
                    if (elementor && elementor.notifications) {
                        elementor.notifications.showToast({
                            message: 'Reloading editor...',
                            type: 'info'
                        });
                    }
                    
                    // Fallback to page reload if switch fails
                    setTimeout(() => {
                        this.fallbackExit();
                    }, 800);
                });
        }

	switchDocumentWithoutReload(baseDocId) {
		return new Promise((resolve, reject) => {
			try {
				// Verify preview is ready before switching
				if (!this.isPreviewReady()) {
					console.warn('Preview not ready, using fallback');
					reject('Preview not ready');
					return;
				}

				// Use $e.run command - the modern Elementor way
				if (window.$e && $e.run) {

					// First, ensure document is closed properly
					if ($e.components && $e.components.get('document')) {
						const documentComponent = $e.components.get('document');

						// Save current document before switching
						if (documentComponent.save) {
							documentComponent.save()
								.then(() => this.performDocumentSwitch(baseDocId, resolve, reject))
								.catch(() => this.performDocumentSwitch(baseDocId, resolve, reject));
						} else {
							this.performDocumentSwitch(baseDocId, resolve, reject);
						}
					} else {
						this.performDocumentSwitch(baseDocId, resolve, reject);
					}
				} else {
					reject('Modern API not available');
				}
			} catch (error) {
				console.error('Switch without reload error:', error);
				reject(error);
			}
		});
	}

	isPreviewReady() {
		try {
			// Check if preview iframe exists
			const $previewFrame = $('#elementor-preview-iframe');
			if (!$previewFrame.length) return false;

			const iframe = $previewFrame[0];
			if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return false;

			// Check if jQuery is available in preview
			const previewWindow = iframe.contentWindow;
			if (!previewWindow.jQuery || typeof previewWindow.jQuery !== 'function') {
				console.warn('Preview jQuery not initialized');
				return false;
			}

			// Check if elementorFrontend exists
			if (!previewWindow.elementorFrontend) {
				console.warn('elementorFrontend not initialized');
				return false;
			}

			return true;
		} catch (error) {
			console.warn('Preview ready check failed:', error);
			return false;
		}
	}

        performDocumentSwitch(baseDocId, resolve, reject) {
            try {
                // Add a small delay to ensure everything is ready
                setTimeout(() => {
                    // Verify one more time before actual switch
                    if (!this.isPreviewReady()) {
                        console.error('Preview became unavailable, falling back to page reload');
                        reject('Preview unavailable');
                        return;
                    }

                    // Switch to base document
                    $e.run('editor/documents/switch', {
                        id: parseInt(baseDocId)
                    }).then(() => {
                        this.completeExitWithoutReload();
                        resolve();
                    }).catch((error) => {
                        console.error('Document switch failed:', error);
                        reject(error);
                    });
                }, 300);
            } catch (error) {
                console.error('Perform switch error:', error);
                reject(error);
            }
        }

        completeExitWithoutReload() {
            // Remove active-document from URL
            const url = new URL(window.location.href);
            url.searchParams.delete('active-document');
            window.history.replaceState({}, '', url.toString());

            // Clean up UI
            this.cleanup();

            // Show success notification
            if (elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: 'Returned to page editor',
                    type: 'success'
                });
            }

            // Refresh the preview to show base document
            setTimeout(() => {
                if (elementor && elementor.reloadPreview) {
                    elementor.reloadPreview();
                } else if (window.$e && $e.run) {
                    $e.run('document/elements/refresh');
                }
            }, 100);
        }

        fallbackExit() {
            // Clean up before redirect
            this.cleanup();
            
            if (this.baseDocumentId) {
                const baseUrl = `${window.location.origin}/wp-admin/post.php?post=${this.baseDocumentId}&action=elementor`;
                console.log('Redirecting to:', baseUrl);
                window.location.href = baseUrl;
            } else {
                console.error('Cannot fallback exit - no base document ID');
                // Try to at least reload current page without active-document param
                const url = new URL(window.location.href);
                url.searchParams.delete('active-document');
                window.location.href = url.toString();
            }
        }

        cleanup() {
            $('.aae-template-mode-bar').remove();
            $('body').removeClass('aae-template-editing-mode');

            // Remove lock style from preview
            const $previewFrame = $('#elementor-preview-iframe');
            if ($previewFrame.length) {
                const previewDoc = $previewFrame[0].contentDocument;
                if (previewDoc) {
                    const lockStyle = previewDoc.getElementById('aae-lock-base-style');
                    if (lockStyle) {
                        lockStyle.remove();
                    }
                }
            }

            this.isTemplateMode = false;
            this.activeDocumentId = null;
        }
    }

    // Initialize
    $(document).ready(() => {
        window.AAE_ActiveDocumentHandler = new AAE_ActiveDocumentHandler();
    });

})(jQuery);
