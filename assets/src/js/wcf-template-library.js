/* eslint-disable semi */
/* eslint-disable arrow-parens */
/**
 * WCF Template Library Editor Core
 * @version 1.0.0
 */

/* global jQuery, WCF_Template_library_Editor*/

(function ($, window, document, config) {
  let storeCategory;
  let currentPage = 1;
  let currentCategory = "";
  let currentType = "";
  let active_menu_first_load = 0;
  let currentColorType = "";
  let currentSection = null;
  let active_resize_first_load = 0;
  let allCategory = async () => {   
    await fetch(
      "https://block.animation-addons.com/wp-json/templates/v2/wcf-tpl-category"
    )
      .then((res) => res.json())
      .then((res) => {
        storeCategory = res;
      });
  };


  allCategory();
  let aae_domain =
    "https://block.animation-addons.com/wp-json/wp/v2/wcf-templates?page=1&per_page=100&subtype=block";
  const activePlugin = async () => {
    await fetch(WCF_TEMPLATE_LIBRARY.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },

      body: new URLSearchParams({
        action: "activate_from_editor_plugin",
        action_base:
          "animation-addons-for-elementor-pro/animation-addons-for-elementor-pro.php",
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

  const templates_validate = function (remotetemplates) {
    let templates = [];

    remotetemplates.forEach((template, index) => {    
      if (
        (WCF_TEMPLATE_LIBRARY?.config?.wcf_valid &&
        WCF_TEMPLATE_LIBRARY?.config?.wcf_valid === true) || 
        template?.is_pro == '0'
      ) {
        template["valid"] = "yes";
      }
      templates.push(template);
    });

    return templates.reverse();
  };

  //get specific category templates
  const get_category_templates = async function (
    category = "",
    type,
    page = 1,
    color_type = ""
  ) {
    let result = [];

    let query_domain = new URL(aae_domain);
    if (type) {
      query_domain.searchParams.set("subtype", type);
    }
    if (category && "" !== category) {
      query_domain.searchParams.set("cat", category);
    }

    if (page) {
      query_domain.searchParams.set("page", page);
      currentPage = page;
    }
    if (color_type) {
      query_domain.searchParams.set("color_type", color_type);
    }
    try {
      const response = await fetch(query_domain);
      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);
      const data = await response.json();
      result = data.templates || [];
    } catch (error) {
      console.error("Fetch Error:", error);
    }

    return templates_validate(result);
  };

  //get specific category templates
  const search_category_templates = async function (text = "") {
    let type =
      $("#elementor-template-library-header-menu .elementor-active").attr(
        "data-tab"
      ) || "block";
    let result = [];
    let query_domain = new URL(aae_domain);
    if (type) {
      query_domain.searchParams.set("subtype", type);
    }
    if (text && "" !== text) {
      query_domain.searchParams.set("s", text);
    }
    const cat = $("wcf-template-library-filter-subtype").val();
    if (cat && "" !== cat) {
      query_domain.searchParams.set("cat", cat);
    }

    try {
      const response = await fetch(query_domain);
      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);
      const data = await response.json();
      result = data.templates || [];
    } catch (error) {
      console.error("Fetch Error:", error);
    }

    return templates_validate(result);
  };

  //get specific categories

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
      $(elementor.$previewContents[0].body).on(
        "click",
        ".elementor-add-wcf-template-button",
        function (event) {
          event.preventDefault();
          currentSection = $(this);
          window.wcftmLibrary = elementorCommon.dialogsManager.createWidget(
            "lightbox",
            {
              id: "wcf-template-library",
              onShow: function () {
                this.getElements("widget").addClass(
                  "elementor-templates-modal"
                );
                this.getElements("header").remove();
                this.getElements("message").remove();
                this.getElements("buttonsWrapper").remove();
                let t = this.getElements("widgetContent");
                //fixed modal position
                render_popup(t);
              },
              onHide: function () {
                window.wcftmLibrary.destroy();
              },
            }
          );

          window.wcftmLibrary.getElements("header").remove();

          window.wcftmLibrary.show();

          $(window).trigger("resize"); //fixed modal position

          function render_popup(t) {
            let tmpTypes = wp.template("wcf-templates-header");
            content = null;

            content = tmpTypes({
              template_types: WCF_TEMPLATE_LIBRARY.template_types,
            });

            t.html(content);

            //active menu
            active_menu(t);

            //category select
            selected_category(t);
            selected_color_type(t);

            render_single_template(t);

            search_function();

            template_import();
          }

          async function render_templates(
            t,
            activeMenu,
            category = "",
            color_type = ""
          ) {
            let templates = wp.template("wcf-templates");
            contents = null;
            let is_loading = true;
            loading(is_loading);

            contents = await templates({
              templates: [],
              categories: storeCategory,
            });

            t.append(contents);
            const container = document.querySelector(".wcf-library-templates");
            currentCategory = category;
            currentType = activeMenu;
            currentColorType = color_type;
            if (active_resize_first_load === 0) {
              $(window).trigger("resize");
              active_resize_first_load++;
            }
            const getTemplate = await get_category_templates(
              category,
              activeMenu,
              1,
              color_type
            );
            getTemplate.forEach((item) => {
              const templateHtml = generateTemplate(item);
              if(container){
                container.innerHTML += templateHtml;
              }
             
            });
            aaeadddon_run_lazy_load();

            $($(".wcf-library-template").last())
              .find("img")
              .on("load", function () {
                is_loading = false;
                loading(is_loading);
                $(window).trigger("resize"); //fixed modal position
              });
            if (category) {
              $(
                "#wcf-template-library-filter-subtype option[value='" +
                  category +
                  "']"
              ).attr("selected", "selected");
            }
            if (color_type) {
              $(
                "#wcf-template-library-color-subtype option[value='" +
                  color_type +
                  "']"
              ).attr("selected", "selected");
            }

          }

          function render_single_template(t) {
            // let template = $('.thumbnail');
            const backContent = $(
              "#wcf-template-library .dialog-widget-content"
            ).html();
            $(document).on("click", ".thumbnail", function () {
              let _that = $(this);
              const template_id = _that
                .closest(".wcf-library-template")
                .data("id");
              const template_url = _that
                .closest(".wcf-library-template")
                .data("url");

              let singleTmp = wp.template("wcf-templates-single");
              content_single = null;
              content_single = singleTmp({
                template_link: template_url,
              });

              t.html(content_single);
              //iframe is loaded
              let is_loading = true;
              loading(is_loading);
              $("#wcf-template-library iframe").on("load", function () {
                is_loading = false;
                loading(is_loading);
              });
              template_import(template_id);
            });

            //single back
            $(document).off(
              "click",
              "#wcf-template-library-header-preview-back"
            );
            $(document).on(
              "click",
              "#wcf-template-library-header-preview-back",
              function () {
                $("#wcf-template-library .dialog-widget-content").html(
                  backContent
                );
                loading(false);
                //active menu
                active_menu(t);
              }
            );

            //hide modal
            $(document).on(
              "click",
              ".elementor-templates-modal__header__close",
              function () {
                window.wcftmLibrary.hide();
              }
            );
          }

          function active_menu(t) {
            active_menu_first_load++;
            const menu_item = $(
              ".wcf-template-library--header .elementor-template-library-menu-item"
            );
            menu_item.click(function () {
              if ($(this).hasClass("elementor-active")) {
                return;
              }

              menu_item.removeClass("elementor-active");

              $(this).addClass("elementor-active");

              activeMenu = $(this).attr("data-tab");

              $(t).find(".dialog-message").remove();

              render_templates(t, activeMenu);
             
            });

            //hide modal
            $(".elementor-templates-modal__header__close").on(
              "click",
              function () {
                window.wcftmLibrary.hide();
              }
            );          

            let activeMenu = $(
              ".wcf-template-library--header .elementor-active"
            ).attr("data-tab");
            render_templates(t, activeMenu);
          }

          function selected_category(t) {
            $(document).on(
              "change",
              "#wcf-template-library-filter-subtype",
              function (e) {
                let activeMenu = $(
                  ".wcf-template-library--header .elementor-active"
                ).attr("data-tab");
                let valueSelected = this.value;
                $(t).find(".dialog-message").remove();
                render_templates(
                  t,
                  activeMenu,
                  valueSelected,
                  currentColorType
                );
                template_import();
              }
            );
          }

          function selected_color_type(t) {
            $(document).on(
              "change",
              "#wcf-template-library-color-subtype",
              function (e) {
                let activeMenu = $(
                  ".wcf-template-library--header .elementor-active"
                ).attr("data-tab");
                let valueSelected = this.value;
                $(t).find(".dialog-message").remove();
                render_templates(t, activeMenu, currentCategory, valueSelected);
                template_import();
              }
            );
          }

          function search_function() {
            function debounce(func, delay) {
              let timeout;
              return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
              };
            }

            $(document).on(
              "keyup",
              "#wcf-template-library-filter-text",
              debounce(async function () {
                const filter = this.value.toLowerCase();
                const container = document.querySelector(
                  ".wcf-library-templates"
                );

                const currentchunk = await search_category_templates(filter);
                container.innerHTML = "";

                currentchunk.forEach((item) => {
                  const templateHtml = generateTemplate(item);
                  container.innerHTML += templateHtml;
                });

                setTimeout(() => {
                  const elements = $(".wcf-library-template");
                  const re = new RegExp(filter, "i");

                  elements.each((_, element) => {
                    const title = $(element).find(".title")[0];
                    if (re.test(title.textContent)) {
                      title.innerHTML = title.textContent.replace(
                        re,
                        "<b>$&</b>"
                      );
                    }
                  });
                }, 100);
              }, 300)
            );
          }
        

          function template_import(id = null) {
            let is_loading = true;
            $(document).on("click", ".library--action.insert", function () {
              let _that = $(this);
              let template_id = id;
              let jurl = $(this).closest(".wcf-library-template").data('jurl');
              if(jurl && jurl !=''){
                jurl = jurl+'.json';
              }

              if (null === template_id) {
                template_id = $(this)
                  .closest(".wcf-library-template")
                  .data("id");
              }
              loading(is_loading);
              _that.hide();
            
              let curPos = currentSection.parents('.elementor-add-section-inline').index();    
            
              let options = { at: curPos };        
              if(jurl){      
               
                $.get({ url: "https://block.animation-addons.com/wp-json/wp/v2/wcf-templates/json-content?url="+jurl, crossDomain: true })
                .done(function (data) {                  
                    if (data?.content) {                      
                       window.wcftmLibrary.currentRequest = elementorCommon.ajax.addRequest("get_wcf_template_data", {
                        unique_id: template_id,
                        data: {
                          edit_mode: !0,
                          display: !0,
                          template_id: template_id,
                          json_data : data
                        },
                        success: function (e) {
                          $e.run("document/elements/import", {
                            model: window.elementor.elementsModel,
                            data: e,
                            options: options
                          });
                          is_loading = false;
                          window.wcftmLibrary.hide();
                        },
                      }).fail(function () {});
               

                  } 
                });
                    
              }else{
                  window.wcftmLibrary.currentRequest = elementorCommon.ajax.addRequest("get_wcf_template_data", {
                    unique_id: template_id,
                    data: {
                      edit_mode: !0,
                      display: !0,
                      template_id: template_id,
                    },
                    success: function (e) {
                      $e.run("document/elements/import", {
                        model: window.elementor.elementsModel,
                        data: e,
                        options: options
                      });
             
                      is_loading = false;
                      window.wcftmLibrary.hide();
                    },
                  }).fail(function () {});
              }              

            }); // end click
          }

          function loading(is_loading) {
            let loading = $(".wcf-template-library--loading");

            if (!is_loading) {
              loading.hide();
              loading.attr("hidden");
            } else {
              loading.show();
              loading.removeAttr("hidden");
            }
          }
        }
      );
    });
  });

  $(document).on("click", ".aaeplugin-activate", function (e) {
    e.preventDefault();
    var userConfirmed = confirm(
      "Are you sure you want to activate plugin? Any unsaved changes will be lost. Please Save change."
    );
    if (userConfirmed) {
      activePlugin();
    }
  });

  function aaeadddon_run_lazy_load() {
    const listItems = document.querySelectorAll(".aaeaadon-loadmore-footer");
    if (!(listItems && listItems.length)) return;
    const lastItem = listItems[listItems.length - 1];

    const observerOptions = {
      root: null, // Uses the viewport as the root
      rootMargin: "0px",
      threshold: 0.1, // Trigger when 10% of the element is visible
    };

    const observerCallback = (entries, observer) => {
      entries.forEach(async (entry) => {
        if (entry.isIntersecting) {
          let currentchunk = await get_category_templates(
            currentCategory,
            currentType,
            currentPage + 1,
            currentColorType
          );
          const container = document.querySelector(".wcf-library-templates");
          if (currentchunk) {
            currentchunk.forEach((item) => {
              const templateHtml = generateTemplate(item);
              container.innerHTML += templateHtml; // Add each generated HTML to the container
            });
          }
        }
      });
    };

    const observer = new IntersectionObserver(
      debounceAsync(observerCallback),
      observerOptions
    );
    observer.observe(lastItem);
  }

  const generateTemplate = (item) => {
    return `
            <div class="wcf-library-template" data-jurl="${item.json_url}" data-id="${item.id}" data-url="${
      item.template_demo_url
    }">
                <div class="thumbnail">
                    <img src="${item?.preview?.url}" alt="${item.title}">
                </div>
                
                ${
                  item?.valid && item.valid
                    ? `
                    <!-- Show the 'Insert' button if the template is valid -->
                    <button class="library--action insert">
                        <i class="eicon-file-download"></i>
                        Insert
                    </button>
                `
                    : `
                    <!-- Show premium or activation buttons based on plugin status -->
                    ${
                      !WCF_TEMPLATE_LIBRARY?.pro_installed
                        ? `
                        <!-- Show 'Go Premium' button if the plugin is not installed -->
                        <a href="https://animation-addons.com" class="library--action pro" target="_blank">
                            <i class="eicon-external-link-square"></i>
                            Go Premium
                        </a>
                    `
                        : ""
                    }
                    ${
                      WCF_TEMPLATE_LIBRARY?.pro_installed &&
                      WCF_TEMPLATE_LIBRARY?.pro_active &&
                      !WCF_TEMPLATE_LIBRARY?.config?.wcf_valid
                        ? `
                        <!-- Show 'Pro' button if the plugin is installed and active -->
                        <a href="${WCF_TEMPLATE_LIBRARY.dashboard_link}" class="library--action pro" target="_blank">
                            <i class="eicon-external-link-square"></i>
                            Activate License
                        </a>
                    `
                        : ""
                    }
                    ${
                      WCF_TEMPLATE_LIBRARY?.pro_installed &&
                      !WCF_TEMPLATE_LIBRARY?.pro_active
                        ? `
                        <!-- Show 'Activate' button if the plugin is installed but not active -->
                        <button class="library--action pro aaeplugin-activate">
                            <i class="eicon-external-link-square"></i>
                            Activate
                        </button>
                    `
                        : ""
                    }
                `
                }
                
                <p class="title">${item.title}</p>
            </div>
        `;
  };

  function debounceAsync(fn, delay = 300) {
    let timeoutId = null;

    return (...args) => {
      return new Promise((resolve, reject) => {
        if (timeoutId) clearTimeout(timeoutId);

        timeoutId = setTimeout(async () => {
          try {
            const result = await fn(...args);
            resolve(result);
          } catch (err) {
            reject(err);
          }
        }, delay);
      });
    };
  }
})(jQuery, window, document);
