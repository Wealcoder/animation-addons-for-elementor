( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const Social_Share_Count = function ($scope, $) {
    
        const $socials = $('.default-details-social-media a', $scope);   
        $share_count = $('.default-details-social-media .aae-share-count'); 
        $socials.on('click', function(e){               
                const type = $(this).attr('data-type') || 'facebook';  
               
                if(WCF_ADDONS_JS?.post_id) {
                    $.ajax({
                        url: WCF_ADDONS_JS.ajaxUrl, // WordPress AJAX handler
                        type: 'POST',
                        data: {
                            action: 'aae_post_shares', // Custom action name
                            post_id: WCF_ADDONS_JS.post_id, // Post ID to update share count
                            nonce : WCF_ADDONS_JS._wpnonce,
                            social: type
                        },
                        success: function(response) {
                           
                            if(response?.success){
                               if($share_count.length){
                                $share_count.each(function(){                                  
                                    if(response.data.post_shares[$(this).attr('data-type')]){                                     
                                        $(this).text(response.data.post_shares[$(this).attr('data-type')]);                                     
                                    }
                                });
                              
                               }
                            }
                        },
                        error: function() {                          
                        }
                    });
                }               
        });        
    };

    // Make sure you run this code under Elementor.
    $( window ).on( 'elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction( 'frontend/element_ready/wcf--blog--post--social-share.default', Social_Share_Count );
    } );
} )( jQuery );