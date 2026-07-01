/**
 * CodeSnippet AJAX Functionality
 * 
 * Handles all AJAX operations for the CodeSnippet list page
 * 
 * @package WCF_ADDONS\CodeSnippet
 * @since 2.3.10
 */

(function($) {
    'use strict';

    // Main AJAX handler object
    var CodeSnippetAjax = {
        
        // Configuration
        config: {
            ajaxInProgress: false,
            searchTimeout: null,
            searchDelay: 500
        },

        /**
         * Initialize the AJAX functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            var self = this;

            // Search with debouncing
            $('#search-submit').on('click', function() {
                clearTimeout(self.config.searchTimeout);
                var searchTerm = $('#snippet-search-input').val();

                const url = new URL(window.location.href);
                if( searchTerm.length > 0 ) {
                    url.searchParams.set('s', searchTerm );
                } else {
                    url.searchParams.delete('s');
                }
                history.replaceState({}, '', url.toString());
                self.config.searchTimeout = setTimeout(function() {
                    self.performAjaxSearch();
                }, self.config.searchDelay);

            });


            // Filter change
            $('.subsubsub a').on('click', function(e) {
                e.preventDefault();
                $('.subsubsub a').removeClass('current');
                $(this).addClass('current');
                self.handleFilterChange($(this));
            });

            // AJAX Delete functionality
            $(document).on('click', '.ajax-delete-snippet', function(e) {
                e.preventDefault();
                self.handleSingleDelete($(this));
            });

            $(document).on('click', '#ajax-delete-snippet', function(e) {
                e.preventDefault();
                self.handleSingleDeleteBtn($(this));
            });

            // AJAX Bulk Actions
            $('#doaction, #doaction2').on('click', function(e) {
                e.preventDefault();
                self.handleBulkAction($(this));
            });

            // Status toggle
            $(document).on('change', '.snippet-status-toggle', function() {
                self.handleStatusToggle($(this));
            });
        },

        /**
         * Perform AJAX search
         */
        performAjaxSearch: function() {
            if (this.config.ajaxInProgress) return;
            const params = new URLSearchParams(window.location.href);
            var searchTerm = $('#snippet-search-input').val();
            var codeType = params.get('code_type') || 'all';
            var currentPage = params.get('paged') || 1;

            this.config.ajaxInProgress = true;
            // this.showLoadingState();

            $.ajax({
                url: WCFCustomCodeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: WCFCustomCodeVars.ajaxActions.search,
                    nonce: WCFCustomCodeVars.nonce,
                    search: searchTerm,
                    code_type: codeType,
                    page: currentPage,
                    per_page: 20
                },
                success: function(response) {
                    if (response.success) {
                        CodeSnippetAjax.updateTableWithResults(response.data);
                    } else {
                        CodeSnippetAjax.showErrorMessage(response.data.message || WCFCustomCodeVars.messages.error);
                    }
                },
                error: function() {
                    CodeSnippetAjax.showErrorMessage(WCFCustomCodeVars.messages.error);
                },
                complete: function() {
                    CodeSnippetAjax.config.ajaxInProgress = false;
                    // CodeSnippetAjax.hideLoadingState();
                }
            });
        },

        /**
         * Update table with AJAX results
         */
        updateTableWithResults: function(data) {
            var tbody = $('.wp-list-table tbody');
            tbody.empty();
            
            if (data.snippets.length === 0) {
                tbody.append('<tr><td colspan="8" class="no-items">No code snippets found.</td></tr>');
                this.updateItemsCount(0);
                this.updatePagination(data);
                return;
            }
            
            var self = this;
            $.each(data.snippets, function(index, snippet) {
                var row = self.createTableRow(snippet);
                tbody.append(row);
            });
            
            // Update items count and pagination
            this.updateItemsCount(data.total);
            this.updatePagination(data);
        },

        /**
         * Create table row HTML
         */
        createTableRow: function(snippet) {
            var codeTypeClass = 'code-type-' + (snippet.code_type || '');
            var codeTypeText = (snippet.code_type || '').toUpperCase().replace(/[-_]/g, ' ');
            var loadLocationText = (snippet.load_location || '').toUpperCase().replace(/[-_]/g, ' ');
            var isActive = snippet.is_active === 'yes';
            var statusToggle = isActive ? 'checked' : '';
            
            var row = '<tr>' +
                '<th scope="row" class="check-column">' +
                    '<input type="checkbox" name="ids[]" value="' + snippet.id + '" />' +
                '</th>' +
                '<td class="name column-name">' +
                    '<strong><a href="' + snippet.edit_url + '">' + snippet.title + '</a></strong>' +
                    '<div class="row-actions">' +
                        '<span class="edit"><a href="' + snippet.edit_url + '">Edit</a> | </span>' +
                        '<span class="delete"><a href="#" class="ajax-delete-snippet" data-id="' + snippet.id + '">Delete</a></span>' +
                    '</div>' +
                '</td>' +
                '<td class="code_type column-code_type">' +
                    '<span class="code-type ' + codeTypeClass + '">' + codeTypeText + '</span>' +
                '</td>' +
                '<td class="visibility_list column-visibility_list">' + snippet.visibility_list + '</td>' +
                '<td class="load_location column-load_location">' +
                    '<span class="load-location '+ ( loadLocationText ? "" : "empty" ) +'">' + ( loadLocationText ? loadLocationText : "—" ) + '</span>' +
                '</td>' +
                '<td class="priority column-priority">' +
                    '<span class="priority priority-' + (snippet.priority || '10') + '">' + (snippet.priority || '10') + '</span>' +
                '</td>' +
                '<td class="snippet_status column-snippet_status">' +
                    '<label class="toggle-switch">' +
                        '<input type="checkbox" class="snippet-status-toggle" data-id="' + snippet.id + '" ' + statusToggle + '>' +
                        '<span class="slider"></span>' +
                    '</label>' +
                '</td>' +
                '<td class="date_created column-date_created">' + snippet.date_modified + '</td>' +
            '</tr>';
            
            return row;
        },

        /**
         * Update items count display
         */
        updateItemsCount: function(total) {
            // Update the items count in the tablenav
            if( total === 0 ) {
                $('.tablenav .tablenav-pages').removeClass('no-pages');
                $('#code-snippet-list-table-form .search-box').remove();
            } else {
                var html = `<p class="search-box">
                    \t<label class="screen-reader-text" for="snippet-search-input">Search Snippets:</label>
                    \t<input type="search" id="snippet-search-input" name="s" value="">
                    \t\t<input type="submit" id="search-submit" class="button" value="Search Snippets"></p>`;
                var tries = 0;
                var maxTries = 50; // ~5 seconds at 100ms
                var interval = setInterval(function(){
                    var $ul = $('#code-snippet-list-table-form .subsubsub');
                    if ($ul.length) {
                        if ($('#code-snippet-list-table-form .search-box').length === 0) {
                            $ul.first().after(html);
                        }
                        clearInterval(interval);
                    }
                    if (++tries >= maxTries) {
                        clearInterval(interval); // give up
                    }
                }, 100);
            }

            $('.tablenav .displaying-num').text(total + ' items');
            
            // Update any other count displays
            $('.wp-list-table .subsubsub .count').each(function() {
                var $this = $(this);
                var currentText = $this.text();
                var newText = currentText.replace(/\(\d+\)/, '(' + total + ')');
                $this.text(newText);
            });
        },

        /**
         * Update pagination
         */
        updatePagination: function(data) {
            var pagination = $('.tablenav-pages .pagination-links');
            if (data.total_pages > 1) {
                pagination.show();
            } else {
                pagination.hide();
            }
        },

        /**
         * Handle filter change
         */
        handleFilterChange: function(link) {
            var href = link.attr('href');
            history.pushState({}, '', href);
            var urlParams = new URLSearchParams(href.split('?')[1]);
            var codeType = urlParams.get('code_type') || 'all';
            this.performAjaxSearch();
        },

        /**
         * Handle single delete
         */
        handleSingleDelete: function(link) {
            if (!confirm(WCFCustomCodeVars.messages.confirmDelete)) {
                return false;
            }
            
            var snippetId = link.data('id');
            var row = link.closest('tr');
            
            $.ajax({
                url: WCFCustomCodeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: WCFCustomCodeVars.ajaxActions.delete,
                    nonce: WCFCustomCodeVars.nonce,
                    snippet_id: snippetId
                },
                success: function(response) {
                    if (response.success) {
                        if( row.length > 0 ) {
                            row.fadeOut(300, function() {
                                $(this).remove();
                                CodeSnippetAjax.showSuccessMessage(response.data.message);
                            });
                        } else {
                            setTimeout(function() {
                                const params = new URLSearchParams(window.location.href);
                                params.delete('edit');
                                window.location.href = params;
                            }, 2000);
                        }
                    } else {
                        CodeSnippetAjax.showErrorMessage(response.data.message || WCFCustomCodeVars.messages.error);
                    }
                },
                error: function() {
                    CodeSnippetAjax.showErrorMessage(WCFCustomCodeVars.messages.error);
                }
            });
        },

        /**
         * Handle single delete
         */
        handleSingleDeleteBtn: function(link) {
            if (!confirm(WCFCustomCodeVars.messages.confirmDelete)) {
                return false;
            }

            var snippetId = link.data('id');
            $.ajax({
                url: WCFCustomCodeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: WCFCustomCodeVars.ajaxActions.delete,
                    nonce: WCFCustomCodeVars.nonce,
                    snippet_id: snippetId
                },
                beforeSend: function() {
                    $('#wcf-code-loading').show();
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        setTimeout(function() {
                            CodeSnippetAjax.showErrorMessage(response.data.message);
                            $('#wcf-code-loading').hide();
                            window.location.href = WCFCustomCodeVars.snippet_page;
                        }, 2000);
                    } else {
                        CodeSnippetAjax.showErrorMessage(response.data.message || WCFCustomCodeVars.messages.error);
                        $('#wcf-code-loading').hide();
                    }
                },
                error: function() {
                    CodeSnippetAjax.showErrorMessage(WCFCustomCodeVars.messages.error);
                    $('#wcf-code-loading').hide();
                }
            });
        },

        /**
         * Handle bulk actions
         */
        handleBulkAction: function(button) {
            // Get the action from the correct select element
            var action;
            if (button.attr('id') === 'doaction') {
                action = $('#bulk-action-selector-top').val();
            } else if (button.attr('id') === 'doaction2') {
                action = $('#bulk-action-selector-bottom').val();
            } else {
                action = button.siblings('select').val();
            }
            
            var checkedBoxes = $('input[name="ids[]"]:checked');
            
            if (checkedBoxes.length === 0) {
                alert('Please select at least one snippet.');
                return false;
            }
            
            
            if (action === 'delete' && !confirm(WCFCustomCodeVars.messages.confirmBulkDelete)) {
                return false;
            }
            
            var snippetIds = [];
            var rowsToAnimate = [];
            checkedBoxes.each(function() {
                snippetIds.push($(this).val());
                rowsToAnimate.push($(this).closest('tr'));
            });
            
            // Disable the button during processing
            button.prop('disabled', true).val('Processing...');
            
            $.ajax({
                url: WCFCustomCodeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: WCFCustomCodeVars.ajaxActions.bulk,
                    nonce: WCFCustomCodeVars.nonce,
                    bulk_action: action,
                    snippet_ids: snippetIds
                },
                success: function(response) {
                    if (response.success) {
                        CodeSnippetAjax.showSuccessMessage(response.data.message);
                        
                        if (action === 'delete') {
                            // Smooth animation for bulk delete
                            CodeSnippetAjax.animateBulkDelete(rowsToAnimate);
                        } else {
                            // For activate/deactivate, just refresh the table
                            CodeSnippetAjax.performAjaxSearch();
                        }

                        $('#cb-select-all-2, #cb-select-all-1').prop('checked', false);
                    } else {
                        CodeSnippetAjax.showErrorMessage(response.data.message || WCFCustomCodeVars.messages.error);
                    }
                },
                error: function() {
                    CodeSnippetAjax.showErrorMessage(WCFCustomCodeVars.messages.error);
                },
                complete: function() {
                    // Re-enable the button
                    button.prop('disabled', false).val('Apply');
                }
            });
        },

        /**
         * Animate bulk delete with smooth transitions
         */
        animateBulkDelete: function(rows) {
            var self = this;
            var totalRows = rows.length;
            var completedRows = 0;
            
            // Animate each row with a slight delay for a cascading effect
            rows.forEach(function(row, index) {
                setTimeout(function() {
                    // Add deleting class for visual feedback
                    row.addClass('deleting');
                    
                    // Animate the row out
                    row.animate({
                        opacity: 0,
                        height: 0,
                        paddingTop: 0,
                        paddingBottom: 0,
                        marginTop: 0,
                        marginBottom: 0
                    }, 400, function() {
                        // Remove the row
                        row.remove();
                        completedRows++;
                        
                        // Update items count after all rows are removed
                        if (completedRows === totalRows) {
                            self.updateItemsCountAfterDelete(totalRows);
                        }
                    });
                }, index * 100); // 100ms delay between each row
            });
        },

        /**
         * Update items count after bulk delete
         */
        updateItemsCountAfterDelete: function(deletedCount) {
            var currentCount = parseInt($('.tablenav .displaying-num').text().replace(/\D/g, '')) || 0;
            var newCount = Math.max(0, currentCount - deletedCount);
            this.updateItemsCount(newCount);
        },

        /**
         * Handle status toggle
         */
        handleStatusToggle: function(toggle) {
            var snippetId = toggle.data('id');
            var status = toggle.is(':checked') ? 'yes' : 'no';
            var self = this;
            var originalState = !toggle.is(':checked'); // Store original state


            // Disable toggle during AJAX request
            toggle.prop('disabled', true);

            // Add visual feedback
            var row = toggle.closest('tr');
            row.addClass('updating');

            $.ajax({
                url: WCFCustomCodeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: WCFCustomCodeVars.ajaxActions.toggle,
                    nonce: WCFCustomCodeVars.nonce,
                    snippet_id: snippetId,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccessMessage(response.data.message);
                        // Update the row with new status
                        self.updateRowStatus(row, status);
                    } else {
                        self.showErrorMessage(response.data.message || WCFCustomCodeVars.messages.error);
                        // Revert toggle to original state
                        toggle.prop('checked', originalState);
                    }
                },
                error: function() {
                    self.showErrorMessage(WCFCustomCodeVars.messages.error);
                    // Revert toggle to original state
                    toggle.prop('checked', originalState);
                },
                complete: function() {
                    // Re-enable toggle and remove visual feedback
                    toggle.prop('disabled', false);
                    row.removeClass('updating');
                }
            });
        },

        /**
         * Update row status after successful toggle
         */
        updateRowStatus: function(row, status) {
            // Update the toggle state
            var toggle = row.find('.snippet-status-toggle');
            toggle.prop('checked', status === 'yes');

            // Update any status-related classes or text
            if (status === 'yes') {
                row.removeClass('snippet-inactive').addClass('snippet-active');
            } else {
                row.removeClass('snippet-active').addClass('snippet-inactive');
            }
        },

        /**
         * Show loading state
         */
        showLoadingState: function() {
            $('.wp-list-table tbody').html('<tr><td colspan="8" class="loading">' + WCFCustomCodeVars.messages.loading + '</td></tr>');
        },

        /**
         * Hide loading state
         */
        hideLoadingState: function() {
            // Loading state will be replaced by results
        },

        /**
         * Show error message
         */
        showErrorMessage: function(message) {
            // Remove any existing notices first
            $('.wcf-admin-page-content .notice').remove();
            
            // Add new error notice
            var notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.wcf-admin-page-content').prepend(notice);
            
            // Auto-dismiss after 8 seconds (longer for errors)
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 8000);
            
            // Add click handler for manual dismiss
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Show success message
         */
        showSuccessMessage: function(message) {
            // Remove any existing notices first
            $('.wcf-admin-page-content .notice').remove();
            
            // Add new success notice
            var notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
            $('.wcf-admin-page-content').prepend(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Add click handler for manual dismiss
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        CodeSnippetAjax.init();
    });

    // Make it globally available
    window.CodeSnippetAjax = CodeSnippetAjax;

})(jQuery);
