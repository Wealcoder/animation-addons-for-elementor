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

    function getFingerprintId() {
        if(!localStorage.getItem('aae_machine_id')){
            const url = 'https://animation-addons.com/wp-json/live/v1/fingerprint/';    
            return $.get(url)
                .done(function(data) {
                    localStorage.setItem('aae_machine_id', data.ip);               
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch fingerprint ID:', textStatus, errorThrown);
                });
            }    
    }
        
    
   // Function to request widget data
   async function requestWidgetData() {
    const machineId = localStorage.getItem('aae_machine_id');     
    const livePasteUrl = `https://animation-addons.com/wp-json/live/v1/copy-paste?machine_id=${machineId}&type=paste`;
    try {
      const response = await fetch(livePasteUrl);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      const data = await response.json();

      let $opts = {
        model: "",
        container: elementor.getPreviewContainer(),
      };
      if (data.content?.content) {
        $e.run("document/elements/import", {
          model: window.elementor.elementsModel,
          data: data.content,
          options: {
            at: 0
          }
      });
        // data.content.content.forEach((element) => {
        //   var newWidget = {};
        //   if(elementor.widgetsCache[element.elType]){
        //     newWidget.elType   = element.elType;
        //     newWidget.settings = element.settings;
        //     newWidget.elements = element.elements;
        //     $opts.model        = newWidget;
        //     $e.run("document/elements/create", $opts);
        //   }
          
        // });
        elementor.notifications.showToast({
          message: elementor.translate("Live Content Pasted! "),
        });
      } else {
        elementor.notifications.showToast({
          message: elementor.translate("Live Content not found! "),
        });
      }
    } catch (error) {
      elementor.notifications.showToast({
        message: elementor.translate(
          "Only same browser tab work, App using browser fingerprint for live copy "
        ),
      });
    }
  }  
  
    window.addEventListener( 'elementor/init', () => {          
        getFingerprintId();
        const elTypes = [ 'widget', 'column', 'section', 'container' ];    
        const newAction = {
            name: 'aae-addon-live-paste',
            icon: 'wcf-logo eicon-link aae-icon-pro',
            title: 'Paste from AAE Site',
            isEnabled: () => true,
            callback: () => requestWidgetData(),
            shortcut: '^+B', // Custom property for shortcut
        };
        elTypes.forEach( ( elType ) => {    
            elementor.hooks.addFilter( `elements/${elType}/contextMenuGroups`, ( groups, view ) => {    
                groups.forEach( ( group ) => {
                    if ( 'general' === group.name ) {
                        group.actions.push( newAction );
                    }
                } );        
                return groups;        
            } );
    
        } );    
    
    } ); 
    
    // End Live Copy paste
    
  
   
})(jQuery, window, document, WCF_Addons_Editor);
