<?php
/**
 * Admin View: Settings Widgets
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$widgets = apply_filters( 'wcf_widgets', $GLOBALS['wcf_addons_config']['widgets'] );
?>
<form action="POST" class="wcf-settings" name="wcf_save_widgets">
    <div class="settings-wrap">
        <div class="section-header">
            <div class="info">
                <h3><?php echo esc_html__( 'Widgets Settings', 'animation-addons-for-elementor' ); ?></h3>
                <span>
                <?php
                $total = wcf_addons_get_all_widgets_count();
                /* translators: %s: total */
                printf( esc_html__( 'Total %s Widgets', 'animation-addons-for-elementor' ), esc_html( $total ) );
                ?>
                </span>
            </div>
            <div class="header-right">
                <div class="switcher">
                    <input type="checkbox" id="view-global-widget" class="wcf-global-switch">
                    <label for="view-global-widget">
	                    <?php esc_html_e( 'Disable All', 'animation-addons-for-elementor' ); ?>
                        <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
	                    <?php esc_html_e( 'Enable All', 'animation-addons-for-elementor' ); ?>
                    </label>
                </div>
                <button type="button" class="wcf-admin-btn wcf-settings-save"><?php esc_html_e( 'Save Settings', 'animation-addons-for-elementor' ); ?></button>
            </div>
        </div>

		<?php foreach ( $widgets as $group ) { ?>
            <div class="settings-group">
                <div class="title-area">
                    <h4><?php echo esc_html( $group['title'] ); ?></h4>
                </div>
                <div class="settings-wrapper">
					<?php foreach ($group['elements'] as $key => $widget ) { ?>
                        <div class="item">
                            <div class="title"><?php echo esc_html( $widget['label'] ); ?></div>
                            <div class="actions">
                                <a href="<?php echo esc_url( $widget['doc_url'] ); ?>" title="<?php esc_attr_e( 'Documentation', 'animation-addons-for-elementor' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 10 11">
                                        <path d="M9.22.72C8.46,0,7.39,0,5.26,0H4.74c-2.13,0-3.2,0-4,.72S0,2.54,0,4.5v2c0,2,0,3,.78,3.78s1.83.72,4,.72h.52c2.13,0,3.2,0,4-.72S10,8.46,10,6.5v-2C10,2.54,10,1.46,9.22.72ZM9,6.5c0,1.86,0,2.63-.46,3.05S7.13,10,5.26,10H4.74c-1.87,0-2.8,0-3.28-.45S1,8.36,1,6.5v-2c0-1.86,0-2.63.46-3S2.87,1,4.74,1h.52c1.87,0,2.8,0,3.28.45S9,2.64,9,4.5Z"
                                              style="fill:#203263"/>
                                        <path d="M7,2.5H3a.5.5,0,0,0,0,1H7a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                        <path d="M7,5H3A.5.5,0,0,0,3,6H7A.5.5,0,0,0,7,5Z" style="fill:#203263"/>
                                        <path d="M5,7.5H3a.5.5,0,0,0,0,1H5a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                    </svg>
                                </a>
                                <a href="<?php echo esc_url( $widget['demo_url'] ); ?>" title="<?php esc_attr_e( 'Demo', 'animation-addons-for-elementor' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" viewBox="0 0 11 11">
                                        <path d="M10.5,1.17A2.67,2.67,0,0,0,9.83.5C9.12,0,8.24,0,6.5,0h-2C2.76,0,1.88,0,1.17.5a2.67,2.67,0,0,0-.67.67C0,1.88,0,2.76,0,4.5S0,7.12.5,7.83a2.67,2.67,0,0,0,.67.67c.67.47,1.5.5,3.07.5A1.44,1.44,0,0,1,4,10H3a.5.5,0,0,0,0,1H8a.5.5,0,0,0,0-1H7a1.44,1.44,0,0,1-.23-1c1.57,0,2.39,0,3.07-.5a2.67,2.67,0,0,0,.67-.67c.5-.71.5-1.59.5-3.33S11,1.88,10.5,1.17ZM5.11,10a2.6,2.6,0,0,0,.12-1h.54a2.6,2.6,0,0,0,.12,1ZM9.68,7.25a1.47,1.47,0,0,1-.43.43C8.8,8,8,8,6.5,8h-2C3,8,2.2,8,1.75,7.68a1.47,1.47,0,0,1-.43-.43C1,6.8,1,6,1,4.5s0-2.3.32-2.75a1.47,1.47,0,0,1,.43-.43C2.2,1,3,1,4.5,1h2C8,1,8.8,1,9.25,1.32a1.47,1.47,0,0,1,.43.43C10,2.2,10,3,10,4.5S10,6.8,9.68,7.25Z"
                                              style="fill:#203263"/>
                                        <path d="M6,6.5H5a.5.5,0,0,0,0,1H6a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                    </svg>
                                </a>
                                <div class="switcher">
	                                <?php $status = wcf_addons_element_status('wcf_save_widgets', $key, $widget) ?>
                                    <input type="checkbox" class="wcf-settings-item" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $status ); ?>>
                                    <label for="<?php echo esc_attr( $key ); ?>">
                                        <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
                                    </label>
                                </div>
                            </div>
							<?php if ( $widget['is_pro'] ) { ?>
                                <div class="ribbon"><?php echo esc_html__( 'Pro', 'animation-addons-for-elementor' ); ?></div>
							<?php } ?>
                        </div>
					<?php } ?>
                </div>
            </div>
		<?php } ?>

    </div>
</form>
