/**
 * WCF Template Library Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Template_library_Editor*/

(function ($, window, document, config) { 
    let Template_Library_data = {};
    let Template_Library_Chunk_data = [];
    // API for get requests
     let fetchRes = fetch("https://themecrowdy.com/wp-json/api/v1/list");    

    const activePlugin = async () => {
        await fetch(WCF_TEMPLATE_LIBRARY.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            Accept: "application/json",
          },
    
          body: new URLSearchParams({
            action: "activate_from_editor_plugin",
            action_base: "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
            nonce: WCF_TEMPLATE_LIBRARY.nonce,
          }),
        })
          .then((response) => {
            return response.json();
          })
          .then((return_content) => {          
            if (return_content?.success) { 
              window.location.reload();
            }
          });
      };   
    // FetchRes is the promise to resolve
    fetchRes.then(res => res.json()).then(d => {
            Template_Library_data = d.library;       
            Template_Library_data['template_types'] = WCF_TEMPLATE_LIBRARY.template_types;
            localStorage.setItem("aae_template_lib_data", Template_Library_data);
    });

    //get type specific templates
    const get_type_templates = function (type) {
        let templates = [];
        if (Template_Library_data['templates'] !== undefined) {
            Template_Library_data['templates'].forEach((template, index) => {                
                if (type === template.type) {
                    if(WCF_TEMPLATE_LIBRARY?.config?.wcf_valid && WCF_TEMPLATE_LIBRARY?.config?.wcf_valid === true){
                        template['valid'] = 'yes';
                    }                  
                    templates.push(template);
                }
            });
        }
        return templates.reverse();
    };

    //get specific category templates
    const get_category_templates = function (category = '', type) {
        const type_templates = get_type_templates(type);
        let templates      = type_templates;
        if (type_templates.length && '' !== category) {
            templates = [];
            for (let template of type_templates) {

                //if template has no category
                if ('' === template.subtype) {
                    continue;
                }

                const categories = template.subtype.split(",");

                if (categories.includes(category)) {
                    templates.push(template);
                }
            }
        }       
        Template_Library_Chunk_data = aaetemplate_chunkArray(templates,30);
       
        return Template_Library_Chunk_data.shift();       
    };

     //get specific category templates
     const search_category_templates = function (text = '', type) {
        let types          = $('#elementor-template-library-header-menu .elementor-active').attr('data-tab') || 'block';
        const type_templates = get_type_templates(types);
        let templates      = type_templates;
       
        if (type_templates.length && '' !== text) {

            templates = [];

            for (let template of type_templates) {

                //if template has no category
                if ('' === template.subtype) {
                    continue;
                }
                text = text.toLowerCase();
                
                if (template.title.toLowerCase().includes(text)) {
                    templates.push(template);                   
                }
            }
        }       
        Template_Library_Chunk_data = aaetemplate_chunkArray(templates,30);
       
        return Template_Library_Chunk_data.shift();       
    };

    const aaetemplate_chunkArray = function(array, chunkSize) {
        const result = [];
        for (let i = 0; i < array.length; i += chunkSize) {
          result.push(array.slice(i, i + chunkSize));
        }
        return result;
    }

    //get specific categories
    const get_categories = function (type) {
        let type_categories = new Set();
        let all_categories = [];
        const type_templates = get_type_templates(type);

        if (type_templates.length) {

            for (let template of type_templates) {

                //if template has no category
                if ('' === template.subtype) {
                    continue;
                }

                const categories = template.subtype.split(",");

                categories.forEach(sca => {
                    type_categories.add(parseInt(sca));
                });
            }
        }

        for (let item of Template_Library_data.categories) {
            for (let category of type_categories) {
                if (item.id === category) {
                    all_categories.push(item);
                    break;
                }
            }
        }

        return all_categories;
    };

    $("document").ready(function () {
        let templateAddSection = $("#tmpl-elementor-add-section");
        if (0 < templateAddSection.length) {
            var oldTemplateButton = templateAddSection.html();
            oldTemplateButton = oldTemplateButton.replace(
                '<div class="elementor-add-section-drag-title',
                '<div class="elementor-add-section-area-button elementor-add-wcf-template-button"></div><div class="elementor-add-section-drag-title'
            );
            templateAddSection.html(oldTemplateButton);
        }

        elementor.on("preview:loaded", function () {
            $(elementor.$previewContents[0].body).on("click", ".elementor-add-wcf-template-button", function (event) {
                event.preventDefault();
              
                window.wcftmLibrary = elementorCommon.dialogsManager.createWidget("lightbox", {
                    id: "wcf-template-library",
                    onShow: function () {
                        this.getElements("widget").addClass("elementor-templates-modal");
                        this.getElements("header").remove();
                        this.getElements("message").remove();
                        this.getElements("buttonsWrapper").remove();
                        let t = this.getElements("widgetContent");
                        render_popup(t);
                    },
                    onHide: function () {
                        window.wcftmLibrary.destroy();
                    },

                });
                
                window.wcftmLibrary.getElements("header").remove();
             
                window.wcftmLibrary.show();
              
                $(window).trigger("resize"); //fixed modal position
               
                let active_menu_first_load = 0;

                function render_popup(t) {
                    let tmpTypes = wp.template('wcf-templates-header');
                    content = null;
                    
                    content = tmpTypes({
                        template_types: Template_Library_data.template_types,
                    });

                    t.html(content);

                    //active menu
                    active_menu(t);

                    //category select
                    selected_category(t);

                    render_single_template(t);

                    search_function();

                    template_import();
                }

                function render_templates(t, activeMenu, category='') {

                    let templates = wp.template( 'wcf-templates' );
                    contents = null;
                    contents = templates({
                        templates: get_category_templates(category, activeMenu),
                        categories: get_categories(activeMenu),
                    });
                
                    t.append(contents);
                    aaeadddon_run_lazy_load();
                    let is_loading = true;
                    loading(is_loading);
                   
                    $($('.wcf-library-template').last()).find('img').on('load', function () {
                        is_loading = false;
                        loading(is_loading);
                    });
                }

                function render_single_template(t) {
                  // let template = $('.thumbnail');
                  const backContent = $('#wcf-template-library .dialog-widget-content').html();
                    $(document).on('click', '.thumbnail', function () {
                        let _that = $(this);
                        const template_id = _that.closest('.wcf-library-template').data('id');
                        const template_url = _that.closest('.wcf-library-template').data('url');                     

                        let singleTmp = wp.template('wcf-templates-single');
                        content_single = null;
                        content_single = singleTmp({
                            template_link: template_url,
                        });

                        t.html(content_single);
                        //iframe is loaded
                        let is_loading = true;
                        loading(is_loading);
                        $('#wcf-template-library iframe').on('load', function () {
                            is_loading = false;
                            loading(is_loading);
                        });
                        template_import(template_id);
                    });

                       //single back                   
                    $(document).on('click', '#wcf-template-library-header-preview-back' ,function () {
                       
                        $('#wcf-template-library .dialog-widget-content').html(backContent);
                        loading(false);
                        //active menu
                        active_menu(t);
                        //category select
                        selected_category(t);

                        render_single_template(t);

                        search_function();

                        template_import();
                    });

                     //hide modal
                     $(document).on('click','.elementor-templates-modal__header__close' ,function (){
                        window.wcftmLibrary.hide();
                    });
                }

                function active_menu(t) {
                    active_menu_first_load++;
                    const menu_item = $('.wcf-template-library--header .elementor-template-library-menu-item');
                    menu_item.click(function () {

                        if ($(this).hasClass("elementor-active")) {
                            return;
                        }

                        menu_item.removeClass("elementor-active");

                        $(this).addClass("elementor-active");

                        activeMenu = $(this).attr("data-tab");

                        $(t).find('.dialog-message').remove();

                        render_templates(t, activeMenu);

                        //category select ensure dom selections
                        selected_category(t);

                        render_single_template(t);

                        search_function();

                        template_import();
                    });

                    //hide modal
                    $('.elementor-templates-modal__header__close').on('click', function (){
                        window.wcftmLibrary.hide();
                    });

                    if (active_menu_first_load >= 1){
                        return;
                    }

                    let activeMenu = $('.wcf-template-library--header .elementor-active').attr('data-tab');
                    render_templates(t, activeMenu);
                }

                function selected_category(t) {
                    $('#wcf-template-library-filter-subtype').on('change', function (e) {
                        let activeMenu = $('.wcf-template-library--header .elementor-active').attr('data-tab');
                        let valueSelected = this.value;
                        $(t).find('.dialog-message').remove();
                        render_templates(t, activeMenu, valueSelected);

                        //selected
                        $("#wcf-template-library-filter-subtype option[value='" + valueSelected + "']").attr("selected","selected");

                        selected_category(t);
                        render_single_template(t);
                        search_function();
                        template_import();
                    });
                }

                function search_function() {
                    $('#wcf-template-library-filter-text').on('keyup', function () {
                        let filter = this.value;                        
                        const container = document.querySelector('.wcf-library-templates');                         
                        let currentchunk = search_category_templates(filter);
                        container.innerHTML = '';
                        currentchunk.forEach(item => {
                            const templateHtml = generateTemplate(item);
                            container.innerHTML += templateHtml;  // Add each generated HTML to the container
                        });
                        
                        setTimeout(function(){
                            let elements = $('.wcf-library-template');   
                            let re = new RegExp(filter, 'i');
                            elements.each((x, element) => {
                                let title = $(element).find('.title')[0];
                                if (re.test(title.textContent)) {
                                    title.innerHTML = title.textContent.replace(re, '<b>$&</b>');                                    
                                } 
                            }); 
                        },100)
                    });
                }

                function template_import(id = null) {                    
                    let is_loading = true;
                    $(document).on('click' ,'.library--action.insert', function () {
                        let _that = $(this);
                        let template_id = id;
                        if (null === template_id) {
                            template_id = $(this).closest('.wcf-library-template').data('id');
                        }
                        loading(is_loading);
                        _that.hide();

                        window.wcftmLibrary.currentRequest = elementorCommon.ajax.addRequest("get_wcf_template_data", {
                            unique_id: template_id,
                            data: {edit_mode: !0, display: !0, template_id: template_id},
                            success: function (e) {                                  
                                $e.run("document/elements/import", {
                                    model: window.elementor.elementsModel,
                                    data: e,
                                });
                                is_loading = false;
                                window.wcftmLibrary.hide();
                            }
                        }).fail((function () {
                        }));
                    });
                }

                function loading(is_loading) {
                    let loading = $('.wcf-template-library--loading');

                    if (!is_loading) {
                        loading.hide();
                        loading.attr("hidden");
                    } else {
                        loading.show();
                        loading.removeAttr("hidden");
                    }
                }

            });
        });
    });
    
    $(document).on('click', '.aaeplugin-activate', function(e){
       e.preventDefault();
       var userConfirmed = confirm("Are you sure you want to activate plugin? Any unsaved changes will be lost. Please Save change.");
       if (userConfirmed) {
         activePlugin();
       }    
    });

    function aaeadddon_run_lazy_load(){
        const listItems = document.querySelectorAll(".aaeaadon-loadmore-footer");
        const lastItem = listItems[listItems.length - 1];
      
        const observerOptions = {
          root: null, // Uses the viewport as the root
          rootMargin: "0px",
          threshold: 0.1 // Trigger when 10% of the element is visible
        };
      
        const observerCallback = (entries, observer) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              let currentchunk = Template_Library_Chunk_data.shift();         
              const container = document.querySelector('.wcf-library-templates');         
              if(currentchunk){
                currentchunk.forEach(item => {
                    const templateHtml = generateTemplate(item);
                    container.innerHTML += templateHtml;  // Add each generated HTML to the container
                });
              }     
            }
          });
        };
      
        const observer = new IntersectionObserver(observerCallback, observerOptions);
        observer.observe(lastItem);
      };

      const generateTemplate = (item, pluginSlug='animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php', allPlugins=[], activePlugins=[]) => {
        return `
            <div class="wcf-library-template" data-id="${item.id}" data-url="${item.url}">
                <div class="thumbnail">
                    <img src="${item.thumbnail}" alt="${item.title}">
                </div>
                
                ${item?.valid && item.valid ? `
                    <!-- Show the 'Insert' button if the template is valid -->
                    <button class="library--action insert">
                        <i class="eicon-file-download"></i>
                        Insert
                    </button>
                ` : `
                    <!-- Show premium or activation buttons based on plugin status -->
                    ${!allPlugins.includes(pluginSlug) && !activePlugins.includes(pluginSlug) ? `
                        <!-- Show 'Go Premium' button if the plugin is not installed -->
                        <a href="https://animation-addons.com" class="library--action pro" target="_blank">
                            <i class="eicon-external-link-square"></i>
                            Go Premium
                        </a>
                    ` : ''}
                    ${activePlugins.includes(pluginSlug) ? `
                        <!-- Show 'Pro' button if the plugin is installed and active -->
                        <button class="library--action pro">
                            <i class="eicon-external-link-square"></i>
                            Pro
                        </button>
                    ` : ''}
                    ${allPlugins.includes(pluginSlug) && !activePlugins.includes(pluginSlug) ? `
                        <!-- Show 'Activate' button if the plugin is installed but not active -->
                        <button class="library--action pro aaeplugin-activate">
                            <i class="eicon-external-link-square"></i>
                            Activate
                        </button>
                    ` : ''}
                `}
                
                <p class="title">${item.title}</p>
            </div>
        `;
    };
    
      

})(jQuery, window, document);
