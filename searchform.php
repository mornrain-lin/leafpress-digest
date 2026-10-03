<?php
/**
 * 搜索表单模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

$lp_search_id = 'lp-search-' . wp_rand( 1000, 9999 );
?>

<form role="search" method="get" class="lp-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="lp-screen-reader-text" for="<?php echo esc_attr( $lp_search_id ); ?>">
		<?php esc_html_e( '搜索本站内容', 'leafpress-digest' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $lp_search_id ); ?>"
		class="lp-search-form__field"
		placeholder="<?php esc_attr_e( '输入关键词…', 'leafpress-digest' ); ?>"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		name="s"
	>
	<button type="submit" class="lp-search-form__submit">
		<?php esc_html_e( '搜索', 'leafpress-digest' ); ?>
	</button>
</form>
