<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;   // Exit if accessed directly.
}

class Archive_Title extends Widget_Base {

	public function get_name() {
		return 'wcf--blog--archive--title';
	}

	public function get_title() {
		return esc_html__( 'Archive Title', 'animation-addons-for-elementor' );
	}

	public function get_icon() {
		return 'wcf eicon-post-title';
	}

	public function get_categories() {
		return [ 'wcf-archive-addon' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'animation-addons-for-elementor' ),
			]
		);


		$this->add_control(
			'header_size',
			[
				'label'   => esc_html__( 'HTML Tag', 'animation-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'default' => 'h2',
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'list_title',
			[
				'label'       => esc_html__( 'Title', 'animation-addons-for-elementor' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'List Title', 'animation-addons-for-elementor' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'list_type',
			[
				'label'   => esc_html__( 'Page Type', 'animation-addons-for-elementor' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'blog'                   => esc_html__( 'Blog Archive', 'animation-addons-for-elementor' ),
					'category'               => esc_html__( 'Category', 'animation-addons-for-elementor' ),
					'tag'                    => esc_html__( 'Tag', 'animation-addons-for-elementor' ),
					'archive_day'            => esc_html__( 'Date Day', 'animation-addons-for-elementor' ),
					'archive_day_month'      => esc_html__( 'Date Day Month', 'animation-addons-for-elementor' ),
					'archive_day_month_year' => esc_html__( 'Date Day Month Year', 'animation-addons-for-elementor' ),
					'search'                 => esc_html__( 'Search', 'animation-addons-for-elementor' ),
					'search_not_found'       => esc_html__( 'Search Not Found', 'animation-addons-for-elementor' ),
					'author'                 => esc_html__( 'Author', 'animation-addons-for-elementor' ),
					'404'                    => esc_html__( '404', 'animation-addons-for-elementor' ),
					'custom_archive'         => esc_html__( 'Custom Archive', 'animation-addons-for-elementor' ),
					'custom_taxonomy'        => esc_html__( 'Custom Taxonomy', 'animation-addons-for-elementor' ),
				],
			]
		);

		$repeater->add_control(
			'list_content',
			[
				'label'       => esc_html__( 'Content', 'animation-addons-for-elementor' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Blogs for {category_name} ', 'animation-addons-for-elementor' ),
				'placeholder' => 'Blogs for {category_name}',
				'description' => 'use {category_name},{taxonomy_name},{archive_name},{author_name},{tag_name},{search_query},{day},{month},{year}',
				'show_label'  => false,
			]
		);

		$this->add_control(
			'custom_condition',
			[
				'label'        => esc_html__( 'Custom?', 'animation-addons-for-elementor' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'animation-addons-for-elementor' ),
				'label_off'    => esc_html__( 'No', 'animation-addons-for-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'list',
			[
				'label'       => esc_html__( 'Custom Page Conditions', 'animation-addons-for-elementor' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ list_title }}}',
				'condition'   => [ 'custom_condition' => 'yes' ]
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label'     => esc_html__( 'Alignment', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => esc_html__( 'Left', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => esc_html__( 'Center', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => esc_html__( 'Right', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'animation-addons-for-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'default'   => '',
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Text Color', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wcf--title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name'     => 'text_stroke',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .wcf--title',
			]
		);

		$this->add_control(
			'blend_mode',
			[
				'label'     => esc_html__( 'Blend Mode', 'animation-addons-for-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					''            => esc_html__( 'Normal', 'animation-addons-for-elementor' ),
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
				],
				'selectors' => [
					'{{WRAPPER}} .wcf--title' => 'mix-blend-mode: {{VALUE}}',
				],
				'separator' => 'none',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		$title = $this->get_the_title();

		if ( ! $title ) {
			return;
		}

		$this->add_render_attribute( 'title', 'class', 'wcf--title' );


		$title_html = sprintf( '<%1$s %2$s>%3$s</%1$s>', Utils::validate_html_tag( $settings['header_size'] ), $this->get_render_attribute_string( 'title' ), $title );

		// PHPCS - the variable $title_html holds safe data.
		echo $title_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function get_the_title() {
		$settings = $this->get_settings_for_display();
		$title    = get_the_archive_title();
		if ( is_tag() ) {
			$title = single_tag_title( "", false );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'tag', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'tag' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{tag_name}' ), array( $title ), $result['list_content'] );
					}
				}
			}
		} elseif ( is_day() ) {
			$title = esc_html__( 'Blogs for', 'animation-addons-for-elementor' ) . get_the_time( 'F jS, Y' );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'archive_day', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'archive_day' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{day}' ), array( get_the_time( 'F jS, Y' ) ), $result['list_content'] );
					}
				}
			}
		} elseif ( is_month() ) {
			$title = esc_html__( 'Blogs for', 'animation-addons-for-elementor' ) . get_the_time( 'F, Y' );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'archive_day_month', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'archive_day_month' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{month}' ), array( get_the_time( 'F, Y' ) ), $result['list_content'] );
					}
				}
			}
		} elseif ( is_year() ) {
			$title = esc_html__( 'Blogs for', 'animation-addons-for-elementor' ) . get_the_time( 'Y' );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'archive_day_month_year', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'archive_day_month_year' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{year}' ), array( get_the_time( 'Y' ) ), $result['list_content'] );
					}
				}
			}
		} elseif ( is_category() ) {
			$category = get_queried_object();
			$title    = $category->name;
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'category', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'category' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{category_name}' ), array( $title ), $result['list_content'] );
					}
				}
			}
		} elseif ( is_404() ) {
			$title = esc_html__( '404 Error', 'animation-addons-for-elementor' );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( '404', $list_types ) && $result = $this->get_custom_page_conditional_settings( '404' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = $result['list_content'];
					}
				}
			}
		}

		if ( is_author() ) {
			$title = get_the_author_meta( 'display_name' );
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'author', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'author' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = str_replace( array( '{author_name}' ), array( get_the_author_meta( 'display_name' ) ), $result['list_content'] );
					}
				}
			}
		}
		if ( ! is_front_page() && is_home() ) {
			$title = 'Blogs';
			if ( $settings['custom_condition'] == 'yes' ) {
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'blog', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'blog' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = $result['list_content'];
					}
				}
			}
		}
		if ( is_search() ) {
			$title = 'Search Page';
			if ( ! have_posts() ) {
				$title = 'Nothing found!';
				if ( $settings['custom_condition'] == 'yes' ) {
					$list       = $settings['list'];
					$list_types = wp_list_pluck( $list, 'list_type' );
					if ( in_array( 'search_not_found', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'search_not_found' ) ) {
						if ( isset( $result['list_content'] ) ) {
							$title = $result['list_content'];
							$title = str_replace( array( '{search_query}' ), array( get_search_query() ), $title );
						}
					}
				}
			} else {
				/* Custom Condition enable */
				if ( $settings['custom_condition'] == 'yes' ) {
					$list       = $settings['list'];
					$list_types = wp_list_pluck( $list, 'list_type' );
					if ( in_array( 'search', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'search' ) ) {
						if ( isset( $result['list_content'] ) ) {
							$title = $result['list_content'];
							$title = str_replace( array( '{search_query}' ), array( get_search_query() ), $title );
						}
					}
				}
			}


		}

		if ( is_tax() ) {

			if ( $settings['custom_condition'] == 'yes' ) {
				$tax        = get_queried_object();
				$list       = $settings['list'];
				$list_types = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'custom_taxonomy', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'custom_taxonomy' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = $result['list_content'];
						$title = str_replace( array( '{taxonomy_name}' ), array( $tax->name ), $title );
					}
				}
			}

		}

		if ( is_post_type_archive() ) {


			if ( $settings['custom_condition'] == 'yes' ) {
				$archive_title = post_type_archive_title( '', false );
				$list          = $settings['list'];
				$list_types    = wp_list_pluck( $list, 'list_type' );
				if ( in_array( 'custom_archive', $list_types ) && $result = $this->get_custom_page_conditional_settings( 'custom_archive' ) ) {
					if ( isset( $result['list_content'] ) ) {
						$title = $result['list_content'];
						$title = str_replace( array( '{archive_name}' ), array( $archive_title ), $title );
					}
				}
			}
		}

		return $title;
	}

	public function get_custom_page_conditional_settings( $type = '' ) {
		$settings = $this->get_settings_for_display();

		if ( $settings['custom_condition'] == 'yes' ) {
			$list       = $settings['list'];
			$list_types = wp_list_pluck( $list, 'list_type' );

			if ( ! in_array( $type, $list_types ) ) {
				return false;
			}

			$key = array_search( $type, array_column( $list, 'list_type' ) );

			return isset( $list[ $key ] ) ? $list[ $key ] : false;
		}

		return false;
	}
}
