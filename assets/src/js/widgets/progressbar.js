/* global WCF_ADDONS_JS */
(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */
    const WcfProgressbar = function ($scope, $) {

        const progressbarWrap = $('.wcf__progressbar ', $scope);
        const progressbar = progressbarWrap.find('.progressbar');
        const settings = progressbarWrap.data('settings');
        const percent = settings['percentage'] / 100;

        const dotAnimation = function () {
            let count = 0;
            let animationDots = Math.floor(percent * 100 / 20);
            let animation = setInterval(function () {
                if (count >= animationDots) {
                    clearInterval(animation)
                } else {
                    $(progressbar.find('.dot')[count]).addClass('active');
                    count++;
                }
            }, 500);
        }

        const progressAnimation = function () {

            let progressBar = null;

            const progressbarOptions = {
                strokeWidth: settings['stroke-width'],
                trailWidth: settings['trail-width'],
                color: settings['color'],
                trailColor: settings['trail-color'],
                duration: 1400,
            }

            //show percentage
            if ('show' === settings['display-percentage']) {

                progressbarOptions['text'] = {
                    value: settings['percentage'] + '%',
                }

                if ('line' === settings['progress-type']) {
                    const rightValue = 100 - settings['percentage'];
                    progressbarOptions['text']['style'] = {
                        position: 'absolute',
                        right: rightValue + '%',
                    }
                }
            }

            if ('line' === settings['progress-type']) {
                progressBar = new ProgressBar.Line(progressbar[0], progressbarOptions);
            }

            if ('circle' === settings['progress-type']) {
                progressBar = new ProgressBar.Circle(progressbar[0], progressbarOptions);
            }

            return progressBar;
        }

        let waypoint = new Waypoint({
            element: progressbarWrap,
            handler: function (direction) {
                if ('dot' === settings['progress-type']) {
                    dotAnimation();
                } else {
                    progressAnimation().animate(percent);
                }
                this.destroy();
            },
            offset: '75%',
        });

        //remove the attribute after getting the progressbar settings
        progressbarWrap.removeAttr('data-settings');
    };

    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--progressbar.default', WcfProgressbar);
    });

})(jQuery);