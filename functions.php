<?php
/**
 * Leafpress Digest 主题函数入口。
 *
 * 装配入口：主题支持、模块加载、Widget、前端资源、过滤器。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEAFPRESS_VERSION' ) ) {
	define( 'LEAFPRESS_VERSION', '1.0.0' );
}

if ( ! defined( 'LEAFPRESS_TEXT_DOMAIN' ) ) {
	define( 'LEAFPRESS_TEXT_DOMAIN', 'leafpress-digest' );
}

/**
 * 读取主题资源的版本号，用于前端缓存 busting。
 *
 * 优先使用文件的最后修改时间，文件不可读时回落到主题版本号。
 *
 * @param string $relative_path 相对主题根目录的路径。
 * @return string
 */
function leafpress_asset_version( $relative_path ) {
	$file = get_template_directory() . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $file ) ) {
		$mtime = filemtime( $file );

		if ( $mtime ) {
			return (string) $mtime;
		}
	}

	return LEAFPRESS_VERSION;
}

require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/subscribers.php';
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/popular.php';
require_once get_template_directory() . '/inc/customizer.php';


if ( ! function_exists( 'leafpress_setup' ) ) {
	/**
	 * 声明主题基础能力。
	 *
	 * @return void
	 */
	function leafpress_setup() {
		load_theme_textdomain( 'leafpress-digest', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-font-sizes' );

		add_theme_support( 'editor-styles' );

		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'navigation-widgets',
			)
		);

		add_theme_support(
			'custom-logo',
			array(
				'height'      => 80,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
				'header-text' => array( 'site-title', 'site-description' ),
			)
		);

		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'image', 'quote', 'status', 'video', 'audio' ) );

		add_editor_style( 'assets/css/editor-style.css' );

		add_image_size( 'leafpress-headline', 900, 560, true );
		add_image_size( 'leafpress-card', 640, 400, true );
		add_image_size( 'leafpress-thumb', 320, 200, true );
		add_image_size( 'leafpress-daily', 1160, 500, true );

		register_nav_menus(
			array(
				'primary'   => esc_html__( '分区导航', 'leafpress-digest' ),
				'top'       => esc_html__( '报头导航', 'leafpress-digest' ),
				'footer'    => esc_html__( '页脚导航', 'leafpress-digest' ),
				'category'  => esc_html__( '三栏分类导航', 'leafpress-digest' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'leafpress_setup' );

if ( ! function_exists( 'leafpress_content_width' ) ) {
	/**
	 * 设置正文宽度。
	 *
	 * @global int $content_width
	 * @return void
	 */
	function leafpress_content_width() {
		$width = 720;

		/**
		 * 过滤正文宽度。
		 *
		 * @param int $width 宽度（像素）。
		 */
		$width = (int) apply_filters( 'leafpress_content_width', $width );

		$GLOBALS['content_width'] = $width;
	}
}
add_action( 'after_setup_theme', 'leafpress_content_width', 0 );


if ( ! function_exists( 'leafpress_widgets_init' ) ) {
	/**
	 * 注册侧栏 1 个 + 页脚 3 个 + 简报专用侧栏 1 个。
	 *
	 * @return void
	 */
	function leafpress_widgets_init() {
		$defaults = array(
			'before_widget' => '<section id="%1$s" class="lp-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="lp-widget__title">',
			'after_title'   => '</h2>',
		);

		register_sidebar(
			array_merge(
				$defaults,
				array(
					'name'        => esc_html__( '侧栏 1（主侧栏）', 'leafpress-digest' ),
					'id'          => 'sidebar-1',
					'description' => esc_html__( '显示在首页、归档、搜索与文章页右侧。', 'leafpress-digest' ),
				)
			)
		);

		register_sidebar(
			array_merge(
				$defaults,
				array(
					'name'        => esc_html__( '侧栏 2（简报专用）', 'leafpress-digest' ),
					'id'          => 'sidebar-digest',
					'description' => esc_html__( '只在单篇简报（digest）页面显示。', 'leafpress-digest' ),
				)
			)
		);

		for ( $i = 1; $i <= 3; $i++ ) {
			register_sidebar(
				array_merge(
					$defaults,
					array(
						/* translators: %d：页脚栏序号。 */
						'name'        => sprintf( esc_html__( '页脚 %d', 'leafpress-digest' ), $i ),
						'id'          => 'footer-' . $i,
						/* translators: %d：页脚栏序号。 */
						'description' => sprintf( esc_html__( '页脚第 %d 栏。', 'leafpress-digest' ), $i ),
					)
				)
			);
		}
	}
}
add_action( 'widgets_init', 'leafpress_widgets_init' );


if ( ! function_exists( 'leafpress_scripts' ) ) {
	/**
	 * 加载前端样式与脚本。
	 *
	 * @return void
	 */
	function leafpress_scripts() {
		wp_enqueue_style(
			'leafpress-style',
			get_stylesheet_uri(),
			array(),
			leafpress_asset_version( 'style.css' )
		);

		wp_style_add_data( 'leafpress-style', 'rtl', 'replace' );

		wp_enqueue_script(
			'leafpress-navigation',
			get_template_directory_uri() . '/assets/js/navigation.js',
			array(),
			leafpress_asset_version( 'assets/js/navigation.js' ),
			true
		);

		// 订阅表单的 AJAX 增强。
		if ( leafpress_get_option( 'subscribe_enabled' ) ) {
			wp_enqueue_script(
				'leafpress-subscribe',
				get_template_directory_uri() . '/assets/js/subscribe.js',
				array(),
				leafpress_asset_version( 'assets/js/subscribe.js' ),
				true
			);

			wp_localize_script(
				'leafpress-subscribe',
				'leafpressL10n',
				array(
					'loading'      => esc_html__( '提交中…', 'leafpress-digest' ),
					'error'        => esc_html__( '订阅失败，请稍后重试。', 'leafpress-digest' ),
					'networkError' => esc_html__( '网络错误，请检查连接后重试。', 'leafpress-digest' ),
				)
			);
		}

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'leafpress_scripts' );

if ( ! function_exists( 'leafpress_body_classes' ) ) {
	/**
	 * 为 <body> 追加状态类。
	 *
	 * @param array $classes 现有类名。
	 * @return array
	 */
	function leafpress_body_classes( $classes ) {
		$classes[] = 'lp-site';

		if ( is_active_sidebar( 'sidebar-1' ) ) {
			$classes[] = 'lp-has-sidebar';
		} else {
			$classes[] = 'lp-no-sidebar';
		}

		if ( is_singular( 'digest' ) ) {
			$classes[] = 'lp-single-digest';
		}

		return $classes;
	}
}
add_filter( 'body_class', 'leafpress_body_classes' );

if ( ! function_exists( 'leafpress_excerpt_length' ) ) {
	/**
	 * 摘要长度。
	 *
	 * @param int $length 默认长度。
	 * @return int
	 */
	function leafpress_excerpt_length( $length ) {
		$custom = (int) leafpress_get_option( 'excerpt_length' );

		return $custom > 0 ? $custom : (int) $length;
	}
}
add_filter( 'excerpt_length', 'leafpress_excerpt_length' );

if ( ! function_exists( 'leafpress_excerpt_more' ) ) {
	/**
	 * 摘要省略符。
	 *
	 * @param string $more 默认省略符。
	 * @return string
	 */
	function leafpress_excerpt_more( $more ) {
		if ( is_admin() ) {
			return $more;
		}

		return '…';
	}
}
add_filter( 'excerpt_more', 'leafpress_excerpt_more' );

if ( ! function_exists( 'leafpress_pingback_header' ) ) {
	/**
	 * 输出 pingback 链接。
	 *
	 * @return void
	 */
	function leafpress_pingback_header() {
		if ( is_singular() && pings_open() ) {
			printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
		}
	}
}
add_action( 'wp_head', 'leafpress_pingback_header' );

if ( ! function_exists( 'leafpress_dynamic_css' ) ) {
	/**
	 * 输出 Customizer 生成的 CSS 变量。
	 *
	 * @return void
	 */
	function leafpress_dynamic_css() {
		$css = leafpress_build_dynamic_css();

		if ( '' === $css ) {
			return;
		}

		wp_add_inline_style( 'leafpress-style', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'leafpress_dynamic_css', 20 );

if ( ! function_exists( 'leafpress_woocommerce_declared' ) ) {
	/**
	 * 仅声明 WooCommerce 支持。
	 *
	 * @return void
	 */
	function leafpress_woocommerce_declared() {
		add_theme_support( 'woocommerce' );
	}
}
add_action( 'after_setup_theme', 'leafpress_woocommerce_declared' );
