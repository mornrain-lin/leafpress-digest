<?php
/**
 * Leafpress Digest 站点页脚模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

?>
	</main><!-- #lp-content -->

	<footer id="colophon" class="lp-site-footer">
		<div class="lp-container">

			<?php leafpress_footer_widgets(); ?>

			<?php if ( leafpress_get_option( 'subscribe_enabled' ) && leafpress_get_option( 'subscribe_inline' ) ) : ?>
				<div class="lp-footer-subscribe">
					<?php
					leafpress_render_subscribe_form(
						array(
							'source'  => 'footer',
							'compact' => true,
						)
					);
					?>
				</div>
			<?php endif; ?>

			<div class="lp-site-info">
				<div class="lp-site-info__inner">
					<p>
						<?php
						printf(
							/* translators: 1：年份，2：站点名称。 */
							esc_html__( '© %1$s %2$s', 'leafpress-digest' ),
							esc_html( wp_date( 'Y' ) ),
							esc_html( get_bloginfo( 'name', 'display' ) )
						);
						?>
					</p>

					<?php if ( has_nav_menu( 'footer' ) ) : ?>
						<nav aria-label="<?php esc_attr_e( '页脚导航', 'leafpress-digest' ); ?>">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer',
									'container'      => false,
									'menu_class'     => 'lp-footer-menu',
									'depth'          => 1,
									'fallback_cb'    => false,
								)
							);
							?>
						</nav>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
