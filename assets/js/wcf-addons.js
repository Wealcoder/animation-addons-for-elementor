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


        const ClickDrop = function ($scope) {
            let $btn = $scope.find(".aae-clickdrop-btn");
            let $modal = $scope.find(".aae-clickdrop-modal");

            $btn.on("click", function (e) {
                e.stopPropagation();
                $modal.slideToggle();
            });

            // Click outside to close
            $(document).on("click", function (e) {
                if (!$scope.is(e.target) && $scope.has(e.target).length === 0) {
                    $modal.slideUp();
                }
            });
        };

        elementorFrontend.hooks.addAction(`frontend/element_ready/wcf--contact-form-7.default`, contact_form_7);
        elementorFrontend.hooks.addAction(`frontend/element_ready/aae--clickdrop.default`, ClickDrop);

        elementorFrontend.hooks.addAction('frontend/element_ready/wcf--countdown.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(Countdown, {
                $element: $scope
            });
        });

    });

})(jQuery);
