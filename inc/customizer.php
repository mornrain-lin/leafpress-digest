<?php
/**
 * Leafpress Digest 自定义器配置。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leafpress_option_defaults' ) ) {
	/**
	 * 主题全部设置项默认值。
	 *
	 * @return array<string,mixed>
	 */
	function leafpress_option_defaults() {
		return array(
			'accent_color'               => '',
			'base_background'            => '',
			'headline_enabled'           => true,
			'headline_secondary_count'   => 4,
			'headline_label'             => '',
			'latest_title'               => '',
			'latest_count'               => 6,
			'latest_style'               => 'standard',
			'columns_enabled'            => true,
			'columns_count'              => 3,
			'columns_count_per_column'   => 4,
			'columns_style'              => 'compact',
			'column_categories'          => '',
			'sidebar_builtin_widgets'    => true,
			'popular_title'              => '',
			'popular_orderby'            => 'views',
			'popular_count'              => 6,
			'tagcloud_title'             => '',
			'tagcloud_count'             => 30,
			'subscribe_enabled'          => true,
			'subscribe_title'            => '',
			'subscribe_text'             => '',
			'subscribe_require_confirm' => false,
			'subscribe_inline'           => true,
			'digest_issue_label'         => '',
			'digest_archive_title'       => '',
			'excerpt_length'             => 40,
			'card_style_default'         => 'standard',
		);
	}
}

if ( ! function_exists( 'leafpress_get_option' ) ) {
	/**
	 * 读取主题设置。
	 *
	 * @param string $key     设置键名。
	 * @param mixed  $default 自定义默认值。
	 * @return mixed
	 */
	function leafpress_get_option( $key, $default = null ) {
		$defaults = leafpress_option_defaults();

		if ( null !== $default ) {
			$defaults[ $key ] = $default;
		}

		if ( ! isset( $defaults[ $key ] ) ) {
			return $default;
		}

		return get_theme_mod( 'leafpress_' . $key, $defaults[ $key ] );
	}
}

if ( ! function_exists( 'leafpress_build_dynamic_css' ) ) {
	/**
	 * 把设置转为 CSS 变量。
	 *
	 * @return string
	 */
	function leafpress_build_dynamic_css() {
		$vars = array();

		$accent = leafpress_get_option( 'accent_color' );

		if ( $accent ) {
			$accent = sanitize_hex_color( $accent );

			if ( $accent ) {
				$vars['--lp-accent'] = $accent;
				$vars['--lp-accent-soft'] = leafpress_hex_to_rgba( $accent, 0.08 );
			}
		}

		$base = leafpress_get_option( 'base_background' );

		if ( $base ) {
			$base = sanitize_hex_color( $base );

			if ( $base ) {
				$vars['--lp-bg'] = $base;
			}
		}

		if ( empty( $vars ) ) {
			return '';
		}

		$declarations = '';

		foreach ( $vars as $name => $value ) {
			$declarations .= $name . ':' . $value . ';';
		}

		return ':root{' . $declarations . '}';
	}
}

if ( ! function_exists( 'leafpress_hex_to_rgba' ) ) {
	/**
	 * 十六进制颜色转 rgba。
	 *
	 * @param string $hex   颜色值。
	 * @param float  $alpha 透明度。
	 * @return string
	 */
	function leafpress_hex_to_rgba( $hex, $alpha = 0.1 ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return 'rgba(176, 58, 43, ' . (float) $alpha . ')';
		}

		return 'rgba(' . hexdec( substr( $hex, 0, 2 ) ) . ', ' . hexdec( substr( $hex, 2, 2 ) ) . ', ' . hexdec( substr( $hex, 4, 2 ) ) . ', ' . (float) $alpha . ')';
	}
}

if ( ! function_exists( 'leafpress_customize_register' ) ) {
	/**
	 * 注册 Customizer 面板与设置项。
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer 实例。
	 * @return void
	 */
	function leafpress_customize_register( $wp_customize ) {
		$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'blogname',
				array(
					'selector'        => '.lp-site-title a',
					'render_callback' => 'leafpress_customize_blogname',
				)
			);

			$wp_customize->selective_refresh->add_partial(
				'blogdescription',
				array(
					'selector'        => '.lp-site-description',
					'render_callback' => 'leafpress_customize_blogdescription',
				)
			);
		}

		$wp_customize->add_panel(
			'leafpress_panel',
			array(
				'title'       => esc_html__( '主题设置', 'leafpress-digest' ),
				'description' => esc_html__( 'Leafpress Digest 的版式与订阅选项。', 'leafpress-digest' ),
				'priority'    => 20,
			)
		);

		/* ------------------------------------------------------------------
		 * 分区一：配色
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_colors',
			array(
				'title' => esc_html__( '配色', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_accent_color',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_accent_color',
			array(
				'label'       => esc_html__( '强调色', 'leafpress-digest' ),
				'description' => esc_html__( '留空使用主题默认的砖红色 #b03a2b。', 'leafpress-digest' ),
				'section'     => 'leafpress_colors',
				'type'        => 'color',
			)
		);

		$wp_customize->add_setting(
			'leafpress_base_background',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_base_background',
			array(
				'label'   => esc_html__( '页面底色', 'leafpress-digest' ),
				'section' => 'leafpress_colors',
				'type'    => 'color',
			)
		);

		/* ------------------------------------------------------------------
		 * 分区二：头条区
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_headline',
			array(
				'title' => esc_html__( '头条区', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_headline_enabled',
			array(
				'default'           => true,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_headline_enabled',
			array(
				'label'   => esc_html__( '显示头条区', 'leafpress-digest' ),
				'section' => 'leafpress_headline',
				'type'    => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'leafpress_headline_label',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_headline_label',
			array(
				'label'       => esc_html__( '头条标签文案', 'leafpress-digest' ),
				'description' => esc_html__( '留空使用「今日头条」。', 'leafpress-digest' ),
				'section'     => 'leafpress_headline',
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_headline_secondary_count',
			array(
				'default'           => 4,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_headline_secondary_count',
			array(
				'label'       => esc_html__( '次条数量', 'leafpress-digest' ),
				'section'     => 'leafpress_headline',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 10,
					'step' => 1,
				),
			)
		);

		/* ------------------------------------------------------------------
		 * 分区三：最新文章
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_latest',
			array(
				'title' => esc_html__( '最新文章区块', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_latest_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_latest_title',
			array(
				'label'   => esc_html__( '区块标题', 'leafpress-digest' ),
				'section' => 'leafpress_latest',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_latest_count',
			array(
				'default'           => 6,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_latest_count',
			array(
				'label'       => esc_html__( '展示数量', 'leafpress-digest' ),
				'section'     => 'leafpress_latest',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 3,
					'max'  => 24,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_latest_style',
			array(
				'default'           => 'standard',
				'sanitize_callback' => 'leafpress_sanitize_card_style',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_latest_style',
			array(
				'label'   => esc_html__( '卡片样式', 'leafpress-digest' ),
				'section' => 'leafpress_latest',
				'type'    => 'select',
				'choices' => array(
					'standard'   => esc_html__( '标准图文', 'leafpress-digest' ),
					'feature'    => esc_html__( '大图', 'leafpress-digest' ),
					'horizontal' => esc_html__( '横向图文', 'leafpress-digest' ),
					'compact'    => esc_html__( '紧凑文字', 'leafpress-digest' ),
				),
			)
		);

		/* ------------------------------------------------------------------
		 * 分区四：三栏分类区块
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_columns',
			array(
				'title' => esc_html__( '三栏分类区块', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_columns_enabled',
			array(
				'default'           => true,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_columns_enabled',
			array(
				'label'   => esc_html__( '显示三栏区块', 'leafpress-digest' ),
				'section' => 'leafpress_columns',
				'type'    => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'leafpress_columns_count',
			array(
				'default'           => 3,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_columns_count',
			array(
				'label'       => esc_html__( '栏数', 'leafpress-digest' ),
				'section'     => 'leafpress_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 4,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_columns_count_per_column',
			array(
				'default'           => 4,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_columns_count_per_column',
			array(
				'label'       => esc_html__( '每栏文章数', 'leafpress-digest' ),
				'section'     => 'leafpress_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 2,
					'max'  => 10,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_columns_style',
			array(
				'default'           => 'compact',
				'sanitize_callback' => 'leafpress_sanitize_card_style',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_columns_style',
			array(
				'label'   => esc_html__( '卡片样式', 'leafpress-digest' ),
				'section' => 'leafpress_columns',
				'type'    => 'select',
				'choices' => array(
					'standard'   => esc_html__( '标准图文', 'leafpress-digest' ),
					'feature'    => esc_html__( '大图', 'leafpress-digest' ),
					'horizontal' => esc_html__( '横向图文', 'leafpress-digest' ),
					'compact'    => esc_html__( '紧凑文字', 'leafpress-digest' ),
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_column_categories',
			array(
				'default'           => '',
				'sanitize_callback' => 'leafpress_sanitize_id_list',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_column_categories',
			array(
				'label'       => esc_html__( '指定分类 ID', 'leafpress-digest' ),
				'description' => esc_html__( '英文逗号分隔的分类 ID，例如 3,5,9。留空时自动取文章数最多的分类。', 'leafpress-digest' ),
				'section'     => 'leafpress_columns',
				'type'        => 'text',
			)
		);

		/* ------------------------------------------------------------------
		 * 分区五：侧栏 Widget
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_sidebar',
			array(
				'title' => esc_html__( '侧栏小组件', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_sidebar_builtin_widgets',
			array(
				'default'           => true,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_sidebar_builtin_widgets',
			array(
				'label'       => esc_html__( '启用内置小组件', 'leafpress-digest' ),
				'description' => esc_html__( '在自定义 Widget 之后自动追加热门文章、标签云与订阅表单。', 'leafpress-digest' ),
				'section'     => 'leafpress_sidebar',
				'type'        => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'leafpress_popular_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_popular_title',
			array(
				'label'   => esc_html__( '热门文章标题', 'leafpress-digest' ),
				'section' => 'leafpress_sidebar',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_popular_orderby',
			array(
				'default'           => 'views',
				'sanitize_callback' => 'leafpress_sanitize_popular_order',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_popular_orderby',
			array(
				'label'       => esc_html__( '热门排序依据', 'leafpress-digest' ),
				'description' => esc_html__( '「浏览数」需要统计方案向 _leafpress_views 写入数据；没有数据时自动回退到评论数。', 'leafpress-digest' ),
				'section'     => 'leafpress_sidebar',
				'type'        => 'select',
				'choices'     => array(
					'views'    => esc_html__( '浏览数', 'leafpress-digest' ),
					'comments' => esc_html__( '评论数', 'leafpress-digest' ),
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_popular_count',
			array(
				'default'           => 6,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_popular_count',
			array(
				'label'       => esc_html__( '热门文章数量', 'leafpress-digest' ),
				'section'     => 'leafpress_sidebar',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 3,
					'max'  => 20,
					'step' => 1,
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_tagcloud_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_tagcloud_title',
			array(
				'label'   => esc_html__( '标签云标题', 'leafpress-digest' ),
				'section' => 'leafpress_sidebar',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_tagcloud_count',
			array(
				'default'           => 30,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_tagcloud_count',
			array(
				'label'       => esc_html__( '标签数量', 'leafpress-digest' ),
				'section'     => 'leafpress_sidebar',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 5,
					'max'  => 60,
					'step' => 1,
				),
			)
		);

		/* ------------------------------------------------------------------
		 * 分区六：邮件订阅
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_subscribe',
			array(
				'title'       => esc_html__( '邮件订阅', 'leafpress-digest' ),
				'description' => esc_html__( '订阅数据存放在「简报 → 订阅者」菜单下，可导出 CSV。', 'leafpress-digest' ),
				'panel'       => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_subscribe_enabled',
			array(
				'default'           => true,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_subscribe_enabled',
			array(
				'label'   => esc_html__( '显示订阅表单', 'leafpress-digest' ),
				'section' => 'leafpress_subscribe',
				'type'    => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'leafpress_subscribe_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_subscribe_title',
			array(
				'label'   => esc_html__( '表单标题', 'leafpress-digest' ),
				'section' => 'leafpress_subscribe',
				'type'    => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_subscribe_text',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_subscribe_text',
			array(
				'label'   => esc_html__( '表单说明文字', 'leafpress-digest' ),
				'section' => 'leafpress_subscribe',
				'type'    => 'textarea',
			)
		);

		$wp_customize->add_setting(
			'leafpress_subscribe_inline',
			array(
				'default'           => true,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_subscribe_inline',
			array(
				'label'       => esc_html__( '在文章底部追加订阅表单', 'leafpress-digest' ),
				'description' => esc_html__( '关闭后仅在侧栏显示订阅表单。', 'leafpress-digest' ),
				'section'     => 'leafpress_subscribe',
				'type'        => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'leafpress_subscribe_require_confirm',
			array(
				'default'           => false,
				'sanitize_callback' => 'leafpress_sanitize_checkbox',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_subscribe_require_confirm',
			array(
				'label'       => esc_html__( '需要确认邮件（双 opt-in）', 'leafpress-digest' ),
				'description' => esc_html__( '开启后新订阅者状态为「待确认」，需在「简报 → 订阅者」中改为「已订阅」后才算生效。', 'leafpress-digest' ),
				'section'     => 'leafpress_subscribe',
				'type'        => 'checkbox',
			)
		);

		/* ------------------------------------------------------------------
		 * 分区七：简报
		 * ------------------------------------------------------------------ */
		$wp_customize->add_section(
			'leafpress_digest',
			array(
				'title' => esc_html__( '简报', 'leafpress-digest' ),
				'panel' => 'leafpress_panel',
			)
		);

		$wp_customize->add_setting(
			'leafpress_digest_issue_label',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_digest_issue_label',
			array(
				'label'       => esc_html__( '期号前缀', 'leafpress-digest' ),
				'description' => esc_html__( '留空使用「第 N 期」。', 'leafpress-digest' ),
				'section'     => 'leafpress_digest',
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_digest_archive_title',
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_digest_archive_title',
			array(
				'label'       => esc_html__( '简报归档页标题', 'leafpress-digest' ),
				'description' => esc_html__( '留空使用「简报」。', 'leafpress-digest' ),
				'section'     => 'leafpress_digest',
				'type'        => 'text',
			)
		);

		$wp_customize->add_setting(
			'leafpress_excerpt_length',
			array(
				'default'           => 40,
				'sanitize_callback' => 'leafpress_sanitize_number',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_excerpt_length',
			array(
				'label'   => esc_html__( '摘要长度（词）', 'leafpress-digest' ),
				'section' => 'leafpress_digest',
				'type'    => 'number',
				'input_attrs' => array(
					'min'  => 10,
					'max'  => 150,
					'step' => 5,
				),
			)
		);

		$wp_customize->add_setting(
			'leafpress_card_style_default',
			array(
				'default'           => 'standard',
				'sanitize_callback' => 'leafpress_sanitize_card_style',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'leafpress_card_style_default',
			array(
				'label'       => esc_html__( '默认卡片样式', 'leafpress-digest' ),
				'description' => esc_html__( '用于没有单独指定样式的区块。', 'leafpress-digest' ),
				'section'     => 'leafpress_digest',
				'type'        => 'select',
				'choices'     => array(
					'standard'   => esc_html__( '标准图文', 'leafpress-digest' ),
					'feature'    => esc_html__( '大图', 'leafpress-digest' ),
					'horizontal' => esc_html__( '横向图文', 'leafpress-digest' ),
					'compact'    => esc_html__( '紧凑文字', 'leafpress-digest' ),
				),
			)
		);
	}
}
add_action( 'customize_register', 'leafpress_customize_register' );

if ( ! function_exists( 'leafpress_customize_blogname' ) ) {
	/**
	 * 站点标题即时刷新。
	 *
	 * @return void
	 */
	function leafpress_customize_blogname() {
		bloginfo( 'name', 'display' );
	}
}

if ( ! function_exists( 'leafpress_customize_blogdescription' ) ) {
	/**
	 * 站点描述即时刷新。
	 *
	 * @return void
	 */
	function leafpress_customize_blogdescription() {
		bloginfo( 'description', 'display' );
	}
}

if ( ! function_exists( 'leafpress_customize_preview_js' ) ) {
	/**
	 * 加载 Customizer 预览脚本。
	 *
	 * @return void
	 */
	function leafpress_customize_preview_js() {
		wp_enqueue_script(
			'leafpress-customizer-preview',
			get_template_directory_uri() . '/assets/js/customizer-preview.js',
			array( 'customize-preview' ),
			leafpress_asset_version( 'assets/js/customizer-preview.js' ),
			true
		);
	}
}
add_action( 'customize_preview_init', 'leafpress_customize_preview_js' );

if ( ! function_exists( 'leafpress_sanitize_checkbox' ) ) {
	/**
	 * 复选框清洗。
	 *
	 * @param mixed $checked 输入值。
	 * @return bool
	 */
	function leafpress_sanitize_checkbox( $checked ) {
		return ( isset( $checked ) && true === (bool) $checked );
	}
}

if ( ! function_exists( 'leafpress_sanitize_number' ) ) {
	/**
	 * 整数清洗。
	 *
	 * @param mixed $value 输入值。
	 * @return int
	 */
	function leafpress_sanitize_number( $value ) {
		return (int) $value;
	}
}

if ( ! function_exists( 'leafpress_sanitize_card_style' ) ) {
	/**
	 * 卡片样式白名单。
	 *
	 * @param mixed $value 输入值。
	 * @return string
	 */
	function leafpress_sanitize_card_style( $value ) {
		$allowed = array( 'standard', 'feature', 'horizontal', 'compact' );
		$value   = is_string( $value ) ? $value : 'standard';

		return in_array( $value, $allowed, true ) ? $value : 'standard';
	}
}

if ( ! function_exists( 'leafpress_sanitize_popular_order' ) ) {
	/**
	 * 热门排序白名单。
	 *
	 * @param mixed $value 输入值。
	 * @return string
	 */
	function leafpress_sanitize_popular_order( $value ) {
		$allowed = array( 'views', 'comments' );
		$value   = is_string( $value ) ? $value : 'views';

		return in_array( $value, $allowed, true ) ? $value : 'views';
	}
}

if ( ! function_exists( 'leafpress_sanitize_id_list' ) ) {
	/**
	 * 逗号分隔的正整数 ID 列表清洗。
	 *
	 * @param mixed $value 输入值。
	 * @return string
	 */
	function leafpress_sanitize_id_list( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$parts = explode( ',', $value );
		$ids   = array();

		foreach ( $parts as $part ) {
			$id = absint( trim( $part ) );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return implode( ',', array_unique( $ids ) );
	}
}
