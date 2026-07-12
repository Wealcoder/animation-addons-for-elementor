(function($) {
	'use strict';

	var ImageGalleryHandler = function($scope, $) {
		var $gallery = $scope.find('.wcf--image-gallery');
		if (!$gallery.length) return;

		var $lightboxData = $scope.find('.wcf--lightbox-data');
		if (!$lightboxData.length) return;

		var images = $lightboxData.data('images') || [];
		var animation = $lightboxData.data('animation') || 'fade-scale';
		var showCounter = $lightboxData.data('counter') === 'yes';
		var closeIcon = $lightboxData.data('close-icon') || '';
		var prevIcon = $lightboxData.data('prev-icon') || '';
		var nextIcon = $lightboxData.data('next-icon') || '';
		var fullscreenIcon = $lightboxData.data('fullscreen-icon') || '';

		var currentIndex = 0;
		var $lightbox = null;

		$gallery.on('click', '.wcf--lightbox-trigger', function(e) {
			e.preventDefault();
			currentIndex = parseInt($(this).data('lightbox-index')) || 0;
			openLightbox();
		});

		function openLightbox() {
			// Remove existing lightbox if any
			$('.wcf--lightbox').remove();

			// Create Lightbox HTML
			var lightboxHtml = 
				'<div class="wcf--lightbox wcf--lightbox-' + animation + '">' +
					'<div class="wcf--lightbox-overlay"></div>' +
					'<div class="wcf--lightbox-toolbar">' +
						(showCounter ? '<span class="wcf--lightbox-counter"></span>' : '') +
						'<button class="wcf--lightbox-btn wcf--lightbox-fullscreen">' + fullscreenIcon + '</button>' +
						'<button class="wcf--lightbox-btn wcf--lightbox-close">' + closeIcon + '</button>' +
					'</div>' +
					'<button class="wcf--lightbox-btn wcf--lightbox-prev">' + prevIcon + '</button>' +
					'<button class="wcf--lightbox-btn wcf--lightbox-next">' + nextIcon + '</button>' +
					'<div class="wcf--lightbox-content">' +
						'<div class="wcf--lightbox-stage">' +
							'<img class="wcf--lightbox-image" src="" alt="" />' +
						'</div>' +
					'</div>' +
				'</div>';

			$lightbox = $(lightboxHtml).appendTo('body');

			// Bind Events
			$lightbox.find('.wcf--lightbox-overlay, .wcf--lightbox-close').on('click', closeLightbox);
			$lightbox.find('.wcf--lightbox-next').on('click', showNext);
			$lightbox.find('.wcf--lightbox-prev').on('click', showPrev);
			$lightbox.find('.wcf--lightbox-fullscreen').on('click', toggleFullscreen);

			// Bind Key Events
			$(document).on('keydown.wcfGallery', function(e) {
				if (e.key === 'Escape') closeLightbox();
				if (e.key === 'ArrowRight') showNext();
				if (e.key === 'ArrowLeft') showPrev();
			});

			loadImage(currentIndex);

			// Trigger Animation
			setTimeout(function() {
				$lightbox.addClass('wcf--lightbox-active');
			}, 10);
		}

		function loadImage(index) {
			if (index < 0 || index >= images.length) return;
			currentIndex = index;

			var $image = $lightbox.find('.wcf--lightbox-image');
			var $stage = $lightbox.find('.wcf--lightbox-stage');

			// Apply fade out stage transition
			$stage.addClass('wcf--lightbox-stage-fade');

			// Preload and swap source
			var img = new Image();
			img.onload = function() {
				$image.attr('src', images[currentIndex]);
				$stage.removeClass('wcf--lightbox-stage-fade');
			};
			img.src = images[currentIndex];

			// Update Counter
			if (showCounter) {
				$lightbox.find('.wcf--lightbox-counter').text((currentIndex + 1) + ' / ' + images.length);
			}
		}

		function showNext() {
			var nextIndex = (currentIndex + 1) % images.length;
			loadImage(nextIndex);
		}

		function showPrev() {
			var prevIndex = (currentIndex - 1 + images.length) % images.length;
			loadImage(prevIndex);
		}

		function closeLightbox() {
			if (!$lightbox) return;
			$lightbox.removeClass('wcf--lightbox-active');
			$(document).off('keydown.wcfGallery');
			setTimeout(function() {
				$lightbox.remove();
				$lightbox = null;
			}, 400);
		}

		function toggleFullscreen() {
			if (!document.fullscreenElement) {
				document.documentElement.requestFullscreen().catch(function(err) {
					console.error('Error attempting to enable full-screen mode:', err.message);
				});
			} else {
				if (document.exitFullscreen) {
					document.exitFullscreen();
				}
			}
		}
	};

	$(window).on('elementor/frontend/init', function() {
		elementorFrontend.hooks.addAction('frontend/element_ready/wcf--image-gallery.default', ImageGalleryHandler);
	});

})(jQuery);
