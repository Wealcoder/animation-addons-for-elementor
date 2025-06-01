document.addEventListener('DOMContentLoaded', function () {
  if (typeof elementorFrontend !== 'undefined') {
    elementorFrontend.hooks.addAction('frontend/init', function () {
      var aaecontact_form_7 = function aaecontact_form_7($scope) {
        var submit_btn = $('.wpcf7-submit', $scope);
        var classes = submit_btn.attr('class');
        classes += ' wcf-btn-default ' + $('.wcf--form-wrapper', $scope).attr('btn-hover');
        console.log(classes);
        submit_btn.replaceWith(function () {
          return $('<button/>', {
            html: $('.btn-icon').html() + submit_btn.attr('value'),
            "class": classes,
            type: 'submit'
          });
        });
      };
      elementorFrontend.hooks.addAction("frontend/element_ready/wcf--contact-form-7.default", aaecontact_form_7);
    });
  }
});