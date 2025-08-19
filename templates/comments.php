<?php
	// If the current post is protected by a password and the visitor has not yet 
	// entered the password we will return early without loading the comments.
	// ----------------------------------------------------------------------------------------
	if ( post_password_required() ) {
		return;
	}

    /*----------------------------------------
   CUSTOM COMMENNS WALKER
-------------------------------------------*/
if ( !function_exists('aae_addon_comment_style') ):

   function aae_addon_comment_style( $comment, $args, $depth ) {
      if ( 'div' === $args[ 'style' ] ) {
         $tag		 = 'div';
         $add_below	 = 'comment';
      } else {
         $tag		 = 'li ';
         $add_below	 = 'div-comment';
      }
      ?>
     
      <<?php
      echo wp_kses_post( $tag );
      comment_class( empty( $args[ 'has_children' ] ) ? 'no-reply' : 'parent has-reply'  );
      ?> id="comment-<?php comment_ID() ?>"><?php if ( 'div' != $args[ 'style' ] ) { ?>
         <div id="div-comment-<?php comment_ID() ?>" class="comment-body"><?php }
      ?>	
        
         <div class="default-details-comment-wrapper mb-50">
            <div class="default-details-comment-thumb">
               <?php
                  if ( $args[ 'avatar_size' ] != 0 ) {
                     echo get_avatar( $comment, $args[ 'avatar_size' ], '', '', array( 'class' => 'comment-avatar float-left' ) );
                  }
               ?>
            </div>
            <div class="default-details-comment-meta">
              <h3 class="default-details-comment-name">
                  <?php
                     echo get_comment_author_link();
                  ?>
              </h3>
              <p class="default-details-comment-date">
                 <?php
                    echo wp_kses_post( get_comment_date() .'<span></span>' . get_comment_time() );
                  ?>
              </p>
              <div class="builder-comment-text"><?php comment_text(); ?></div>
              <?php
                  comment_reply_link(
                  array_merge(
                  $args, array(
                     'add_below'	 => $add_below,
                     'depth'		 => $depth,
                     'max_depth'	 => $args[ 'max_depth' ]
                  ) ) );
               ?>
               <?php if ( $comment->comment_approved == '0' ) { ?>
               <p class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'animation-addons-for-elementor' ); ?></p><br/><?php }
               ?>
            </div>
           
         </div>
         <?php if ( 'div' != $args[ 'style' ] ) : ?>
         </div><?php
      endif;
   }
endif;
?>

<?php if ( have_comments() || comments_open()) : ?>
	<div id="comments" class="joya--comment joya--blog-post-comment font-heading-prata">
		<?php if ( have_comments()) : ?>

			<h3 class="comment-num mb-50">
				<?php

					if(get_comments_number() < 1){
						printf( '%1$s ' . esc_html__( 'Comment', 'animation-addons-for-elementor' ), wp_kses_post( get_comments_number() ) );
					}else{
						printf( '%1$s ' . esc_html__( 'Comments', 'animation-addons-for-elementor' ), wp_kses_post( get_comments_number() ) );
					}
				
				?>
			</h3>

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
				<nav id="comment-nav-above" class="navigation comment-navigation" role="navigation">

					<h1 class="screen-reader-text">
						<?php esc_html_e( 'Comment navigation', 'animation-addons-for-elementor' ); ?>
					</h1>
					<div class="nav-previous">
						<?php previous_comments_link( esc_html__( '&larr; Older Comments', 'animation-addons-for-elementor' ) ); ?>
					</div>
					<div class="nav-next">
						<?php next_comments_link( esc_html__( 'Newer Comments &rarr;', 'animation-addons-for-elementor' ) ); ?>
					</div>
				
				</nav><!-- #comment-nav-above -->
			<?php endif; //check for comment navigation ?>

			<ul class="joya--comments-list comments-list ">
				<?php
						wp_list_comments( array(
							'reply_text'        => sprintf('<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M205 34.8c11.5 5.1 19 16.6 19 29.2v64H336c97.2 0 176 78.8 176 176c0 113.3-81.5 163.9-100.2 174.1c-2.5 1.4-5.3 1.9-8.1 1.9c-10.9 0-19.7-8.9-19.7-19.7c0-7.5 4.3-14.4 9.8-19.5c9.4-8.8 22.2-26.4 22.2-56.7c0-53-43-96-96-96H224v64c0 12.6-7.4 24.1-19 29.2s-25 3-34.4-5.4l-160-144C3.9 225.7 0 217.1 0 208s3.9-17.7 10.6-23.8l160-144c9.4-8.5 22.9-10.6 34.4-5.4z"/></svg> %s',esc_html__('Reply','animation-addons-for-elementor')),
								'callback'    => 'aae_addon_comment_style',
								'style'       => 'ul',
								'short_ping'  => false,
								'type'        => 'all',
								'format'      => current_theme_supports( 'html5', 'comment-list' ) ? 'html5' : 'xhtml',
								'avatar_size' => 60,
						) );
				?>
			</ul><!-- .comment-list -->

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
						<nav id="comment-nav-bellow" class="navigation comment-navigation" role="navigation">
						
							<h1 class="screen-reader-text">
								<?php esc_html_e( 'Comment navigation', 'animation-addons-for-elementor' ); ?>
							</h1>

							<div class="nav-previous">
								<?php previous_comments_link( esc_html__( '&larr; Older Comments', 'animation-addons-for-elementor' ) ); ?>
							</div>
							
							<div class="nav-next">
								<?php next_comments_link( esc_html__( 'Newer Comments &rarr;', 'animation-addons-for-elementor' ) ); ?>
							</div>
						</nav><!-- #comment-nav-bellow -->
			<?php endif; //check for comment navigation ?>

			<?php if ( !comments_open() ) : ?>
				<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'animation-addons-for-elementor' ); ?></p>
			<?php endif; ?>

		<?php endif; // comments have end ?>

		<?php

			$post_id = '';
			if ( null === $post_id )
				$post_id = get_the_ID();
			else
				$id		 = $post_id;

			$commenter		 = wp_get_current_commenter();
			$user			 = wp_get_current_user();
			$user_identity	 = $user->exists() ? $user->display_name : '';
	
			$req		 = get_option( 'require_name_email' );
			$aria_req	 = ( $req ? " aria-required='true'" : '' );

			$fields = array(
				'author' => '<div class="grid-row"><div class="col-lg-6"><div class="elc-inbd-comment__field mb-30"><label for="author">' . esc_html__( 'Name*' ,'animation-addons-for-elementor' ) . '</label> <input placeholder="'.  esc_attr__('Enter Name', 'animation-addons-for-elementor').'" id="author" class="form-input" name="author" type="text" value="' . esc_attr( $commenter[ 'comment_author' ] ) . '" size="30"' . $aria_req . ' /></div></div>',
				'email'	 => '<div class="col-lg-6"><div class="elc-inbd-comment__field mb-30"><label for="email">' . esc_html__( 'Email*' ,'animation-addons-for-elementor' ) . '</label><input placeholder="'.  esc_attr__('Enter Email', 'animation-addons-for-elementor').'" id="email" name="email" class="form-input" type="email" value="' . esc_attr( $commenter[ 'comment_author_email' ] ) . '" size="30"' . $aria_req . ' /></div></div> </div>',
			);

			if ( is_user_logged_in() ) {
				$cl = 'loginformuser';
			} else {
				$cl = '';
			}
			
			$button_style = 'btn-hover-divide';
			$defaults = [
				'fields'			 => $fields,
				'comment_field'		 => '
							<div class="elc-inbd-comment__field order-4">
	                              <label for="name">Comment*</label>
	                              <textarea
	                              id="comment" 
								  name="comment"
								  aria-required="true"
								  placeholder="'.  esc_attr__('Write your comments......', 'animation-addons-for-elementor').'" 
								  ></textarea>
	                        </div>
				',
				/** This filter is documented in wp-includes/link-template.php */
				'must_log_in'		 => '
					<p class="must-log-in">
					'.esc_html__('You must be','animation-addons-for-elementor').' <a href="'.esc_url(wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) )).'">'.esc_html__('logged in','animation-addons-for-elementor').'</a> '.esc_html__('to post a comment.','animation-addons-for-elementor').'
					</p>',
				/** This filter is documented in wp-includes/link-template.php */
				'logged_in_as'		 => '
					<p class="logged-in-as">
					'.esc_html__('Logged in as','animation-addons-for-elementor').' <a href="'.esc_url(get_edit_user_link()).'">'.esc_html($user_identity).'</a>. <a href="'.esc_url(wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) )).'" title="'.esc_attr__('Log out of this account','animation-addons-for-elementor').'">'.esc_html__('Log out?','animation-addons-for-elementor').'</a>
					</p>',
				'id_form'			 => 'commentform',
				'id_submit'			 => 'submit',
				'class_form'         => 'd-flex flex-column comment-form',
				'class_submit'		 => sprintf("wcf--theme-btn wc-btn-primary %s",esc_attr( $button_style )),
				'title_reply_before' => '<h3 id="reply-title" class="elc-inbd-comment__title mb-10">',
				'title_reply'		 => esc_html__( 'Leave a Reply', 'animation-addons-for-elementor' ),
				/* translators: %s: the author name being replied to. */
				'title_reply_to'	 => esc_html__( 'Leave a Reply to %s', 'animation-addons-for-elementor' ),
				'cancel_reply_link'	 => esc_html__( 'Cancel Reply', 'animation-addons-for-elementor' ),
				'label_submit'		 => esc_html__( 'Submit Now', 'animation-addons-for-elementor' ),
				'submit_field' =>  '<div class="cf_btn default-details__cmtbtn mt-45 mb-45 order-5">%1$s %2$s</div>',
				'submit_button' => '<button name="%1$s" type="submit" id="%2$s" class="%3$s" > %4$s <i class="icon-wcf-checvron-right"></i></button>',
				'format'			 => 'xhtml',
			];

			comment_form( $defaults );
		?>

	</div><!-- #comments -->
<?php endif;