;(function($){
    "use strict";

    const WCFAdmin = {

        init: function() {
            this.adminTabs();
            this.saveData();
            this.installPlugin();
            this.setupWizard();
            this.add_tab_args();

            //accordion
            if ($("#wcf-accordion").length) {
                $("#wcf-accordion").accordion();
            }
        },

        // Render admin settings tabs
        adminTabs: function( event ){
            let tabItem = $('.wcf-admin-tab button');
            $(tabItem[0]).addClass('active');

            tabItem.each(function() {
                if ( $(this).hasClass('active') ){
                    $(this).click(activeTab);
                    $(this).trigger('click');
                }else {
                    tabItem.on('click', activeTab);
                }
            });
            function activeTab ( e ) {
                let tabContent = $(".wcf-tab-pane");
                let target = $(this).data('target');
                tabContent.css('display', 'none');
                tabItem.removeClass('active');
                $(`#${target}`).css('display', 'block');
                $(this).addClass('active');
            }
        },

        //save widget and extension
        saveData: function () {

            //global on off
            $(document).on('click', '.wcf-global-switch', function (e) {
                let _this = $(this);

                let status = $(this).prop("checked");
                let classes = ".wcf-settings-item:enabled";

                _this.closest('form.wcf-settings').find(classes).each(function () {
                    $(this).prop("checked", status).change();
                });
            });

            //gsap on off
            $(document).on('click', '.wcf-gsap-switch', function (e) {
                let _this = $(this);

                let status = _this.prop("checked");
                let classes = ".wcf-settings-item:enabled";

                _this.closest('.settings-group').find(classes).each(function () {
                    $(this).prop("checked", status).change();
                });
            });

            //gsap items
            $(document).on('click', '.wcf-settings-item', function (e) {
                let _this = $(this);
                let group_area = _this.closest('.settings-group');
                let gsap_switch = group_area.find('.wcf-gsap-switch');

                if (!gsap_switch.length) {
                    return;
                }
                if (!gsap_switch.prop("checked")) {
                    _this.prop("checked", false).change();
                }

            });

            //smooth scroller
            $(document).on('click', '.smooth-settings', function (e) {

                let checkbox = $('.wcf-smooth-scroller-switch');

                let status = checkbox.prop("checked");

                let smooth_value = 1.35;
                let on_mobile = '';
                if (null !== WCF_ADDONS_ADMIN.smoothScroller) {
                    smooth_value = WCF_ADDONS_ADMIN.smoothScroller.smooth
                    on_mobile = 'on' === WCF_ADDONS_ADMIN.smoothScroller.mobile;
                }

                let popupTmp = wp.template('wcf-settings-smooth-scroller'),
                    content = null;

                content = popupTmp({
                    smooth_value: smooth_value,
                    on_mobile: on_mobile,
                });
                WCFAdmin.renderPopup(content);

                if (status) {
                    $('[data-checked="true"]').prop('checked', true);
                    WCFAdmin.openPopup()

                    $('.popup-button').on('click', function () {

                        $.ajax({
                            url: WCF_ADDONS_ADMIN.ajaxurl,
                            data: {
                                'action': 'save_smooth_scroller_settings',
                                'nonce': WCF_ADDONS_ADMIN.nonce,
                                'smooth': $(".input-items input[type='number']").val(),
                                'mobile': $(".input-items input[type='checkbox']:checked").val() ?? ''
                            },
                            type: 'POST',

                            beforeSend: function () {
                            },
                            success: function (response) {
                                const smoothScroller = JSON.parse(response);
                                WCF_ADDONS_ADMIN.smoothScroller.smooth = smoothScroller.smooth;
                                WCF_ADDONS_ADMIN.smoothScroller.mobile = smoothScroller.mobile;
                            },

                            complete: function (response) {

                            },

                            error: function (errorThrown) {

                                console.log(errorThrown);
                            }
                        });
                    })
                }

            });

            $(".wcf-settings-save").on("click", function (e) {
                let _this = $(this);
                let forms = _this.closest('form.wcf-settings');

                //if this is wizard save button
                if (_this.closest('.wizard-content').length) {
                    return;
                }

                let popupTmp = wp.template('wcf-settings-save'),
                    content = null;

                $.ajax({
                    url: WCF_ADDONS_ADMIN.ajaxurl,
                    data: {
                        'action': 'save_settings_with_ajax',
                        'nonce': WCF_ADDONS_ADMIN.nonce,
                        'settings': forms.attr('name'),
                        'fields': forms.serialize(),
                    },
                    type: 'POST',

                    beforeSend: function () {
                        _this.html("Saving Data...");
                    },
                    success: function (response) {
                        content = popupTmp({
                            title: "Good job!",
                            text: "Settings Saved!",
                            icon: "success",
                        });
                        WCFAdmin.renderPopup(content)

                        setTimeout(function () {
                            _this.html("Save Settings");
                            WCFAdmin.openPopup();
                        }, 500);
                    },

                    complete: function (response) {
                        setTimeout(() => {
                            WCFAdmin.closePopup();
                        }, 2000)
                    },

                    error: function (errorThrown) {
                        content = popupTmp({
                            title: "Oops...",
                            text: "Something went wrong!",
                            icon: "error",
                        });

                        WCFAdmin.renderPopup(content)

                        WCFAdmin.openPopup();

                        setTimeout(() => {
                            WCFAdmin.closePopup();
                        }, 2000)

                        console.log(errorThrown);
                    }

                });
            })
        },

        //installPlugin
        installPlugin: function () {
            $(".wcf-plugin-installer").on("click", function (e) {
                e.preventDefault();

                let _this = $(this);
                let action_base = _this.data('base');
                let plugin_source = _this.data('source');

                if (_this.hasClass('activated')) {
                    return;
                }

                //download plugin
                if (_this.hasClass('download')){
                    window.open(action_base, '_blank')
                    return;
                }

                //active plugin
                if (_this.hasClass('active')){

                    let action_base = _this.data('file'),
                        action = 'active',
                        before = 'Activating...',
                        addClass = 'activated',
                        addHtml = 'Activated';

                    $.ajax({
                        url: WCF_ADDONS_ADMIN.ajaxurl,
                        data: {
                            'action': `wcf_${action}_plugin`,
                            'nonce': WCF_ADDONS_ADMIN.nonce,
                            'action_base': action_base,
                            'plugin_source': plugin_source,
                        },
                        type: 'POST',

                        beforeSend: function () {
                            _this.html(before);
                        },
                        success: function (response) {
                            setTimeout(()=>{
                                _this.removeClass(action)
                                _this.addClass(addClass)
                                _this.html(addHtml);
                            }, 200)
                        },
                        complete:function( response ){

                        },

                        error: function( errorThrown ){
                            _this.html('Install');
                            console.log( errorThrown );
                        }

                    });
                }
            })
        },

        //setup Wizard
        setupWizard: function (){
            let wizardBar = document.querySelector('[data-wizard-bar]')
            let btnPrevious = document.querySelector('[data-btn-previous]')
            let currentTab = 0;
            showTab(currentTab);

            function showTab(n) {
                let formTabs = document.querySelectorAll('[data-form-tab]');
                let wizardItem = document.querySelectorAll('[data-wizard-item]');

                if ( 0 === wizardItem.length) {
                    return
                }
                formTabs[n].classList.add('active')
                wizardItem[n].classList.add('active')
                wizardItem[n].classList.add('current')

                if (formTabs.length - 1 === n) {
                    $('.wizard-buttons').hide();
                }

                if (n == 0) {
                    btnPrevious.style.display = "none";
                } else {
                    btnPrevious.style.display = "flex";
                }
            }

            function nextPrev(n) {
                let formTabs = document.querySelectorAll('[data-form-tab]');
                let wizardItem = document.querySelectorAll('[data-wizard-item]')

                formTabs[currentTab].classList.remove('active')
                wizardItem[currentTab].classList.remove('current');

                currentTab = currentTab + n;
                showTab(currentTab);
            }


            function updateWizardBarWidth() {
                const activeWizards = document.querySelectorAll(".wizard-item.active");
                let wizardItem = document.querySelectorAll('[data-wizard-item]')
                const currentWidth = ((activeWizards.length - 1) / (wizardItem.length - 1)) * 100;
                wizardBar.style.setProperty("--width", currentWidth + "%");
            }

            document.querySelector('*').addEventListener('click', function (event) {

                if (event.target.dataset.btnPrevious) {
                    let wizardItem = document.querySelectorAll('[data-wizard-item]')
                    wizardItem[currentTab].classList.remove('active')
                    nextPrev(-1)
                    updateWizardBarWidth()
                }
                if (event.target.dataset.btnNext) {
                    nextPrev(1)
                    updateWizardBarWidth()
                }
            })


            //save data
            $(".wizard-congrats .wcf-settings-save").on("click", function (e) {
                let _this = $( this );
                let forms = $('.wizard-content').find('form');
                let settings = {};

                forms.each(function (){
                    let _that = $(this);
                    settings[_that.attr('name')] = _that.serialize();
                })

                setTimeout(function () {

                    $.ajax({
                        url: WCF_ADDONS_ADMIN.ajaxurl,
                        data: {
                            'action': 'save_setup_wizard_settings',
                            'nonce': WCF_ADDONS_ADMIN.nonce,
                            'settings': settings,
                        },
                        type: 'POST',

                        beforeSend: function () {
                            _this.html("Saving Data...");
                        },
                        success: function (response) {
                            setTimeout(function () {
                                _this.html("Save Settings");
                            }, 500);
                        },

                        complete: function (response) {
                            window.location = response.responseJSON.data.redirect_url;
                        },

                        error: function (errorThrown) {
                            console.log(errorThrown);
                        }

                    });
                }, 500)

            })

        },

        renderPopup: function (content) {
            $('.wcf-addons-settings-content').html(content);
        },

        openPopup: function () {
            $('.wcf-addons-settings-popup').addClass('open-popup');

            $('.popup-button').on('click', WCFAdmin.closePopup)
        },

        closePopup: function () {
            $('.wcf-addons-settings-popup').removeClass('open-popup');
        },
        
        add_tab_args: function(){
           const url = $('#toplevel_page_wcf_addons_page > a').attr('href');           
        }

    };

    WCFAdmin.init();


})(jQuery);
