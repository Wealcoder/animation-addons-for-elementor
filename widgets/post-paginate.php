<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;
use function Avifinfo\skip;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Post_Paginate extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--post--paginate';
	}

	public function get_title() {
		return esc_html__( 'Post Pagination', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-navigation-horizontal';
	}

	public function get_categories() {
		return [ 'wcf-single-addon' ];
	}

	public function get_keywords() {
		return [ 'navigate', 'post paginate' ];
	}

	public function get_style_depends() {
		return [ 'wcf--meta-info' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Settings', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'preset_style',
			[
				'label'   => esc_html__( 'Preset Style', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''         => esc_html__( 'Default', 'animation-addons-for-elementor' ),
					'layout-2' => esc_html__( 'Layout 2', 'animation-addons-for-elementor' ),

				],
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => esc_html__( 'Show Post Title', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'Hide', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'preset_style' => '' ]
			]
		);

		// Previous
		$this->add_control(
			'enable_prev',
			[
				'label'        => esc_html__( 'Enable Prev', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'prev_heading',
			[
				'label'     => esc_html__( 'Previous', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'condition' => [ 'enable_prev' => [ 'yes' ] ]
			]
		);

		$this->add_control(
			'prev_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Previous Post', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type prev title', 'animation-addons-for-elementor' ),
				'condition'   => [ 'enable_prev' => [ 'yes' ] ]
			]
		);

		$this->add_control(
			'prev_icon',
			[
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [ 'enable_prev' => [ 'yes' ] ]
			]
		);

		// Next
		$this->add_control(
			'enable_next',
			[
				'label'        => esc_html__( 'Enable Next', 'animation-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'next_heading',
			[
				'label'     => esc_html__( 'Next', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'condition' => [ 'enable_next' => [ 'yes' ] ]
			]
		);

		$this->add_control(
			'next_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Next Post', 'animation-addons-for-elementor' ),
				'placeholder' => esc_html__( 'Type next title', 'animation-addons-for-elementor' ),
				'condition'   => [ 'enable_next' => [ 'yes' ] ]
			]
		);

		$this->add_control(
			'next_icon',
			[
				'label'       => esc_html__( 'Icon', 'animation-addons-for-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [ 'enable_next' => [ 'yes' ] ]
			]
		);

		$this->end_controls_section();

		// Style Tab
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--post-paginate' => 'gap: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->add_responsive_control(
			'spacing',
			[
				'label'      => esc_html__( 'Margin', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .wcf--post-paginate' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Prev / Next
		$this->add_control(
			'prev_style_heading',
			[
				'label'     => esc_html__( 'Previous / Next', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'next_prev_typography',
				'selector' => '{{WRAPPER}} .next-prev',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'next_prev_border',
				'selector' => '{{WRAPPER}} .wcf--post-paginate a',
			]
		);

		$this->add_responsive_control(
			'next_prev_border_radious',
			[
				'label'      => esc_html__( 'Border radius', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--post-paginate a' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'next_prev_width',
			[
				'label'      => esc_html__( 'Width', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--post-paginate a' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'next_prev_hth',
			[
				'label'      => esc_html__( 'Height', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .wcf--post-paginate a' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'next_prev_gap',
			[
				'label'      => esc_html__( 'Gap', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .next-prev' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => esc_html__( 'Icon Size', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .next-prev i, {{WRAPPER}} .next-prev svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'prev_style_tabs'
		);

		$this->start_controls_tab(
			'prev_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'prev_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--post-paginate a *'     => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'prevbackground',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf--post-paginate a',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'prev_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'prev_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--post-paginate a:hover *' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'prevbhoveackground',
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .wcf--post-paginate a:hover',
			]
		);

		$this->add_control(
			'nextprev_hover_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--post-paginate a:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// Title
		$this->start_controls_section(
			'section_title_style',
			[
				'label'     => esc_html__( 'Post Title', 'animation-addons-for-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'preset_style' => '',
					'show_title'   => 'yes',
				],
			]
		);

		$this->add_control(
			'title_style_heading',
			[
				'label'     => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .title',
			]
		);

		$this->add_control(
			'title_spacing',
			[
				'label'      => esc_html__( 'Spacing', 'animation-addons-for-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 200,
						'step' => 1,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .title' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$this->start_controls_tabs(
			'title_style_tabs'
		);

		$this->start_controls_tab(
			'title_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--post-paginate a .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'title_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'animation-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => esc_html__( 'Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--post-paginate a:hover .title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function switch_post() {
		if ( 'wcf-addons-template' === get_post_type() ) {

			$recent_posts = wp_get_recent_posts( array(
				'numberposts' => 1,
				'post_status' => 'publish'
			) );

			$post_id = get_the_id();

			if ( isset( $recent_posts[0] ) ) {
				$post_id = $recent_posts[0]['ID'];
			}

			Plugin::$instance->db->switch_to_post( $post_id );
		}
	}

	protected function render() {

		$settings  = $this->get_settings_for_display();

		$this->switch_post();

		$next_post = get_next_post();
		$pre_post  = get_previous_post();
		if ( ! $next_post && ! $pre_post ) {
			return;
		}
		?>
        <nav class="wcf--post-paginate <?php echo esc_attr( $settings['preset_style'] ); ?>">
			<?php if ( $settings['enable_prev'] == 'yes' ) { ?>
                <div class="post-previous">
					<?php if ( ! empty( $pre_post ) ): ?>
                        <a href="<?php echo esc_url( get_the_permalink( $pre_post->ID ) ); ?>">
							<?php if ( 'yes' === $settings['show_title'] ) { ?>
                                <h3 class="title"><?php echo esc_html( get_the_title( $pre_post->ID ) ) ?></h3>
							<?php } ?>
                            <span class="next-prev">
                            <?php
                            Icons_Manager::render_icon( $settings['prev_icon'], [ 'aria-hidden' => 'true' ] );
                            if ( ! empty( $settings['prev_title'] ) ) {
	                            echo wp_kses_post( $settings['prev_title'] );
                            }
                            ?>
                        </span>
                        </a>
					<?php endif; ?>
                </div>
			<?php } ?>
			<?php if ( $settings['enable_next'] == 'yes' ) { ?>
                <div class="post-next">
					<?php if ( ! empty( $next_post ) ): ?>
                        <a href="<?php echo esc_url( get_the_permalink( $next_post->ID ) ); ?>">
							<?php if ( 'yes' === $settings['show_title'] ) { ?>
                                <h3 class="title"><?php echo esc_html( get_the_title( $next_post->ID ) ); ?></h3>
							<?php } ?>
                            <span class="next-prev">
                            <?php
                            if ( ! empty( $settings['next_title'] ) ) {
	                            echo wp_kses_post( $settings['next_title'] );
                            }
                            Icons_Manager::render_icon( $settings['next_icon'], [ 'aria-hidden' => 'true' ] );
                            ?>
                        </span>
                        </a>
					<?php endif; ?>
                </div>
			<?php } ?>
        </nav>
		<?php

		Plugin::$instance->db->restore_current_post();

		?>

		<?php
	}
}
