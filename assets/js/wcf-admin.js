;
(function ($) {
  "use strict";

  var WCFAdmin = {
    init: function init() {
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
    adminTabs: function adminTabs(event) {
      var tabItem = $('.wcf-admin-tab button');
      $(tabItem[0]).addClass('active');
      tabItem.each(function () {
        if ($(this).hasClass('active')) {
          $(this).click(activeTab);
          $(this).trigger('click');
        } else {
          tabItem.on('click', activeTab);
        }
      });
      function activeTab(e) {
        var tabContent = $(".wcf-tab-pane");
        var target = $(this).data('target');
        tabContent.css('display', 'none');
        tabItem.removeClass('active');
        $("#".concat(target)).css('display', 'block');
        $(this).addClass('active');
      }
    },
    //save widget and extension
    saveData: function saveData() {
      //global on off
      $(document).on('click', '.wcf-global-switch', function (e) {
        var _this = $(this);
        var status = $(this).prop("checked");
        var classes = ".wcf-settings-item:enabled";
        _this.closest('form.wcf-settings').find(classes).each(function () {
          $(this).prop("checked", status).change();
        });
      });

      //gsap on off
      $(document).on('click', '.wcf-gsap-switch', function (e) {
        var _this = $(this);
        var status = _this.prop("checked");
        var classes = ".wcf-settings-item:enabled";
        _this.closest('.settings-group').find(classes).each(function () {
          $(this).prop("checked", status).change();
        });
      });

      //gsap items
      $(document).on('click', '.wcf-settings-item', function (e) {
        var _this = $(this);
        var group_area = _this.closest('.settings-group');
        var gsap_switch = group_area.find('.wcf-gsap-switch');
        if (!gsap_switch.length) {
          return;
        }
        if (!gsap_switch.prop("checked")) {
          _this.prop("checked", false).change();
        }
      });

      //smooth scroller
      $(document).on('click', '.smooth-settings', function (e) {
        var checkbox = $('.wcf-smooth-scroller-switch');
        var status = checkbox.prop("checked");
        var smooth_value = 1.35;
        var on_mobile = '';
        if (null !== WCF_ADDONS_ADMIN.smoothScroller) {
          smooth_value = WCF_ADDONS_ADMIN.smoothScroller.smooth;
          on_mobile = 'on' === WCF_ADDONS_ADMIN.smoothScroller.mobile;
        }
        var popupTmp = wp.template('wcf-settings-smooth-scroller'),
          content = null;
        content = popupTmp({
          smooth_value: smooth_value,
          on_mobile: on_mobile
        });
        WCFAdmin.renderPopup(content);
        if (status) {
          $('[data-checked="true"]').prop('checked', true);
          WCFAdmin.openPopup();
          $('.popup-button').on('click', function () {
            var _$$val;
            $.ajax({
              url: WCF_ADDONS_ADMIN.ajaxurl,
              data: {
                'action': 'save_smooth_scroller_settings',
                'nonce': WCF_ADDONS_ADMIN.nonce,
                'smooth': $(".input-items input[type='number']").val(),
                'mobile': (_$$val = $(".input-items input[type='checkbox']:checked").val()) !== null && _$$val !== void 0 ? _$$val : ''
              },
              type: 'POST',
              beforeSend: function beforeSend() {},
              success: function success(response) {
                var smoothScroller = JSON.parse(response);
                WCF_ADDONS_ADMIN.smoothScroller.smooth = smoothScroller.smooth;
                WCF_ADDONS_ADMIN.smoothScroller.mobile = smoothScroller.mobile;
              },
              complete: function complete(response) {},
              error: function error(errorThrown) {
                console.log(errorThrown);
              }
            });
          });
        }
      });
      $(".wcf-settings-save").on("click", function (e) {
        var _this = $(this);
        var forms = _this.closest('form.wcf-settings');

        //if this is wizard save button
        if (_this.closest('.wizard-content').length) {
          return;
        }
        var popupTmp = wp.template('wcf-settings-save'),
          content = null;
        $.ajax({
          url: WCF_ADDONS_ADMIN.ajaxurl,
          data: {
            'action': 'save_settings_with_ajax',
            'nonce': WCF_ADDONS_ADMIN.nonce,
            'settings': forms.attr('name'),
            'fields': forms.serialize()
          },
          type: 'POST',
          beforeSend: function beforeSend() {
            _this.html("Saving Data...");
          },
          success: function success(response) {
            content = popupTmp({
              title: "Good job!",
              text: "Settings Saved!",
              icon: "success"
            });
            WCFAdmin.renderPopup(content);
            setTimeout(function () {
              _this.html("Save Settings");
              WCFAdmin.openPopup();
            }, 500);
          },
          complete: function complete(response) {
            setTimeout(function () {
              WCFAdmin.closePopup();
            }, 2000);
          },
          error: function error(errorThrown) {
            content = popupTmp({
              title: "Oops...",
              text: "Something went wrong!",
              icon: "error"
            });
            WCFAdmin.renderPopup(content);
            WCFAdmin.openPopup();
            setTimeout(function () {
              WCFAdmin.closePopup();
            }, 2000);
            console.log(errorThrown);
          }
        });
      });
    },
    //installPlugin
    installPlugin: function installPlugin() {
      $(".wcf-plugin-installer").on("click", function (e) {
        e.preventDefault();
        var _this = $(this);
        var action_base = _this.data('base');
        var plugin_source = _this.data('source');
        if (_this.hasClass('activated')) {
          return;
        }

        //download plugin
        if (_this.hasClass('download')) {
          window.open(action_base, '_blank');
          return;
        }

        //active plugin
        if (_this.hasClass('active')) {
          var _action_base = _this.data('file'),
            action = 'active',
            before = 'Activating...',
            addClass = 'activated',
            addHtml = 'Activated';
          $.ajax({
            url: WCF_ADDONS_ADMIN.ajaxurl,
            data: {
              'action': "wcf_".concat(action, "_plugin"),
              'nonce': WCF_ADDONS_ADMIN.nonce,
              'action_base': _action_base,
              'plugin_source': plugin_source
            },
            type: 'POST',
            beforeSend: function beforeSend() {
              _this.html(before);
            },
            success: function success(response) {
              setTimeout(function () {
                _this.removeClass(action);
                _this.addClass(addClass);
                _this.html(addHtml);
              }, 200);
            },
            complete: function complete(response) {},
            error: function error(errorThrown) {
              _this.html('Install');
              console.log(errorThrown);
            }
          });
        }
      });
    },
    //setup Wizard
    setupWizard: function setupWizard() {
      var wizardBar = document.querySelector('[data-wizard-bar]');
      var btnPrevious = document.querySelector('[data-btn-previous]');
      var currentTab = 0;
      showTab(currentTab);
      function showTab(n) {
        var formTabs = document.querySelectorAll('[data-form-tab]');
        var wizardItem = document.querySelectorAll('[data-wizard-item]');
        if (0 === wizardItem.length) {
          return;
        }
        formTabs[n].classList.add('active');
        wizardItem[n].classList.add('active');
        wizardItem[n].classList.add('current');
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
        var formTabs = document.querySelectorAll('[data-form-tab]');
        var wizardItem = document.querySelectorAll('[data-wizard-item]');
        formTabs[currentTab].classList.remove('active');
        wizardItem[currentTab].classList.remove('current');
        currentTab = currentTab + n;
        showTab(currentTab);
      }
      function updateWizardBarWidth() {
        var activeWizards = document.querySelectorAll(".wizard-item.active");
        var wizardItem = document.querySelectorAll('[data-wizard-item]');
        var currentWidth = (activeWizards.length - 1) / (wizardItem.length - 1) * 100;
        wizardBar.style.setProperty("--width", currentWidth + "%");
      }
      document.querySelector('*').addEventListener('click', function (event) {
        if (event.target.dataset.btnPrevious) {
          var wizardItem = document.querySelectorAll('[data-wizard-item]');
          wizardItem[currentTab].classList.remove('active');
          nextPrev(-1);
          updateWizardBarWidth();
        }
        if (event.target.dataset.btnNext) {
          nextPrev(1);
          updateWizardBarWidth();
        }
      });

      //save data
      $(".wizard-congrats .wcf-settings-save").on("click", function (e) {
        var _this = $(this);
        var forms = $('.wizard-content').find('form');
        var settings = {};
        forms.each(function () {
          var _that = $(this);
          settings[_that.attr('name')] = _that.serialize();
        });
        setTimeout(function () {
          $.ajax({
            url: WCF_ADDONS_ADMIN.ajaxurl,
            data: {
              'action': 'save_setup_wizard_settings',
              'nonce': WCF_ADDONS_ADMIN.nonce,
              'settings': settings
            },
            type: 'POST',
            beforeSend: function beforeSend() {
              _this.html("Saving Data...");
            },
            success: function success(response) {
              setTimeout(function () {
                _this.html("Save Settings");
              }, 500);
            },
            complete: function complete(response) {
              window.location = response.responseJSON.data.redirect_url;
            },
            error: function error(errorThrown) {
              console.log(errorThrown);
            }
          });
        }, 500);
      });
    },
    renderPopup: function renderPopup(content) {
      $('.wcf-addons-settings-content').html(content);
    },
    openPopup: function openPopup() {
      $('.wcf-addons-settings-popup').addClass('open-popup');
      $('.popup-button').on('click', WCFAdmin.closePopup);
    },
    closePopup: function closePopup() {
      $('.wcf-addons-settings-popup').removeClass('open-popup');
    },
    add_tab_args: function add_tab_args() {
      var url = $('#toplevel_page_wcf_addons_page > a').attr('href');
    }
  };
  WCFAdmin.init();
})(jQuery);