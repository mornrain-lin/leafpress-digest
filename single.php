<?php
/**
 * 单篇文章模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

get_header();
?>

<div class="lp-container">
	<?php leafpress_breadcrumb(); ?>

	<div class="lp-content-area">
		<div class="lp-primary">
			<?php
			while ( have_posts() ) :
				the_post();

				$lp_categories = get_the_category( get_the_ID() );
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'lp-entry' ); ?>>

					<header class="lp-entry__header">
						<?php if ( ! empty( $lp_categories ) && ! is_wp_error( $lp_categories ) ) : ?>
							<a class="lp-entry__kicker" href="<?php echo esc_url( (string) get_category_link( $lp_categories[0]->term_id ) ); ?>">
								<?php echo esc_html( $lp_categories[0]->name ); ?>
							</a>
						<?php endif; ?>

						<h1 class="lp-entry__title"><?php the_title(); ?></h1>

						<?php if ( has_excerpt() ) : ?>
							<p class="lp-entry__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>

						<ul class="lp-entry__meta">
							<li>
								<a href="<?php echo esc_url( (string) get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
									<?php echo esc_html( get_the_author() ); ?>
								</a>
							</li>
							<li>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							</li>
							<?php if ( comments_open() || get_comments_number() ) : ?>
								<li>
									<span>
										<?php
										$lp_comments = (int) get_comments_number();

										printf(
											/* translators: %s：评论数。 */
											esc_html( _n( '%s 条评论', '%s 条评论', $lp_comments, 'leafpress-digest' ) ),
											esc_html( number_format_i18n( $lp_comments ) )
										);
										?>
									</span>
								</li>
							<?php endif; ?>
						</ul>
					</header>

					<?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
						<figure class="lp-entry__thumbnail">
							<?php the_post_thumbnail( 'leafpress-daily', array( 'decoding' => 'async' ) ); ?>
						</figure>
					<?php endif; ?>

					<div class="lp-entry__content">
						<?php
						the_content(
							sprintf(
								/* translators: %s：文章标题。 */
								esc_html__( '继续阅读「%s」', 'leafpress-digest' ),
								esc_html( get_the_title() )
							)
						);

						wp_link_pages(
							array(
								'before'      => '<nav class="lp-pagination">' . esc_html__( '页面：', 'leafpress-digest' ),
								'after'       => '</nav>',
								'link_before' => '<span class="page-numbers">',
								'link_after'  => '</span>',
							)
						);
						?>
					</div>

					<footer class="lp-entry__footer">
						<?php
						$lp_tags = get_the_tag_list( '', '' );

						if ( $lp_tags && ! is_wp_error( $lp_tags ) ) {
							echo '<ul class="lp-entry__tags">' . wp_kses_post( $lp_tags ) . '</ul>';
						}

						leafpress_author_box();
						?>
					</footer>

				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}

				if ( leafpress_get_option( 'subscribe_enabled' ) && leafpress_get_option( 'subscribe_inline' ) ) {
					echo '<div class="lp-entry-subscribe">';
					leafpress_render_subscribe_form( array( 'source' => 'post' ) );
					echo '</div>';
				}
				?>

			<?php
			endwhile;
			?>
		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
