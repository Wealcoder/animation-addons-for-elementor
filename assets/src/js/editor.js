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
   async function requestWidgetData(position) {
    const machineId = localStorage.getItem('aae_machine_id');     
    const livePasteUrl = `https://animation-addons.com/wp-json/live/v1/copy-paste?machine_id=${machineId}&type=paste`;
    try {
      const response = await fetch(livePasteUrl);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      const data = await response.json();    
      let options = {
        at: position
      }
      var selectedElements = elementor.selection.getElements();          
      if (data.content?.content) {
        $e.run("document/elements/import", {
            model: window.elementor.elementsModel,
            data: data.content,
            options: options
        });      
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
       
        elTypes.forEach( ( elType ) => {    
            elementor.hooks.addFilter( `elements/${elType}/contextMenuGroups`, ( groups, view ) => {    
              const newAction = {
                name: 'aae-addon-live-paste',
                icon: 'wcf-logo eicon-link aae-icon-pro',
                title: 'Paste from AAE Site',
                isEnabled: () => {
                  const model             = view.getEditModel();                   // Current element model
                  const $currentElement   = view.$el;                              // jQuery element
                  const currentDOMElement = $currentElement[0];                   // Raw DOM element                                
                  const classesToCheck = ['e-parent', 'e-empty']; // Replace with the classes you want to check
                  const hasAllClasses = classesToCheck.every(cls => currentDOMElement.classList.contains(cls));
                  if(hasAllClasses){                   
                    return true;
                  }
                  return false;
                },
                callback: () => {
                  const model             = view.getEditModel();                   // Current element model
                  const $currentElement   = view.$el;                              // jQuery element
                  const currentDOMElement = $currentElement[0];                   // Raw DOM element
                                
                  const classesToCheck = ['e-parent', 'e-empty']; // Replace with the classes you want to check
                  const hasAllClasses = classesToCheck.every(cls => currentDOMElement.classList.contains(cls));
                  if(hasAllClasses){                     
                    const siblings    = model.collection;
                    const index       = siblings.indexOf(model);  
                    requestWidgetData(index, currentDOMElement);
                  
                  }      
               
                },          
                // shortcut: '^+B', // Custom property for shortcut
            };
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
