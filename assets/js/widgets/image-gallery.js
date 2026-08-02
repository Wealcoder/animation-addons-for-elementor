(function ($) {
    "use strict";

    function initLightbox(scope) {
        // scope is a raw DOM element (the Elementor widget wrapper)
        var wrapper = scope.querySelector('.wcf--lightbox-enabled');
        if (!wrapper) return;

        // Prevent double init
        if (wrapper.dataset.wcfLightboxInit === 'true') return;
        wrapper.dataset.wcfLightboxInit = 'true';

        var dataEl = scope.querySelector('.wcf--lightbox-data');
        if (!dataEl) return;

        var images = [];
        try {
            images = JSON.parse(dataEl.getAttribute('data-images') || '[]');
        } catch (e) {
            return;
        }

        var animation = dataEl.getAttribute('data-animation') || 'fade-scale';
        var showCounter = dataEl.getAttribute('data-counter') === 'yes';
        var closeIconHtml = dataEl.getAttribute('data-close-icon') || '&times;';
        var prevIconHtml = dataEl.getAttribute('data-prev-icon') || '&#8249;';
        var nextIconHtml = dataEl.getAttribute('data-next-icon') || '&#8250;';
        var fullscreenIconHtml = dataEl.getAttribute('data-fullscreen-icon') || '&#x26F6;';

        if (!images.length) return;

        var currentIndex = 0;
        var lightboxEl = null;
        var isFullscreen = false;
        var isTransitioning = false;

        function createLightbox() {
            var el = document.createElement('div');
            el.className = 'wcf--lightbox wcf--lightbox-' + animation;
            el.innerHTML =
                '<div class="wcf--lightbox-overlay"></div>' +
                '<div class="wcf--lightbox-content">' +
                    '<div class="wcf--lightbox-toolbar">' +
                        (showCounter ? '<span class="wcf--lightbox-counter"></span>' : '') +
                        '<button class="wcf--lightbox-btn wcf--lightbox-fullscreen" type="button" aria-label="Fullscreen">' + fullscreenIconHtml + '</button>' +
                        '<button class="wcf--lightbox-btn wcf--lightbox-close" type="button" aria-label="Close">' + closeIconHtml + '</button>' +
                    '</div>' +
                    '<div class="wcf--lightbox-stage">' +
                        '<img class="wcf--lightbox-image" src="" alt="" />' +
                    '</div>' +
                    '<button class="wcf--lightbox-btn wcf--lightbox-prev" type="button" aria-label="Previous">' + prevIconHtml + '</button>' +
                    '<button class="wcf--lightbox-btn wcf--lightbox-next" type="button" aria-label="Next">' + nextIconHtml + '</button>' +
                '</div>';

            document.body.appendChild(el);
            return el;
        }

        function showImage(index, direction) {
            if (isTransitioning) return;

            var img = lightboxEl.querySelector('.wcf--lightbox-image');
            var stage = lightboxEl.querySelector('.wcf--lightbox-stage');

            if (animation === 'slide' && typeof direction !== 'undefined') {
                isTransitioning = true;

                var slideOutClass = direction === 'next' ? 'wcf--slide-out-left' : 'wcf--slide-out-right';
                var slideInClass = direction === 'next' ? 'wcf--slide-in-right' : 'wcf--slide-in-left';

                img.classList.add(slideOutClass);

                setTimeout(function () {
                    img.classList.remove(slideOutClass);
                    img.src = images[index];
                    img.classList.add(slideInClass);

                    setTimeout(function () {
                        img.classList.remove(slideInClass);
                        isTransitioning = false;
                    }, 350);
                }, 350);
            } else {
                if (lightboxEl.classList.contains('wcf--lightbox-active')) {
                    isTransitioning = true;
                    stage.classList.add('wcf--lightbox-stage-fade');

                    setTimeout(function () {
                        img.src = images[index];
                        stage.classList.remove('wcf--lightbox-stage-fade');
                        isTransitioning = false;
                    }, 250);
                } else {
                    img.src = images[index];
                }
            }

            currentIndex = index;
            updateCounter();
            updateNavVisibility();
        }

        function updateCounter() {
            var counter = lightboxEl.querySelector('.wcf--lightbox-counter');
            if (counter) {
                counter.textContent = (currentIndex + 1) + ' / ' + images.length;
            }
        }

        function updateNavVisibility() {
            var prevBtn = lightboxEl.querySelector('.wcf--lightbox-prev');
            var nextBtn = lightboxEl.querySelector('.wcf--lightbox-next');
            if (images.length <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            }
        }

        function openLightbox(index) {
            if (!lightboxEl) {
                lightboxEl = createLightbox();
                bindLightboxEvents();
            }

            currentIndex = index;
            showImage(index);

            // Force reflow
            void lightboxEl.offsetHeight;
            lightboxEl.classList.add('wcf--lightbox-active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            if (!lightboxEl) return;

            lightboxEl.classList.remove('wcf--lightbox-active');
            document.body.style.overflow = '';
            isTransitioning = false;

            if (isFullscreen) {
                exitFullscreen();
            }
        }

        function goNext() {
            if (images.length <= 1) return;
            var nextIndex = (currentIndex + 1) % images.length;
            showImage(nextIndex, 'next');
        }

        function goPrev() {
            if (images.length <= 1) return;
            var prevIndex = (currentIndex - 1 + images.length) % images.length;
            showImage(prevIndex, 'prev');
        }

        function toggleFullscreen() {
            var content = lightboxEl.querySelector('.wcf--lightbox-content');
            if (!isFullscreen) {
                if (content.requestFullscreen) {
                    content.requestFullscreen();
                } else if (content.webkitRequestFullscreen) {
                    content.webkitRequestFullscreen();
                } else if (content.msRequestFullscreen) {
                    content.msRequestFullscreen();
                }
                isFullscreen = true;
            } else {
                exitFullscreen();
            }
        }

        function exitFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            isFullscreen = false;
        }

        function onKeyDown(e) {
            if (!lightboxEl || !lightboxEl.classList.contains('wcf--lightbox-active')) return;

            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowRight') {
                goNext();
            } else if (e.key === 'ArrowLeft') {
                goPrev();
            }
        }

        function bindLightboxEvents() {
            lightboxEl.querySelector('.wcf--lightbox-close').addEventListener('click', closeLightbox);
            lightboxEl.querySelector('.wcf--lightbox-overlay').addEventListener('click', closeLightbox);

            lightboxEl.querySelector('.wcf--lightbox-prev').addEventListener('click', function (e) {
                e.stopPropagation();
                goPrev();
            });
            lightboxEl.querySelector('.wcf--lightbox-next').addEventListener('click', function (e) {
                e.stopPropagation();
                goNext();
            });

            lightboxEl.querySelector('.wcf--lightbox-fullscreen').addEventListener('click', function (e) {
                e.stopPropagation();
                toggleFullscreen();
            });

            document.addEventListener('keydown', onKeyDown);

            document.addEventListener('fullscreenchange', function () {
                if (!document.fullscreenElement) {
                    isFullscreen = false;
                }
            });

            // Touch/swipe
            var touchStartX = 0;
            var stage = lightboxEl.querySelector('.wcf--lightbox-stage');

            stage.addEventListener('touchstart', function (e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            stage.addEventListener('touchend', function (e) {
                var touchEndX = e.changedTouches[0].screenX;
                var diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        goNext();
                    } else {
                        goPrev();
                    }
                }
            }, { passive: true });
        }

        // Bind click events to gallery triggers
        var triggers = wrapper.querySelectorAll('.wcf--lightbox-trigger');
        for (var i = 0; i < triggers.length; i++) {
            (function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var index = parseInt(this.getAttribute('data-lightbox-index'), 10);
                    openLightbox(index);
                });
            })(triggers[i]);
        }
    }

    // Elementor frontend hook
    var WcfImageGalleryHandler = function ($scope) {
        initLightbox($scope[0]);
    };

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--image-gallery.default', WcfImageGalleryHandler);
    });

    // Fallback: also init on DOMContentLoaded in case Elementor hooks don't fire
    document.addEventListener('DOMContentLoaded', function () {
        var widgets = document.querySelectorAll('.elementor-widget-wcf--image-gallery');
        for (var i = 0; i < widgets.length; i++) {
            initLightbox(widgets[i]);
        }
    });

})(jQuery);

//# sourceMappingURL=image-gallery.js.map
