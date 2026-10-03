<?php
/**
 * 归档模板：分类、标签、日期、作者。
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
				<h1 class="lp-page-header__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>

				<?php
				$lp_description = get_the_archive_description();

				if ( $lp_description ) {
					echo '<div class="lp-page-header__description">' . wp_kses_post( wpautop( $lp_description ) ) . '</div>';
				}
				?>
			</header>

			<div class="lp-card-list">
				<?php
				while ( have_posts() ) {
					the_post();
					leafpress_card( array( 'style' => 'horizontal' ) );
				}
				?>
			</div>

			<?php leafpress_pagination(); ?>

		</div>

		<?php leafpress_sidebar( 'sidebar-1' ); ?>
	</div>
</div>

<?php
get_footer();
