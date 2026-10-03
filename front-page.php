<?php
/**
 * 首页模板：头条区 + 最新文章 + 三栏分类区块。
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
			// 头条区：大图头条 + 次条列表。
			if ( leafpress_get_option( 'headline_enabled' ) ) {
				leafpress_headline_section( 1, (int) leafpress_get_option( 'headline_secondary_count' ) );
			}

			// 最新文章区块。
			$lp_latest_count = (int) leafpress_get_option( 'latest_count' );

			if ( $lp_latest_count < 3 ) {
				$lp_latest_count = 6;
			}

			$lp_latest_title = (string) leafpress_get_option( 'latest_title' );

			if ( '' === $lp_latest_title ) {
				$lp_latest_title = esc_html__( '最新发布', 'leafpress-digest' );
			}

			$lp_latest_style = (string) leafpress_get_option( 'latest_style' );
			$lp_latest_query = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => $lp_latest_count,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);

			if ( $lp_latest_query->have_posts() ) :
				?>
				<section class="lp-section" aria-labelledby="lp-latest-title">
					<div class="lp-section__header">
						<h2 class="lp-section__title" id="lp-latest-title">
							<?php echo esc_html( $lp_latest_title ); ?>
						</h2>
						<a class="lp-section__more" href="<?php echo esc_url( (string) get_post_type_archive_link( 'digest' ) ); ?>">
							<?php esc_html_e( '全部简报', 'leafpress-digest' ); ?>
						</a>
					</div>

					<div class="lp-card-grid">
						<?php
						while ( $lp_latest_query->have_posts() ) :
							$lp_latest_query->the_post();
							leafpress_card( array( 'style' => $lp_latest_style ) );
						endwhile;
						?>
					</div>
				</section>
				<?php
			endif;

			wp_reset_postdata();

			// 三栏分类区块。
			if ( leafpress_get_option( 'columns_enabled' ) ) :
				$lp_columns_count = (int) leafpress_get_option( 'columns_count' );
				$lp_per_column    = (int) leafpress_get_option( 'columns_count_per_column' );
				$lp_columns_style = (string) leafpress_get_option( 'columns_style' );

				if ( $lp_columns_count < 2 ) {
					$lp_columns_count = 3;
				}

				if ( $lp_per_column < 2 ) {
					$lp_per_column = 4;
				}

				$lp_terms = leafpress_get_column_terms( $lp_columns_count );

				if ( ! empty( $lp_terms ) ) :
					?>
					<section class="lp-section lp-columns-section" aria-label="<?php esc_attr_e( '分类速览', 'leafpress-digest' ); ?>">
						<div class="lp-columns">
							<?php
							foreach ( $lp_terms as $lp_term ) {
								leafpress_column_section( $lp_term, $lp_per_column, $lp_columns_style );
							}
							?>
						</div>
					</section>
					<?php
				endif;
			endif;
			?>

		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
