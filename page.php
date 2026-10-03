<?php
/**
 * 单页面模板。
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
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'lp-entry' ); ?>>

					<header class="lp-entry__header">
						<h1 class="lp-entry__title"><?php the_title(); ?></h1>
					</header>

					<?php if ( has_post_thumbnail() && ! post_password_required() ) : ?>
						<figure class="lp-entry__thumbnail">
							<?php the_post_thumbnail( 'leafpress-daily', array( 'decoding' => 'async' ) ); ?>
						</figure>
					<?php endif; ?>

					<div class="lp-entry__content">
						<?php
						the_content();

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
						edit_post_link(
							sprintf(
								/* translators: %s：页面标题。 */
								esc_html__( '编辑「%s」', 'leafpress-digest' ),
								esc_html( get_the_title() )
							),
							'<span class="lp-edit-link">',
							'</span>'
						);
						?>
					</footer>

				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
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
