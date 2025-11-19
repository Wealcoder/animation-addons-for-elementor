<?php
namespace WCF_ADDONS\Widgets\Loop_Builder\Controls;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template Query Control
 *
 * Custom control for selecting loop item templates
 */
class Template_Query extends \Elementor\Base_Data_Control {

	const CONTROL_ID = 'template_query';

	/**
	 * Get control type
	 */
	public function get_type() {
		return self::CONTROL_ID;
	}

	/**
	 * Render control content.
	 *
	 * @since 2.4.16
	 * @access public
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
				var placeholder = data.placeholder || '<?php esc_html_e( 'Select Template', 'animation-addons-for-elementor' ); ?>';
				#>
				<select id="<?php echo esc_attr( $control_uid ); ?>" class="elementor-select2 elementor-control-template-query" type="select2" {{ multiple }} data-setting="{{ data.name }}" data-placeholder="{{ placeholder }}">
					<# 
					var printOptions = function( options ) {
						_.each( options, function( option_title, option_value ) { 
							var value = data.controlValue;
							if ( typeof value === 'string' ) {
								var selected = ( option_value === value ) ? 'selected' : '';
							} else if ( null !== value ) {
								var value = _.multiple ? value : [ value ];
								var selected = ( -1 !== value.indexOf( option_value ) ) ? 'selected' : '';
							}
							#>
							<option {{ selected }} value="{{ option_value }}">{{{ option_title }}}</option>
						<# } );
					};

					if ( data.options ) {
						printOptions( data.options );
					}
					#>
				</select>
			</div>
			
			<# if ( data.actions && ( data.actions.new || data.actions.edit ) ) { #>
				<div class="elementor-control-template-query-actions">
					<# if ( data.actions.new && data.actions.new.visible ) { #>
						<button type="button" class="elementor-button elementor-button-default" data-action="new">
							<i class="eicon-plus" aria-hidden="true"></i>
							<?php esc_html_e( 'Create Template', 'animation-addons-for-elementor' ); ?>
						</button>
					<# } #>
					<# if ( data.actions.edit && data.actions.edit.visible ) { #>
						<button type="button" class="elementor-button elementor-button-default" data-action="edit" style="display: none;">
							<i class="eicon-pencil" aria-hidden="true"></i>
							<?php esc_html_e( 'Edit Template', 'animation-addons-for-elementor' ); ?>
						</button>
					<# } #>
				</div>
			<# } #>
			
			<# if ( !data.controlValue || data.controlValue === '' ) { #>
				<div class="elementor-control-template_query-empty-state">
					<i class="eicon-document-file" aria-hidden="true"></i>
					<h4><?php esc_html_e( 'No Template Selected', 'animation-addons-for-elementor' ); ?></h4>
					<p><?php esc_html_e( 'Create a new template or select an existing one to start building your loop items.', 'animation-addons-for-elementor' ); ?></p>
				</div>
			<# } #>
		</div>

		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}

	/**
	 * Get the default value of the control.
	 *
	 * @since 2.4.16
	 * @return string
	 */
	public function get_default_value() {
		return '';
	}

	/**
	 * Enqueue control scripts and styles.
	 *
	 * @since 2.4.16
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script(
			'aae-loop-builder-template-query',
			WCF_ADDONS_URL . 'assets/js/loop-builder/controls/template-query.js',
			array( 'elementor-editor', 'elementor-common' ),
			WCF_ADDONS_VERSION,
			true
		);

		wp_localize_script(
			'aae-loop-builder-template-query',
			'aaeLoopBuilderTemplateQuery',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'aae_loop_builder_nonce' ),
				'create_template_url' => admin_url( 'post-new.php?post_type=elementor_library&template_type=loop-item' ),
			)
		);
	}
}


