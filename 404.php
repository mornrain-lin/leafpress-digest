<?php
/**
 * 404 未找到页面模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

get_header();
?>

<div class="lp-container">
	<div class="lp-404">
		<p class="lp-404__code">404</p>
		<h1 class="lp-404__title"><?php esc_html_e( '这一页没找到', 'leafpress-digest' ); ?></h1>
		<p class="lp-404__text"><?php esc_html_e( '地址可能已变更或被删除。可以搜索关键词，或从下面的最新内容开始。', 'leafpress-digest' ); ?></p>

		<div class="lp-404__actions">
			<a class="lp-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '返回首页', 'leafpress-digest' ); ?></a>
			<a class="lp-button lp-button--outline" href="<?php echo esc_url( (string) get_post_type_archive_link( 'digest' ) ); ?>">
				<?php esc_html_e( '简报归档', 'leafpress-digest' ); ?>
			</a>
		</div>

		<?php get_search_form(); ?>

		<?php
		$lp_recent = new WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'no_found_rows'  => true,
			)
		);

		if ( $lp_recent->have_posts() ) :
			?>
			<section class="lp-404__recent">
				<h2><?php esc_html_e( '最新发布', 'leafpress-digest' ); ?></h2>
				<div class="lp-card-list">
					<?php
					while ( $lp_recent->have_posts() ) {
						$lp_recent->the_post();
						leafpress_card( array( 'style' => 'compact' ) );
					}
					?>
				</div>
			</section>
			<?php
		endif;

		wp_reset_postdata();
		?>
	</div>
</div>

<?php
get_footer();
