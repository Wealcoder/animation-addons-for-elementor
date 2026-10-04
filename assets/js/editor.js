/**
 * WCF Addons Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Addons_Editor*/

(function ($, window, document, config) {
  const forms_fields_ajax_request = function ($api, $list_id) {
    jQuery.ajax({
      type: "post",
      dataType: "json",
      url: config.ajaxUrl,
      data: {
        action: "wcf_mailchimp_list_fields",
        nonce: config._wpnonce,
        api: $api,
        list_id: $list_id,
      },
      success: function (response) {
        console.log(response);
      },
    });
  };
  function MailpushOptions(data, $mailchimp_lists) {
    let newOption = new Option(
      AAE_MailChimp.text,
      AAE_MailChimp.id,
      false,
      false
    );
    $mailchimp_lists.append(newOption).trigger("change");
  }
  const ajax_request = function ($api, $mailchimp_lists) {
    if (window.AAE_MailChimp) {
      MailpushOptions(window.AAE_MailChimp, $mailchimp_lists);
    }
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
        const audience = $mailchimp_lists;
        if (Object.keys(response).length) {
          const data = {
            id: Object.keys(response),
            text: Object.values(response),
          };
          window.AAE_MailChimp = data;
          MailpushOptions(window.AAE_MailChimp, $mailchimp_lists);
        } else {
          audience.empty();
        }
      },
    });
  };

  function attachMailchimpEvent(panel) {
    const $mailchimp_lists = panel.$el.find('[data-setting="mailchimp_lists"]');
    const $element = panel.$el.find('[data-setting="mailchimp_api"]');

    if ($element.val()) {
      ajax_request($element.val(), $mailchimp_lists);
    }

    $mailchimp_lists.on("change", function () {
      if ($mailchimp_lists.val() && $element.val()) {
        forms_fields_ajax_request($element.val(), $mailchimp_lists.val());
      }
    });

    $element.on("keyup", function () {
      ajax_request($element.val(), $mailchimp_lists);
    });
  }

  elementor.channels.editor.on(
    "editor:widget:wcf--mailchimp:_section_mailchimp:activated",
    (panelView) => {
      attachMailchimpEvent(panelView);
    }
  );

  elementor.channels.editor.on(
    "editor:widget:aae--advanced-mailchimp:_section_mailchimp:activated",
    (panelView) => {
      attachMailchimpEvent(panelView);
    }
  );

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
    }
  );

  // Custom Css
  elementor.hooks.addFilter("editor/style/styleText", function (css, context) {
    if (!context) {
      return;
    }
    const model = context.model,
      customCSS = model.get("settings").get("wcf_custom_css");
    let selector = ".elementor-element.elementor-element-" + model.get("id");
    if ("document" === model.get("elType")) {
      selector = elementor.config.document.settings.cssWrapperSelector;
    }
    if (customCSS) {
      css += customCSS.replace(/selector/g, selector);
    }
    return css;
  });
})(jQuery, window, document, WCF_Addons_Editor);
