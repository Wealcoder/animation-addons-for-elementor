<?php
/**
 * Custom SELECT2 Control for Dynamic Tags
 *
 * @package Wealcoder\AnimationAddons
 * @since 1.0.0
 */

namespace Wealcoder\AnimationAddons\DynamicTags\Controls;

use Wealcoder\AnimationAddons\Nonce;
use Elementor\Base_Data_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SELECT2 Control Class.
 *
 * @since 1.0.0
 * @package Wealcoder\AnimationAddons\DynamicTags\Controls
 */
class Select2_Control extends Base_Data_Control {

	const CONTROL_ID = 'aae_select2';

	/**
	 * Get control type.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::CONTROL_ID;
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return array(
			'label_block' => true,
			'multiple'    => false,
			'options'     => array(),
			'ajax'        => array(
				'action' => '',
				'params' => array(),
			),
		);
	}

	/**
	 * Render control content.
	 *
	 * @return void
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) { #>
				<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper elementor-control-unit-5">
				<# 
				var multiple = ( data.multiple ) ? 'multiple' : '';
				var placeholder = data.placeholder || '<?php echo esc_js( __( 'Select...', 'animation-addons-for-elementor' ) ); ?>';
				#>
				<select id="<?php echo esc_attr( $control_uid ); ?>" 
					class="elementor-select2 aae-dt-select2" 
					type="select2" 
					{{ multiple }} 
					data-setting="{{ data.name }}" 
					data-placeholder="{{ placeholder }}"
					<# if ( data.ajax && data.ajax.action ) { #>
					data-ajax-action="{{ data.ajax.action }}"
					<# if ( data.ajax.params ) { #>
					data-ajax-params='{{ JSON.stringify(data.ajax.params) }}'
					<# } #>
					<# } #>
					<# 
					var printOptions = function( options ) {
						_.each( options, function( option_title, option_value ) { 
							var value = data.controlValue;
							if ( typeof value === 'string' ) {
								var selected = ( option_value == value ) ? 'selected' : '';
							} else if ( null !== value ) {
								var value = data.multiple ? value : [ value ];
								var selected = ( -1 !== value.indexOf( option_value ) ) ? 'selected' : '';
							} else {
								var selected = '';
							}
							#>
							<option {{ selected }} value="{{ option_value }}">{{{ option_title }}}</option>
						<# } );
					};

					if ( data.options && ! _.isEmpty( data.options ) ) {
						printOptions( data.options );
					}
					#>
				</select>
			</div>
			<# if ( data.description ) { #>
				<div class="elementor-control-field-description">{{{ data.description }}}</div>
			<# } #>
		</div>
		<?php
	}

	/**
	 * Get default value.
	 *
	 * @return string
	 */
	public function get_default_value() {
		return '';
	}

	/**
	 * Enqueue control scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script(
			'aae-dt-select2-control',
			AAEADDON_URL . 'assets/js/dynamic-tags/select2-control.js',
			array( 'jquery', 'elementor-editor', 'elementor-common' ),
			AAEADDON_VERSION . '.2',
			true
		);

		wp_localize_script(
			'aae-dt-select2-control',
			'aaeDynamicTagsSelect2',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => Nonce::create( Nonce::DYNAMIC_TAGS ),
			)
		);
	}
}

