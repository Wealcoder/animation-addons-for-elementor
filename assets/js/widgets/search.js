(function($) {
	/**
	 * @param $scope The Widget wrapper element as a jQuery element
	 */
	var WcfAjaxSearch = function WcfAjaxSearch($scope) {
		var searchWrapper = $('.search--wrapper', $scope);
		var toggle_open = $('.toggle--open', $scope);
		var toggle_close = $('.toggle--close', $scope);
		toggle_open.on('click', function(e) {
			searchWrapper.addClass('search-visible');
		});
		toggle_close.on('click', function(e) {
			searchWrapper.removeClass('search-visible');
			$('.selected-category-display').html('');
			$dateContainer.find('.preset-options li').removeClass('selected');
			$categoryListItems.removeClass('selected');
			$fromDate.val('');
			$toDate.val('');
		});
		$("input", $scope).focus(function() {
			$(".wcf-search-form", $scope).addClass('wcf-search-form--focus');
		});
		$("input", $scope).focusout(function() {
			$(".wcf-search-form", $scope).removeClass('wcf-search-form--focus');
		});

		// Search Filter
		var $dateContainer = $scope.find('.date-container');
		var $categoryContainer = $scope.find('.category-container');

		// ==== Date Dropdown Toggle ====
		$dateContainer.find('.date-toggle').on('click', function(e) {
			e.preventDefault();
			$scope.find('.date-container').not($dateContainer).removeClass('active');
			$dateContainer.toggleClass('active');
			$categoryContainer.removeClass('active');
		});

		// ==== Date Presets ====
		var $fromDate = $dateContainer.find('.from-date');
		var $toDate = $dateContainer.find('.to-date');
		$dateContainer.find('.preset-options li').on('click', function() {
			var preset = $(this).data('preset');
			var today = new Date();
			var from, to;
			$(this).toggleClass('selected').siblings().removeClass('selected');
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
			$dateContainer.find('.preset-options li').removeClass('selected');
		});
		$dateContainer.find('.apply-btn').on('click', function() {
			$dateContainer.removeClass('active');
			handleSearch();
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
			$dateContainer.removeClass('active');
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
				$display.html("<span class=\"category-pill\">All Categories</span>");
			}
		});

		// ==== Category Clear & Apply Buttons ====
		$categoryContainer.find('.clear-cat-btn').on('click', function(e) {
			e.preventDefault();
			selectedCategories.length = 0;
			$categoryListItems.removeClass('selected');

			// Re-add default "All Categories"
			var $allItem = $categoryListItems.filter('[data-value=""]');
			$allItem.addClass('selected');

			// Remove all hidden inputs
			$categoryContainer.find('input[name="category[]"]').remove();

			// Update display
			$scope.find('.selected-category-display').html("<span class=\"category-pill\">All Categories</span>");
		});
		$categoryContainer.find('.apply-cat-btn').on('click', function(e) {
			e.preventDefault();
			$categoryContainer.removeClass('active');
			handleSearch();
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
			if (!$inputField.length) return;
			var keyword = $inputField.val().trim();
			var fromDate = $scope.find('.from-date').val();
			var toDate = $scope.find('.to-date').val();
			var categoryInputs = $scope.find('input[name="category[]"]');

			// Don't search only if EVERYTHING is empty
			if (!keyword && !fromDate && !toDate && categoryInputs.length === 0) {
				$resultBox.hide();
				return;
			}
			var data = {
				action: 'live_search',
				keyword: keyword
			};
			if (fromDate && toDate) {
				data.from_date = fromDate;
				data.to_date = toDate;
			}
			if (categoryInputs.length > 0) {
				data.category = categoryInputs.map(function() {
					return $(this).val();
				}).get();
			}
			$.ajax({
				url: WCF_ADDONS_JS.ajaxUrl,
				type: 'POST',
				data: data,
				success: function success(response) {
					console.log(response);
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
		// if ($searchWrapper.data('enable-ajax-search') === 'search-field') {
		//     $inputField.on('keyup input', debounce(handleSearch, 500));
		// }
	};

	// Hook into Elementor
	$(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', WcfAjaxSearch);
	});
})(jQuery);
//# sourceMappingURL=search.js.map
