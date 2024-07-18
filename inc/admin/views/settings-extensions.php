<?php
/**
 * Admin View: Settings Extensions
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extensions = apply_filters( 'wcf_extensions', $GLOBALS['wcf_addons_config']['extensions'] );
?>
<form action="POST" class="wcf-settings" name="wcf_save_extensions">
<div class="settings-wrap">
    <div class="section-header">
        <div class="info">
            <h3><?php echo esc_html__( 'Extension Settings', 'animation-addons-for-elementor' ); ?></h3>
            <span>
                <?php
                $total = wcf_addons_get_all_extensions_count();
                /* translators: %s: total */
                printf( esc_html__( 'Total %s Extensions', 'animation-addons-for-elementor' ), esc_html( $total ) );
                ?>
            </span>
        </div>
        <div class="header-right">
            <div class="switcher">
                <input type="checkbox" id="view-global-extensions" class="wcf-global-switch">
                <label for="view-global-extensions">
		            <?php esc_html_e( 'Disable All', 'animation-addons-for-elementor' ); ?>
                    <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
		            <?php esc_html_e( 'Enable All', 'animation-addons-for-elementor' ); ?>
                </label>
            </div>
            <button type="button" class="wcf-admin-btn wcf-settings-save"><?php esc_html_e( 'Save Settings', 'animation-addons-for-elementor' ); ?></button>
        </div>
    </div>

	<?php foreach ( $extensions as $groupkey => $group ) { ?>
        <div class="settings-group">
            <div class="title-area">
                <div>
                    <h4><?php echo esc_html( $group['title'] ); ?></h4>
	                <?php if ( 'gsap-extensions' === $groupkey ) { ?>
                        <i><?php echo esc_html__( 'N.B : Without Enabling Gsap Settings, the related Extensions will not work.', 'animation-addons-for-elementor' ); ?></i>
	                <?php } ?>
                </div>

	            <?php if ( 'gsap-extensions' === $groupkey ) { ?>
                    <div class="header-right">
                        <div class="switcher">
	                        <?php $status = ! defined( 'WCF_ADDONS_EX_VERSION' ) ? 'disabled' : checked( 1, wcf_addons_get_settings( 'wcf_save_extensions', 'wcf-gsap' ), false ); ?>
                            <input type="checkbox" id="view-gsap" class="wcf-gsap-switch wcf-settings-item" name="wcf-gsap" <?php echo esc_attr( $status ); ?>>
                            <label for="view-gsap">
					            <?php esc_html_e( 'Gsap', 'animation-addons-for-elementor' ); ?>
                                <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
                            </label>
                        </div>
                        <div class="switcher smooth-scroll">
	                        <?php $status = ! defined( 'WCF_ADDONS_EX_VERSION' ) ? 'disabled' : checked( 1, wcf_addons_get_settings( 'wcf_save_extensions', 'wcf-smooth-scroller' ), false ); ?>
                            <input type="checkbox" id="view-smooth-scroller" class="wcf-smooth-scroller-switch wcf-settings-item" name="wcf-smooth-scroller" <?php echo esc_attr( $status ); ?>>
                            <label for="view-smooth-scroller">
			                    <?php esc_html_e( 'Smooth Scroller', 'animation-addons-for-elementor' ); ?>
                                <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
                            </label>
                            <div class="smooth-settings">
                                <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 12.7 13"><path d="M11.53,7a1,1,0,0,1,0-1l.62-.7a1.6,1.6,0,0,0,.56-.94,1.74,1.74,0,0,0-.35-1h0L12,2.82c-.25-.44-.42-.73-.77-.88A1.71,1.71,0,0,0,10.11,2l-.71.2a.62.62,0,0,1-.45,0l-.19-.1a.66.66,0,0,1-.26-.32l-.21-.6A1.68,1.68,0,0,0,7.76.21,1.69,1.69,0,0,0,6.68,0H6A1.69,1.69,0,0,0,4.94.21a1.69,1.69,0,0,0-.54,1l-.19.58A.71.71,0,0,1,4,2.07l-.18.1a.69.69,0,0,1-.46.07L2.59,2a1.71,1.71,0,0,0-1.16-.09c-.35.14-.51.43-.77.88l-.3.51a1.72,1.72,0,0,0-.35,1,1.65,1.65,0,0,0,.56,1l.6.67a1,1,0,0,1,.15.51A1,1,0,0,1,1.19,7l-.62.69a1.65,1.65,0,0,0-.56,1,1.72,1.72,0,0,0,.35,1l.31.52c.25.44.41.73.76.87A1.71,1.71,0,0,0,2.59,11l.71-.2a.62.62,0,0,1,.45.05l.19.1a.66.66,0,0,1,.26.32l.2.6a1.69,1.69,0,0,0,.54.95A1.69,1.69,0,0,0,6,13h.66a1.69,1.69,0,0,0,1.08-.21,1.75,1.75,0,0,0,.54-.95l.19-.58a.67.67,0,0,1,.26-.33l.17-.1a.69.69,0,0,1,.46-.07l.73.21a1.71,1.71,0,0,0,1.16.09c.35-.14.52-.44.77-.88l.3-.51a1.68,1.68,0,0,0,.35-1,1.65,1.65,0,0,0-.56-1Zm-.06,2.15-.3.51a3.13,3.13,0,0,1-.27.45,2.47,2.47,0,0,1-.52-.12l-.76-.22A1.75,1.75,0,0,0,8.44,10l-.22.13a1.7,1.7,0,0,0-.67.84l-.2.61a3.77,3.77,0,0,1-.17.45,3,3,0,0,1-.5,0H5.53a3.13,3.13,0,0,1-.18-.47l-.2-.62a1.79,1.79,0,0,0-.69-.83l-.23-.13a1.72,1.72,0,0,0-.77-.19,2,2,0,0,0-.41.05L2.32,10a4.7,4.7,0,0,1-.5.13h0a2.19,2.19,0,0,1-.29-.46l-.3-.51C1.13,9,1,8.78,1,8.74a2.17,2.17,0,0,1,.32-.39L2,7.64A2,2,0,0,0,2.32,6.5a2,2,0,0,0-.38-1.16l-.62-.69A3.73,3.73,0,0,1,1,4.28a3.18,3.18,0,0,1,.23-.45l.3-.52a3.47,3.47,0,0,1,.27-.44A4,4,0,0,1,2.32,3l.76.22a1.77,1.77,0,0,0,1.17-.16l.22-.13a1.67,1.67,0,0,0,.68-.84l.2-.6A4.19,4.19,0,0,1,5.52,1,2.92,2.92,0,0,1,6,1H7.17a3.13,3.13,0,0,1,.18.47l.2.62a1.69,1.69,0,0,0,.69.83l.23.13a1.7,1.7,0,0,0,1.18.14L10.38,3a4.7,4.7,0,0,1,.5-.13h0a2.19,2.19,0,0,1,.29.46l.3.51c.1.18.22.39.23.43a2.75,2.75,0,0,1-.32.39l-.64.72a1.93,1.93,0,0,0-.36,1.13,2,2,0,0,0,.38,1.16l.63.69c.13.16.29.33.31.37A2.35,2.35,0,0,1,11.47,9.17Z" style="fill:#203263"/><path d="M6.32,3.9A2.6,2.6,0,1,0,8.93,6.5,2.6,2.6,0,0,0,6.32,3.9Zm0,4.2A1.6,1.6,0,1,1,7.93,6.5,1.6,1.6,0,0,1,6.32,8.1Z" /></svg>
                            </div>
                        </div>
                    </div>
	            <?php } ?>
            </div>
            <div class="settings-wrapper">
				<?php foreach ( $group['elements'] as $key => $extension ) { ?>
                    <div class="item">
                        <div class="title"><?php echo esc_html( $extension['label'] ); ?></div>
                        <div class="actions">
                            <a href="<?php echo esc_url( $extension['doc_url'] ); ?>" title="<?php esc_attr_e( 'Documentation', 'animation-addons-for-elementor' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 11">
                                    <path d="M9.22.72C8.46,0,7.39,0,5.26,0H4.74c-2.13,0-3.2,0-4,.72S0,2.54,0,4.5v2c0,2,0,3,.78,3.78s1.83.72,4,.72h.52c2.13,0,3.2,0,4-.72S10,8.46,10,6.5v-2C10,2.54,10,1.46,9.22.72ZM9,6.5c0,1.86,0,2.63-.46,3.05S7.13,10,5.26,10H4.74c-1.87,0-2.8,0-3.28-.45S1,8.36,1,6.5v-2c0-1.86,0-2.63.46-3S2.87,1,4.74,1h.52c1.87,0,2.8,0,3.28.45S9,2.64,9,4.5Z"
                                          style="fill:#203263"/>
                                    <path d="M7,2.5H3a.5.5,0,0,0,0,1H7a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                    <path d="M7,5H3A.5.5,0,0,0,3,6H7A.5.5,0,0,0,7,5Z" style="fill:#203263"/>
                                    <path d="M5,7.5H3a.5.5,0,0,0,0,1H5a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                </svg>
                            </a>
                            <a href="<?php echo esc_url( $extension['demo_url'] ); ?>" title="<?php esc_attr_e( 'Demo', 'animation-addons-for-elementor' ); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 11 11">
                                    <path d="M10.5,1.17A2.67,2.67,0,0,0,9.83.5C9.12,0,8.24,0,6.5,0h-2C2.76,0,1.88,0,1.17.5a2.67,2.67,0,0,0-.67.67C0,1.88,0,2.76,0,4.5S0,7.12.5,7.83a2.67,2.67,0,0,0,.67.67c.67.47,1.5.5,3.07.5A1.44,1.44,0,0,1,4,10H3a.5.5,0,0,0,0,1H8a.5.5,0,0,0,0-1H7a1.44,1.44,0,0,1-.23-1c1.57,0,2.39,0,3.07-.5a2.67,2.67,0,0,0,.67-.67c.5-.71.5-1.59.5-3.33S11,1.88,10.5,1.17ZM5.11,10a2.6,2.6,0,0,0,.12-1h.54a2.6,2.6,0,0,0,.12,1ZM9.68,7.25a1.47,1.47,0,0,1-.43.43C8.8,8,8,8,6.5,8h-2C3,8,2.2,8,1.75,7.68a1.47,1.47,0,0,1-.43-.43C1,6.8,1,6,1,4.5s0-2.3.32-2.75a1.47,1.47,0,0,1,.43-.43C2.2,1,3,1,4.5,1h2C8,1,8.8,1,9.25,1.32a1.47,1.47,0,0,1,.43.43C10,2.2,10,3,10,4.5S10,6.8,9.68,7.25Z"
                                          style="fill:#203263"/>
                                    <path d="M6,6.5H5a.5.5,0,0,0,0,1H6a.5.5,0,0,0,0-1Z" style="fill:#203263"/>
                                </svg>
                            </a>
                            <div class="switcher">
								<?php $status = wcf_addons_element_status('wcf_save_extensions', $key, $extension) ?>
                                <input type="checkbox" class="wcf-settings-item" id="<?php echo esc_attr( $key ); ?>"
                                       name="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $status ); ?>>
                                <label for="<?php echo esc_attr( $key ); ?>">
                                    <span class="control-label-switch" data-active="ON" data-inactive="OFF"></span>
                                </label>
                            </div>
                        </div>
						<?php if ( $extension['is_pro'] ) { ?>
                            <div class="ribbon"><?php echo esc_html__( 'Pro', 'animation-addons-for-elementor' ); ?></div>
						<?php } ?>
                    </div>
				<?php } ?>
            </div>
        </div>
	<?php } ?>

</div>
</form>


