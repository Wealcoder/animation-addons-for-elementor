<?php

// author profile added some extra fields

namespace WCF_ADDONS;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class WCF_Author_Posts {

    public function __construct() {

        add_action('show_user_profile', array($this, 'wcf_add_extra_user_profile_fields'),20);
        add_action('edit_user_profile', array($this, 'wcf_add_extra_user_profile_fields'),20);

        add_action('personal_options_update', array($this, 'wcf_save_extra_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'wcf_save_extra_user_profile_fields'));


        add_action( 'admin_footer-user-edit.php', array($this,'author_fb_icon_media_script') );
        add_action( 'admin_footer-profile.php', array($this,'author_fb_icon_media_script') );

        add_action( 'admin_enqueue_scripts', array($this,'wp_media_uploader') ); 

    }

    public function wp_media_uploader() {
        if ( is_admin() ) {
            wp_enqueue_media();
        }
    }

    public function wcf_add_extra_user_profile_fields($user) {
        // Add your custom fields here

        ?>
            <h3>Author Social Information</h3>
            <table class="form-table">
        <tr>
            <th><label for="author_phone">Phone</label></th>
            <td>
                <input type="text" name="author_phone" id="author_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_phone', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_fb_icon">Facebook Icon</label></th>
            <td>
                <input type="text" name="author_fb_icon" id="author_fb_icon" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_fb_icon', true ) ); ?>" class="regular-text" />
                <input type="button" class="button upload-icon-button" data-target="#author_fb_icon" data-preview="#fb_icon_preview" value="Upload Icon" />
                <div id="fb_icon_preview" style="margin-top:10px;">
                    <?php if ( get_user_meta( $user->ID, 'author_fb_icon', true ) ) : ?>
                        <img src="<?php echo esc_url( get_user_meta( $user->ID, 'author_fb_icon', true ) ); ?>" style="max-width:100px;" />
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="author_fb_url">Facebook Profile URL</label></th>
            <td>
                <input type="text" name="author_fb_url" id="author_fb_url" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_fb_url', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_fb_follower">Facebook Follower</label></th>
            <td>
                <input type="text" name="author_fb_follower" id="author_fb_follower" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_fb_follower', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_tw_icon">Twitter Icon</label></th>
            <td>
                <input type="text" name="author_tw_icon" id="author_tw_icon" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tw_icon', true ) ); ?>" class="regular-text" />
                <input type="button"class="button upload-icon-button" data-target="#author_tw_icon" data-preview="#tw_icon_preview" value="Upload Icon" />
                <div id="fb_icon_preview" style="margin-top:10px;">
                    <?php if ( get_user_meta( $user->ID, 'author_tw_icon', true ) ) : ?>
                        <img src="<?php echo esc_url( get_user_meta( $user->ID, 'author_tw_icon', true ) ); ?>" style="max-width:100px;" />
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="author_tw_url">Twitter Profile URL</label></th>
            <td>
                <input type="text" name="author_tw_url" id="author_tw_url" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tw_url', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_tw_follower">Twitter Follower</label></th>
            <td>
                <input type="text" name="author_tw_follower" id="author_tw_follower" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tw_follower', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_tik_icon">TikTok Icon</label></th>
            <td>
                <input type="text" name="author_tik_icon" id="author_tik_icon" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tik_icon', true ) ); ?>" class="regular-text" />
                <input type="button" class="button upload-icon-button" data-target="#author_tik_icon" data-preview="#tik_icon_preview" value="Upload Icon" />
                <div id="fb_icon_preview" style="margin-top:10px;">
                    <?php if ( get_user_meta( $user->ID, 'author_tik_icon', true ) ) : ?>
                        <img src="<?php echo esc_url( get_user_meta( $user->ID, 'author_tik_icon', true ) ); ?>" style="max-width:100px;" />
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="author_tik_url">TikTok Profile URL</label></th>
            <td>
                <input type="text" name="author_tik_url" id="author_tik_url" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tik_url', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="author_tik_follower">TikTok Follower</label></th>
            <td>
                <input type="text" name="author_tik_follower" id="author_tik_follower" value="<?php echo esc_attr( get_user_meta( $user->ID, 'author_tik_follower', true ) ); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
        <?php
    }

    public function wcf_save_extra_user_profile_fields($user_id) {
        // Save your custom fields here
        update_user_meta( $user_id, 'author_phone', sanitize_text_field( $_POST['author_phone'] ) );

        update_user_meta( $user_id, 'author_fb_icon', sanitize_text_field( $_POST['author_fb_icon'] ) );
        update_user_meta( $user_id, 'author_fb_url', esc_url_raw( $_POST['author_fb_url'] ) );
        update_user_meta( $user_id, 'author_fb_follower', sanitize_text_field( $_POST['author_fb_follower'] ) );

        update_user_meta( $user_id, 'author_tw_icon', sanitize_text_field( $_POST['author_tw_icon'] ) );
        update_user_meta( $user_id, 'author_tw_url', esc_url_raw( $_POST['author_tw_url'] ) );
        update_user_meta( $user_id, 'author_tw_follower', sanitize_text_field( $_POST['author_tw_follower'] ) );

        update_user_meta( $user_id, 'author_tik_icon', sanitize_text_field( $_POST['author_tik_icon'] ) );
        update_user_meta( $user_id, 'author_tik_url', esc_url_raw( $_POST['author_tik_url'] ) );
        update_user_meta( $user_id, 'author_tik_follower', sanitize_text_field( $_POST['author_tik_follower'] ) );
    }

    function author_fb_icon_media_script() {
        ?>
        <script>
       jQuery(document).ready(function($){
            var mediaUploader;

            $('.upload-icon-button').on('click', function(e){
                e.preventDefault();
                
                var button = $(this);
                var targetInput = $(button.data('target'));
                var previewBox = $(button.data('preview'));

                // Prevent creating multiple media frames
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Select Icon Image',
                    button: {
                        text: 'Use This Icon'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function(){
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url);
                    previewBox.html('<img src="' + attachment.url + '" style="max-width:100px;" />');
                });

                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

}

new WCF_Author_Posts();

?>