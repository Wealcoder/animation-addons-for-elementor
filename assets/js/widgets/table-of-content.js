( function( $ ) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */

       $(window).on("elementor/frontend/init", function () {   
        const Modules = elementorModules.frontend.handlers.Base;
          //table of content
        const table_of_content = Modules.extend({
        getDefaultSettings: function getDefaultSettings() {
            const elementSettings = this.getElementSettings(),
            listWrapperTag =
                "numbers" === elementSettings.marker_view ? "ol" : "ul";
            return {
            selectors: {
                widgetContainer: ".elementor-widget-container",
                postContentContainer:
                '.elementor:not([data-elementor-type="header"]):not([data-elementor-type="footer"]):not([data-elementor-type="popup"])',
                expandButton: ".toc__toggle-button--expand",
                collapseButton: ".toc__toggle-button--collapse",
                body: ".toc__body",
                headerTitle: ".toc__header-title",
            },
            classes: {
                anchor: "elementor-menu-anchor",
                listWrapper: "toc__list-wrapper",
                listItem: "toc__list-item",
                listTextWrapper: "toc__list-item-text-wrapper",
                firstLevelListItem: "toc__top-level",
                listItemText: "toc__list-item-text",
                activeItem: "elementor-item-active",
                headingAnchor: "toc__heading-anchor",
                collapsed: "toc--collapsed",
            },
            listWrapperTag,
            };
        },
        getDefaultElements: function getDefaultElements() {
            const settings = this.getSettings();
            return {
            $pageContainer: this.getContainer(),
            $widgetContainer: this.$element.find(
                settings.selectors.widgetContainer
            ),
            $expandButton: this.$element.find(settings.selectors.expandButton),
            $collapseButton: this.$element.find(
                settings.selectors.collapseButton
            ),
            $tocBody: this.$element.find(settings.selectors.body),
            $listItems: this.$element.find("." + settings.classes.listItem),
            };
        },
        getContainer: function getContainer() {
            const elementSettings = this.getElementSettings();

            // If there is a custom container defined by the user, use it as the headings-scan container
            if (elementSettings.container) {
            return jQuery(elementSettings.container);
            }

            // Get the document wrapper element in which the TOC is located
            const $documentWrapper = this.$element.parents(".elementor");

            // If the TOC container is a popup, only scan the popup for headings
            if ("popup" === $documentWrapper.attr("data-elementor-type")) {
            return $documentWrapper;
            }

            // If the TOC container is anything other than a popup, scan only the post/page content for headings
            const settings = this.getSettings();
            return jQuery(settings.selectors.postContentContainer);
        },
        getHeadings: function () {
            // Get all headings from document by user-selected tags
            const elementSettings = this.getElementSettings(),
            tags = elementSettings.headings_by_tags.join(","),
            selectors = this.getSettings("selectors"),
            excludedSelectors = elementSettings.exclude_headings_by_selector;
            return this.elements.$pageContainer
            .find(tags)
            .not(selectors.headerTitle)
            .filter((index, heading) => {
                if(typeof ScrollTrigger === 'object') {
                     
                    ScrollTrigger.create({
                    trigger: heading,
                    start: "top center",
                    end: "bottom center",
                    onEnter: () => this.setActiveLink(heading.previousSibling.id),
                    onLeaveBack: () => this.setActiveLink(heading.previousSibling.id),
                    });
                }
               
                return !jQuery(heading).closest(excludedSelectors).length; // Handle excluded selectors if there are any
            });
        },
        setActiveLink: function (id) {
            for (const element of this.headingsData) {
            let link = document.querySelector(`[href="#${element.anchorLink}"]`);
            link.classList.toggle(
                "elementor-item-active",
                link.getAttribute("href") === `#${id}`
            );
            }
        },
        handleNoHeadingsFound: function () {
            const noHeadingsText = "No headings were found on this page.";
            return this.elements.$tocBody.html(noHeadingsText);
        },
        getHeadingAnchorLink: function (index, classes) {
            const headingID = this.elements.$headings[index].id,
            wrapperID =
                this.elements.$headings[index].closest(".elementor-widget").id;
            let anchorLink = "";
            if (headingID) {
            anchorLink = headingID;
            } else if (wrapperID) {
            // If the heading itself has an ID, we don't want to overwrite it
            anchorLink = wrapperID;
            }

            // If there is no existing ID, use the heading text to create a semantic ID
            if (headingID || wrapperID) {
            jQuery(this.elements.$headings[index]).data("hasOwnID", true);
            } else {
            anchorLink = `${classes.headingAnchor}-${index}`;
            }
            return anchorLink;
        },
        setHeadingsData: function () {
            this.headingsData = [];
            const classes = this.getSettings("classes");

            // Create an array for simplifying TOC list creation
            this.elements.$headings.each((index, element) => {
            const anchorLink = this.getHeadingAnchorLink(index, classes);
            this.headingsData.push({
                tag: +element.nodeName.slice(1),
                text: element.textContent,
                anchorLink,
            });
            });
        },
        addAnchorsBeforeHeadings: function () {
            const classes = this.getSettings("classes");

            // Add an anchor element right before each TOC heading to create anchors for TOC links
            this.elements.$headings.before((index) => {
            // Check if the heading element itself has an ID, or if it is a widget which includes a main heading element, whether the widget wrapper has an ID
            if (jQuery(this.elements.$headings[index]).data("hasOwnID")) {
                return;
            }
            return `<span id="${classes.headingAnchor}-${index}" class="${classes.anchor} "></span>`;
            });
        },

        followAnchors: function () {
            this.$listItemTexts = this.$element.find(".toc__list-item-text");
            gsap.registerPlugin(ScrollToPlugin);
            this.$listItemTexts.toArray().forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                const targetId = link.getAttribute("href");
                gsap.to(window, {
                duration: 0.6,
                scrollTo: targetId,
                ease: "power2.inOut",
                });
            });
            });
        },
        populateTOC: function () {
            this.listItemPointer = 0;
            const elementSettings = this.getElementSettings();
            if (elementSettings.hierarchical_view) {
            this.createNestedList();
            } else {
            this.createFlatList();
            }

            if (!elementorFrontend.isEditMode()) {
            this.followAnchors();
            }
        },
        createNestedList: function () {
            this.headingsData.forEach((heading, index) => {
            heading.level = 0;
            for (let i = index - 1; i >= 0; i--) {
                const currentOrderedItem = this.headingsData[i];
                if (currentOrderedItem.tag <= heading.tag) {
                heading.level = currentOrderedItem.level;
                if (currentOrderedItem.tag < heading.tag) {
                    heading.level++;
                }
                break;
                }
            }
            });
            this.elements.$tocBody.html(this.getNestedLevel(0));
        },
        createFlatList: function () {
            this.elements.$tocBody.html(this.getNestedLevel());
        },
        getNestedLevel: function (level) {
            const settings = this.getSettings(),
            elementSettings = this.getElementSettings(),
            icon = this.getElementSettings("icon");
            let renderedIcon;
            if (icon) {
            // We generate the icon markup in PHP and make it available via get_frontend_settings(). As a result, the
            // rendered icon is not available in the editor, so in the editor we use the regular <i> tag.
            if (
                elementorFrontend.config.experimentalFeatures.e_font_icon_svg &&
                !elementorFrontend.isEditMode()
            ) {
                renderedIcon = icon.rendered_tag;
            } else {
                renderedIcon = `<i class="${icon.value}"></i>`;
            }
            }

            // Open new list/nested list
            let html = `<${settings.listWrapperTag} class="${settings.classes.listWrapper}">`;

            // For each list item, build its markup.
            while (this.listItemPointer < this.headingsData.length) {
            const currentItem = this.headingsData[this.listItemPointer];
            let listItemTextClasses = settings.classes.listItemText;
            if (0 === currentItem.level) {
                // If the current list item is a top level item, give it the first level class
                listItemTextClasses += " " + settings.classes.firstLevelListItem;
            }
            if (level > currentItem.level) {
                break;
            }
            if (level === currentItem.level) {
                html += `<li class="${settings.classes.listItem}">`;
                html += `<div class="${settings.classes.listTextWrapper}">`;
                let liContent = `<a href="#${currentItem.anchorLink}" class="${listItemTextClasses}">${currentItem.text}</a>`;

                // If list type is bullets, add the bullet icon as an <i> tag
                if ("bullets" === elementSettings.marker_view && icon) {
                liContent = `${renderedIcon}${liContent}`;
                }
                html += liContent;
                html += "</div>";
                this.listItemPointer++;
                const nextItem = this.headingsData[this.listItemPointer];
                if (nextItem && level < nextItem.level) {
                // If a new nested list has to be created under the current item,
                // this entire method is called recursively (outside the while loop, a list wrapper is created)
                html += this.getNestedLevel(nextItem.level);
                }
                html += "</li>";
            }
            }
            html += `</${settings.listWrapperTag}>`;
            return html;
        },
        run: function run() {
            this.elements.$headings = this.getHeadings();
            if (!this.elements.$headings.length) {
            return this.handleNoHeadingsFound();
            }
            this.setHeadingsData();
            if (!elementorFrontend.isEditMode()) {
            this.addAnchorsBeforeHeadings();
            }
            this.populateTOC();

            if (this.getElementSettings("minimize_box")) {
            this.collapseBodyListener();
            }
        },
        bindEvents: function bindEvents() {
            this.viewportItems = [];
            this.run();

            const elementSettings = this.getElementSettings();
            if (elementSettings.minimize_box) {
            this.elements.$expandButton
                .on("click", () => this.expandBox())
                .on("keyup", (event) => this.triggerClickOnEnterSpace(event));
            this.elements.$collapseButton
                .on("click", () => this.collapseBox())
                .on("keyup", (event) => this.triggerClickOnEnterSpace(event));
            }
            if (elementSettings.collapse_subitems) {
            this.elements.$listItems.on("hover", (event) =>
                jQuery(event.target).slideToggle()
            );
            }
        },

        expandBox: function () {
            let changeFocus =
            arguments.length > 0 && arguments[0] !== undefined
                ? arguments[0]
                : true;
            const boxHeight = this.getCurrentDeviceSetting("min_height");
            this.$element.removeClass(this.getSettings("classes.collapsed"));
            this.elements.$tocBody.attr("aria-expanded", "true").slideDown();

            // Return container to the full height in case a min-height is defined by the user
            this.elements.$widgetContainer.css(
            "min-height",
            boxHeight.size + boxHeight.unit
            );
            if (changeFocus) {
            this.elements.$collapseButton.trigger("focus");
            }
        },
        collapseBox: function () {
            let changeFocus =
            arguments.length > 0 && arguments[0] !== undefined
                ? arguments[0]
                : true;
            this.$element.addClass(this.getSettings("classes.collapsed"));
            this.elements.$tocBody.attr("aria-expanded", "false").slideUp();

            // Close container in case a min-height is defined by the user
            this.elements.$widgetContainer.css("min-height", "0px");
            if (changeFocus) {
            this.elements.$expandButton.trigger("focus");
            }
        },
        triggerClickOnEnterSpace: function (event) {
            const ENTER_KEY = 13,
            SPACE_KEY = 32;
            if (ENTER_KEY === event.keyCode || SPACE_KEY === event.keyCode) {
            event.currentTarget.click();
            event.stopPropagation();
            }
        },
        collapseBodyListener: function () {
            const activeBreakpoints =
            elementorFrontend.breakpoints.getActiveBreakpointsList({
                withDesktop: true,
            });
            const minimizedOn = this.getElementSettings("minimized_on"),
            currentDeviceMode = elementorFrontend.getCurrentDeviceMode(),
            isCollapsed = this.$element.hasClass(
                this.getSettings("classes.collapsed")
            );

            // If minimizedOn value is set to desktop, it applies for widescreen as well.
            if (
            "desktop" === minimizedOn ||
            activeBreakpoints.indexOf(minimizedOn) >=
                activeBreakpoints.indexOf(currentDeviceMode)
            ) {
            if (!isCollapsed) {
                this.collapseBox(false);
            }
            } else if (isCollapsed) {
            this.expandBox(false);
            }
        },
        });

        elementorFrontend.hooks.addAction(
        "frontend/element_ready/wcf--table-of-contents.default",
        function ($scope) {
            elementorFrontend.elementsHandler.addHandler(table_of_content, {
            $element: $scope,
            });
        }
        );

});

} )( jQuery );