<?php
/**
 * Leafpress Digest 邮件订阅系统。
 *
 * 职责：
 * 1. 注册前台订阅表单的 AJAX / 普通 POST 处理器（带 Nonce + 蜜罐反 spam）
 * 2. 数据落库：写入 digest_subscriber 类型，邮箱存 post_title，详情存 post_meta
 * 3. 后台菜单：查看、删除、导出 CSV
 *
 * 安全约定：
 * - 所有写操作校验 Nonce + 权限
 * - 邮箱用 is_email() + sanitize_email() 双重校验
 * - 插入数据库一律走 $wpdb->prepare() 或 WordPress 数据 API
 *
 * @package LeafpressDigest
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leafpress_subscriber_nonce_action' ) ) {
	/**
	 * 订阅表单的 Nonce action 名。
	 *
	 * @return string
	 */
	function leafpress_subscriber_nonce_action() {
		return 'leafpress_subscribe';
	}
}

if ( ! function_exists( 'leafpress_normalize_email' ) ) {
	/**
	 * 规范化并校验邮箱。
	 *
	 * @param string $email 原始邮箱。
	 * @return string 合法邮箱；不合法时返回空字符串。
	 */
	function leafpress_normalize_email( $email ) {
		$email = sanitize_email( trim( (string) $email ) );

		// sanitize_email 会去掉非法字符，这里再确认格式。
		if ( '' === $email || ! is_email( $email ) ) {
			return '';
		}

		// 统一转小写，避免同一邮箱重复订阅。
		return strtolower( $email );
	}
}

if ( ! function_exists( 'leafpress_find_subscriber' ) ) {
	/**
	 * 按邮箱查找已有订阅记录。
	 *
	 * 用 post_title 精确查询；表名与字段名来自 WordPress 核心，
	 * 不含用户输入，因此无需 prepare。
	 *
	 * @param string $email 规范化后的邮箱。
	 * @return int 记录 ID，未找到时返回 0。
	 */
	function leafpress_find_subscriber( $email ) {
		global $wpdb;

		$email = leafpress_normalize_email( $email );

		if ( '' === $email ) {
			return 0;
		}

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status = 'publish' LIMIT 1",
				$email,
				'digest_subscriber'
			)
		);

		return $post_id ? (int) $post_id : 0;
	}
}

if ( ! function_exists( 'leafpress_create_subscriber' ) ) {
	/**
	 * 创建或更新一条订阅记录。
	 *
	 * @param string $email     邮箱。
	 * @param string $source    订阅来源（表单位置标识）。
	 * @param string $status    状态：active / pending / unsubscribed。
	 * @param array  $extra     附加字段：name / ip_hash / user_agent。
	 * @return int|WP_Error 记录 ID 或错误对象。
	 */
	function leafpress_create_subscriber( $email, $source = 'unknown', $status = 'active', $extra = array() ) {
		$email = leafpress_normalize_email( $email );

		if ( '' === $email ) {
			return new WP_Error( 'leafpress_invalid_email', __( '邮箱格式不正确，请检查后重试。', 'leafpress-digest' ) );
		}

		$defaults = array(
			'name'       => '',
			'ip_hash'    => '',
			'user_agent' => '',
		);

		$extra = wp_parse_args( $extra, $defaults );

		$allowed_status = array( 'active', 'pending', 'unsubscribed' );

		if ( ! in_array( $status, $allowed_status, true ) ) {
			$status = 'active';
		}

		$existing = leafpress_find_subscriber( $email );

		$postarr = array(
			'post_type'   => 'digest_subscriber',
			'post_status' => 'publish',
			'post_title'  => $email,
		);

		if ( $existing > 0 ) {
			$postarr['ID'] = $existing;
		}

		// digest_subscriber 禁止 create_posts，这里以程序方式插入是允许的。
		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_leafpress_email', $email );
		update_post_meta( $post_id, '_leafpress_source', sanitize_text_field( $source ) );
		update_post_meta( $post_id, '_leafpress_status', $status );
		update_post_meta( $post_id, '_leafpress_ip_hash', sanitize_text_field( $extra['ip_hash'] ) );
		update_post_meta( $post_id, '_leafpress_agent', sanitize_text_field( $extra['user_agent'] ) );

		if ( '' !== $extra['name'] ) {
			update_post_meta( $post_id, '_leafpress_name', sanitize_text_field( $extra['name'] ) );
		}

		update_post_meta( $post_id, '_leafpress_updated', current_time( 'mysql' ) );

		return (int) $post_id;
	}
}

if ( ! function_exists( 'leafpress_hash_ip' ) ) {
	/**
	 * 记录订阅来源 IP 的哈希（不存明文 IP）。
	 *
	 * @return string 32 位哈希，空字符串表示无法获取。
	 */
	function leafpress_hash_ip() {
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( '' === $ip ) {
			return '';
		}

		// 混入站点 salt，让哈希无法被彩虹表反推。
		$salt = wp_salt( 'nonce' );

		return substr( md5( $ip . $salt ), 0, 32 );
	}
}

if ( ! function_exists( 'leafpress_handle_subscribe' ) ) {
	/**
	 * 处理订阅请求。
	 *
	 * 同时服务 AJAX 与普通 POST 两种提交方式。
	 *
	 * @return array{success:bool,message:string,code:string}
	 */
	function leafpress_handle_subscribe() {
		// 蜜罐字段：正常用户不会填，填了就是机器人。
		$honeypot = isset( $_POST['leafpress_website'] ) ? trim( (string) wp_unslash( $_POST['leafpress_website'] ) ) : '';

		if ( '' !== $honeypot ) {
			// 静默返回成功，不给机器人反馈信号。
			return array(
				'success' => true,
				'message' => __( '订阅成功，请查收确认邮件。', 'leafpress-digest' ),
				'code'    => 'ok',
			);
		}

		$nonce = isset( $_POST['leafpress_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['leafpress_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, leafpress_subscriber_nonce_action() ) ) {
			return array(
				'success' => false,
				'message' => __( '请求已失效，请刷新页面后重试。', 'leafpress-digest' ),
				'code'    => 'invalid_nonce',
			);
		}

		$raw_email = isset( $_POST['leafpress_email'] ) ? wp_unslash( $_POST['leafpress_email'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- 下方规范化。
		$email     = leafpress_normalize_email( is_string( $raw_email ) ? $raw_email : '' );

		if ( '' === $email ) {
			return array(
				'success' => false,
				'message' => __( '请填写有效的邮箱地址。', 'leafpress-digest' ),
				'code'    => 'invalid_email',
			);
		}

		// 频率限制：同一 IP 在冷却时间内只能提交一次。
		$ip_hash    = leafpress_hash_ip();
		$transient  = 'leafpress_sub_' . md5( $email . '|' . $ip_hash );
		$last_time  = (int) get_transient( $transient );
		$cooldown   = (int) apply_filters( 'leafpress_subscribe_cooldown', 30 );

		if ( $cooldown > 0 && $last_time > 0 && ( time() - $last_time ) < $cooldown ) {
			return array(
				'success' => false,
				'message' => __( '提交太频繁了，请稍后再试。', 'leafpress-digest' ),
				'code'    => 'too_frequent',
			);
		}

		set_transient( $transient, time(), $cooldown + 60 );

		$name    = isset( $_POST['leafpress_name'] ) ? sanitize_text_field( wp_unslash( $_POST['leafpress_name'] ) ) : '';
		$source  = isset( $_POST['leafpress_source'] ) ? sanitize_key( wp_unslash( $_POST['leafpress_source'] ) ) : 'unknown';
		$agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// 双 opt-in 时先落为 pending。
		$require_confirm = (bool) leafpress_get_option( 'subscribe_require_confirm' );
		$status          = $require_confirm ? 'pending' : 'active';

		$already = leafpress_find_subscriber( $email );

		$result = leafpress_create_subscriber(
			$email,
			$source,
			$status,
			array(
				'name'       => $name,
				'ip_hash'    => $ip_hash,
				'user_agent' => $agent,
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => __( '订阅保存失败，请稍后重试。', 'leafpress-digest' ),
				'code'    => 'save_failed',
			);
		}

		/**
		 * 订阅成功后的钩子，可用于对接邮件服务。
		 *
		 * @param int    $result  订阅记录 ID。
		 * @param string $email   邮箱。
		 * @param string $status  状态。
		 * @param bool   $renewed 此前是否已存在记录。
		 */
		do_action( 'leafpress_subscribed', $result, $email, $status, $already > 0 );

		if ( $already > 0 ) {
			return array(
				'success' => true,
				'message' => __( '这个邮箱已经在订阅列表里了，无需重复提交。', 'leafpress-digest' ),
				'code'    => 'already_subscribed',
			);
		}

		if ( $require_confirm ) {
			return array(
				'success' => true,
				'message' => __( '订阅请求已收到，请查收确认邮件完成订阅。', 'leafpress-digest' ),
				'code'    => 'pending',
			);
		}

		return array(
			'success' => true,
			'message' => __( '订阅成功，欢迎加入。', 'leafpress-digest' ),
			'code'    => 'ok',
		);
	}
}

if ( ! function_exists( 'leafpress_ajax_subscribe' ) ) {
	/**
	 * AJAX 订阅处理器。
	 *
	 * @return void
	 */
	function leafpress_ajax_subscribe() {
		$result = leafpress_handle_subscribe();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		}

		wp_send_json_error( $result, 400 );
	}
}
add_action( 'wp_ajax_leafpress_subscribe', 'leafpress_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_leafpress_subscribe', 'leafpress_ajax_subscribe' );

if ( ! function_exists( 'leafpress_render_subscribe_form' ) ) {
	/**
	 * 渲染订阅表单。
	 *
	 * @param array $args 参数：
	 *                     - source   表单来源标识（用于统计）
	 *                     - compact  紧凑样式（侧栏用）
	 *                     - title    标题
	 *                     - text     说明文字
	 * @return void
	 */
	function leafpress_render_subscribe_form( $args = array() ) {
		$defaults = array(
			'source'  => 'sidebar',
			'compact' => true,
			'title'   => '',
			'text'    => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$form_id = 'leafpress-sub-' . sanitize_html_class( $args['source'] );

		$title = $args['title'];
		$text  = $args['text'];

		if ( '' === $title ) {
			$title = (string) leafpress_get_option( 'subscribe_title' );
		}

		if ( '' === $title ) {
			$title = esc_html__( '订阅每日简报', 'leafpress-digest' );
		}

		if ( '' === $text ) {
			$text = (string) leafpress_get_option( 'subscribe_text' );
		}

		if ( '' === $text ) {
			$text = esc_html__( '每周一封，汇总本周值得读的东西。', 'leafpress-digest' );
		}

		$classes = 'lp-subscribe';

		if ( $args['compact'] ) {
			$classes .= ' lp-subscribe--compact';
		}

		?>
		<form
			class="<?php echo esc_attr( $classes ); ?>"
			data-leafpress-form
			data-source="<?php echo esc_attr( $args['source'] ); ?>"
			action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			method="post"
		>
			<h3 class="lp-subscribe__title"><?php echo esc_html( $title ); ?></h3>
			<p class="lp-subscribe__text"><?php echo esc_html( $text ); ?></p>

			<input type="hidden" name="action" value="leafpress_subscribe">
			<input type="hidden" name="leafpress_source" value="<?php echo esc_attr( $args['source'] ); ?>">
			<?php wp_nonce_field( leafpress_subscriber_nonce_action(), 'leafpress_nonce' ); ?>

			<div class="lp-subscribe__field">
				<label for="<?php echo esc_attr( $form_id ); ?>">
					<?php esc_html_e( '邮箱地址', 'leafpress-digest' ); ?>
				</label>
				<input
					type="email"
					id="<?php echo esc_attr( $form_id ); ?>"
					name="leafpress_email"
					placeholder="<?php esc_attr_e( 'you@example.com', 'leafpress-digest' ); ?>"
					required
					autocomplete="email"
				>
			</div>

			<div class="lp-subscribe__field">
				<label for="<?php echo esc_attr( $form_id ); ?>-name">
					<?php esc_html_e( '称呼（选填）', 'leafpress-digest' ); ?>
				</label>
				<input
					type="text"
					id="<?php echo esc_attr( $form_id ); ?>-name"
					name="leafpress_name"
					autocomplete="name"
				>
			</div>

			<?php // 蜜罐字段：对真实用户不可见，机器人会填充。 ?>
			<div class="lp-subscribe__honeypot" aria-hidden="true">
				<label for="<?php echo esc_attr( $form_id ); ?>-hp"><?php esc_html_e( '请留空', 'leafpress-digest' ); ?></label>
				<input
					type="text"
					id="<?php echo esc_attr( $form_id ); ?>-hp"
					name="leafpress_website"
					tabindex="-1"
					autocomplete="off"
				>
			</div>

			<button type="submit" class="lp-subscribe__submit">
				<?php esc_html_e( '订阅', 'leafpress-digest' ); ?>
			</button>

			<div class="lp-subscribe__result" data-leafpress-result aria-live="polite"></div>

			<p class="lp-subscribe__privacy">
				<?php esc_html_e( '我们只用这个地址发送简报，不会用于其他用途，随时可以退订。', 'leafpress-digest' ); ?>
			</p>
		</form>
		<?php
	}
}

if ( ! function_exists( 'leafpress_subscribe_shortcode' ) ) {
	/**
	 * 订阅表单短代码。
	 *
	 * 用法：`[leafpress_subscribe source="sidebar" compact="0"]`
	 *
	 * @param array $atts 短代码属性。
	 * @return string
	 */
	function leafpress_subscribe_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'source'  => 'shortcode',
				'compact' => 0,
				'title'   => '',
				'text'    => '',
			),
			$atts,
			'leafpress_subscribe'
		);

		ob_start();
		leafpress_render_subscribe_form(
			array(
				'source'  => sanitize_key( $atts['source'] ),
				'compact' => (bool) (int) $atts['compact'],
				'title'   => sanitize_text_field( $atts['title'] ),
				'text'    => sanitize_text_field( $atts['text'] ),
			)
		);

		return (string) ob_get_clean();
	}
}
add_shortcode( 'leafpress_subscribe', 'leafpress_subscribe_shortcode' );

if ( ! function_exists( 'leafpress_subscriber_admin_menu' ) ) {
	/**
	 * 在「简报」菜单下添加订阅者子菜单（含导出入口）。
	 *
	 * @return void
	 */
	function leafpress_subscriber_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=digest',
			esc_html__( '订阅者', 'leafpress-digest' ),
			esc_html__( '订阅者', 'leafpress-digest' ),
			'edit_posts',
			'edit.php?post_type=digest_subscriber'
		);

		add_submenu_page(
			'edit.php?post_type=digest',
			esc_html__( '导出订阅者 CSV', 'leafpress-digest' ),
			esc_html__( '导出 CSV', 'leafpress-digest' ),
			'export_private_posts',
			'leafpress_export_csv'
		);
	}
}
add_action( 'admin_menu', 'leafpress_subscriber_admin_menu' );

if ( ! function_exists( 'leafpress_export_csv' ) ) {
	/**
	 * 导出订阅者为 CSV。
	 *
	 * 用 fputcsv() 写入 php://output，逐条查询避免一次性载入全部记录。
	 * 输出前会终止脚本，防止 WordPress 在 CSV 后追加空白。
	 *
	 * @return void
	 */
	function leafpress_export_csv() {
		if ( ! current_user_can( 'export_private_posts' ) ) {
			wp_die(
				esc_html__( '你没有权限导出订阅者数据。', 'leafpress-digest' ),
				esc_html__( '权限不足', 'leafpress-digest' ),
				array( 'response' => 403 )
			);
		}

		// Nonce 校验，防止构造链接被他人触发。
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'leafpress_export' ) ) {
			wp_die(
				esc_html__( '导出链接已失效，请返回后台重新点击导出。', 'leafpress-digest' ),
				esc_html__( '链接失效', 'leafpress-digest' ),
				array( 'response' => 403 )
			);
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

		$query_args = array(
			'post_type'      => 'digest_subscriber',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'paged'          => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => '',
		);

		if ( in_array( $status, array( 'active', 'pending', 'unsubscribed' ), true ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_leafpress_status',
					'value' => $status,
				),
			);
		}

		$query = new WP_Query( $query_args );

		$filename = 'leafpress-subscribers-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		// 写入 UTF-8 BOM，让 Excel 正确识别中文。
		echo "\xEF\xBB\xBF";

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( '无法开启输出流。', 'leafpress-digest' ) );
		}

		fputcsv( $output, array( '邮箱', '称呼', '状态', '来源', '订阅时间', '最后更新' ) );

		$status_labels = array(
			'active'       => '已订阅',
			'pending'      => '待确认',
			'unsubscribed' => '已退订',
		);

		$page = 1;

		do {
			foreach ( $query->posts as $subscriber ) {
				$post_status = get_post_meta( $subscriber->ID, '_leafpress_status', true );
				$created     = get_post_time( 'U', true, $subscriber->ID );
				$updated     = get_post_meta( $subscriber->ID, '_leafpress_updated', true );

				fputcsv(
					$output,
					array(
						get_post_meta( $subscriber->ID, '_leafpress_email', true ),
						get_post_meta( $subscriber->ID, '_leafpress_name', true ),
						isset( $status_labels[ $post_status ] ) ? $status_labels[ $post_status ] : $post_status,
						get_post_meta( $subscriber->ID, '_leafpress_source', true ),
						$created ? gmdate( 'Y-m-d H:i:s', $created ) : '',
						is_string( $updated ) && '' !== $updated ? $updated : '',
					)
				);
			}

			$page++;
			$query = new WP_Query( array_merge( $query_args, array( 'paged' => $page ) ) );
		} while ( $query->have_posts() );

		fclose( $output );

		// CSV 已完整输出，终止后续渲染。
		exit;
	}
}

if ( ! function_exists( 'leafpress_subscriber_row_actions' ) ) {
	/**
	 * 在订阅者列表行添加「导出 CSV」链接。
	 *
	 * @param array $actions 行操作链接。
	 * @param WP_Post $post  当前记录。
	 * @return array
	 */
	function leafpress_subscriber_row_actions( $actions, $post ) {
		if ( 'digest_subscriber' !== $post->post_type ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?page=leafpress_export_csv' ),
			'leafpress_export'
		);

		$actions['leafpress_export'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( '导出 CSV', 'leafpress-digest' )
		);

		return $actions;
	}
}
add_filter( 'post_row_actions', 'leafpress_subscriber_row_actions', 10, 2 );

if ( ! function_exists( 'leafpress_subscriber_admin_notice' ) ) {
	/**
	 * 订阅者列表页顶部的统计提示。
	 *
	 * @return void
	 */
	function leafpress_subscriber_admin_notice() {
		global $wpdb;

		$screen = get_current_screen();

		if ( ! $screen || 'digest_subscriber' !== $screen->post_type ) {
			return;
		}

		$counts = wp_count_posts( 'digest_subscriber' );
		$total  = isset( $counts->publish ) ? (int) $counts->publish : 0;

		$status_counts = $GLOBALS['wpdb']->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT meta_value AS status, COUNT( post_id ) AS total
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				GROUP BY meta_value",
				'_leafpress_status'
			),
			OBJECT_K
		);

		$active = 0;

		if ( is_array( $status_counts ) && isset( $status_counts['active'] ) ) {
			$active = (int) $status_counts['active']->total;
		}

		echo '<div class="notice notice-info is-dismissible">';
		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: 1：总数，2：已订阅数。 */
					__( '共 %1$d 位订阅者，其中 %2$d 位处于已订阅状态。「简报 → 导出 CSV」可下载完整名单。', 'leafpress-digest' ),
					$total,
					$active
				)
			)
		);
		echo '</div>';
	}
}
add_action( 'admin_notices', 'leafpress_subscriber_admin_notice' );
