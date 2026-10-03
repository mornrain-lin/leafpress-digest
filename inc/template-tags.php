<?php
/**
 * Leafpress Digest 模板标签。
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leafpress_card_style_classes' ) ) {
	/**
	 * 卡片样式 class 表。
	 *
	 * @return array<string,string>
	 */
	function leafpress_card_style_classes() {
		return array(
			'standard'  => '',
			'feature'   => 'lp-card--feature',
			'horizontal' => 'lp-card--horizontal',
			'compact'   => 'lp-card--compact',
		);
	}
}

if ( ! function_exists( 'leafpress_card' ) ) {
	/**
	 * 输出文章卡片。
	 *
	 * 四种样式：
	 * - standard   标准图文卡片
	 * - feature    大图卡片（更大的标题与摘要）
	 * - horizontal 横向图文卡片
	 * - compact    紧凑纯文字卡片
	 *
	 * @param array $args 参数：
	 *                     - post  WP_Post 或 ID
	 *                     - style 卡片样式
	 * @return void
	 */
	function leafpress_card( $args = array() ) {
		$defaults = array(
			'post'  => null,
			'style' => 'standard',
		);

		$args = wp_parse_args( $args, $defaults );

		$post = get_post( $args['post'] );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$permalink = get_permalink( $post );

		if ( ! $permalink ) {
			return;
		}

		$styles = leafpress_card_style_classes();
		$style  = isset( $styles[ $args['style'] ] ) ? $args['style'] : 'standard';

		$class = 'lp-card lp-card--' . $style;

		$categories = get_the_category( $post );

		$kicker = '';

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$kicker = get_category_link( $categories[0]->term_id );

			if ( ! $kicker ) {
				$kicker = '#';
			}
		}

		$title_attr = the_title_attribute( array( 'echo' => false, 'post' => $post ) );

		?>
		<article id="card-<?php echo esc_attr( (string) $post->ID ); ?>" class="<?php echo esc_attr( $class ); ?>">
			<?php
			$show_media = ( 'compact' !== $style ) && has_post_thumbnail( $post );

			if ( $show_media ) :
				?>
				<div class="lp-card__media">
					<a href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
						<?php
						echo get_the_post_thumbnail(
							$post,
							'leafpress-card',
							array(
								'loading'  => 'lazy',
								'decoding' => 'async',
								'alt'      => $title_attr,
							)
						);
						?>
					</a>
				</div>
			<?php endif; ?>

			<div class="lp-card__body">
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
					<a class="lp-card__kicker" href="<?php echo esc_url( (string) $kicker ); ?>">
						<?php echo esc_html( $categories[0]->name ); ?>
					</a>
				<?php endif; ?>

				<h3 class="lp-card__title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
				</h3>

				<?php if ( 'compact' !== $style ) : ?>
					<?php
					$excerpt = leafpress_get_excerpt( $post );

					if ( '' !== $excerpt ) {
						echo '<p class="lp-card__excerpt">' . esc_html( $excerpt ) . '</p>';
					}
					?>
				<?php endif; ?>

				<?php leafpress_card_meta( $post ); ?>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'leafpress_card_meta' ) ) {
	/**
	 * 输出卡片元信息。
	 *
	 * @param WP_Post $post 文章对象。
	 * @return void
	 */
	function leafpress_card_meta( $post ) {
		$permalink = get_permalink( $post );

		$created = get_post_time( 'U', true, $post );

		echo '<ul class="lp-card__meta">';

		if ( $created ) {
			printf(
				'<li><a href="%1$s" rel="bookmark"><time datetime="%2$s">%3$s</time></a></li>',
				esc_url( (string) $permalink ),
				esc_attr( gmdate( DATE_W3C, $created ) ),
				esc_html( mysql2date( 'Y-m-d', (string) $created ) )
			);
		}

		$author_id = (int) $post->post_author;

		if ( $author_id > 0 ) {
			printf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( (string) get_author_posts_url( $author_id ) ),
				esc_html( get_the_author_meta( 'display_name', $author_id ) )
			);
		}

		$comments = (int) get_comments_number( $post );

		if ( $comments > 0 && comments_open( $post ) ) {
			printf(
				'<li><span>%s</span></li>',
				esc_html(
					sprintf(
						/* translators: %s：评论数。 */
						_n( '%s 条评论', '%s 条评论', $comments, 'leafpress-digest' ),
						number_format_i18n( $comments )
					)
				)
			);
		}

		echo '</ul>';
	}
}

if ( ! function_exists( 'leafpress_get_excerpt' ) ) {
	/**
	 * 获取文章摘要纯文本。
	 *
	 * @param WP_Post $post 文章对象。
	 * @return string
	 */
	function leafpress_get_excerpt( $post = null ) {
		$post = get_post( $post );

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$length = (int) leafpress_get_option( 'excerpt_length' );

		if ( $length < 10 ) {
			$length = 40;
		}

		if ( '' !== trim( (string) $post->post_excerpt ) ) {
			return wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ) );
		}

		$content = $post->post_password_required() ? '' : $post->post_content;
		$content = strip_shortcodes( $content );
		$content = excerpt_remove_blocks( $content );

		return wp_trim_words( wp_strip_all_tags( $content ), $length, '…' );
	}
}

if ( ! function_exists( 'leafpress_headline_section' ) ) {
	/**
	 * 输出头条区（大图头条 + 次条列表）。
	 *
	 * @param int $lead_count    头条数量（固定 1，传参仅为可读性）。
	 * @param int $secondary_count 次条数量。
	 * @return void
	 */
	function leafpress_headline_section( $lead_count = 1, $secondary_count = 4 ) {
		$lead_query = new WP_Query(
			array(
				'post_type'           => array( 'post', 'digest' ),
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $lead_count ),
				'ignore_sticky_posts' => false,
				'no_found_rows'       => true,
			)
		);

		$secondary_query = new WP_Query(
			array(
				'post_type'           => array( 'post', 'digest' ),
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $secondary_count ),
				'post__not_in'        => wp_list_pluck( $lead_query->posts, 'ID' ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		if ( ! $lead_query->have_posts() ) {
			wp_reset_postdata();
			return;
		}

		$lead_query->the_post();
		$lead_post = get_post();

		?>
		<section class="lp-headline" aria-labelledby="lp-headline-label">
			<span class="lp-headline__label" id="lp-headline-label">
				<?php esc_html_e( '今日头条', 'leafpress-digest' ); ?>
			</span>

			<div class="lp-headline__grid">

				<div class="lp-headline__lead">
					<?php if ( has_post_thumbnail( $lead_post ) ) : ?>
						<div class="lp-card__media">
							<a href="<?php echo esc_url( (string) get_permalink( $lead_post ) ); ?>" tabindex="-1" aria-hidden="true">
								<?php
								echo get_the_post_thumbnail(
									$lead_post,
									'leafpress-headline',
									array(
										'decoding' => 'async',
										'alt'      => the_title_attribute( array( 'echo' => false, 'post' => $lead_post ) ),
									)
								);
								?>
							</a>
						</div>
					<?php endif; ?>

					<h2 class="lp-headline__lead-title">
						<a href="<?php echo esc_url( (string) get_permalink( $lead_post ) ); ?>">
							<?php echo esc_html( get_the_title( $lead_post ) ); ?>
						</a>
					</h2>

					<?php
					$lead_excerpt = leafpress_get_excerpt( $lead_post );

					if ( '' !== $lead_excerpt ) {
						echo '<p class="lp-headline__lead-excerpt">' . esc_html( $lead_excerpt ) . '</p>';
					}

					leafpress_card_meta( $lead_post );
					?>
				</div>

				<?php if ( $secondary_query->have_posts() ) : ?>
					<div class="lp-headline__secondary">
						<h3 class="lp-headline__secondary-title">
							<?php esc_html_e( '今日要闻', 'leafpress-digest' ); ?>
						</h3>
						<ol class="lp-headline__list">
							<?php
							while ( $secondary_query->have_posts() ) :
								$secondary_query->the_post();

								$created = get_post_time( 'U', true );
								?>
								<li>
									<h4 class="lp-headline__list-title">
										<a href="<?php echo esc_url( (string) get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
									</h4>
									<?php if ( $created ) : ?>
										<span class="lp-headline__list-meta">
											<time datetime="<?php echo esc_attr( gmdate( DATE_W3C, $created ) ); ?>">
												<?php echo esc_html( mysql2date( 'Y-m-d', (string) $created ) ); ?>
											</time>
										</span>
									<?php endif; ?>
								</li>
								<?php
							endwhile;
							?>
						</ol>
					</div>
				<?php endif; ?>

			</div>
		</section>
		<?php

		wp_reset_postdata();
	}
}

if ( ! function_exists( 'leafpress_section_header' ) ) {
	/**
	 * 输出区块标题条。
	 *
	 * @param string $title    标题。
	 * @param string $link     更多链接。
	 * @param string $link_text 链接文案。
	 * @return void
	 */
	function leafpress_section_header( $title, $link = '', $link_text = '' ) {
		if ( '' === $link_text ) {
			$link_text = esc_html__( '查看全部', 'leafpress-digest' );
		}

		echo '<div class="lp-section__header">';
		printf( '<h2 class="lp-section__title">%s</h2>', esc_html( $title ) );

		if ( '' !== $link ) {
			printf(
				'<a class="lp-section__more" href="%1$s">%2$s</a>',
				esc_url( $link ),
				esc_html( $link_text )
			);
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'leafpress_column_section' ) ) {
	/**
	 * 输出单个分类栏（内含指定数量的卡片）。
	 *
	 * @param WP_Term $term  分类对象。
	 * @param int     $count 卡片数量。
	 * @param string  $style 卡片样式。
	 * @return void
	 */
	function leafpress_column_section( $term, $count = 4, $style = 'compact' ) {
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $count ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'cat'                 => (int) $term->term_id,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			$link = '';
		}

		echo '<div class="lp-column">';
		leafpress_section_header( $term->name, (string) $link, esc_html__( '更多', 'leafpress-digest' ) );

		echo '<div class="lp-card-list">';

		while ( $query->have_posts() ) {
			$query->the_post();
			leafpress_card( array( 'style' => $style ) );
		}

		echo '</div></div>';

		wp_reset_postdata();
	}
}

if ( ! function_exists( 'leafpress_get_column_terms' ) ) {
	/**
	 * 获取首页三栏区块要展示的分类。
	 *
	 * 优先使用 Customizer 中配置的分类 ID，缺省时按文章数取前三个。
	 *
	 * @param int $limit 分类数量。
	 * @return WP_Term[]
	 */
	function leafpress_get_column_terms( $limit = 3 ) {
		$limit = max( 1, (int) $limit );

		$configured = (string) leafpress_get_option( 'column_categories' );
		$terms      = array();

		if ( '' !== $configured ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $configured ) ) );

			foreach ( $ids as $id ) {
				$term = get_term( $id, 'category' );

				if ( $term instanceof WP_Term ) {
					$terms[] = $term;
				}

				if ( count( $terms ) >= $limit ) {
					break;
				}
			}
		}

		if ( count( $terms ) >= $limit ) {
			return $terms;
		}

		// 补齐：按文章数排序取热门分类。
		$fallback = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'number'     => $limit * 2,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);

		if ( is_wp_error( $fallback ) ) {
			return $terms;
		}

		$existing = wp_list_pluck( $terms, 'term_id' );

		foreach ( $fallback as $term ) {
			if ( in_array( (int) $term->term_id, $existing, true ) ) {
				continue;
			}

			$terms[] = $term;

			if ( count( $terms ) >= $limit ) {
				break;
			}
		}

		return $terms;
	}
}

if ( ! function_exists( 'leafpress_digest_issue_number' ) ) {
	/**
	 * 计算简报的期号（第 N 期）。
	 *
	 * 以该类型已发布文章总数倒序推算，最新一期为最新的一篇。
	 *
	 * @param int $post_id 简报 ID。
	 * @return int 期号，无法计算时返回 0。
	 */
	function leafpress_digest_issue_number( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

		if ( $post_id <= 0 ) {
			return 0;
		}

		$counts = wp_count_posts( 'digest' );
		$total  = isset( $counts->publish ) ? (int) $counts->publish : 0;

		if ( $total <= 0 ) {
			return 0;
		}

		// 找出这篇文章在同一类型所有文章中的位置（按时间倒序）。
		$position = 0;

		$published = get_posts(
			array(
				'post_type'      => 'digest',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		foreach ( (array) $published as $index => $id ) {
			if ( (int) $id === $post_id ) {
				$position = $index;
				break;
			}
		}

		return $total - $position;
	}
}

if ( ! function_exists( 'leafpress_popular_widget' ) ) {
	/**
	 * 输出热门文章 Widget。
	 *
	 * @param array $args 参数：title / limit / post_type。
	 * @return void
	 */
	function leafpress_popular_widget( $args = array() ) {
		$defaults = array(
			'title'     => '',
			'limit'     => 6,
			'post_type' => 'any',
		);

		$args = wp_parse_args( $args, $defaults );

		$title = $args['title'];

		if ( '' === $title ) {
			$title = (string) leafpress_get_option( 'popular_title' );
		}

		if ( '' === $title ) {
			$title = esc_html__( '热门文章', 'leafpress-digest' );
		}

		$post_ids = leafpress_get_popular_posts( (int) $args['limit'], (string) $args['post_type'] );

		if ( empty( $post_ids ) ) {
			return;
		}

		echo '<section class="lp-widget lp-widget--popular">';
		printf( '<h2 class="lp-widget__title">%s</h2>', esc_html( $title ) );
		echo '<ol class="lp-popular">';

		foreach ( $post_ids as $popular_post ) {
			$permalink = get_permalink( $popular_post );

			if ( ! $permalink ) {
				continue;
			}

			$created = get_post_time( 'U', true, $popular_post );
			$views   = (int) get_post_meta( $popular_post->ID, '_leafpress_views', true );

			echo '<li>';
			printf(
				'<h3 class="lp-popular__title"><a href="%1$s">%2$s</a></h3>',
				esc_url( $permalink ),
				esc_html( get_the_title( $popular_post ) )
			);
			echo '<span class="lp-popular__meta">';

			if ( $created ) {
				printf(
					'<time datetime="%1$s">%2$s</time>',
					esc_attr( gmdate( DATE_W3C, $created ) ),
					esc_html( mysql2date( 'Y-m-d', (string) $created ) )
				);
			}

			if ( $views > 0 ) {
				echo ' · ';
				printf(
					/* translators: %s：浏览次数。 */
					esc_html( _n( '%s 次浏览', '%s 次浏览', $views, 'leafpress-digest' ) ),
					esc_html( number_format_i18n( $views ) )
				);
			}

			echo '</span></li>';
		}

		echo '</ol></section>';
	}
}

if ( ! function_exists( 'leafpress_tag_cloud_widget' ) ) {
	/**
	 * 输出标签云 Widget。
	 *
	 * @param array $args 参数：title / limit。
	 * @return void
	 */
	function leafpress_tag_cloud_widget( $args = array() ) {
		$defaults = array(
			'title' => '',
			'limit' => 30,
		);

		$args = wp_parse_args( $args, $defaults );

		$title = $args['title'];

		if ( '' === $title ) {
			$title = (string) leafpress_get_option( 'tagcloud_title' );
		}

		if ( '' === $title ) {
			$title = esc_html__( '热门标签', 'leafpress-digest' );
		}

		$tags = get_tags(
			array(
				'number'     => (int) $args['limit'],
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => true,
			)
		);

		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			return;
		}

		echo '<section class="lp-widget lp-widget--tagcloud">';
		printf( '<h2 class="lp-widget__title">%s</h2>', esc_html( $title ) );
		echo '<div class="lp-tag-cloud">';

		foreach ( $tags as $tag ) {
			$link = get_tag_link( $tag );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			printf(
				'<a href="%1$s" rel="tag">%2$s<span class="lp-tag-cloud__count">%3$s</span></a>',
				esc_url( $link ),
				esc_html( $tag->name ),
				esc_html( number_format_i18n( $tag->count ) )
			);
		}

		echo '</div></section>';
	}
}

if ( ! function_exists( 'leafpress_subscribe_widget' ) ) {
	/**
	 * 输出订阅 Widget。
	 *
	 * @param array $args 参数：title / text / source。
	 * @return void
	 */
	function leafpress_subscribe_widget( $args = array() ) {
		$defaults = array(
			'title'  => '',
			'text'   => '',
			'source' => 'sidebar',
		);

		$args = wp_parse_args( $args, $defaults );

		echo '<section class="lp-widget lp-widget--subscribe">';

		leafpress_render_subscribe_form(
			array(
				'source'  => sanitize_key( $args['source'] ),
				'compact' => true,
				'title'   => sanitize_text_field( $args['title'] ),
				'text'    => sanitize_text_field( $args['text'] ),
			)
		);

		echo '</section>';
	}
}

if ( ! function_exists( 'leafpress_sidebar' ) ) {
	/**
	 * 输出侧栏。
	 *
	 * 依次输出：自定义 Widget → 热门文章 → 标签云 → 订阅表单。
	 *
	 * @param string $sidebar_id Widget 区域 ID。
	 * @return void
	 */
	function leafpress_sidebar( $sidebar_id = 'sidebar-1' ) {
		// 简报页优先使用简报专用侧栏。
		$target = $sidebar_id;

		if ( is_singular( 'digest' ) && is_active_sidebar( 'sidebar-digest' ) ) {
			$target = 'sidebar-digest';
		}

		$builtin_enabled = (bool) leafpress_get_option( 'sidebar_builtin_widgets' );

		if ( ! is_active_sidebar( $target ) && ! $builtin_enabled ) {
			return;
		}

		echo '<aside class="lp-sidebar" aria-label="' . esc_attr__( '侧栏', 'leafpress-digest' ) . '">';

		if ( is_active_sidebar( $target ) ) {
			dynamic_sidebar( $target );
		}

		// 自定义 Widget 已有内容时，不再追加内置小组件，避免重复堆叠。
		if ( $builtin_enabled && ! is_active_sidebar( $target ) ) {
			leafpress_popular_widget( array( 'source' => 'sidebar' ) );
			leafpress_tag_cloud_widget();
			leafpress_subscribe_widget( array( 'source' => 'sidebar' ) );
		}

		echo '</aside>';
	}
}

if ( ! function_exists( 'leafpress_author_box' ) ) {
	/**
	 * 输出作者简介框。
	 *
	 * @return void
	 */
	function leafpress_author_box() {
		$author_id = (int) get_the_author_meta( 'ID' );

		if ( ! $author_id ) {
			return;
		}

		$description = get_the_author_meta( 'description', $author_id );

		echo '<div class="lp-author-box">';
		echo '<div class="lp-author-box__avatar">' . get_avatar( $author_id, 52, '', '', array( 'loading' => 'lazy' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar 自行转义。
		echo '<div class="lp-author-box__body">';
		printf( '<h3 class="lp-author-box__name">%s</h3>', esc_html( get_the_author_meta( 'display_name', $author_id ) ) );

		if ( $description ) {
			echo '<p class="lp-author-box__bio">' . esc_html( $description ) . '</p>';
		}

		printf(
			'<a class="lp-author-box__link" href="%1$s">%2$s</a>',
			esc_url( (string) get_author_posts_url( $author_id ) ),
			esc_html__( '查看全部文章', 'leafpress-digest' )
		);
		echo '</div></div>';
	}
}

if ( ! function_exists( 'leafpress_section_nav_toggle' ) ) {
	/**
	 * 输出移动端分区导航的展开按钮。
	 *
	 * @return void
	 */
	function leafpress_section_nav_toggle() {
		?>
		<button
			type="button"
			class="lp-nav-toggle"
			data-lp-nav-toggle
			aria-expanded="false"
			aria-controls="lp-section-nav"
		>
			<span class="lp-nav-toggle__bars" aria-hidden="true"></span>
			<span><?php esc_html_e( '分区', 'leafpress-digest' ); ?></span>
		</button>
		<?php
	}
}

if ( ! function_exists( 'leafpress_footer_widgets' ) ) {
	/**
	 * 输出页脚 Widget。
	 *
	 * @return void
	 */
	function leafpress_footer_widgets() {
		if ( ! is_active_sidebar( 'footer-1' ) && ! is_active_sidebar( 'footer-2' ) && ! is_active_sidebar( 'footer-3' ) ) {
			return;
		}

		echo '<div class="lp-footer-widgets">';

		for ( $i = 1; $i <= 3; $i++ ) {
			$id = 'footer-' . $i;

			if ( ! is_active_sidebar( $id ) ) {
				continue;
			}

			echo '<div class="lp-footer-widgets__col">';
			dynamic_sidebar( $id );
			echo '</div>';
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'leafpress_pagination' ) ) {
	/**
	 * 输出分页标记。
	 *
	 * @return void
	 */
	function leafpress_pagination() {
		global $wp_query;

		if ( ! $wp_query instanceof WP_Query || $wp_query->max_num_pages < 2 ) {
			return;
		}

		$links = paginate_links(
			array(
				'total'     => $wp_query->max_num_pages,
				'current'   => max( 1, (int) get_query_var( 'paged' ) ),
				'type'      => 'array',
				'mid_size'  => 1,
				'prev_text' => esc_html__( '上一页', 'leafpress-digest' ),
				'next_text' => esc_html__( '下一页', 'leafpress-digest' ),
			)
		);

		if ( ! $links ) {
			return;
		}

		echo '<nav class="lp-pagination" aria-label="' . esc_attr__( '分页导航', 'leafpress-digest' ) . '"><div class="nav-links">';

		foreach ( $links as $link ) {
			echo wp_kses_post( $link );
		}

		echo '</div></nav>';
	}
}

if ( ! function_exists( 'leafpress_breadcrumb' ) ) {
	/**
	 * 输出面包屑。
	 *
	 * @return void
	 */
	function leafpress_breadcrumb() {
		if ( is_front_page() ) {
			return;
		}

		$items = array(
			array(
				'url'   => home_url( '/' ),
				'label' => esc_html__( '首页', 'leafpress-digest' ),
			),
		);

		if ( is_singular() ) {
			$post_id = (int) get_the_ID();

			foreach ( array_reverse( (array) get_post_ancestors( $post_id ) ) as $ancestor_id ) {
				$items[] = array(
					'url'   => (string) get_permalink( $ancestor_id ),
					'label' => (string) get_the_title( $ancestor_id ),
				);
			}

			$items[] = array(
				'url'   => '',
				'label' => (string) get_the_title( $post_id ),
			);
		} elseif ( is_search() ) {
			$items[] = array(
				'url'   => '',
				/* translators: %s：搜索关键词。 */
				'label' => sprintf( esc_html__( '搜索「%s」', 'leafpress-digest' ), (string) get_search_query() ),
			);
		} elseif ( is_404() ) {
			$items[] = array(
				'url'   => '',
				'label' => esc_html__( '页面未找到', 'leafpress-digest' ),
			);
		} elseif ( is_archive() ) {
			$items[] = array(
				'url'   => '',
				'label' => wp_strip_all_tags( get_the_archive_title() ),
			);
		}

		$last = count( $items ) - 1;

		echo '<nav class="lp-breadcrumb-nav" aria-label="' . esc_attr__( '面包屑导航', 'leafpress-digest' ) . '"><ol class="lp-breadcrumb">';

		foreach ( $items as $index => $item ) {
			echo '<li>';

			if ( $index === $last || '' === $item['url'] ) {
				printf( '<span aria-current="page">%s</span>', esc_html( $item['label'] ) );
			} else {
				printf( '<a href="%1$s">%2$s</a>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
			}

			echo '</li>';
		}

		echo '</ol></nav>';
	}
}
