/**
 * WCF Addons Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Addons_Editor*/

(function ($, window, document, config) {
    elementor.hooks.addAction('panel/open_editor/widget/wcf--mailchimp', function (panel, model, view) {

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
                            text: Object.values(response)
                        };
                        const newOption = new Option(data.text, data.id, false, false);
                        audience.append(newOption).trigger('change');
                    } else {
                        audience.empty();
                    }
                }
            });
        };

        const $element = panel.$el.find('[data-setting="mailchimp_api"]');

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
 

   function generateFingerprintString() {
        const fingerprint = {
            userAgent: navigator.userAgent, // safe fallback
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            colorDepth: window.screen.colorDepth,
            language: navigator.language,
            languages: navigator.languages ? navigator.languages.join(",") : "unknown",
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            hardwareConcurrency: navigator.hardwareConcurrency || "unknown",
            deviceMemory: navigator.deviceMemory || "unknown",
            touchSupport: navigator.maxTouchPoints || 0,
        };
        return JSON.stringify(fingerprint);
    }

    function longHash(str) {
        const encoder = new TextEncoder();
        const data = encoder.encode(str);
    
        // FNV-1a 32-bit hash with multi-pass shifting
        let hash1 = 2166136261;
        let hash2 = 2166136261;
        let hash3 = 2166136261;
        
        for (let i = 0; i < data.length; i++) {
            const byte = data[i];
            hash1 ^= byte;
            hash1 = (hash1 * 16777619) >>> 0;
    
            hash2 ^= byte;
            hash2 = (hash2 * 16777619) >>> 0;
            hash2 = ((hash2 << 5) | (hash2 >>> 27)) >>> 0; // Rotate left
    
            hash3 ^= byte;
            hash3 = (hash3 * 16777619) >>> 0;
            hash3 = ((hash3 << 11) | (hash3 >>> 21)) >>> 0; // Rotate left more
        }
    
        // Concatenate and return as longer hex string
        return (
            hash1.toString(16).padStart(8, '0') +
            hash2.toString(16).padStart(8, '0') +
            hash3.toString(16).padStart(8, '0')
        );
    }
    

    function getFingerprintId() {
        const fingerprintString = generateFingerprintString();
        const fingerprintId = longHash(fingerprintString);
        return fingerprintId; // Ready to store or send
    }

   // Function to request widget data
   async function requestWidgetData() {
    const machineId = getFingerprintId();  
    const livePasteUrl = `https://animation-addons.com/wp-json/live/v1/copy-paste?machine_id=${machineId}&type=paste`;
    try {
      const response = await fetch(livePasteUrl);
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      const data = await response.json();
     
        let $opts = {
            model: '',
            container: elementor.getPreviewContainer()                       
        };
        if(data.content?.content){
            data.content.content.forEach(element => {                       
                var newWidget = {};
                newWidget.elType = element.elType;
                newWidget.settings = element.settings;
                newWidget.elements = element.elements;
                $opts.model = newWidget;
                $e.run("document/elements/create", $opts);
            });
            elementor.notifications.showToast({
                message: elementor.translate('Content Pasted! ')
            });
        }else{
            elementor.notifications.showToast({
                message: elementor.translate('Content not found! ')
            });
        }       

       
    } catch (error) {
        elementor.notifications.showToast({
            message: elementor.translate('Only same browser tab work, App using browser fingerprint for live copy ')
        });
    }
  }
  
   
    window.addEventListener( 'elementor/init', () => {          
    
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


