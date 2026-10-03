<?php
/**
 * Leafpress Digest 热门文章模块。
 *
 * 排序策略：优先按浏览数（_leafpress_views），没有浏览数据时回退到评论数。
 * 浏览数通过 `leafpress_track_view` 过滤器由外部统计方案写入 postmeta；
 * 主题本身不写浏览数，避免无谓的数据库写入。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leafpress_get_popular_posts' ) ) {
	/**
	 * 获取热门文章列表。
	 *
	 * @param int    $limit     数量。
	 * @param string $post_type 文章类型，'any' 表示全部公开类型。
	 * @return WP_Post[]
	 */
	function leafpress_get_popular_posts( $limit = 6, $post_type = 'any' ) {
		$limit = max( 1, min( 20, (int) $limit ) );

		$orderby = (string) leafpress_get_option( 'popular_orderby' );

		if ( 'comments' === $orderby ) {
			$orderby = 'comment_count';
		} elseif ( 'views' !== $orderby ) {
			$orderby = 'comment_count';
		}

		$args = array(
			'post_type'           => ( 'any' === $post_type ) ? array( 'post', 'digest' ) : $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_key'            => '_leafpress_views',
			'orderby'             => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
		);

		$query = new WP_Query( $args );

		$posts = $query->posts;

		wp_reset_postdata();

		// 有浏览数据的文章优先，不足时用评论数补齐。
		if ( count( $posts ) >= $limit || 'views' !== $orderby ) {
			return is_array( $posts ) ? $posts : array();
		}

		$excluded = wp_list_pluck( $posts, 'ID' );

		$fallback = new WP_Query(
			array(
				'post_type'           => array( 'post', 'digest' ),
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'post__not_in'        => $excluded,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => 'comment_count',
				'order'               => 'DESC',
			)
		);

		$merged = array_merge( is_array( $posts ) ? $posts : array(), $fallback->posts );

		wp_reset_postdata();

		return array_slice( $merged, 0, $limit );
	}
}

if ( ! function_exists( 'leafpress_get_popular_ids' ) ) {
	/**
	 * 只取热门文章 ID（用于批量补齐浏览数等场景）。
	 *
	 * @param int    $limit     数量。
	 * @param string $post_type 文章类型。
	 * @return int[]
	 */
	function leafpress_get_popular_ids( $limit = 6, $post_type = 'any' ) {
		$posts = leafpress_get_popular_posts( $limit, $post_type );

		return array_map( 'intval', wp_list_pluck( $posts, 'ID' ) );
	}
}
