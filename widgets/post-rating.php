<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;
use WP_Query;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Post_Rating extends Widget_Base
{

	public function get_name()
	{
		return 'aae--post-rating';
	}

	public function get_title()
	{
		return esc_html__('Post Rating', 'animation-addons-for-elementor');
	}

	public function get_icon()
	{
		return 'wcf eicon-rating';
	}

	public function get_categories()
	{
		return ['weal-coder-addon'];
	}

	public function get_keywords()
	{
		return ['rating', 'review', 'feedback'];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends()
	{
		return ['aae-post-rating'];
	}

	public function get_script_depends()
	{
		return ['aae-post-rating'];
	}

	protected function register_controls()
	{
		$this->register_rating_controls();

		$this->style_post_rating_layout();

		$this->style_reviewer_name();

		$this->style_review_date();

		$this->style_reviewer_rating();

		$this->style_reviewer_review();

		$this->style_average_rating();
	}

	protected function register_rating_controls()
	{
		$this->start_controls_section(
			'section_rating_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
			]
		);

		$this->add_control(
			'rating_layout',
			[
				'label'   => esc_html__('Layout', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'average-rating',
				'options' => [
					'list'           => esc_html__('List View', 'animation-addons-for-elementor'),
					'average-rating' => esc_html__('Average Rating', 'animation-addons-for-elementor'),
				],
			]
		);

		$this->add_control(
			'rating_icon',
			[
				'label'       => esc_html__('Rating Icon', 'animation-addons-for-elementor'),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'default'     => [
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'        => esc_html__('Alignment', 'animation-addons-for-elementor'),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'align-dir-',
				'options'      => [
					'start'  => [
						'title' => esc_html__('Left', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-center',
					],
					'end'    => [
						'title' => esc_html__('Right', 'animation-addons-for-elementor'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors'    => [
					'{{WRAPPER}} .average-rating'        => 'justify-self: {{VALUE}};',
					'{{WRAPPER}} .aae--post-rating.list' => 'justify-content: {{VALUE}}; text-align: {{VALUE}};',
				],
				'separator'    => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function style_post_rating_layout()
	{
		$this->start_controls_section(
			'style_layout',
			[
				'label' => esc_html__('Layout', 'animation-addons-for-elementor'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'avg_rating_border',
				'selector' => '{{WRAPPER}} .rating, {{WRAPPER}} .list .rating-item',
			]
		);

		$this->add_responsive_control(
			'avg_rating_radius',
			[
				'label'      => esc_html__('Border Radius', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating, {{WRAPPER}} .list .rating-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avg_rating_padding',
			[
				'label'      => esc_html__('Padding', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .rating, {{WRAPPER}} .list .rating-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avg_circle_size',
			[
				'label'      => esc_html__('Circle Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['rating_layout' => 'average-rating'],
			]
		);

		$this->add_responsive_control(
			'avg_rating_icon_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition'  => ['rating_layout' => 'average-rating'],
			]
		);

		$this->end_controls_section();
	}

	protected function style_average_rating()
	{
		$this->start_controls_section(
			'style_avg_rating',
			[
				'label'     => esc_html__('Average Rating', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_layout' => 'average-rating'],
			]
		);

		$this->add_control(
			'avg_rating_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'avg_rating_typo',
				'selector' => '{{WRAPPER}} .rating',
			]
		);

		$this->add_control(
			'avg_rating_icon_heading',
			[
				'label'     => esc_html__('Icon', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'avg_rating_icon_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .rating .icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'avg_rating_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .rating .icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_reviewer_name()
	{
		$this->start_controls_section(
			'style_name',
			[
				'label'     => esc_html__('Name', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_layout' => 'list'],
			]
		);

		$this->add_control(
			'name_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .name' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typo',
				'selector' => '{{WRAPPER}} .name',
			]
		);

		$this->add_control(
			'separator_heading',
			[
				'label'     => esc_html__('Separator', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'dash_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .dash' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'dash_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .name-wrap' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dash_width',
			[
				'label'      => esc_html__('Width', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .dash' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_review_date()
	{
		$this->start_controls_section(
			'style_date',
			[
				'label'     => esc_html__('Date', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_layout' => 'list'],
			]
		);

		$this->add_control(
			'date_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .date' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'date_typo',
				'selector' => '{{WRAPPER}} .date',
			]
		);

		$this->end_controls_section();
	}

	protected function style_reviewer_review()
	{
		$this->start_controls_section(
			'style_review',
			[
				'label'     => esc_html__('Review', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_layout' => 'list'],
			]
		);

		$this->add_control(
			'review_color',
			[
				'label'     => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .review' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'review_typo',
				'selector' => '{{WRAPPER}} .review',
			]
		);

		$this->add_responsive_control(
			'review_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .review' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function style_reviewer_rating()
	{
		$this->start_controls_section(
			'style_stars',
			[
				'label'     => esc_html__('Rating', 'animation-addons-for-elementor'),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => ['rating_layout' => 'list'],
			]
		);

		$this->add_control(
			'star_color',
			[
				'label' => esc_html__('Color', 'animation-addons-for-elementor'),
				'type'  => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'star_active_color',
			[
				'label'   => esc_html__('Active Color', 'animation-addons-for-elementor'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#ffc107',
			]
		);

		$this->add_responsive_control(
			'star_size',
			[
				'label'      => esc_html__('Icon Size', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .star' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stars_gap',
			[
				'label'      => esc_html__('Gap', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .stars' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stars_margin',
			[
				'label'      => esc_html__('Margin', 'animation-addons-for-elementor'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px'],
				'selectors'  => [
					'{{WRAPPER}} .stars' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}


	// Render
	protected function render()
	{
		$settings = $this->get_settings_for_display();
		$post_id  = get_the_ID();

		$this->add_render_attribute('wrapper', 'class', 'aae--post-rating ' . $settings['rating_layout']);

?>
		<div <?php $this->print_render_attribute_string('wrapper'); ?>>
			<?php
			if ('average-rating' === $settings['rating_layout']) {
				$this->render_average_post_rating($settings);
			} elseif ('list' === $settings['rating_layout']) {
				$this->render_list_view_rating($settings);
			}
			?>
		</div>
	<?php
	}


	protected function render_average_post_rating($settings)
	{
		$post_id = get_the_ID();

		$ratings = get_posts([
			'post_type'  => 'aaeaddon_post_rating',
			'meta_query' => [
				[
					'key'   => 'post_id',
					'value' => $post_id,
				]
			]
		]);

		$total_ratings = count($ratings);
		$total_stars   = 0;

		foreach ($ratings as $rating) {
			$total_stars += get_post_meta($rating->ID, 'rating', true);
		}

		$average_rating = $total_ratings > 0 ? round($total_stars / $total_ratings, 1) : '0';
	?>
		<div class="rating-item">
			<div class="rating">
				<div class="icon">
					<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
				</div>
				<p>
					<span><?php echo esc_html($average_rating); ?></span>/<?php echo esc_html__('5', 'animation-addons-for-elementor'); ?>
				</p>
			</div>
		</div>
		<?php
	}

	protected function render_list_view_rating($settings)
	{
		$post_id = get_the_ID();

		$reviews = new WP_Query([
			'post_type'      => 'aaeaddon_post_rating',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'   => 'post_id',
					'value' => $post_id,
				]
			]
		]);

		if ($reviews->have_posts()) {
			while ($reviews->have_posts()) {
				$reviews->the_post();

				$rating  = get_post_meta(get_the_ID(), 'rating', true);
				$review  = get_post_meta(get_the_ID(), 'review', true);
				$date    = get_the_date();
				$user_id = get_post_meta(get_the_ID(), 'user_id', true);

				if ($user_id) {
					$name = get_the_author_meta('display_name', $user_id);
				} else {
					$name = get_post_meta(get_the_ID(), 'name', true);
				}
		?>
				<div class="rating-item">
					<div class="content">
						<div class="name-wrap">
							<div class="name"><?php echo esc_html($name); ?></div>
							<span class="dash"></span>
							<div class="date"><?php echo esc_html($date); ?></div>
						</div>
						<div class="stars">
							<?php
							for ($i = 1; $i <= 5; $i++) {
								$color = ($i <= $rating) ? $settings['star_active_color'] : $settings['star_color'];
							?>
								<span class="star" style="color:<?php echo esc_attr($color); ?>; fill:<?php echo esc_attr($color); ?>;">
									<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
								</span>
							<?php
							}
							?>
						</div>
					</div>
					<p class="review"><?php echo esc_html($review); ?></p>
				</div>
			<?php
			}
			wp_reset_postdata();
		} else {
			if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
			?>
				<div class="rating-item">
					<div class="content">
						<div class="name-wrap">
							<div class="name">Mr. Johnson</div>
							<span class="dash"></span>
							<div class="date">June 17, 2025</div>
						</div>
						<div class="stars">
							<?php
							for ($i = 1; $i <= 5; $i++) {
								$color = ($i <= 4) ? $settings['star_active_color'] : $settings['star_color'];
							?>
								<span class="star" style="color:<?php echo esc_attr($color); ?>; fill:<?php echo esc_attr($color); ?>;">
									<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
								</span>
							<?php
							}
							?>
						</div>
					</div>
					<p class="review">Absolutely loved the content — informative and well-presented!</p>
				</div>
				<div class="rating-item">
					<div class="content">
						<div class="name-wrap">
							<div class="name">Baby Charles</div>
							<span class="dash"></span>
							<div class="date">July 17, 2025</div>
						</div>
						<div class="stars">
							<?php
							for ($i = 1; $i <= 5; $i++) {
								$color = ($i <= 4) ? $settings['star_active_color'] : $settings['star_color'];
							?>
								<span class="star" style="color:<?php echo esc_attr($color); ?>; fill:<?php echo esc_attr($color); ?>;">
									<?php Icons_Manager::render_icon($settings['rating_icon'], ['aria-hidden' => 'true']); ?>
								</span>
							<?php
							}
							?>
						</div>
					</div>
					<p class="review">Great post overall, but could use a bit more depth in certain areas.</p>
				</div>
<?php
			}
		}
	}
}
