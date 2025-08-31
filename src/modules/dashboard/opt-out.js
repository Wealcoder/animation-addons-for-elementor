// Animation Addons Deactivation Feedback System
(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        PLUGIN_SLUG: 'animation-addons-for-elementor',
        API_URL: 'https://data.animation-addons.com/wp-json/wmd/v1/feedback',       
        SELECTORS: {
            PLUGIN_ROW: `tr[data-slug="animation-addons-for-elementor"]`,
            DEACTIVATE_LINK: '.deactivate a',
            MODAL_OVERLAY: '#aae-deactivate-overlay',
            CLOSE_BUTTON: '.close-btn',
            SKIP_BUTTON: '.skip-btn',
            SUBMIT_BUTTON: '.submit-btn',
            RADIO_INPUTS: 'input[name="reason"]',
            MESSAGE_WRAPPER: '.other-reason',
            MESSAGE_INPUT: '#feedback-message'
        }
    };

    // HTML Template popup
    const HTML_TEMPLATE = `
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-content">
                    <div class="header-left">
                        <div class="wp-logo">
                            <img src="${aae_ajax.logo_url}" alt="logo" />
                        </div>
                        <h3>Provide a feedback</h3>
                    </div>
                    <div class="close-btn">&times;</div>
                </div>
            </div>
            <div class="modal-body">
                <div class="question">
                    What made you deactivate Animation Addons?
                </div>
                <div class="feedback-form">
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="reason" value="I no longer need the plugin." />
                            <span>I no longer need the plugin.</span>
                        </label>
                        <label>
                            <input type="radio" name="reason" value="I found a better plugin." />
                            <span>I found a better plugin.</span>
                        </label>
                        <label>
                            <input type="radio" name="reason" value="I couldn't get the plugin to work." />
                            <span>I couldn't get the plugin to work.</span>
                        </label>
                        <label>
                            <input type="radio" name="reason" value="It's a temporary deactivation." />
                            <span>It's a temporary deactivation.</span>
                        </label>
                        <label>
                            <input type="radio" name="reason" value="I have Elementor Pro" />
                            <span>I have Elementor Pro.</span>
                        </label>
                        <label>
                            <input type="radio" name="reason" value="others" />
                            <span>Others (Please specify)</span>
                        </label>
                    </div>
                    <div class="other-reason">
                        <textarea placeholder="Please share us the reason." id="feedback-message"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="skip-btn">Skip & Deactivate</button>
                <button class="submit-btn">Submit & Deactivate</button>
            </div>
        </div>
    `;

    // DOM Utilities
    const DOM = {
        getElement: (selector) => document.querySelector(selector),
        getElements: (selector) => document.querySelectorAll(selector),
        createElement: (tag, className, innerHTML) => {
            const element = document.createElement(tag);
            if (className) element.className = className;
            if (innerHTML) element.innerHTML = innerHTML;
            return element;
        },
        addEvent: (element, event, handler) => {
            if (element) element.addEventListener(event, handler);
        },
        removeEvent: (element, event, handler) => {
            if (element) element.removeEventListener(event, handler);
        }
    };

    // Modal Management
    const Modal = {
        overlay: null,
        isVisible: false,

        init() {
            this.createOverlay();
            this.bindEvents();
        },

        createOverlay() {
            this.overlay = DOM.createElement('div', 'aae-deactivate-modal');
            this.overlay.id = 'aae-deactivate-overlay';
            this.overlay.style.display = 'none';
            this.overlay.innerHTML = HTML_TEMPLATE;
            document.body.appendChild(this.overlay);
        },

        show() {
            if (this.overlay) {
                this.overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
                this.isVisible = true;
            }
        },

        hide() {
            if (this.overlay) {
                this.overlay.style.display = 'none';
                document.body.style.overflow = '';
                this.isVisible = false;
            }
        },

        bindEvents() {
            // Close on overlay click
            DOM.addEvent(this.overlay, 'click', (e) => {
                if (e.target === this.overlay) {
                    this.hide();
                }
            });

            // Close on escape key
            DOM.addEvent(document, 'keydown', (e) => {
                if (e.key === 'Escape' && this.isVisible) {
                    this.hide();
                }
            });
        }
    };

    // Form Management
    const Form = {
        init() {
            this.bindFormEvents();
        },

        bindFormEvents() {
            // Radio button changes
            const radioInputs = DOM.getElements(CONFIG.SELECTORS.RADIO_INPUTS);
            radioInputs.forEach(radio => {
                DOM.addEvent(radio, 'change', this.handleRadioChange.bind(this));
            });

            // Close button
            const closeBtn = DOM.getElement(CONFIG.SELECTORS.CLOSE_BUTTON);
            DOM.addEvent(closeBtn, 'click', () => Modal.hide());

            // Skip button
            const skipBtn = DOM.getElement(CONFIG.SELECTORS.SKIP_BUTTON);
            DOM.addEvent(skipBtn, 'click', this.handleSkip.bind(this));

            // Submit button
            const submitBtn = DOM.getElement(CONFIG.SELECTORS.SUBMIT_BUTTON);
            DOM.addEvent(submitBtn, 'click', this.handleSubmit.bind(this));
        },

        handleRadioChange(event) {
            const messageWrapper = DOM.getElement(CONFIG.SELECTORS.MESSAGE_WRAPPER);
            if (event.target.value === 'others') {
                messageWrapper.style.display = 'block';
            } else {
                messageWrapper.style.display = 'none';
            }
        },

        handleSkip() {
            Modal.hide();
            Deactivation.proceed();
        },

        async handleSubmit() {
            const selectedReason = DOM.getElement('input[name="reason"]:checked');
            
            if (!selectedReason) {
                alert('Please select a reason for deactivation.');
                return;
            }

            const submitBtn = DOM.getElement(CONFIG.SELECTORS.SUBMIT_BUTTON);
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                await Feedback.submit(selectedReason.value);
            } catch (error) {
               
            } finally {
                Modal.hide();
                Deactivation.proceed();
            }
        }
    };

    // Feedback API
    const Feedback = {
        async submit(reason) {
            const siteUrl = window.location.origin;
            const messageInput = DOM.getElement(CONFIG.SELECTORS.MESSAGE_INPUT);
            const comment = reason === 'others' ? messageInput.value : reason;

            const params = new URLSearchParams({
                plugin_slug: CONFIG.PLUGIN_SLUG,
                page_url: siteUrl,
                comment: comment,
                rating: ''
            });

            const response = await fetch(`${CONFIG.API_URL}?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.ok) {
              
            } else {
                throw new Error('Feedback submission failed');
            }

            return data;
        }
    };

    // Deactivation Management
    const Deactivation = {
        link: null,

        init() {
            this.findDeactivateLink();
            this.bindDeactivateEvent();
        },

        findDeactivateLink() {
            const pluginRow = DOM.getElement(CONFIG.SELECTORS.PLUGIN_ROW);            
            if (pluginRow) {
                this.link = pluginRow.querySelector(CONFIG.SELECTORS.DEACTIVATE_LINK);
            }
        },

        bindDeactivateEvent() {
            if (this.link) {
                DOM.addEvent(this.link, 'click', this.handleDeactivateClick.bind(this));
            }
        },

        handleDeactivateClick(event) {
            event.preventDefault();
            Modal.show();
        },

        proceed() {
            if (this.link) {
                window.location.href = this.link.href;
            }
        }
    };

    // Main Application
    const App = {
        init() {
     
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                DOM.addEvent(document, 'DOMContentLoaded', this.start.bind(this));
            } else {
                this.start();
            }
        },

        start() {
      
            // Check if we're on the plugins page and the plugin exists
            if (!this.isPluginsPage()) {            
                return;
            }
            
            if (!this.pluginExists()) {               
                return;
            }

          
            // Initialize all modules
            Modal.init();
            Form.init();
            Deactivation.init();
  
        },

        isPluginsPage() {
            return window.location.pathname.includes('plugins.php');
        },

        pluginExists() {
            const pluginRow = DOM.getElement(CONFIG.SELECTORS.PLUGIN_ROW);         
            return !!pluginRow;
        }
    };

    // Start the application
    App.init();

})();
