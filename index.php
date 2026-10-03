<?php
/**
 * 主模板文件（fallback template）。
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

			<?php if ( have_posts() ) : ?>

				<header class="lp-page-header">
					<?php
					if ( is_home() && ! is_front_page() ) {
						$lp_page_for_posts = (int) get_option( 'page_for_posts' );
						$lp_title = $lp_page_for_posts ? (string) get_the_title( $lp_page_for_posts ) : esc_html__( '最新文章', 'leafpress-digest' );

						echo '<h1 class="lp-page-header__title">' . esc_html( $lp_title ) . '</h1>';
					} else {
						echo '<h1 class="lp-page-header__title">' . esc_html( wp_strip_all_tags( get_the_archive_title() ) ) . '</h1>';
					}

					$lp_description = get_the_archive_description();

					if ( $lp_description ) {
						echo '<div class="lp-page-header__description">' . wp_kses_post( wpautop( $lp_description ) ) . '</div>';
					}
					?>
				</header>

				<div class="lp-card-list">
					<?php
					$lp_style = (string) leafpress_get_option( 'card_style_default' );

					while ( have_posts() ) {
						the_post();
						leafpress_card( array( 'style' => $lp_style ) );
					}
					?>
				</div>

				<?php leafpress_pagination(); ?>

			<?php else : ?>

				<div class="lp-no-results">
					<h2><?php esc_html_e( '暂时没有内容', 'leafpress-digest' ); ?></h2>
					<p><?php esc_html_e( '这里还没有发布文章。换个关键词，或从首页开始浏览。', 'leafpress-digest' ); ?></p>
					<?php get_search_form(); ?>
				</div>

			<?php endif; ?>

		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
