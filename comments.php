<?php
/**
 * 评论模板。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="lp-comments">

	<?php if ( have_comments() ) : ?>
		<h2 class="lp-comments__title">
			<?php
			$lp_comment_count = (int) get_comments_number();

			printf(
				/* translators: %s：评论数量。 */
				esc_html( _n( '%s 条评论', '%s 条评论', $lp_comment_count, 'leafpress-digest' ) ),
				esc_html( number_format_i18n( $lp_comment_count ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( '上一页', 'leafpress-digest' ),
				'next_text' => esc_html__( '下一页', 'leafpress-digest' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="no-comments"><?php esc_html_e( '评论已关闭。', 'leafpress-digest' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>
</div>
