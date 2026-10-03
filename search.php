<?php
/**
 * 搜索结果模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

get_header();

global $wp_query;
$lp_found = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
?>

<div class="lp-container">
	<div class="lp-content-area">
		<div class="lp-primary">

			<header class="lp-page-header">
				<h1 class="lp-page-header__title">
					<?php
					printf(
						/* translators: %s：搜索关键词。 */
						esc_html__( '搜索：%s', 'leafpress-digest' ),
						esc_html( get_search_query() )
					);
					?>
				</h1>

				<?php if ( have_posts() ) : ?>
					<p class="lp-page-header__description">
						<?php
						printf(
							/* translators: %s：结果数量。 */
							esc_html( _n( '共找到 %s 条结果。', '共找到 %s 条结果。', $lp_found, 'leafpress-digest' ) ),
							esc_html( number_format_i18n( $lp_found ) )
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( have_posts() ) : ?>

				<div class="lp-card-list">
					<?php
					while ( have_posts() ) {
						the_post();
						leafpress_card( array( 'style' => 'horizontal' ) );
					}
					?>
				</div>

				<?php leafpress_pagination(); ?>

			<?php else : ?>

				<div class="lp-no-results">
					<h2><?php esc_html_e( '没有找到相关内容', 'leafpress-digest' ); ?></h2>
					<p><?php esc_html_e( '请尝试更换关键词，或使用更简短的词组。', 'leafpress-digest' ); ?></p>
					<?php get_search_form(); ?>
				</div>

			<?php endif; ?>

		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
