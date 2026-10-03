<?php
/**
 * Leafpress Digest 自定义文章类型注册。
 *
 * `digest`（简报）：按期发布的资讯汇总，与普通文章分开归档。
 * `digest_subscriber`（订阅者）：邮件订阅记录，后台可查看与导出 CSV。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leafpress_register_post_types' ) ) {
	/**
	 * 注册简报与订阅者类型。
	 *
	 * @return void
	 */
	function leafpress_register_post_types() {

		$digest_labels = array(
			'name'                  => esc_html__( '简报', 'leafpress-digest' ),
			'singular_name'         => esc_html__( '简报', 'leafpress-digest' ),
			'menu_name'             => esc_html__( '简报', 'leafpress-digest' ),
			'add_new'               => esc_html__( '发布简报', 'leafpress-digest' ),
			/* translators: %s：简报类型名称。 */
			'add_new_item'          => sprintf( esc_html__( '发布新%s', 'leafpress-digest' ), esc_html__( '简报', 'leafpress-digest' ) ),
			/* translators: %s：简报类型名称。 */
			'edit_item'             => sprintf( esc_html__( '编辑%s', 'leafpress-digest' ), esc_html__( '简报', 'leafpress-digest' ) ),
			/* translators: %s：简报类型名称。 */
			'new_item'              => sprintf( esc_html__( '新%s', 'leafpress-digest' ), esc_html__( '简报', 'leafpress-digest' ) ),
			/* translators: %s：简报类型名称。 */
			'search_items'          => sprintf( esc_html__( '搜索%s', 'leafpress-digest' ), esc_html__( '简报', 'leafpress-digest' ) ),
			'not_found'             => esc_html__( '还没有简报', 'leafpress-digest' ),
			'not_found_in_trash'    => esc_html__( '回收站里没有简报', 'leafpress-digest' ),
			'all_items'             => esc_html__( '全部简报', 'leafpress-digest' ),
			'featured_image'        => esc_html__( '简报封面', 'leafpress-digest' ),
			/* translators: %s：简报类型名称。 */
			'set_featured_image'    => sprintf( esc_html__( '设置%s封面', 'leafpress-digest' ), esc_html__( '简报', 'leafpress-digest' ) ),
			'remove_featured_image' => esc_html__( '移除封面', 'leafpress-digest' ),
			'use_featured_image'    => esc_html__( '作为封面', 'leafpress-digest' ),
		);

		register_post_type(
			'digest',
			array(
				'labels'             => $digest_labels,
				'description'        => esc_html__( '按期发布的资讯汇总简报。', 'leafpress-digest' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'menu_position'      => 20,
				'menu_icon'          => 'dashicons-newspaper',
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'rewrite'            => array(
					'slug'       => 'digest',
					'with_front' => false,
				),
				'query_var'          => true,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'author', 'page-attributes' ),
			)
		);

		register_post_type(
			'digest_subscriber',
			array(
				'labels'              => array(
					'name'          => esc_html__( '订阅者', 'leafpress-digest' ),
					'singular_name' => esc_html__( '订阅者', 'leafpress-digest' ),
					'menu_name'     => esc_html__( '订阅者', 'leafpress-digest' ),
					'search_items'  => esc_html__( '搜索订阅者', 'leafpress-digest' ),
					'not_found'     => esc_html__( '还没有订阅者', 'leafpress-digest' ),
					'all_items'     => esc_html__( '全部订阅者', 'leafpress-digest' ),
					'edit_item'     => esc_html__( '查看订阅者', 'leafpress-digest' ),
				),
				'description'         => esc_html__( '邮件简报的订阅记录，由前台订阅表单自动创建，不支持手工新增。', 'leafpress-digest' ),
				// 完全私有：不允许公开查询，也不显示在前台。
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'menu_position'       => 21,
				'menu_icon'           => 'dashicons-email-alt',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				// 禁止手工新增与快速编辑，避免产生无邮箱的脏数据。
				'supports'            => array( 'title' ),
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
			)
		);
	}
}
add_action( 'init', 'leafpress_register_post_types' );

if ( ! function_exists( 'leafpress_flush_rewrite_on_switch' ) ) {
	/**
	 * 切换主题时刷新固定链接。
	 *
	 * @return void
	 */
	function leafpress_flush_rewrite_on_switch() {
		leafpress_register_post_types();
		flush_rewrite_rules();
	}
}
add_action( 'after_switch_theme', 'leafpress_flush_rewrite_on_switch' );

if ( ! function_exists( 'leafpress_digest_subscriber_columns' ) ) {
	/**
	 * 订阅者列表的自定义列。
	 *
	 * @param array $columns 默认列。
	 * @return array
	 */
	function leafpress_digest_subscriber_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['lp_email']  = esc_html__( '邮箱', 'leafpress-digest' );
				$new['lp_source'] = esc_html__( '来源', 'leafpress-digest' );
				$new['lp_status'] = esc_html__( '状态', 'leafpress-digest' );
				$new['lp_date']   = esc_html__( '订阅时间', 'leafpress-digest' );
			}
		}

		return $new;
	}
}
add_filter( 'manage_digest_subscriber_posts_columns', 'leafpress_digest_subscriber_columns' );

if ( ! function_exists( 'leafpress_digest_subscriber_column_content' ) ) {
	/**
	 * 订阅者列表的自定义列内容。
	 *
	 * @param string $column  列名。
	 * @param int    $post_id 记录 ID。
	 * @return void
	 */
	function leafpress_digest_subscriber_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'lp_email':
				$email = get_post_meta( $post_id, '_leafpress_email', true );
				printf(
					'<a href="mailto:%1$s">%1$s</a>',
					esc_attr( is_string( $email ) ? $email : '' )
				);
				break;

			case 'lp_source':
				$source = get_post_meta( $post_id, '_leafpress_source', true );
				echo esc_html( is_string( $source ) && '' !== $source ? $source : esc_html__( '—', 'leafpress-digest' ) );
				break;

			case 'lp_status':
				$status = get_post_meta( $post_id, '_leafpress_status', true );
				$labels = array(
					'active'   => esc_html__( '已订阅', 'leafpress-digest' ),
					'unsubscribed' => esc_html__( '已退订', 'leafpress-digest' ),
					'pending'  => esc_html__( '待确认', 'leafpress-digest' ),
				);
				$label = isset( $labels[ $status ] ) ? $labels[ $status ] : esc_html__( '未知', 'leafpress-digest' );
				echo esc_html( $label );
				break;

			case 'lp_date':
				$created = get_post_time( 'U', true, $post_id );

				if ( $created ) {
					echo esc_html( mysql2date( 'Y-m-d H:i', (string) $created ) );
				}
				break;
		}
	}
}
add_action( 'manage_digest_subscriber_posts_custom_column', 'leafpress_digest_subscriber_column_content', 10, 2 );

if ( ! function_exists( 'leafpress_digest_subscriber_sortable' ) ) {
	/**
	 * 让「订阅时间」列可排序。
	 *
	 * @param array $columns 可排序列。
	 * @return array
	 */
	function leafpress_digest_subscriber_sortable( $columns ) {
		$columns['lp_date'] = 'lp_date';

		return $columns;
	}
}
add_filter( 'manage_edit-digest_subscriber_sortable_columns', 'leafpress_digest_subscriber_sortable' );

if ( ! function_exists( 'leafpress_digest_subscriber_pre_get_posts' ) ) {
	/**
	 * 处理订阅者列表的排序与搜索。
	 *
	 * @param WP_Query $query 查询对象。
	 * @return void
	 */
	function leafpress_digest_subscriber_pre_get_posts( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'digest_subscriber' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'lp_date' === $orderby ) {
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
		}

		$search = $query->get( 's' );

		if ( ! $search ) {
			return;
		}

		// WordPress 默认只搜标题，这里同时匹配邮箱与来源。
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_leafpress_email',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_leafpress_source',
					'value'   => $search,
					'compare' => 'LIKE',
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'leafpress_digest_subscriber_pre_get_posts' );
