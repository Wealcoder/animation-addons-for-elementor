<?php
/**
 * Admin View: Settings Module
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$plugins = [
	'pro-plugin'  => [
		'title'    => esc_html__( 'Premium Plugins', 'animation-addons-for-elementor' ),
		'elements' => [

		]
	],
	'free-plugin' => [
		'title'    => esc_html__( 'Free Plugins', 'animation-addons-for-elementor' ),
		'elements' => [
			'extension-for-animation-addons' => [
				'label'    => esc_html__( 'Extension For Animation Addon', 'animation-addons-for-elementor' ),
				'basename' => 'extension-for-animation-addons/extension-for-animation-addons.php',
				'source'   => 'custom',
				'is_pro'   => false,
				'download_url' => 'https://animation-addons.com/',
			],
		]
	],
];
$plugins = apply_filters( 'wcf_integrated_plugins', $plugins );

?>

<div class="settings-wrap">
    <div class="section-header">
        <div class="info">
            <h3><?php echo esc_html__( 'Integrations Settings', 'animation-addons-for-elementor' ); ?></h3>
            <span>
                <?php
                $total = 1;
                /* translators: %s: total */
                printf( esc_html__( 'Total %s Integrations', 'animation-addons-for-elementor' ), esc_html( $total ) );
                ?>
            </span>
        </div>
    </div>

	<?php foreach ( $plugins as $group ) { ?>
		<?php
		if ( empty( $group['elements'] ) ) {
		    continue;
		}
		?>
    <div class="settings-group">
        <div class="title-area">
            <h4><?php echo esc_html( $group['title'] ); ?></h4>
        </div>
        <div class="settings-wrapper">
	        <?php foreach ($group['elements'] as $key => $plugin ) { ?>
                <div class="item">
                    <div class="title"><?php echo esc_html( $plugin['label'] ); ?></div>
                    <div class="actions">
	                    <?php
	                    $action = '';
	                    $data_base = '';
	                    if ( wcf_addons_get_local_plugin_data( $plugin['basename'] ) === false ) {
		                    $action = 'Download';
		                    $data_base = $plugin['download_url'];
	                    } else {
		                    if ( is_plugin_active( $plugin['basename'] ) ) {
			                    $action = 'Activated';
		                    } else {
			                    $action = 'Active';
			                    $data_base = $plugin['basename'];
		                    }
	                    }
	                    printf( '<a class="wcf-plugin-installer %1s" data-base="%2s" data-file="%3s" data-source="%4s" >%5s</a>',
		                    esc_attr( strtolower( $action ) ),
		                    esc_attr( $data_base ),
		                    esc_attr( $plugin['basename'] ),
		                    esc_attr( $plugin['source'] ),
		                    esc_html( $action ),
	                    )
	                    ?>
                    </div>
	                <?php if ( $plugin['is_pro'] ) { ?>
                        <div class="ribbon"><?php echo esc_html__( 'Pro', 'animation-addons-for-elementor' ); ?></div>
	                <?php } ?>
                </div>
	        <?php } ?>
        </div>
    </div>
	<?php } ?>

</div>
