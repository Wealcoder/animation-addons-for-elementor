<?php
// Author profile extra fields with image icon uploader

namespace WCF_ADDONS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WCF_Author_Posts {

	public function __construct() {

		add_action( 'show_user_profile', array( $this, 'wcf_add_extra_user_profile_fields' ), 20 );
		add_action( 'edit_user_profile', array( $this, 'wcf_add_extra_user_profile_fields' ), 20 );

		add_action( 'personal_options_update', array( $this, 'wcf_save_extra_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'wcf_save_extra_user_profile_fields' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'wp_media_uploader' ) );

		// Footer JS injection for user profile and user edit pages
		add_action( 'admin_footer-user-edit.php', array( $this, 'author_fb_icon_media_script' ) );
		add_action( 'admin_footer-profile.php', array( $this, 'author_fb_icon_media_script' ) );

	}

	public function wp_media_uploader() {
		if ( is_admin() && ( get_current_screen()->id === 'user-edit' || get_current_screen()->id === 'profile' ) ) {
			wp_enqueue_media();
		}
	}

	public function wcf_add_extra_user_profile_fields( $user ) {
		$phone   = get_user_meta( $user->ID, 'wcf_phone_number', true );
		$socials = get_user_meta( $user->ID, 'author_social_profiles', true );
		if ( ! is_array( $socials ) ) {
			$socials = [];
		}
		?>
        <h2>Contact Info</h2>
        <table class="form-table">
            <tr>
                <th><label for="wcf_phone_number">Phone Number</label></th>
                <td>
                    <input type="text" name="wcf_phone_number" id="wcf_phone_number"
                           value="<?php echo esc_attr( $phone ); ?>" class="regular-text"/>
                    <p class="description">Enter your phone number for contact purposes.</p>
                </td>
            </tr>
        </table>

        <h3>Author Social Media Profiles</h3>
        <table class="form-table" id="author-social-repeater">
            <tbody>
			<?php foreach ( $socials as $index => $item ): ?>
                <tr class="social-row">
                    <td>
                        <input type="text" name="social_icon[]" placeholder="Icon/Image URL"
                               class="regular-text icon-url" id="icon-<?php echo $index; ?>"
                               value="<?php echo esc_attr( $item['icon'] ); ?>"/>
                        <button class="button upload-icon-button" data-target="#icon-<?php echo $index; ?>"
                                data-preview="#preview-<?php echo $index; ?>">Upload Icon
                        </button>
                        <span id="preview-<?php echo $index; ?>" style="margin:5px 0;">
							<?php if ( ! empty( $item['icon'] ) ): ?>
                                <img src="<?php echo esc_url( $item['icon'] ); ?>" style="max-width:50px;"/>
							<?php endif; ?>
                        </span>
                        <input type="text" name="social_url[]" placeholder="Profile URL" class="regular-text"
                               value="<?php echo esc_attr( $item['url'] ); ?>"/>
                        <input type="number" name="social_follower[]" placeholder="Followers" class="regular-text"
                               value="<?php echo esc_attr( $item['follower'] ); ?>"/>
                        <a href="#" class="button remove-social">Remove</a>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <a href="#" class="button" id="add-social">Add Social Profile</a>
        </p>

		<?php
		// Add nonce field for security
		wp_nonce_field( 'wcf_save_author_social_profiles', 'wcf_author_social_profiles_nonce' );
	}

	public function wcf_save_extra_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['wcf_author_social_profiles_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['wcf_author_social_profiles_nonce'], 'wcf_save_author_social_profiles' ) ) {
			return;
		}

		if ( isset( $_POST['wcf_phone_number'] ) ) {
			update_user_meta( $user_id, 'wcf_phone_number', sanitize_text_field( $_POST['wcf_phone_number'] ) );
		}

		$icons     = isset( $_POST['social_icon'] ) ? $_POST['social_icon'] : [];
		$urls      = isset( $_POST['social_url'] ) ? $_POST['social_url'] : [];
		$followers = isset( $_POST['social_follower'] ) ? $_POST['social_follower'] : [];

		$social_profiles = [];

		for ( $i = 0; $i < count( $icons ); $i ++ ) {
			if ( ! empty( $icons[ $i ] ) || ! empty( $urls[ $i ] ) || ! empty( $followers[ $i ] ) ) {
				$social_profiles[] = [
					'icon'     => sanitize_text_field( $icons[ $i ] ),
					'url'      => esc_url_raw( $urls[ $i ] ),
					'follower' => intval( $followers[ $i ] ),
				];
			}
		}

		update_user_meta( $user_id, 'author_social_profiles', $social_profiles );
	}

	public function author_fb_icon_media_script() {
		?>
        <script>
            jQuery(document).ready(function ($) {

                function bindUploader(button) {
                    button.on('click', function (e) {
                        e.preventDefault();
                        const $btn = $(this);
                        const targetInput = $($btn.data('target'));
                        const previewBox = $($btn.data('preview'));

                        const frame = wp.media({
                            title: 'Select Icon Image',
                            button: {text: 'Use this image'},
                            multiple: false
                        });

                        frame.on('select', function () {
                            const attachment = frame.state().get('selection').first().toJSON();
                            targetInput.val(attachment.url);
                            previewBox.html('<img src="' + attachment.url + '" style="max-width:50px;" />');
                        });

                        frame.open();
                    });
                }

                // Bind existing upload buttons
                bindUploader($('.upload-icon-button'));

                $('#add-social').on('click', function (e) {
                    e.preventDefault();
                    const count = $('.social-row').length;
                    const newRow = `
                    <tr class="social-row">
                        <td>
                            <input type="text" name="social_icon[]" placeholder="Icon/Image URL" class="regular-text icon-url" id="icon-${count}" />
                            <button class="button upload-icon-button" data-target="#icon-${count}" data-preview="#preview-${count}">Upload Icon</button>
                            <span id="preview-${count}" style="margin:5px 0;"></span>
                            <input type="text" name="social_url[]" placeholder="Profile URL" class="regular-text" />
                            <input type="number" name="social_follower[]" placeholder="Followers" class="regular-text" />
                            <a href="#" class="button remove-social">Remove</a>
                        </td>
                    </tr>`;
                    $('#author-social-repeater tbody').append(newRow);

                    // Bind uploader to new row
                    bindUploader($('#author-social-repeater tbody .social-row:last .upload-icon-button'));
                });

                $(document).on('click', '.remove-social', function (e) {
                    e.preventDefault();
                    $(this).closest('tr').remove();
                });
            });
        </script>
		<?php
	}

}

new WCF_Author_Posts();
