<?php
namespace WCF_ADDONS\AtomicWidgets\Widgets\SearchForm;

use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Element_Base;
use Elementor\Modules\AtomicWidgets\Elements\Base\Has_Element_Template;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Switch_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\Components\PropTypes\Overridable_Prop_Type;

require_once __DIR__ . '/class-aae-a-search-form-btn.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AAE_A_Search_Form extends Atomic_Element_Base {
	use Has_Element_Template;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->meta( 'is_container', true );
	}

	public static function get_type(): string {
		return 'e-aae-a-search-form';
	}

	public static function get_element_type(): string {
		return 'e-aae-a-search-form';
	}

	public function get_title(): string {
		return esc_html__( 'AAE Search Form', 'animation-addons-for-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-search';
	}

	public function get_keywords(): array {
		return [ 'search', 'form', 'search form', 'atomic', 'ajax', 'filter' ];
	}

	protected static function define_props_schema(): array {
		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'attributes' => Attributes_Prop_Type::make()->meta( Overridable_Prop_Type::ignore() ),

			// Layout presets
			'preset' => String_Prop_Type::make()
				->enum( [ 'classic', 'dropdown', 'full-screen' ] )
				->default( 'classic' ),

			// Input settings
			'placeholder'  => String_Prop_Type::make()->default( 'Search...' ),
			'autocomplete' => String_Prop_Type::make()
				->enum( [ 'off', 'on' ] )
				->default( 'off' ),

			// Dropdown-specific: which side the panel appears
			'search_position' => String_Prop_Type::make()
				->enum( [ 'left', 'right' ] )
				->default( 'left' ),

			// Ajax live search
			'enable_ajax_search' => Boolean_Prop_Type::make()->default( false ),

			// Filter bar
			'show_search_filter' => Boolean_Prop_Type::make()->default( false ),
			'show_date_filter'   => Boolean_Prop_Type::make()->default( true ),
			'show_cat_filter'    => Boolean_Prop_Type::make()->default( true ),
		];
	}

	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_id( 'content' )
				->set_label( __( 'Search Form', 'animation-addons-for-elementor' ) )
				->set_items( [
					Select_Control::bind_to( 'preset' )
						->set_label( __( 'Preset', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'classic',     'label' => __( 'Classic',     'animation-addons-for-elementor' ) ],
							[ 'value' => 'dropdown',    'label' => __( 'Dropdown',    'animation-addons-for-elementor' ) ],
							[ 'value' => 'full-screen', 'label' => __( 'Full Screen', 'animation-addons-for-elementor' ) ],
						] ),

					Text_Control::bind_to( 'placeholder' )
						->set_label( __( 'Placeholder', 'animation-addons-for-elementor' ) ),

					Select_Control::bind_to( 'autocomplete' )
						->set_label( __( 'Auto Suggest', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'off', 'label' => __( 'No',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'on',  'label' => __( 'Yes', 'animation-addons-for-elementor' ) ],
						] ),

					Select_Control::bind_to( 'search_position' )
						->set_label( __( 'Panel Position (Dropdown)', 'animation-addons-for-elementor' ) )
						->set_options( [
							[ 'value' => 'left',  'label' => __( 'Left',  'animation-addons-for-elementor' ) ],
							[ 'value' => 'right', 'label' => __( 'Right', 'animation-addons-for-elementor' ) ],
						] ),

					Switch_Control::bind_to( 'enable_ajax_search' )
						->set_label( __( 'Enable Ajax Search', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_search_filter' )
						->set_label( __( 'Enable Search Filter', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_date_filter' )
						->set_label( __( 'Show Date Filter', 'animation-addons-for-elementor' ) ),

					Switch_Control::bind_to( 'show_cat_filter' )
						->set_label( __( 'Show Category Filter', 'animation-addons-for-elementor' ) ),
				] ),

			Section::make()
				->set_id( 'settings' )
				->set_label( __( 'Settings', 'animation-addons-for-elementor' ) )
				->set_items( [
					Text_Control::bind_to( '_cssid' )
						->set_label( __( 'ID', 'animation-addons-for-elementor' ) )
						->set_meta( $this->get_css_id_control_meta() ),
				] ),
		];
	}

	/**
	 * Base styles applied to the root wrapper element.
	 * Inner form/input/button defaults live in the shared aae--search stylesheet.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make()->add_props( [
					'display'  => String_Prop_Type::generate( 'block' ),
					'position' => String_Prop_Type::generate( 'relative' ),
				] ) ),
		];
	}

	/**
	 * The submit button content is driven by the child widgets dropped here.
	 * Default: a single Atomic_Svg which the user configures as their search icon.
	 * To use a text label instead, swap or add an Atomic_Paragraph child.
	 */
	protected function define_default_children(): array {
		return [
			AAE_A_Search_Form_Btn::generate()
				->editor_settings( [ 'title' => 'Search Button' ] )
				->build(),
		];
	}

	protected function define_allowed_child_types(): array {
		return [ 'e-aae-a-search-form-btn' ];
	}

	public function get_script_depends(): array {
		return [ 'aae-a-search-form-js' ];
	}

	public function get_style_depends(): array {
		return [ 'aae-a-search-form-css' ];
	}

	protected function get_templates(): array {
		return [
			'elementor/elements/aae-a-search-form' => __DIR__ . '/aae-a-search-form.html.twig',
		];
	}

	/**
	 * Inject server-side dynamic values so Twig has everything it needs:
	 * - home_url: form action URL
	 * - current_search_query: pre-fill the input on search result pages
	 * - widget_id: unique id for <label for="…"> / <input id="…"> pairing
	 * - filter_html: fully-rendered PHP filter markup (categories, date pickers)
	 */
	public function get_atomic_settings(): array {
		$settings = parent::get_atomic_settings();

		$settings['home_url']             = esc_url( home_url( '/' ) );
		$settings['current_search_query'] = get_search_query();
		$settings['widget_id']            = $this->get_id();

		if ( ! empty( $settings['show_search_filter'] ) ) {
			$settings['filter_html'] = $this->build_filter_html( $settings );
		}

		return $settings;
	}

	private function build_filter_html( array $settings ): string {
		$categories = get_categories( [
			'taxonomy'   => 'category',
			'hide_empty' => true,
		] );

		// Chevron-down icon for filter toggle buttons
		$chevron = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" aria-hidden="true"><path d="M137.4 374.6c12.5 12.5 32.8 12.5 45.3 0l128-128c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8L32 192c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l128 128z"/></svg>';

		ob_start();
		?>
		<div class="aae--search-filter">
			<?php if ( ! empty( $settings['show_date_filter'] ) ) : ?>
			<div class="date-container">
				<div class="date-toggle">
					<?php esc_html_e( 'Date', 'animation-addons-for-elementor' ); ?>
					<span class="icon"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
				<div class="date-dropdown">
					<ul class="preset-options">
						<li data-preset="today"><?php esc_html_e( 'Today', 'animation-addons-for-elementor' ); ?></li>
						<li data-preset="yesterday"><?php esc_html_e( 'Yesterday', 'animation-addons-for-elementor' ); ?></li>
						<li data-preset="week"><?php esc_html_e( 'This week', 'animation-addons-for-elementor' ); ?></li>
						<li data-preset="month"><?php esc_html_e( 'This month', 'animation-addons-for-elementor' ); ?></li>
					</ul>
					<div class="custom-range">
						<div class="wrap">
							<label><?php esc_html_e( 'From', 'animation-addons-for-elementor' ); ?></label>
							<input type="date" name="from_date" class="from-date" />
						</div>
						<div class="wrap">
							<label><?php esc_html_e( 'To', 'animation-addons-for-elementor' ); ?></label>
							<input type="date" name="to_date" class="to-date" />
						</div>
					</div>
					<div class="date-buttons">
						<button type="button" class="clear-btn"><?php esc_html_e( 'Clear', 'animation-addons-for-elementor' ); ?></button>
						<button type="button" class="apply-btn"><?php esc_html_e( 'Apply', 'animation-addons-for-elementor' ); ?></button>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $settings['show_cat_filter'] ) ) : ?>
			<div class="category-container">
				<div class="category-toggle">
					<?php esc_html_e( 'Category', 'animation-addons-for-elementor' ); ?>
					<span class="icon"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
				<div class="category-dropdown">
					<ul class="category-list">
						<li data-value=""><?php esc_html_e( 'All Categories', 'animation-addons-for-elementor' ); ?></li>
						<?php foreach ( $categories as $cat ) : ?>
						<li data-value="<?php echo esc_attr( $cat->term_id ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
					<div class="category-footer">
						<button class="clear-cat-btn"><?php esc_html_e( 'Clear', 'animation-addons-for-elementor' ); ?></button>
						<button class="apply-cat-btn"><?php esc_html_e( 'Apply', 'animation-addons-for-elementor' ); ?></button>
					</div>
				</div>
				<input type="hidden" name="category[]" id="selectedCategory" value="" />
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

}
