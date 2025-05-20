<?php

namespace WCF_ADDONS\Widgets;

use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Posts
 *
 * Elementor widget for Posts.
 *
 * @since 1.0.0
 */
class Author_Posts extends Widget_Base {

	/**
	 * @var \WP_Query
	 */
	protected $query = null;

	/**
	 * Retrieve the widget name.
	 *
	 * @return string Widget name.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_name() {
		return 'wcf--author-posts';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @return string Widget title.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_title() {
		return esc_html__( 'Author Posts', 'animation-addons-for-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @return string Widget icon.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_icon() {
		return 'wcf eicon-post-list';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @return array Widget categories.
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 */
	public function get_categories() {
		return [ 'weal-coder-addon', 'wcf-archive-addon' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @return array Widget scripts dependencies.
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function get_script_depends() {
		return [];
	}

	/**
	 * Requires css files.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array('wcf--author-posts');
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
	protected function register_controls() {
		//layout
		$this->start_controls_section(
			'section_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

	
		$this->end_controls_section();
	}

	protected function register_design_layout_controls() {
		$this->start_controls_section(
			'section_design_layout',
			[
				'label' => esc_html__( 'Layout', 'animation-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		

		$this->end_controls_section();
	}


	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

        // get current author id
        $author_id = get_queried_object_id();
        
        if(! $author_id){
            echo '<h1>Author Not Found</h1>';
            return;
        }

        // author details
        $author_image = get_avatar_url( $author_id );
        $author_name = get_the_author_meta( 'display_name', $author_id );
        $author_designation = get_the_author_meta( 'description', $author_id );
        $author_email = get_the_author_meta('user_email', $author_id);
        $author_phone = get_the_author_meta('author_phone', $author_id);

        // get data from custom meta fields
        $author_fb_icon = get_user_meta($author_id, 'author_fb_icon',true);
        $author_fb_url = get_user_meta($author_id, 'author_fb_url',true);
        $author_fb_follower = get_user_meta($author_id, 'author_fb_follower',true);
        $author_tw_icon = get_user_meta($author_id, 'author_tw_icon',true);
        $author_tw_url = get_user_meta($author_id, 'author_tw_url',true);
        $author_tw_follower = get_user_meta($author_id, 'author_tw_follower',true);
        $author_tik_icon = get_user_meta($author_id, 'author_tik_icon',true);
        $author_tik_url = get_user_meta($author_id, 'author_tik_url',true);
        $author_tik_follower = get_user_meta($author_id, 'author_tik_follower',true);


        ?>
        <div class="wcf--author-posts">
			<div class="wcf--author-posts-box">
            <div class="wcf--author-posts-header">
                <div class="wcf--author-posts-image">
                    <img src="<?php echo esc_url($author_image);?>" alt="<?php echo esc_html($author_name); ?>">
                </div>
                <div class="wcf--author-posts-details">
                    <h2 class="wcf--author-posts-name"><?php echo esc_html($author_name); ?></h2>
                    <p class="wcf--author-posts-designation"><?php echo esc_html($author_designation); ?></p>
                    <p class="wcf--author-posts-email"><?php echo esc_html($author_email); ?></p>
                    <p class="wcf--author-posts-phone"><?php echo esc_html($author_phone); ?></p>
                </div>
            </div>

            <div class="wcf--author-posts__social">
				<h3 class="wcf--author-posts-social-title">Social Media:</h3>
				<div class="wcf--author-posts-social-icons">
						<div class="wcf--social-icon">
							<a href="<?php echo esc_url($author_fb_url); ?>" target="_blank"><img src="<?php echo esc_url($author_fb_icon);?>" alt="facebook"></i></a>
							<span><?php echo esc_html($author_fb_follower);?></span>
						</div>
						<div class="wcf--social-icon">
							<a href="<?php echo esc_url($author_tw_url); ?>" target="_blank"><img src="<?php echo esc_url($author_fb_icon);?>" alt="twiter"></i></a>
							<span><?php echo esc_html($author_tw_follower);?></span>
						</div>
						<div class="wcf--social-icon">
							<a href="<?php echo esc_url($author_tik_url); ?>" target="_blank"><img src="<?php echo esc_url($author_fb_icon);?>" alt="twiter"></i></a>
							<span><?php echo esc_html($author_tik_follower);?></span>
						</div>
				</div>
            </div>
			</div>
            <div class="wcf--author-posts__posts">
                <?php
                $args = array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    'author' => $author_id,
                );
                $query = new \WP_Query( $args );
                
                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        ?>
                        <div class="wcf--author-posts__posts__item">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php the_excerpt(); ?></p>
                        </div>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p>No posts found.</p>';
                }
                ?>
            </div
        </div>
        <?php
	}

}
