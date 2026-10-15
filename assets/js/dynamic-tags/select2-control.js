/**
 * AAE Dynamic Tags - SELECT2 Control JavaScript
 *
 * Handles autocomplete functionality for dynamic tags
 *
 * @package Wealcoder\AnimationAddonsPro
 * @since 1.0.0
 */

(function ($) {
  'use strict';

  var ControlSelect2ItemView = elementor.modules.controls.BaseData.extend({
    onReady: function () {
      this.initSelect2();
    },
    initSelect2: function () {
      var self = this;
      var $select = this.$el.find('.aae-dt-select2');
      if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
        return;
      }
      var ajaxAction = $select.data('ajax-action');
      var ajaxParams = $select.data('ajax-params');

      // Get value from Elementor's control data, not from select element
      // because select might be empty if no options were provided
      var currentValue = this.getControlValue();

      // Remove empty options that were created by the template
      $select.find('option').each(function () {
        var $option = $(this);
        if ($option.val() && (!$option.text() || $option.text().trim() === '')) {
          $option.remove();
        }
      });

      // Parse ajax params if it's a string
      if (typeof ajaxParams === 'string' && ajaxParams !== '') {
        try {
          ajaxParams = JSON.parse(ajaxParams);
        } catch (e) {
          ajaxParams = {};
        }
      } else if (!ajaxParams) {
        ajaxParams = {};
      }
      var select2Options = {
        placeholder: $select.data('placeholder') || 'Select...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        dropdownParent: this.$el
      };

      // Add AJAX configuration if action is specified
      if (ajaxAction && typeof aaeDynamicTagsSelect2 !== 'undefined') {
        select2Options.ajax = {
          url: aaeDynamicTagsSelect2.ajax_url,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            var data = {
              action: ajaxAction,
              q: params.term || '',
              nonce: aaeDynamicTagsSelect2.nonce
            };

            // Add custom parameters
            if (ajaxParams && typeof ajaxParams === 'object') {
              $.extend(data, ajaxParams);
            }
            return data;
          },
          processResults: function (response) {
            if (response.success && response.data && response.data.results) {
              return {
                results: response.data.results
              };
            }
            return {
              results: []
            };
          },
          cache: true
        };

        // Check if we need to load current value
        // Get from control data because select element might not have the value set
        // Only proceed if currentValue exists and looks like an ID (is numeric or string number)
        if (currentValue && (typeof currentValue === 'number' || typeof currentValue === 'string' && currentValue.match(/^\d+$/)) && $select.find('option[value="' + currentValue + '"]').length === 0) {
          this.loadCurrentValue($select, ajaxAction, ajaxParams, currentValue, select2Options);
          return;
        }
      }

      // Initialize SELECT2
      $select.select2(select2Options);

      // Update value on change
      $select.on('change', function () {
        self.setValue($(this).val());
      });
    },
    loadCurrentValue: function ($select, ajaxAction, ajaxParams, currentValue, select2Options) {
      var self = this;
      if (typeof aaeDynamicTagsSelect2 === 'undefined') {
        $select.select2(select2Options);
        return;
      }

      // Fetch the current value label via AJAX
      var data = {
        action: ajaxAction,
        id: currentValue,
        nonce: aaeDynamicTagsSelect2.nonce
      };

      // Add custom parameters
      if (ajaxParams && typeof ajaxParams === 'object') {
        $.extend(data, ajaxParams);
      }
      $.ajax({
        url: aaeDynamicTagsSelect2.ajax_url,
        dataType: 'json',
        data: data,
        success: function (response) {
          if (response.success && response.data && response.data.results && response.data.results.length > 0) {
            // Clear any existing options first
            $select.empty();

            // Add the option to the select with selected attribute
            var option = new Option(response.data.results[0].text, response.data.results[0].id, true, true);
            $select.append(option);
          }

          // Initialize Select2
          $select.select2(select2Options);

          // Set the value after Select2 initialization
          if (currentValue) {
            $select.val(currentValue).trigger('change');
          }

          // Update value on change
          $select.on('change', function () {
            self.setValue($(this).val());
          });
        },
        error: function () {
          // Initialize Select2 even if AJAX fails
          $select.select2(select2Options);

          // Update value on change
          $select.on('change', function () {
            self.setValue($(this).val());
          });
        }
      });
    },
    onBeforeDestroy: function () {
      var $select = this.$el.find('.aae-dt-select2');
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
      }
    }
  });

  // Register the control
  elementor.addControlView('aae_select2', ControlSelect2ItemView);
})(jQuery);