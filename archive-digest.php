<?php
/**
 * 简报归档模板（archive-digest.php）。
 *
 * 按发布日期倒序列出全部简报，左侧显示大号日期，右侧是标题与摘要。
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

			<header class="lp-page-header">
				<h1 class="lp-page-header__title">
					<?php
					$lp_archive_title = (string) leafpress_get_option( 'digest_archive_title' );

					echo esc_html( '' !== $lp_archive_title ? $lp_archive_title : wp_strip_all_tags( get_the_archive_title() ) );
					?>
				</h1>

				<?php
				$lp_description = get_the_archive_description();

				if ( $lp_description ) {
					echo '<div class="lp-page-header__description">' . wp_kses_post( wpautop( $lp_description ) ) . '</div>';
				}
				?>
			</header>

			<?php if ( have_posts() ) : ?>

				<ul class="lp-digest-list">
					<?php
					while ( have_posts() ) :
						the_post();

						$lp_created = get_post_time( 'U', true );
						$lp_issue   = leafpress_digest_issue_number( (int) get_the_ID() );
						?>
						<li class="lp-digest-item">
							<div class="lp-digest-item__date">
								<?php if ( $lp_created ) : ?>
									<strong><?php echo esc_html( wp_date( 'm.d', $lp_created ) ); ?></strong>
									<time datetime="<?php echo esc_attr( gmdate( DATE_W3C, $lp_created ) ); ?>">
										<?php echo esc_html( wp_date( 'Y', $lp_created ) ); ?>
									</time>
								<?php endif; ?>
							</div>

							<div class="lp-digest-item__body">
								<h2 class="lp-digest-item__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>

									<?php if ( $lp_issue > 0 ) : ?>
										<span class="lp-digest-item__badge">
											<?php
											$lp_issue_label = (string) leafpress_get_option( 'digest_issue_label' );

											if ( '' === $lp_issue_label ) {
												$lp_issue_label = esc_html__( '第 %s 期', 'leafpress-digest' );
											}

											echo esc_html( sprintf( $lp_issue_label, number_format_i18n( $lp_issue ) ) );
											?>
										</span>
									<?php endif; ?>
								</h2>

								<?php
								$lp_excerpt = leafpress_get_excerpt();

								if ( '' !== $lp_excerpt ) {
									echo '<p class="lp-digest-item__excerpt">' . esc_html( $lp_excerpt ) . '</p>';
								}
								?>

								<?php leafpress_card_meta( get_post() ); ?>
							</div>
						</li>
						<?php
					endwhile;
					?>
				</ul>

				<?php leafpress_pagination(); ?>

			<?php else : ?>

				<div class="lp-no-results">
					<h2><?php esc_html_e( '还没有发布简报', 'leafpress-digest' ); ?></h2>
					<p><?php esc_html_e( '发布第一期简报后，这里会按时间倒序排列。', 'leafpress-digest' ); ?></p>
				</div>

			<?php endif; ?>

		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
