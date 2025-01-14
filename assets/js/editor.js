/**
 * WCF Addons Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Addons_Editor*/

(function ($, window, document, config) {
  elementor.hooks.addAction('panel/open_editor/widget/wcf--mailchimp', function (panel, model, view) {
    var ajax_request = function ajax_request($api) {
      jQuery.ajax({
        type: "post",
        dataType: "json",
        url: config.ajaxUrl,
        data: {
          action: "mailchimp_api",
          nonce: config._wpnonce,
          api: $api
        },
        success: function success(response) {
          var audience = panel.$el.find('[data-setting="mailchimp_lists"]');
          if (Object.keys(response).length) {
            var data = {
              id: Object.keys(response),
              text: Object.values(response)
            };
            var newOption = new Option(data.text, data.id, false, false);
            audience.append(newOption).trigger('change');
          } else {
            audience.empty();
          }
        }
      });
    };
    var $element = panel.$el.find('[data-setting="mailchimp_api"]');
    if ($element.val()) {
      ajax_request($element.val());
    }
    $element.on('keyup', function () {
      ajax_request($element.val());
    });
  });

  // Custom Css
  elementor.hooks.addFilter('editor/style/styleText', function (css, context) {
    if (!context) {
      return;
    }
    var model = context.model,
      customCSS = model.get('settings').get('wcf_custom_css');
    var selector = '.elementor-element.elementor-element-' + model.get('id');
    if ('document' === model.get('elType')) {
      selector = elementor.config.document.settings.cssWrapperSelector;
    }
    if (customCSS) {
      css += customCSS.replace(/selector/g, selector);
    }
    return css;
  });
  $(window).on('elementor:init', function () {
    var interval = setInterval(function () {
      var $sidebar = $('.wcf.eicon-lock');
      if ($sidebar.length) {
        $sidebar.parent().parent('.elementor-element-wrapper').off('mouseenter mouseleave mousemove');
        clearInterval(interval); // Stop checking once the sidebar is found
      }
    }, 100);
  });
})(jQuery, window, document, WCF_Addons_Editor);
//wcf eicon-lock