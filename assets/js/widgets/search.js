(function($) {
	/**
	 * @param $scope The Widget wrapper element as a jQuery element
	 */
	var WcfAjaxSearch = function WcfAjaxSearch($scope) {
		// Search Filter
		var $dateContainer = $scope.find('.date-container');
		var $categoryContainer = $scope.find('.category-container');

		// ==== Date Dropdown Toggle ====
		$dateContainer.find('.date-toggle').on('click', function(e) {
			e.preventDefault();
			$scope.find('.date-container').not($dateContainer).removeClass('active');
			$dateContainer.toggleClass('active');
		});

		// ==== Date Presets ====
		var $fromDate = $dateContainer.find('.from-date');
		var $toDate = $dateContainer.find('.to-date');
		$dateContainer.find('.preset-options li').on('click', function() {
			var preset = $(this).data('preset');
			var today = new Date();
			var from, to;
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
		$dateContainer.find('.clear-btn').on('click', function() {
			$fromDate.val('');
			$toDate.val('');
		});
		$dateContainer.find('.apply-btn').on('click', function() {
			$dateContainer.removeClass('active');
		});

		// ==== Category Dropdown ====
		var $categoryToggle = $categoryContainer.find('.category-toggle');
		var $categoryDropdown = $categoryContainer.find('.category-dropdown');
		var $categoryListItems = $categoryContainer.find('.category-list li');
		var $selectedCategoryInput = $categoryContainer.find('#selectedCategory');
		$categoryToggle.on('click', function(e) {
			e.preventDefault();
			$scope.find('.category-container').not($categoryContainer).removeClass('active');
			$categoryContainer.toggleClass('active');
		});
		var selectedCategories = [];
		$categoryListItems.on('click', function() {
			var $item = $(this);
			var value = $item.data('value');
			var label = $item.text().trim();

			// "All Categories" resets everything
			if (!value) {
				selectedCategories.length = 0;
				$categoryListItems.removeClass('selected');
				$item.addClass('selected');
			} else {
				// Toggle selection
				var index = selectedCategories.findIndex(function(c) {
					return c.value === value;
				});
				if (index === -1) {
					selectedCategories.push({
						value: value,
						label: label
					});
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

			selectedCategories.forEach(function(cat) {
				$categoryContainer.append("<input type=\"hidden\" name=\"category[]\" value=\"".concat(cat.value, "\"/>"));
			});

			// Show selected category labels
			var $display = $scope.find('.selected-category-display');
			if (selectedCategories.length) {
				$display.html(selectedCategories.map(function(c) {
					return "<span class=\"category-pill\">".concat(c.label, "</span>");
				}).join(', '));
			} else {
				$display.text('All Categories');
			}
			$categoryContainer.removeClass('active');
		});

		// ==== Close dropdowns if clicked outside ====
		$(document).on('click.advancedSearchOutside', function(e) {
			if (!$scope[0].contains(e.target)) {
				$scope.find('.date-container, .category-container').removeClass('active');
			}
		});

		// Ajax Search
		var $inputField = $scope.find('.search-field');
		var $resultBox = $scope.find('.aae--live-search-results');
		var $searchWrapper = $('.search--wrapper.style-full-screen .wcf-search-container');

		// Debounce function
		function debounce(func, delay) {
			var timeout;
			return function() {
				var context = this;
				var args = arguments;
				clearTimeout(timeout);
				timeout = setTimeout(function() {
					return func.apply(context, args);
				}, delay);
			};
		}

		function handleSearch() {
			var keyword = $inputField.val().trim();
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
				success: function success(response) {
					if ($searchWrapper.length) {
						$searchWrapper.addClass('ajax-fs-wrap');
					}
					$resultBox.html(response).css('display', 'grid');
					$scope.find('.toggle--close').on('click', function() {
						$resultBox.hide();
						if ($searchWrapper.length) {
							$searchWrapper.removeClass('ajax-fs-wrap');
						}
					});
				},
				error: function error() {
					$resultBox.html('<div class="error">Something went wrong.</div>').show();
				}
			});
		}

		// Attach debounce to keyup
		$inputField.on('keyup input', debounce(handleSearch, 500));
	};

	// Hook into Elementor
	$(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', WcfAjaxSearch);
	});
})(jQuery);
//# sourceMappingURL=search.js.map
