(function ($) {
    const WCF_Feature_Posts = function ($scope, $) {
        const $popup_id = $scope.attr('data-id');
        let popup_content = $(`.wcf--popup-video-wrapper`).first();
        // Move popup content to body if not already moved
        if (!popup_content.parent().is('body'))
        {
            if (!$('body > .wcf--popup-video-wrapper').length) {
                popup_content.appendTo('body');
            }
        }

        const open_popup = $(`.elementor-element-${$popup_id} .wcf-post-popup,.wcf-post-popup`);
   
        open_popup.each(function (index) {

            $(this).next('.wcf--popup-video-wrapper').remove();
            $(this).off('click').on('click', function () {
                let $_url = $(this).attr('data-src');

                $(`.wcf--popup-video-wrapper`).find('.aae-popup-content-container').html('');

                if ($(this).hasClass('audio')) {
                    $('.wcf--popup-video-wrapper').find('.aae-popup-content-container').html(`<div class="audio wcf-audio-wrapper-clean">
                        <audio controls>
                            <source src="${$_url}" type="audio/mpeg">
                        </audio>
                    </div>`);
                }

                if ($(this).hasClass('video')) {
                    $('.wcf--popup-video-wrapper').find('.aae-popup-content-container').html(`<iframe  src="${$_url}" ></iframe>`);
                }

                if ($(this).hasClass('gallery')) {
                    let gallery = $scope.find('.aae-post-gallery-wrapper').clone();
                    $('.wcf--popup-video-wrapper').find('.aae-popup-content-container').html(gallery);
                    const swiper = new Swiper(".aae-popup-content-container .gallery-slider", {
                        'speed': '1500',
                        navigation: {
                            nextEl: ".btn-next",
                            prevEl: ".btn-prev",
                        },
                    });
                }

                window.VideoAnimation = gsap.timeline({defaults: {ease: "power2.inOut"}})
                    .to(`body > .wcf--popup-video-wrapper`, {
                        scaleY: 0.01,
                        x: 1,
                        opacity: 1,
                        visibility: 'visible',
                        duration: 0.4
                    })
                    .to(`body > .wcf--popup-video-wrapper`, {
                        scaleY: 1,
                        duration: 0.6
                    })
                    .to(`body > .wcf--popup-video-wrapper .wcf--popup-video`, {
                        scaleY: 1,
                        opacity: 1,
                        visibility: 'visible',
                        duration: 0.6
                    }, "-=0.4");
            });
        });

        // Calculate audio duration
        $('.audio-duration-wrapper', $scope).each(function () {
            const wrapper = $(this);
            const link = wrapper.attr('data-link');

            if (link) {
                const audio = new Audio(link);

                audio.addEventListener('loadedmetadata', function () {
                    const totalDuration = Math.floor(audio.duration);
                    const minutes = Math.floor(totalDuration / 60);
                    const seconds = totalDuration % 60;

                    wrapper.find('.audio-duration').text(`${minutes}:${seconds < 10 ? '0' : ''}${seconds}`);
                });

                wrapper.find('.audio-icon').on('click', function () {
                    if (audio.paused) {
                        audio.play();
                        $(this).find('.play-icon').hide();
                        $(this).find('.pause-icon').show();

                        const interval = setInterval(function () {
                            const remainingTime = Math.floor(audio.duration - audio.currentTime);
                            const minutes = Math.floor(remainingTime / 60);
                            const seconds = remainingTime % 60;

                            wrapper.find('.audio-duration').text(`${minutes}:${seconds < 10 ? '0' : ''}${seconds}`);

                            if (remainingTime <= 0) {
                                clearInterval(interval);
                                wrapper.find('.play-icon').show();
                                // wrapper.find('.play-icon').text('▶');
                            }
                        }, 1000);
                    } else {
                        audio.pause();
                        $(this).find('.pause-icon').hide();
                        $(this).find('.play-icon').show();
                    }
                });

                audio.addEventListener('error', function () {
                    console.error(`Could not load audio from: ${link}`);
                    wrapper.find('.audio-duration').text('Error');
                });
            }
        });

    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--feature-posts.default', WCF_Feature_Posts);
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--posts-pro.default', WCF_Feature_Posts);
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--banner-posts.default', WCF_Feature_Posts);
        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--filterable-gallery.default`, WCF_Feature_Posts);
        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--posts-filter.default`, WCF_Feature_Posts);
        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--posts-slider.default`, WCF_Feature_Posts);
        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--posts-timeline.default`, WCF_Feature_Posts);
    });

})(jQuery);