/**
 * WCF Addons Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Addons_Editor*/

(function ($, window, document, config) {
  elementor.hooks.addAction(
    "panel/open_editor/widget/wcf--mailchimp",
    function (panel, model, view) {
      const ajax_request = function ($api) {
        jQuery.ajax({
          type: "post",
          dataType: "json",
          url: config.ajaxUrl,
          data: {
            action: "mailchimp_api",
            nonce: config._wpnonce,
            api: $api,
          },
          success: function (response) {
            const audience = panel.$el.find('[data-setting="mailchimp_lists"]');
            if (Object.keys(response).length) {
              const data = {
                id: Object.keys(response),
                text: Object.values(response),
              };
              const newOption = new Option(data.text, data.id, false, false);
              audience.append(newOption).trigger("change");
            } else {
              audience.empty();
            }
          },
        });
      };

    });
    
    // Custom Css
    elementor.hooks.addFilter('editor/style/styleText', function (css, context) {
        if (!context) {
            return;
        }
        const model = context.model,
            customCSS = model.get('settings').get('wcf_custom_css');
        let selector = '.elementor-element.elementor-element-' + model.get('id');
        if ('document' === model.get('elType')) {
            selector = elementor.config.document.settings.cssWrapperSelector;
        }
        if (customCSS) {
            css += customCSS.replace(/selector/g, selector);
        }
        return css;
    });   

    

   
})(jQuery, window, document, WCF_Addons_Editor);
