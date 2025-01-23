;(function($){
    "use strict";

    const WCFThemeBuilder = {

        instance: [],
        templateId: 0,

        init: function() {
            this.renderPopup();        
            this.reinitSelect2();
            //open popup onclick
            $( 'body.post-type-wcf-addons-template #wpcontent' ).on( 'click', '.page-title-action, .row-title, .row-actions .edit > a', this.openPopup );
            $(document )
                .on( 'click', '.wcf-addons-body-overlay,.wcf-addons-template-edit-cross', this.closePopup )
                .on( 'click', ".wcf-addons-tmp-save", this.savePost )
                .on( 'click', '.wcf-addons-tmp-elementor', this.redirectEditPage )
                .on( 'wcf_template_edit_popup_open', this.displayLocation)
                .on( 'change', '#wcf-addons-template-type, #wcf-addons-hf-display-type', this.displayLocation);
                
                $('#wcf-addons-hf-s-display-type').on('select2:select', function (e) {
                    // Get the selected data
                    var selectedItems = $(this).val(); // Get the selected values
             
                    var uniqueItems = [...new Set(selectedItems)]; // Remove duplicates
                
                    // Update the Select2 element with unique values
                    $(this).val(uniqueItems).trigger('change');              
                 
                });
                
        },
        
        reinitSelect2: function(){
        
            $('#wcf-addons-hf-s-display-type').select2({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    method: 'post',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page || 1,
                            action: 'wcf_get_posts_by_query',
                            nonce: WCF_Theme_Builder.nonce,
                        };
                    },
                    processResults: function (data) {  
                       
                        let uniqueData = [];
                        let seen = new Set();            
                        data.forEach(item => {
                            if (!seen.has(item.id)) {
                                seen.add(item.id);
                                uniqueData.push(item);
                            }
                        });            
                        return {
                            results: uniqueData
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search and select an option',
                allowClear: true
            });
           
        },

        // Render Popup HTML
        renderPopup: function( event ){
            var popupTmp = wp.template( 'wcf-addons-ctppopup' ),
                content = null;

            content = popupTmp( {
                templatetype: WCF_Theme_Builder.templatetype,
                hflocation: WCF_Theme_Builder.hflocation,
                archivelocation: WCF_Theme_Builder.archivelocation,
                singlelocation: WCF_Theme_Builder.singlelocation,
                editor:       WCF_Theme_Builder.editor,
                heading:      WCF_Theme_Builder.labels,
            } );

            $( 'body' ).append( content );

        },

        // Edit PopUp
        openPopup: function( event ) {
            event.preventDefault();
                
            let rowId = $(this).closest('tr').attr('id'),
                tmpId = null,
                elementorEditlink = null;

            if ( rowId ) {
                tmpId = rowId.replace( 'post-', '' );
                elementorEditlink = 'post.php?post='+tmpId+'&action=elementor';
            }


            $('.wcf-addons-tmp-save').attr( 'data-tmpid', tmpId );
            $('.wcf-addons-tmp-elementor').attr( { 'data-link': elementorEditlink, 'data-tmpid': tmpId } );
                 
            if( tmpId ){
                //fetch existing template data
                $.ajax({
                    url: WCF_Theme_Builder.ajaxurl,
                    data: {
                        'action': 'wcf_get_template',
                        'nonce' : WCF_Theme_Builder.nonce,
                        'tmpId' : tmpId,
                    },
                    type: 'POST',

                    beforeSend: function(){

                    },

                    success:function( response ) {
                        //type
                        document.querySelector("#wcf-addons-template-type option[value='"+response.data.tmpType+"']").selected = "true";
                        $('#wcf-addons-template-title').attr( 'value', response.data.tmpTitle );

                       $('.wcf-addons-tmp-elementor').removeClass( 'disabled').removeAttr('disabled','disabled');
                    },

                    complete:function( response ){
                        $('#wcf-addons-hf-s-display-type').val(null).trigger('change');  
                        // Fire custom event.
                        $(document).trigger('wcf_template_edit_popup_open');

                        //display
                        let temDisplay = $('.hf-location:visible select, .archive-location:visible select, .single-location:visible select');
                        temDisplay.find("option[value='"+response.responseJSON.data.tmpLocation+"']")[0].selected = "true";

                        //display specific locations
                        if (response.responseJSON.data.tmpSpLocation) {
                            $.each(response.responseJSON.data.tmpSpLocation, function (i, item) {
                                // Create a DOM Option and pre-select by default
                                let data = {id: i, text: item};
                                let newOption = new Option(data.text, data.id, true, true);
                                // Append it to the select
                                $('#wcf-addons-hf-s-display-type').append(newOption).trigger('change');
                            });
                        }


                        $('.wcf-addons-template-edit-popup-area').addClass('open-popup');
                    },

                    error: function( errorThrown ){
                        console.log( errorThrown );
                    }

                });

            } else {

                // Fire custom event.
                $(document).trigger('wcf_template_edit_popup_open');
                $('.wcf-addons-tmp-elementor').addClass( 'button disabled').attr('disabled','disabled');
                $('.wcf-addons-template-edit-popup-area').addClass('open-popup');

            }

        },

        // Close Popup
        closePopup: function( event ) {
            $('.wcf-addons-template-edit-popup-area').removeClass('open-popup');
            $('#wcf-addons-hf-s-display-type').select2('destroy');   
            const $_select2html = `<label class="wcf-addons-template-edit-label"></label>
                                <select class="wcf-addons-template-edit-input" name="wcf-addons-hf-s-display-type[]"
                                        id="wcf-addons-hf-s-display-type" multiple="multiple">
                                </select>`;
            $('.wcf-addons-template-edit-field.hf-s-location').html($_select2html);                    
            WCFThemeBuilder.reinitSelect2();
        },

        // Save Post
        savePost: function (event) {

            const $this = $(this),
                tmpId = event.target.dataset.tmpid ? event.target.dataset.tmpid : '',
                title = $('#wcf-addons-template-title').val(),
                tmpType = $('#wcf-addons-template-type').val(),
                temDisplay = $('.hf-location:visible select, .archive-location:visible select, .single-location:visible select').val(),
                specificsDisplay = $('.hf-s-location:visible select').val();

            $.ajax({
                url: WCF_Theme_Builder.ajaxurl,
                data: {
                    'action': 'wcf_save_template',
                    'nonce': WCF_Theme_Builder.nonce,
                    'tmpId': tmpId,
                    'title': title,
                    'tmpType': tmpType,
                    'tmpDisplay': temDisplay,
                    'specificsDisplay': JSON.stringify(specificsDisplay) ?? null,
                },
                type: 'POST',

                beforeSend: function () {
                    $this.text(WCF_Theme_Builder.labels.buttons.save.saving);
                    $this.addClass('updating-message');
                },

                success: function (data) {

                    if (tmpId == '') {

                        if (data.data.id) {
                            var elementorEditlink = 'post.php?post=' + data.data.id + '&action=elementor';
                        }
                        $('.wcf-addons-tmp-save').attr('data-tmpid', data.data.id);
                        $('.wcf-addons-tmp-elementor').attr({
                            'data-link': elementorEditlink,
                            'data-tmpid': data.data.id
                        });
                        $('.wcf-addons-tmp-elementor').removeClass('disabled').removeAttr('disabled', 'disabled');

                    } else {

                    }
                },

                complete: function (data) {
                    $('body.post-type-woolentor-template').removeClass('loading');
                    $this.removeClass('updating-message');
                    $this.text(WCF_Theme_Builder.labels.buttons.save.saved);
                },

                error: function (errorThrown) {
                    console.log(errorThrown);
                }

            });

        },

        // Redirect Edit Page
        redirectEditPage: function( event ){
            event.preventDefault();

            var $this   = $( this ),
                link = $this.data( 'link' ) ? $this.data( 'link' ) : '';

            window.location.replace( WCF_Theme_Builder.adminURL + link );

        },

        displayLocation: function (event){
            let type = $('#wcf-addons-template-type').val();

            $('.hf-s-location').addClass('hidden');

            if ('archive' === type) {
                $('.archive-location').removeClass('hidden');
                $('.hf-location, .single-location').addClass('hidden');
            } else if ('single' === type) {
                $('.single-location').removeClass('hidden');
                $('.hf-location, .archive-location').addClass('hidden');
            } else {
                $('.hf-location').removeClass('hidden');
                $('.single-location, .archive-location').addClass('hidden');

                setTimeout(function () {
                    //specifics location for page post taxonomy etc
                    if ('specifics' === $('#wcf-addons-hf-display-type').val()) {
                        $('.hf-s-location').removeClass('hidden');
                    }
                }, 100);
            }
        }

    };

    WCFThemeBuilder.init();


})(jQuery);
