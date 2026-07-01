<?php
namespace WCF_ADDONS\Widgets\Skin;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Skin_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Skin_Pricing_Table_Base extends Skin_Base {

	/**
	 * Register skin controls actions.
	 *
	 * Run on init and used to register new skins to be injected to the widget.
	 * This method is used to register new actions that specify the location of
	 * the skin in the widget.
	 *
	 * Example usage:
	 * `add_action( 'elementor/element/{widget_id}/{section_id}/before_section_end', [ $this, 'register_controls' ] );`
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls_actions() {
		add_action( 'elementor/element/wcf--a-pricing-table/section_layout/after_section_end', [ $this, 'register_controls' ] );
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	public function register_controls( Widget_Base $widget ) {
		$this->parent = $widget;
	}

	// Title
	public function render_title() {
		if ( empty( $this->parent->get_settings( 'title' ) ) ) {
			return;
		}

		$title_tag = Utils::validate_html_tag( $this->parent->get_settings( 'title_tag' ) );
		?>
        <div class="title-wrap">
        <<?php Utils::print_validated_html_tag( $title_tag ); ?> class="title">
		<?php $this->parent->print_unescaped_setting( 'title' ); ?>
        </<?php Utils::print_validated_html_tag( $title_tag ); ?>>
        </div>
		<?php
	}

	// Sub Title
	public function render_sub_title() {
		if ( empty( $this->parent->get_settings( 'sub_title' ) ) ) {
			return;
		}
		?>
        <div class="sub-title">
			<?php $this->parent->print_unescaped_setting( 'sub_title' ); ?>
        </div>
		<?php
	}

	// Currency Symbol
	public function render_currency_symbol() {
		$settings = $this->parent->get_settings_for_display();
		$symbol   = '';
		if ( ! empty( $settings['currency_symbol'] ) ) {
			if ( 'custom' !== $settings['currency_symbol'] ) {
				$symbol = $this->get_currency_symbol( $settings['currency_symbol'] );
			} else {
				$symbol = $settings['currency_symbol_custom'];
			}
		}

		if ( empty( $symbol ) ) {
			return;
		}
		echo esc_html( $symbol );
	}

	private function get_currency_symbol( $symbol_name ) {
		$symbols = [
			'dollar'       => '&#36;',
			'euro'         => '&#128;',
			'franc'        => '&#8355;',
			'pound'        => '&#163;',
			'ruble'        => '&#8381;',
			'shekel'       => '&#8362;',
			'baht'         => '&#3647;',
			'yen'          => '&#165;',
			'won'          => '&#8361;',
			'guilder'      => '&fnof;',
			'peso'         => '&#8369;',
			'peseta'       => '&#8359',
			'lira'         => '&#8356;',
			'rupee'        => '&#8360;',
			'indian_rupee' => '&#8377;',
			'real'         => 'R$',
			'krona'        => 'kr',
		];

		return isset( $symbols[ $symbol_name ] ) ? $symbols[ $symbol_name ] : '';
	}

	// Original Price
	public function render_original_price() {
		if ( ! $this->parent->get_settings( 'sale' ) ) {
			return;
		}
		?>
        <span class="pt-org-price">
		<?php
		$this->render_currency_symbol();
		$this->parent->print_unescaped_setting( 'original_price' );
		?>
		</span>
		<?php
	}

    // Price
	public function render_price( ) {
		?>
        <span class="pt-sale-price"><?php $this->parent->print_unescaped_setting( 'price' );	?></span>
		<?php
    }

	// Feature List
	public function render_feature_list() {
		if ( empty( $this->parent->get_settings( 'features_list' ) ) ) {
			return;
		}
		$settings = $this->parent->get_settings_for_display();
		?>
        <div class="pt-feature">
            <div class="feature-title">
                <?php if( !empty( $settings['feature_title'] ) ) {
                    echo esc_html( $settings['feature_title'] );
                }?>
            </div>
            <ul>
				<?php
				foreach ( $settings['features_list'] as $index => $item ) :
					?>
                    <li class="elementor-repeater-item-<?php echo esc_attr( $item['_id'] ); ?>">
						<?php Icons_Manager::render_icon( $item['selected_item_icon'], [ 'aria-hidden' => 'true' ] ); ?>
						<?php if ( ! empty( $item['item_text'] ) ) : ?>
							<?php $this->parent->print_unescaped_setting( 'item_text', 'features_list', $index ); ?>
						<?php
						else :
							echo '&nbsp;';
						endif;
						?>
                    </li>
				<?php endforeach; ?>
            </ul>
        </div>
		<?php
	}

	// Period
	public function render_period() {
		?>
        <span class="pt-period">
            <?php $this->parent->print_unescaped_setting( 'period' ); ?>
        </span>
		<?php
	}

	// Button
	protected function render_button( $settings = [], $setting = null, $repeater_name = null, $index = null ) {
		if ( empty( $settings ) ) {
			$settings = $this->parent->get_settings_for_display();
		}

		$link_key = 'link_';

		if ( $repeater_name ) {
			$repeater = $this->parent->get_settings_for_display( $repeater_name );
			$link     = $repeater[ $index ][ $setting ];

			$link_key = 'link_' . $index;
			if ( ! empty( $link['url'] ) ) {
				$this->parent->add_link_attributes( $link_key, $link );
			}
		} else {
			if ( ! empty( $settings['btn_link']['url'] ) ) {
				$this->parent->add_link_attributes( $link_key, $settings['btn_link'] );
			} else {
				$this->parent->add_render_attribute( $link_key, 'role', 'button' );
			}
		}

		$this->parent->add_render_attribute( 'button_wrapper', 'class', 'wcf__btn' );
		$this->parent->add_render_attribute( $link_key, 'class', 'wcf-btn-' . $settings['btn_element_list'] );

		if ( 'right' === $settings['button_icon_align'] ) {
			$this->parent->add_render_attribute( 'button_wrapper', 'class', 'icon-position-after' );
		}

		if ( ! empty( $settings['btn_hover_list'] ) ) {
			$this->parent->add_render_attribute( $link_key, 'class', 'btn-' . $settings['btn_hover_list'] );
		}

		if ( 'mask' === $settings['btn_element_list'] ) {
			$this->parent->add_render_attribute( $link_key, 'data-text', $settings['btn_text'] );
		}

		$ext_wrap = in_array( $settings['btn_element_list'], [ 'oval', 'circle', 'ellipse' ] );

		if ( $ext_wrap ) {
			$this->parent->add_render_attribute( $link_key, 'class', 'btn-item' );

			if ( 'ellipse' !== $settings['btn_element_list'] ) {
				$this->parent->add_render_attribute( $link_key, 'class', 'btn-hover-bgchange' );
			}
		}

		$migrated = isset( $settings['__fa4_migrated']['button_icon'] );
		$is_new   = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();
		?>
        <div <?php $this->parent->print_render_attribute_string( 'button_wrapper' ); ?>>
			<?php if ( $ext_wrap ) : ?>
            <div class="btn-wrapper">
				<?php endif; ?>
                <a <?php $this->parent->print_render_attribute_string( $link_key ); ?>>
					<?php if ( $is_new || $migrated ) :
						Icons_Manager::render_icon( $this->parent->get_settings('button_icon'), [ 'aria-hidden' => 'true' ] );
					else : ?>
                        <i class="<?php echo esc_attr( $settings['icon'] ); ?>" aria-hidden="true"></i>
					<?php endif; ?>
					<?php $this->parent->print_unescaped_setting( 'btn_text' ); ?>
                </a>
				<?php if ( $ext_wrap ) : ?>
            </div>
		<?php endif; ?>
        </div>
		<?php
	}

	// Ribbon
	public function render_ribbon() {
		if ( ! $this->parent->get_settings( 'show_ribbon' ) ) {
			return;
		}

		if ( empty( $this->parent->get_settings( 'ribbon_title' ) ) ) {
			return;
		}
		?>
        <div class="ribbon">
            <span><?php $this->parent->print_unescaped_setting( 'ribbon_title' ); ?></span>
        </div>
		<?php
    }

}
