/**
 * Animation Addons Loop Builder - Frontend JavaScript
 */
(function($) {
    'use strict';

    class AAE_LoopBuilder {
        constructor() {
            this.debug = false; // Set to true for debugging
            this.init();
        }

        log(message, data = null) {
            if (this.debug && console && console.log) {
                if (data) {
                } else {
                }
            }
        }

        init() {
            this.log('Initializing AAE Loop Builder');
            
            // Initialize on document ready
            $(document).ready(() => {
                this.log('Document ready');
                this.initLoopGrids();
                this.initEventHandlers();
            });

            // Initialize on Elementor frontend init
            $(window).on('elementor/frontend/init', () => {
                this.log('Elementor frontend init');
                this.initElementorIntegration();
            });
        }

        initLoopGrids() {
            const $containers = $('.custom-loop-container');
            this.log('Found loop containers:', $containers.length);
            
            $containers.each((index, element) => {
                this.initLoopGrid($(element));
            });
        }

        initLoopGrid($container) {
            const settings = $container.data('settings') || {};
            this.log('Initializing loop grid', settings);
            
            // Initialize masonry if enabled
            if ($container.hasClass('custom-loop-masonry')) {
                this.initMasonry($container);
            }

            // Initialize pagination
            this.initPagination($container, settings);

            // Initialize infinite scroll
            if (settings.pagination_type === 'infinite_scroll') {
                this.log('Initializing infinite scroll');
                this.initInfiniteScroll($container, settings);
            }
        }

        initMasonry($container) {
            // Simple masonry implementation
            const items = $container.find('.e-loop-item');
            const columns = parseInt($container.css('--grid-columns') || 3);
            
            if (columns <= 1) return;

            // Wait for images to load
            $container.imagesLoaded(() => {
                this.arrangeMasonryItems($container, items, columns);
            });

            // Re-arrange on window resize
            $(window).on('resize', this.debounce(() => {
                this.arrangeMasonryItems($container, items, columns);
            }, 250));
        }

        arrangeMasonryItems($container, items, columns) {
            const columnHeights = new Array(columns).fill(0);
            const gap = parseInt($container.css('--grid-row-gap')) || 20;

            items.each((index, item) => {
                const $item = $(item);
                const shortestColumn = columnHeights.indexOf(Math.min(...columnHeights));
                
                $item.css({
                    'grid-column': shortestColumn + 1,
                    'grid-row-start': Math.floor(columnHeights[shortestColumn] / gap) + 1
                });

                columnHeights[shortestColumn] += $item.outerHeight() + gap;
            });
        }

        initPagination($container, settings) {
            const $wrapper = $container.closest('.custom-loop-wrapper');
            const $widget = $container.closest('.elementor-widget-aae--loop-grid');
            
            this.log('Initializing pagination', {
                pagination_type: settings.pagination_type,
                pagination_load_type: settings.pagination_load_type,
                wrapper_found: $wrapper.length > 0,
                widget_found: $widget.length > 0
            });
            
            // Handle load more button
            $wrapper.on('click', '.custom-loop-load-more', (e) => {
                e.preventDefault();
                this.log('Load more clicked');
                this.handleLoadMore($(e.currentTarget), $container, settings);
            });

            // Handle AJAX pagination links
            if (settings.pagination_load_type === 'ajax') {
                this.log('Setting up AJAX pagination');
                
                // Delegate to wrapper for dynamically loaded pagination
                $wrapper.on('click', '.custom-loop-pagination a, .custom-loop-pagination-wrapper a, .page-numbers a', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const $link = $(e.currentTarget);
                    const href = $link.attr('href');
                    const page = this.extractPageFromUrl(href);
                    
                    this.log('AJAX pagination link clicked', {
                        href: href,
                        page: page
                    });
                    
                    this.loadPage($container, settings, page);
                });
            }
        }

        handleLoadMore($button, $container, settings) {
            if ($button.hasClass('loading') || $button.prop('disabled')) {
                return;
            }

            const page = parseInt($button.data('page'));
            const maxPage = parseInt($button.data('max-page'));

            this.log('Load more', { page, maxPage });

            if (page > maxPage) {
                $button.hide();
                return;
            }

            $button.addClass('loading').prop('disabled', true);
            $button.find('.custom-loop-load-more-text').hide();
            $button.find('.custom-loop-loading-text').show();

            this.loadMorePosts($container, settings, page).then((response) => {
                this.log('Load more response:', response);
                
                if (response.success && response.data.html) {
                    // Append new items
                    const $newItems = $(response.data.html);
                    $newItems.addClass('new-item');
                    $container.append($newItems);

                    this.log('Appended items:', $newItems.length);

                    // Update button state
                    if (response.data.has_more) {
                        $button.data('page', response.data.next_page);
                        $button.removeClass('loading').prop('disabled', false);
                        $button.find('.custom-loop-loading-text').hide();
                        $button.find('.custom-loop-load-more-text').show();
                        this.log('More items available, next page:', response.data.next_page);
                    } else {
                        $button.fadeOut();
                        this.log('No more items to load');
                    }

                    // Re-initialize masonry if needed
                    if ($container.hasClass('custom-loop-masonry')) {
                        this.initMasonry($container);
                    }

                    // Trigger custom event
                    $container.trigger('aae-loop-builder:itemsLoaded', [$newItems]);
                } else {
                    this.log('No HTML in load more response', response);
                    $button.removeClass('loading').prop('disabled', false);
                    $button.find('.custom-loop-loading-text').hide();
                    $button.find('.custom-loop-load-more-text').show();
                }
            }).catch((error) => {
                console.error('[AAE Loop Builder] Load more failed:', error);
                $button.removeClass('loading').prop('disabled', false);
                $button.find('.custom-loop-loading-text').hide();
                $button.find('.custom-loop-load-more-text').show();
            });
        }

        initInfiniteScroll($container, settings) {
            let loading = false;
            let currentPage = settings.paged || 1;
            let page = currentPage + 1; // Start from next page
            
            const loadMoreOnScroll = () => {
                if (loading) return;

                const containerBottom = $container.offset().top + $container.outerHeight();
                const viewportBottom = $(window).scrollTop() + $(window).height();
                const threshold = 200; // Load when 200px before reaching end

                if (viewportBottom >= containerBottom - threshold) {
                    loading = true;
                    
                    // Show loading indicator
                    if (!$container.find('.infinite-scroll-loading').length) {
                        $container.append('<div class="infinite-scroll-loading" style="text-align:center;padding:20px;"><i class="eicon-loading eicon-animation-spin" style="font-size:24px;color:#007cba;"></i></div>');
                    }
                    
                    this.loadMorePosts($container, settings, page).then((response) => {
                        $container.find('.infinite-scroll-loading').remove();
                        
                        if (response.success && response.data.html) {
                            const $newItems = $(response.data.html);
                            $newItems.addClass('new-item');
                            $container.append($newItems);
                            
                            if (response.data.has_more) {
                                page++;
                                loading = false;
                                
                                // Re-initialize masonry if needed
                                if ($container.hasClass('custom-loop-masonry')) {
                                    this.initMasonry($container);
                                }
                            } else {
                                $(window).off('scroll', loadMoreOnScroll);
                                // Show end message
                                $container.append('<div class="infinite-scroll-end" style="text-align:center;padding:20px;color:#999;">No more items to load</div>');
                            }

                            $container.trigger('aae-loop-builder:itemsLoaded', [$newItems]);
                        } else {
                            loading = false;
                        }
                    }).catch((error) => {
                        console.error('Infinite scroll failed:', error);
                        $container.find('.infinite-scroll-loading').remove();
                        loading = false;
                    });
                }
            };

            $(window).on('scroll', this.debounce(loadMoreOnScroll, 150));
        }

        loadMorePosts($container, settings, page) {
            const ajaxUrl = typeof wcf_addons_frontend !== 'undefined' ? wcf_addons_frontend.ajax_url : '/wp-admin/admin-ajax.php';
            const nonce = settings.nonce || (typeof wcf_addons_frontend !== 'undefined' ? wcf_addons_frontend.nonce : '');
            
            this.log('Load more posts AJAX', {
                url: ajaxUrl,
                page: page,
                nonce: nonce ? 'present' : 'missing'
            });
            
            return $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clb_load_more',
                    nonce: nonce,
                    settings: settings,
                    page: page
                }
            });
        }

        loadPage($container, settings, page) {
            this.log('Loading page:', page);
            $container.addClass('ajax-loading');
            
            const newSettings = {...settings, paged: page};
            const ajaxUrl = typeof wcf_addons_frontend !== 'undefined' ? wcf_addons_frontend.ajax_url : '/wp-admin/admin-ajax.php';
            const nonce = settings.nonce || (typeof wcf_addons_frontend !== 'undefined' ? wcf_addons_frontend.nonce : '');
            
            this.log('AJAX request', {
                url: ajaxUrl,
                page: page,
                nonce: nonce ? 'present' : 'missing'
            });
            
            return $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clb_load_page',
                    nonce: nonce,
                    settings: newSettings,
                    page: page
                }
            }).then((response) => {
                this.log('AJAX response:', response);
                
                if (response.success && response.data.html) {
                    // Remove old items
                    $container.find('.e-loop-item').remove();
                    $container.removeClass('ajax-loading');
                    
                    // Add new items
                    const $newItems = $(response.data.html);
                    $container.append($newItems);
                    
                    this.log('Items loaded:', $newItems.length);
                    
                    // Update pagination if provided
                    if (response.data.pagination) {
                        const $wrapper = $container.closest('.custom-loop-wrapper');
                        const $paginationContainer = $wrapper.find('.custom-loop-pagination-wrapper');
                        if ($paginationContainer.length) {
                            $paginationContainer.html(response.data.pagination);
                            this.log('Pagination updated');
                        } else {
                            // If pagination doesn't exist, create it
                            $wrapper.append('<div class="custom-loop-pagination-wrapper">' + response.data.pagination + '</div>');
                        }

                        // Re-bind pagination events for newly loaded pagination
                        this.initPagination($container, settings);
                    }
                    
                    // Scroll to top of container
                    $('html, body').animate({
                        scrollTop: $container.offset().top - 100
                    }, 500);
                    
                    // Re-initialize masonry if needed
                    if ($container.hasClass('custom-loop-masonry')) {
                        this.initMasonry($container);
                    }
                } else {
                    this.log('No HTML in response', response);
                    $container.removeClass('ajax-loading');
                }
            }).catch((error) => {
                console.error('[AAE Loop Builder] Page load failed:', error);
                $container.removeClass('ajax-loading');
            });
        }

        extractPageFromUrl(url) {
            if (!url) return 1;

            // Handle AJAX pagination links (which may be relative URLs)
            if (url.startsWith('?') || url.startsWith('#')) {
                let match = url.match(/[?&]paged=(\d+)/);
                if (match) return parseInt(match[1]);

                match = url.match(/[?&]page=(\d+)/);
                if (match) return parseInt(match[1]);
            }

            // Handle full URLs
            let match = url.match(/[?&]paged=(\d+)/);
            if (match) return parseInt(match[1]);

            match = url.match(/[?&]page=(\d+)/);
            if (match) return parseInt(match[1]);

            // Try URL path pagination (e.g., /page/2/)
            match = url.match(/\/page\/(\d+)\//);
            if (match) return parseInt(match[1]);

            // Handle pagination links without query parameters (e.g., /page/2)
            match = url.match(/\/page\/(\d+)$/);
            if (match) return parseInt(match[1]);

            // Try to extract from href attribute (for pagination links like #/page/2)
            if (url.includes('#')) {
                const hashPart = url.split('#')[1];
                if (hashPart) {
                    match = hashPart.match(/page\/(\d+)/);
                    if (match) return parseInt(match[1]);
                }
            }

            return 1;
        }

        initEventHandlers() {
            // Handle template creation from empty view
            $(document).on('click', '.custom-loop-empty-view__button', (e) => {
                e.preventDefault();
                this.handleCreateTemplate($(e.currentTarget));
            });
        }

        handleCreateTemplate($button) {
            // This would integrate with Elementor's template creation system
            if (typeof elementor !== 'undefined' && elementor.config) {
            }
        }

        initElementorIntegration() {
            if (typeof elementorFrontend === 'undefined') {
                this.log('Elementor frontend not available');
                return;
            }

            this.log('Registering Elementor hooks');

            // Add handler for Elementor widgets
            elementorFrontend.hooks.addAction('frontend/element_ready/aae--loop-grid.default', ($scope) => {
                this.log('Loop grid widget ready');
                const $container = $scope.find('.custom-loop-container');
                if ($container.length) {
                    this.initLoopGrid($container);
                } else {
                    this.log('Warning: Loop container not found in scope');
                }
            });

            elementorFrontend.hooks.addAction('frontend/element_ready/aae--loop-carousel.default', ($scope) => {
                this.log('Loop carousel widget ready');
                const $carousel = $scope.find('.custom-loop-carousel');
                if ($carousel.length) {
                    this.initLoopCarousel($carousel);
                }
            });
        }

        initLoopCarousel($carousel) {
            if (typeof Swiper === 'undefined') {
                console.warn('Swiper is required for carousel functionality');
                return;
            }

            const settings = $carousel.data('settings') || {};
            
            const swiperOptions = {
                slidesPerView: parseInt(settings.slides_to_show) || 3,
                slidesPerGroup: parseInt(settings.slides_to_scroll) || 1,
                spaceBetween: 20,
                loop: settings.infinite !== false,
                speed: parseInt(settings.speed) || 500,
                autoplay: settings.autoplay ? {
                    delay: parseInt(settings.autoplay_speed) || 3000,
                    pauseOnMouseEnter: settings.pause_on_hover !== false,
                } : false,
                navigation: settings.show_arrows ? {
                    nextEl: $carousel.find('.swiper-button-next')[0],
                    prevEl: $carousel.find('.swiper-button-prev')[0],
                } : false,
                pagination: settings.show_dots ? {
                    el: $carousel.find('.swiper-pagination')[0],
                    clickable: true,
                } : false,
                breakpoints: {
                    320: {
                        slidesPerView: 1,
                        slidesPerGroup: 1,
                    },
                    768: {
                        slidesPerView: Math.min(2, parseInt(settings.slides_to_show) || 3),
                        slidesPerGroup: 1,
                    },
                    1024: {
                        slidesPerView: parseInt(settings.slides_to_show) || 3,
                        slidesPerGroup: parseInt(settings.slides_to_scroll) || 1,
                    }
                }
            };

            new Swiper($carousel[0], swiperOptions);
        }

        // Utility functions
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Images loaded polyfill
    $.fn.imagesLoaded = function(callback) {
        const $images = this.find('img');
        let loaded = 0;
        const total = $images.length;

        if (total === 0) {
            callback && callback();
            return this;
        }

        $images.each(function() {
            const img = new Image();
            img.onload = img.onerror = () => {
                loaded++;
                if (loaded === total) {
                    callback && callback();
                }
            };
            img.src = this.src;
        });

        return this;
    };

    // Initialize the plugin
    const loopBuilder = new AAE_LoopBuilder();

    // Expose to window for debugging
    window.AAE_LoopBuilder = loopBuilder;
    
    // Debug helper function
    window.aaeEnableLoopDebug = function() {
        loopBuilder.debug = true;
        document.body.classList.add('aae-loop-debug');
    };
    
    window.aaeDisableLoopDebug = function() {
        loopBuilder.debug = false;
        document.body.classList.remove('aae-loop-debug');
    };

})(jQuery);


