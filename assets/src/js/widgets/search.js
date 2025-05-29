(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     */
    const WcfAjaxSearch = function ($scope) {
        // Search Filter
        const $dateContainer = $scope.find('.date-container');
        const $categoryContainer = $scope.find('.category-container');

        // ==== Date Dropdown Toggle ====
        $dateContainer.find('.date-toggle').on('click', function (e) {
            e.preventDefault();
            $scope.find('.date-container').not($dateContainer).removeClass('active');
            $dateContainer.toggleClass('active');
        });

        // ==== Date Presets ====
        const $fromDate = $dateContainer.find('.from-date');
        const $toDate = $dateContainer.find('.to-date');

        $dateContainer.find('.preset-options li').on('click', function () {
            const preset = $(this).data('preset');
            const today = new Date();
            let from, to;

            switch (preset) {
                case 'today':
                    from = to = today;
                    break;
                case 'yesterday':
                    from = to = new Date(today.setDate(today.getDate() - 1));
                    break;
                case 'week':
                    from = new Date(today.setDate(today.getDate() - 6));
                    to = new Date();
                    break;
                case 'month':
                    from = new Date(today.getFullYear(), today.getMonth(), 1);
                    to = new Date();
                    break;
            }

            $fromDate.val(from.toISOString().split('T')[0]);
            $toDate.val(to.toISOString().split('T')[0]);
        });


        // ==== Date Clear & Apply Buttons ====
        $dateContainer.find('.clear-btn').on('click', function () {
            $fromDate.val('');
            $toDate.val('');
        });

        $dateContainer.find('.apply-btn').on('click', function () {
            $dateContainer.removeClass('active');
        });

        // ==== Category Dropdown ====
        const $categoryToggle = $categoryContainer.find('.category-toggle');
        const $categoryDropdown = $categoryContainer.find('.category-dropdown');
        const $categoryListItems = $categoryContainer.find('.category-list li');
        const $selectedCategoryInput = $categoryContainer.find('#selectedCategory');

        $categoryToggle.on('click', function (e) {
            e.preventDefault();
            $scope.find('.category-container').not($categoryContainer).removeClass('active');
            $categoryContainer.toggleClass('active');
        });

        const selectedCategories = [];

        $categoryListItems.on('click', function () {
            const $item = $(this);
            const value = $item.data('value');
            const label = $item.text().trim();

            // "All Categories" resets everything
            if (!value) {
                selectedCategories.length = 0;
                $categoryListItems.removeClass('selected');
                $item.addClass('selected');
            } else {
                // Toggle selection
                const index = selectedCategories.findIndex(c => c.value === value);
                if (index === -1) {
                    selectedCategories.push({value, label});
                    $item.addClass('selected');
                } else {
                    selectedCategories.splice(index, 1);
                    $item.removeClass('selected');
                }

                // Remove "All Categories" selected
                $categoryListItems.filter('[data-value=""]').removeClass('selected');
            }

            // Update hidden inputs (clear and re-add)
            $categoryContainer.find('input[name="category[]"]').remove(); // clear existing

            selectedCategories.forEach(cat => {
                $categoryContainer.append(`<input type="hidden" name="category[]" value="${cat.value}"/>`);
            });

            // Show selected category labels
            const $display = $scope.find('.selected-category-display');
            if (selectedCategories.length) {
                $display.html(
                    selectedCategories.map(c => `<span class="category-pill">${c.label}</span>`).join(', ')
                );
            } else {
                $display.text('All Categories');
            }

            $categoryContainer.removeClass('active');
        });

        // ==== Close dropdowns if clicked outside ====
        $(document).on('click.advancedSearchOutside', function (e) {
            if (!$scope[0].contains(e.target)) {
                $scope.find('.date-container, .category-container').removeClass('active');
            }
        });


        // Ajax Search
        const $inputField = $scope.find('.search-field');
        const $resultBox = $scope.find('.aae--live-search-results');
        const $searchWrapper = $('.search--wrapper.style-full-screen .wcf-search-container');

        // Debounce function
        function debounce(func, delay) {
            let timeout;
            return function () {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        }

        function handleSearch() {
            const keyword = $inputField.val().trim();

            if (keyword.length < 1) {
                $resultBox.hide();
                return;
            }

            $.ajax({
                url: WCF_ADDONS_JS.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'live_search',
                    keyword: keyword
                },
                success: function (response) {
                    if ($searchWrapper.length) {
                        $searchWrapper.addClass('ajax-fs-wrap');
                    }

                    $resultBox.html(response).css('display', 'grid');

                    $scope.find('.toggle--close').on('click', function () {
                        $resultBox.hide();
                        if ($searchWrapper.length) {
                            $searchWrapper.removeClass('ajax-fs-wrap');
                        }
                    });
                },
                error: function () {
                    $resultBox.html('<div class="error">Something went wrong.</div>').show();
                }
            });
        }

        // Attach debounce to keyup
        $inputField.on('keyup input', debounce(handleSearch, 500));
    };

    // Hook into Elementor
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/wcf--blog--search--form.default',
            WcfAjaxSearch
        );
    });
})(jQuery);
