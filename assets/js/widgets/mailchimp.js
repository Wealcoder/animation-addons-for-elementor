
/* global WCF_ADDONS_JS */
( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

    const MailChimp = function MailChimp($scope) {
        const elForm = $scope.find('.wcf-mailchimp-form'),
            elMessage = $scope.find('.mailchimp-response-message'),
            elFormDataAttr = elForm.data(),
            Settings = $scope.attr('data-settings') ? JSON.parse($scope.attr('data-settings')) : {};

            elForm.on('submit', function (e) {
            e.preventDefault();

            const data = {};
            $.extend(data, elFormDataAttr, {
                action: 'wcf_mailchimp_ajax',
                nonce: WCF_ADDONS_JS._wpnonce,
                subscriber_info: elForm.serialize(),
            });
          
            $.ajax({
                type: 'post',
                url: WCF_ADDONS_JS.ajaxUrl,
                data: data,
                success: function success(response) {
                    elForm.trigger('reset');

                    if (response.status) {
                        elMessage.removeClass('error');
                        elMessage.addClass('success');
                        if(Settings.success_message && response.status === 'success_message'){
                            elMessage.text(Settings.success_message);
                        }else if(Settings.confirmation_message && response.status === 'confirmation_message'){
                             elMessage.text(Settings.confirmation_message);
                        }else{
                            elMessage.text(response.msg);
                        }                       
                       
                    } else {
                        elMessage.addClass('error');
                        elMessage.removeClass('success');
                        elMessage.text(response.msg);
                    }

                    const hideMsg = setTimeout(function () {
                        elMessage.removeClass('error');
                        elMessage.removeClass('success');
                        clearTimeout(hideMsg);
                    }, 5000);
                },
                error: function error(_error3) {                
                }
            });
        });

        elForm.removeAttr('data-key');
        // E-News
        const radios = document.querySelectorAll('.enews');
        const slider = document.createElement('span');
        slider.classList.add('slider');

        const inputContainer = document.querySelector('.aae-radio .input');

        if (inputContainer) {
            inputContainer.appendChild(slider);
        } 

        radios.forEach(radio => {
            radio.addEventListener('change', function () {
            
                if (this.value === "NO") {
                    slider.style.left = "50%";
                } else {
                    slider.style.left = "0%";
                }
            });
        });


    };

    // Make sure you run this code under Elementor.
    $( window ).on( 'elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wcf--mailchimp.default', MailChimp );
        elementorFrontend.hooks.addAction( 'frontend/element_ready/aae--advanced-mailchimp.default', MailChimp );
    } );
} )( jQuery );