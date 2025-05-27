/* global WCF_ADDONS_JS */

(function ($) {
    /**
     * @param $scope The Widget wrapper element as a jQuery element
     * @param $ The jQuery alias
     */    
    // Make sure you run this code under Elementor.
    $(window).on('elementor/frontend/init', function () {

        const Modules = elementorModules.frontend.handlers.Base;

        const contact_form_7 = function ($scope) {
            const submit_btn = $('.wpcf7-submit', $scope);
            let classes = submit_btn.attr('class');
            classes += ' wcf-btn-default ' + $('.wcf--form-wrapper', $scope).attr('btn-hover');

            submit_btn.replaceWith(function () {
                return $('<button/>', {
                    html: $('.btn-icon').html() + submit_btn.attr('value'),
                    class: classes,
                    type: 'submit'
                });
            });
        };

        const Countdown = Modules.extend({
            bindEvents: function bindEvents() {
                this.run();
            },

            run: function run() {

                // Update the count down every 1 second
                const x = setInterval(()=> {
                    this.count_down(x);
                }, 1000);

                this.count_down(x);

            },

            count_down: function count_down(x) {

                // Set the date we're counting down to
                const countDownDate = new Date(this.getElementSettings('countdown_timer_due_date')).getTime();

                // Get today's date and time
                let now = new Date().getTime();

                // Find the distance between now and the count down date
                let distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                let days = Math.floor(distance / (1000 * 60 * 60 * 24));
                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // If the count down is over, write some text
                if (distance < 0) {
                    clearInterval(x);
                    this.findElement('.wcf--countdown').html(this.time_finish_content());
                } else {
                    this.findElement('.wcf--countdown').html(this.timer_content({
                        'days': days,
                        'hours': hours,
                        'minutes': minutes,
                        'seconds': seconds
                    }));
                }

            },

            timer_content: function timer_content(times= []) {
                if ( 0 === times.length ){
                    return ;
                }
                let time_content = '';

                $.each(times, (index, time)=>{
                    const title = this.getElementSettings(`countdown_timer_${index}_label`);
                    time_content +=`<div class="timer-content timer-item-${index} "><span class="time-count ${index}-count">${time}</span><span class="time-title ${index}-title">${title}</span></div>`;
                })

                return time_content;
            },

            time_finish_content: function () {
                const title = this.getElementSettings('time_expire_title');
                const description = this.getElementSettings('time_expire_desc');
                let finish_content = '<div class="countdown-expire">';
                if (title) {
                    finish_content += `<div class="countdown-expire-title">${title}</div>`
                }
                if (description) {
                    finish_content += `<div class="countdown-expire-desc">${description}</div>`
                }
                finish_content += '</div>';

                return finish_content;
            }

        });

        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--contact-form-7.default`, contact_form_7);

        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--countdown.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(Countdown, {
                $element: $scope
            });
        });

        const Search = function ($scope) {
            const searchWrapper = $('.search--wrapper', $scope);
            const toggle_open = $('.toggle--open', $scope);
            const toggle_close = $('.toggle--close', $scope);

            toggle_open.on('click', function (e) {
                searchWrapper.addClass('search-visible');
            });

            toggle_close.on('click', function (e) {
                searchWrapper.removeClass('search-visible');
            });

            $("input", $scope).focus(function () {
                $(".wcf-search-form", $scope).addClass('wcf-search-form--focus');
            });

            $("input", $scope).focusout(function () {
                $(".wcf-search-form", $scope).removeClass('wcf-search-form--focus');
            });

        };
        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--blog--search--form.default', Search);

    });

})(jQuery);
