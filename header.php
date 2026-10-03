<?php
/**
 * Leafpress Digest 报头模板。
 *
 * 三段式结构：报头通栏（日期 + 快捷导航）→ 报头主体（Logo + 站名）→ 分区导航。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
} else {
	do_action( 'wp_body_open' );
}
?>

<a class="lp-skip-link" href="#lp-content"><?php esc_html_e( '跳到正文', 'leafpress-digest' ); ?></a>

<div id="page" class="lp-site">

	<header id="masthead" class="lp-masthead">

		<div class="lp-masthead__top">
			<div class="lp-container">
				<time class="lp-masthead__date" datetime="<?php echo esc_attr( gmdate( DATE_W3C ) ); ?>">
					<?php echo esc_html( wp_date( 'Y年n月j日 l' ) ); ?>
				</time>

				<nav class="lp-masthead__top-nav" aria-label="<?php esc_attr_e( '报头导航', 'leafpress-digest' ); ?>">
					<?php if ( has_nav_menu( 'top' ) ) : ?>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'top',
								'container'      => false,
								'menu_class'     => 'lp-top-menu',
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
						?>
					<?php else : ?>
						<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'digest' ) ); ?>">
							<?php esc_html_e( '全部简报', 'leafpress-digest' ); ?>
						</a>
					<?php endif; ?>

					<button type="button" class="lp-search-toggle" data-lp-search-toggle aria-expanded="false" aria-controls="lp-search-drawer">
						<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M6.8 1.6a5.2 5.2 0 0 1 4.08 8.4l3.6 3.6-1.06 1.06-3.6-3.6A5.2 5.2 0 1 1 6.8 1.6Zm0 1.46a3.74 3.74 0 1 0 0 7.48 3.74 3.74 0 0 0 0-7.48Z"/></svg>
						<span><?php esc_html_e( '搜索', 'leafpress-digest' ); ?></span>
					</button>
				</nav>
			</div>
		</div>

		<div class="lp-masthead__main">
			<div class="lp-container">
				<div class="lp-masthead__brand">
					<?php if ( has_custom_logo() ) : ?>
						<div class="lp-masthead__logo"><?php the_custom_logo(); ?></div>
					<?php endif; ?>

					<?php
					$lp_title_tag = ( is_front_page() && is_home() ) ? 'h1' : 'p';
					?>
					<<?php echo esc_attr( $lp_title_tag ); ?> class="lp-site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?>
						</a>
					</<?php echo esc_attr( $lp_title_tag ); ?>>

					<?php
					$lp_description = get_bloginfo( 'description', 'display' );

					if ( $lp_description ) :
						?>
						<p class="lp-site-description"><?php echo esc_html( $lp_description ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div id="lp-section-nav" class="lp-section-nav">
			<div class="lp-container">
				<div class="lp-section-nav__inner">
					<?php
					leafpress_section_nav_toggle();

					if ( has_nav_menu( 'primary' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'primary',
								'container'      => false,
								'menu_class'     => 'lp-section-menu',
								'depth'          => 2,
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<ul class="lp-section-menu">';
						printf(
							'<li><a href="%1$s">%2$s</a></li>',
							esc_url( home_url( '/' ) ),
							esc_html__( '首页', 'leafpress-digest' )
						);
						printf(
							'<li><a href="%1$s">%2$s</a></li>',
							esc_url( (string) get_post_type_archive_link( 'digest' ) ),
							esc_html__( '简报归档', 'leafpress-digest' )
						);
						wp_list_categories(
							array(
								'title_li' => '',
								'number'   => 6,
								'orderby'  => 'count',
								'order'    => 'DESC',
							)
						);
						echo '</ul>';
					}
					?>
				</div>
			</div>
		</div>

		<div id="lp-search-drawer" class="lp-search-drawer" data-lp-search-drawer>
			<div class="lp-search-drawer__inner">
				<div class="lp-container">
					<?php get_search_form(); ?>
				</div>
			</div>
		</div>

	</header>

	<main id="lp-content" class="lp-main" tabindex="-1">
