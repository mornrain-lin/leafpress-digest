<?php
/**
 * 简报详情模板（single-digest.php）。
 *
 * 与普通文章的区别：顶部展示期号、发布时间、覆盖分类的「本期信息条」。
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

				$lp_id       = (int) get_the_ID();
				$lp_issue    = leafpress_digest_issue_number( $lp_id );
				$lp_created  = get_post_time( 'U', true, $lp_id );
				$lp_previous = get_previous_post();
				$lp_next     = get_next_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'lp-entry lp-entry--digest' ); ?>>

					<header class="lp-entry__header">
						<span class="lp-entry__kicker">
							<?php
							$lp_issue_label = (string) leafpress_get_option( 'digest_issue_label' );

							if ( '' === $lp_issue_label ) {
								$lp_issue_label = esc_html__( '第 %s 期', 'leafpress-digest' );
							}

							if ( $lp_issue > 0 ) {
								echo esc_html( sprintf( $lp_issue_label, number_format_i18n( $lp_issue ) ) );
							} else {
								esc_html_e( '简报', 'leafpress-digest' );
							}
							?>
						</span>

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
							<?php if ( $lp_created ) : ?>
								<li>
									<time datetime="<?php echo esc_attr( gmdate( DATE_W3C, $lp_created ) ); ?>">
										<?php echo esc_html( wp_date( 'Y 年 n 月 j 日', $lp_created ) ); ?>
									</time>
								</li>
							<?php endif; ?>
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

					<?php
					$lp_categories = get_the_category( $lp_id );
					$lp_tags       = get_the_tag_list( '', ', ', '', $lp_id );
					?>
					<?php if ( ! empty( $lp_categories ) || ( $lp_tags && ! is_wp_error( $lp_tags ) ) ) : ?>
						<div class="lp-digest-meta">
							<?php if ( ! empty( $lp_categories ) && ! is_wp_error( $lp_categories ) ) : ?>
								<div class="lp-digest-meta__item">
									<span class="lp-digest-meta__label"><?php esc_html_e( '覆盖分类', 'leafpress-digest' ); ?></span>
									<span class="lp-digest-meta__value">
										<?php echo esc_html( implode( '、', wp_list_pluck( $lp_categories, 'name' ) ) ); ?>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( $lp_tags && ! is_wp_error( $lp_tags ) ) : ?>
								<div class="lp-digest-meta__item">
									<span class="lp-digest-meta__label"><?php esc_html_e( '涉及话题', 'leafpress-digest' ); ?></span>
									<span class="lp-digest-meta__value"><?php echo esc_html( wp_strip_all_tags( $lp_tags ) ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="lp-entry__content">
						<?php
						the_content(
							sprintf(
								/* translators: %s：简报标题。 */
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
						if ( $lp_tags && ! is_wp_error( $lp_tags ) ) {
							echo '<ul class="lp-entry__tags">' . wp_kses_post( $lp_tags ) . '</ul>';
						}

						if ( $lp_previous || $lp_next ) :
							?>
							<nav class="lp-digest-nav" aria-label="<?php esc_attr_e( '简报导航', 'leafpress-digest' ); ?>">
								<?php if ( $lp_previous ) : ?>
									<a class="lp-digest-nav__link" href="<?php echo esc_url( (string) get_permalink( $lp_previous ) ); ?>" rel="prev">
										<span><?php esc_html_e( '上一期', 'leafpress-digest' ); ?></span>
										<?php echo esc_html( get_the_title( $lp_previous ) ); ?>
									</a>
								<?php endif; ?>

								<?php if ( $lp_next ) : ?>
									<a class="lp-digest-nav__link lp-digest-nav__link--next" href="<?php echo esc_url( (string) get_permalink( $lp_next ) ); ?>" rel="next">
										<span><?php esc_html_e( '下一期', 'leafpress-digest' ); ?></span>
										<?php echo esc_html( get_the_title( $lp_next ) ); ?>
									</a>
								<?php endif; ?>
							</nav>
							<?php
						endif;

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
					leafpress_render_subscribe_form( array( 'source' => 'digest' ) );
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
