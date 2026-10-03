# Leafpress Digest

资讯与简报聚合 WordPress 主题。报刊感排版、大图头条区、三栏分类区块、热门文章与标签云侧栏，内置完整的邮件订阅系统（后台可查看、导出 CSV）。零外部资源依赖。

- 文本域：`leafpress-digest`
- 版本：1.0.0
- 需要 WordPress：6.0 及以上
- 需要 PHP：7.4 及以上
- 测试到：WordPress 6.6
- 许可证：MIT

---

## 简介

做资讯站最容易遇到的两个问题：

**一是内容形态混杂。** 有些内容是「今天发生了什么」（时效性强），有些是「这个季度怎么看」（需要沉淀），有些是「每周精选」（需要仪式感）。把它们全塞进同一个 `post` 类型，到最后归档页和首页都组织不起来。

Leafpress 注册了独立的 `digest` 简报类型：普通文章走文章流和卡片网格，简报走专属的归档模板（`archive-digest.php`，左侧大号日期、右侧期号徽章）和详情模板（`single-digest.php`，顶部「本期信息条」显示覆盖分类与涉及话题）。两种内容各走各的模板，互不干扰。

**二是订阅系统要自己写。** 市面上的「订阅」插件大多只做到存个邮箱，后台看列表要装额外的插件，导数据更是没有。Leafpress 把整条链路做完了：前台表单（AJAX 提交 + 蜜罐反 spam + 频率限制 + Nonce）、落库（`digest_subscriber` 类型，邮箱存 `post_title`、状态/来源/IP 哈希存 `post_meta`）、后台列表（自定义列、可排序、可搜索邮箱）、CSV 导出（分页流式输出 + UTF-8 BOM，Excel 打开中文不乱码）。

## 特性

### 报头结构

三段式头部，视觉上就是报纸的报头：

1. **通栏**：当天日期（`wp_date()` 输出，自动本地化）+ 快捷导航 + 搜索按钮
2. **报头主体**：自定义 Logo 或站名（衬线体，字号 3.4rem）+ 站点描述
3. **分区导航**：支持二级下拉，移动端点击展开；未分配菜单时自动用热门分类兜底

### 首页区块

| 区块 | 说明 |
| --- | --- |
| 头条区 | 大图头条（置顶优先）+ 右侧「今日要闻」编号列表。置顶文章会优先上头条。 |
| 最新发布 | 卡片网格，样式可选 |
| 三栏分类 | 分类速览，分类可指定或自动取文章数最多的三个 |

每个区块可独立开关，卡片样式可独立配置。

### 四种卡片样式

| 样式 | 用途 |
| --- | --- |
| `standard` 标准图文 | 默认样式，缩略图 + 标题 + 摘要 + 元信息 |
| `feature` 大图 | 更大的标题与摘要，适合重点内容 |
| `horizontal` 横向图文 | 缩略图在右，适合归档页与搜索页 |
| `compact` 紧凑文字 | 纯文字，适合三栏分类区与 404 页 |

### 邮件订阅系统

**前台：**

- AJAX 提交（`wp_enqueue_script` 本地化文案），无 JS 时表单仍可正常提交
- 蜜罐字段（`leafpress_website`）反 spam，填了静默返回成功不给反馈
- Nonce 校验
- 频率限制：同一邮箱 + IP 组合 30 秒冷却（`leafpress_subscribe_cooldown` 过滤器可调）
- 邮箱双重校验：`sanitize_email()` + `is_email()`，统一转小写防重复
- IP 只存哈希（混入 `wp_salt()`），不存明文

**后台：**

- 「简报 → 订阅者」菜单，完全私有类型（`public => false` + `exclude_from_search`），前台不可查询
- 禁止手工新增（`create_posts => do_not_allow`），避免无邮箱的脏数据
- 自定义列：邮箱（mailto 链接）、来源、状态、订阅时间
- 「订阅时间」列可排序
- 搜索框同时匹配邮箱与来源（默认只搜标题）
- 列表顶部显示总人数与已订阅人数
- 「简报 → 导出 CSV」：分页流式输出，字段为邮箱 / 称呼 / 状态 / 来源 / 订阅时间 / 最后更新，文件带 UTF-8 BOM
- 导出链接带 Nonce 校验 + `export_private_posts` 权限校验
- 每行也提供「导出 CSV」快捷链接

**状态字段**：`active`（已订阅）/ `pending`（待确认）/ `unsubscribed`（已退订）。开启 Customizer 的「双 opt-in」后新订阅者落为 `pending`，需在后台改为 `active` 才算生效。

**扩展点**：订阅成功后触发 `leafpress_subscribed` 动作，可对接任意邮件服务：

```php
add_action( 'leafpress_subscribed', function ( $subscriber_id, $email, $status ) {
	// 在这里调用你的邮件服务 API 发送确认邮件
}, 10, 3 );
```

### 短代码

```
[leafpress_subscribe source="sidebar" compact="0" title="订阅周报" text="每周一封。"]
```

参数：`source`（来源标识，用于统计）、`compact`（紧凑样式）、`title`、`text`。

### 其他

- 简报自动计算期号（第 N 期），按发布时间在同一类型内定位
- 热门文章排序可选浏览数（`_leafpress_views` postmeta）或评论数；浏览数数据不足时自动回退评论数
- 侧栏可同时使用自定义 Widget 与内置的三个小组件（热门文章 / 标签云 / 订阅表单）
- 「侧栏 2（简报专用）」只在单篇简报页显示
- 快捷键：按 `/` 聚焦搜索框
- 打印友好样式、`prefers-reduced-motion` 降级

## 截图说明

WordPress 后台的主题截图要求尺寸为 **1200 × 900 像素**（PNG 格式）。

- **文件路径**：`screenshot.png`（放在主题根目录）
- **推荐内容**：以首页为主视角。顶部是三段式报头（通栏日期 + 大号衬线体站名 + 分区导航条），下方是头条区（左侧大图头条配大标题，右侧是带序号的「今日要闻」列表），再往下露出最新文章卡片网格的顶部一行。整体白底、细灰线、单一砖红色点缀。
- **建议分辨率**：1200 × 900 px，24 位色 PNG，文件体积控制在 250KB 以内。

> 本仓库为纯代码分发，未附带二进制截图文件；安装后请按上述说明自行补充 `screenshot.png`。

## 安装

### 从后台安装（推荐）

1. 把主题目录打包成 `leafpress-digest.zip`，确保压缩包内层是 `leafpress-digest/` 目录。
2. 进入 **外观 → 主题 → 添加新主题 → 上传主题**。
3. 点击 **启用**。主题会自动刷新固定链接，简报归档页 `/digest/` 立即可用。

### 通过 FTP / SSH 上传

1. 将 `leafpress-digest` 目录上传到 `wp-content/themes/`。
2. 进入 **外观 → 主题**，点击 **启用**。

### 本地开发

```bash
git clone https://github.com/mornrain/leafpress-digest.git
cd leafpress-digest
php -l functions.php   # 语法自检
```

## 主题配置项

进入 **外观 → 自定义 → 主题设置**。

### 配色

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 强调色 | 颜色选择器 | `#b03a2b` | 留空使用主题默认砖红 |
| 页面底色 | 颜色选择器 | `#ffffff` | 覆盖站点底色 |

### 头条区

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 显示头条区 | 复选框 | 勾选 | |
| 头条标签文案 | 文本 | `今日头条` | |
| 次条数量 | 数字 | `4` | 2 - 10 |

### 最新文章区块

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 区块标题 | 文本 | `最新发布` | |
| 展示数量 | 数字 | `6` | 3 - 24 |
| 卡片样式 | 下拉 | `标准图文` | 四选一 |

### 三栏分类区块

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 显示三栏区块 | 复选框 | 勾选 | |
| 栏数 | 数字 | `3` | 2 - 4 |
| 每栏文章数 | 数字 | `4` | 2 - 10 |
| 卡片样式 | 下拉 | `紧凑文字` | 四选一 |
| 指定分类 ID | 文本 | 空 | 英文逗号分隔，如 `3,5,9`。留空自动取文章数最多的分类 |

### 侧栏小组件

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 启用内置小组件 | 复选框 | 勾选 | 在自定义 Widget 之后追加热门文章、标签云、订阅表单 |
| 热门文章标题 | 文本 | `热门文章` | |
| 热门排序依据 | 下拉 | `浏览数` | 可选 `浏览数` / `评论数` |
| 热门文章数量 | 数字 | `6` | 3 - 20 |
| 标签云标题 | 文本 | `热门标签` | |
| 标签数量 | 数字 | `30` | 5 - 60 |

### 邮件订阅

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 显示订阅表单 | 复选框 | 勾选 | 关闭后侧栏与文章底部都不输出 |
| 表单标题 | 文本 | `订阅每日简报` | |
| 表单说明文字 | 多行文本 | `每周一封，汇总本周值得读的东西。` | |
| 在文章底部追加订阅表单 | 复选框 | 勾选 | 关闭后仅在侧栏显示 |
| 需要确认邮件（双 opt-in） | 复选框 | 不勾选 | 开启后新订阅者状态为「待确认」 |

### 简报

| 配置项 | 类型 | 默认值 | 说明 |
| --- | --- | --- | --- |
| 期号前缀 | 文本 | `第 %s 期` | 支持 `%s` 占位符 |
| 简报归档页标题 | 文本 | `简报` | |
| 摘要长度（词） | 数字 | `40` | 10 - 150 |
| 默认卡片样式 | 下拉 | `标准图文` | 用于未单独指定样式的区块 |

### 菜单与 Widget

- **菜单位置**：分区导航（主导航）、报头导航、页脚导航、三栏分类导航。
- **Widget 区域**：侧栏 1（主侧栏）、侧栏 2（简报专用）、页脚 1 / 2 / 3。

### 图片尺寸

| 名称 | 尺寸 | 用途 |
| --- | --- | --- |
| `leafpress-headline` | 900 × 560 | 头条大图 |
| `leafpress-card` | 640 × 400 | 卡片缩略图 |
| `leafpress-thumb` | 320 × 200 | 小尺寸缩略图 |
| `leafpress-daily` | 1160 × 500 | 文章顶部大图 |

## 模板层级

```
leafpress-digest/
├── style.css                    # 主题头注释 + 全部样式
├── functions.php                # 装配入口
├── theme.json                   # 块编辑器配置（衬线标题 + 无衬线正文双字体栈）
├── index.php                    # 通用回退模板
├── front-page.php               # 首页：头条区 + 最新文章 + 三栏分类
├── single.php                   # 单篇文章
├── single-digest.php            # 简报详情（期号 + 本期信息条 + 上/下期导航）
├── page.php                     # 单页面
├── archive.php                  # 其他归档
├── archive-digest.php           # 简报归档（大号日期列表）
├── search.php                   # 搜索结果
├── 404.php                      # 未找到
├── comments.php                 # 评论
├── sidebar.php                  # 侧栏
├── searchform.php               # 搜索表单
├── header.php                   # 三段式报头
├── footer.php                   # 页脚 + 订阅表单
├── inc/
│   ├── post-types.php           # CPT 注册 + 订阅者列表自定义列 / 排序 / 搜索
│   ├── subscribers.php          # 订阅表单渲染、提交处理、落库、CSV 导出
│   ├── popular.php              # 热门文章查询（浏览数优先，评论数回退）
│   ├── template-tags.php        # 模板标签、卡片四种样式、头条区、面包屑
│   └── customizer.php           # Customizer 面板与设置项
└── assets/
    ├── css/editor-style.css     # 区块编辑器内样式
    └── js/
        ├── navigation.js        # 分区导航、搜索抽屉、快捷键
        ├── subscribe.js         # 订阅表单 AJAX 提交
        └── customizer-preview.js# Customizer 实时预览
```

> 翻译文件（`.pot` / `.mo` / `.l10n`）放在 `languages/` 目录，该目录在首次翻译时创建。主题已调用 `load_theme_textdomain()`，放入语言包后即可生效。

### 可覆盖的模板

在子主题中创建同名文件即可覆盖，例如 `front-page.php`、`archive-digest.php`、`single-digest.php`。

## 子主题制作

1. 在 `wp-content/themes/` 下创建目录，例如 `my-leafpress-child`。
2. 目录内只需两个文件：

```php
<?php
/**
 * My Leafpress 子主题样式。
 */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'my-leafpress-child',
		get_stylesheet_uri(),
		array( 'leafpress-style' ),
		'1.0.0'
	);
} );
```

```css
/*
Theme Name: My Leafpress
Description: Leafpress Digest 的子主题。
Template: leafpress-digest
Version: 1.0.0
*/
```

3. 启用子主题。

### 扩展点

| 名称 | 类型 | 说明 |
| --- | --- | --- |
| `leafpress_get_option( $key, $default )` | 函数 | 读取主题设置 |
| `leafpress_content_width` | filter | 覆盖正文宽度 |
| `leafpress_subscribed` | action | 订阅成功后触发，参数：记录 ID、邮箱、状态、是否重复 |
| `leafpress_subscribe_cooldown` | filter | 订阅冷却秒数，默认 30 |
| `leafpress_get_popular_posts( $limit, $type )` | 函数 | 热门文章查询 |
| `leafpress_card( $args )` | 函数 | 渲染单张卡片，`$args['style']` 指定样式 |
| `leafpress_headline_section( $lead, $secondary )` | 函数 | 渲染头条区 |
| `leafpress_render_subscribe_form( $args )` | 函数 | 渲染订阅表单 |
| `leafpress_digest_issue_number( $post_id )` | 函数 | 计算简报期号 |
| `leafpress_get_column_terms( $limit )` | 函数 | 获取三栏区块的分类 |

### 对接邮件服务

```php
add_action( 'leafpress_subscribed', function ( $subscriber_id, $email, $status ) {
	// 你的邮件服务 SDK 调用示例
	// $service->send( $email, '确认订阅', $body );
}, 10, 3 );
```

### 调整三栏分类的取词逻辑

```php
add_filter( 'leafpress_column_terms', function ( $terms ) {
	return array_slice( $terms, 0, 2 );
} );
```

## FAQ

**Q：简报和普通文章的区别是什么？**
类型不同，模板不同。`digest` 走 `archive-digest.php` 和 `single-digest.php`，自动获得期号徽章与「本期信息条」；`post` 走常规模板。两者在首页头条区会一起被查询（按时间倒序），所以头条可能是简报也可能是文章。

**Q：为什么简报不能手工新增？**
订阅者类型（`digest_subscriber`）禁止手工新增，因为它的数据由表单生成，手工新增容易产生无邮箱的脏记录。简报类型（`digest`）当然可以正常发布，在后台「简报」菜单里点「发布简报」。

**Q：热门文章按浏览数排序，但没有浏览数数据怎么办？**
主题不写浏览数（避免每次访问都 UPDATE 数据库）。你可以用任意统计方案向 `_leafpress_views` postmeta 写数据。在没有数据或数据不足时，主题会自动用评论数补齐，两种数据混排不会让侧栏空掉。

**Q：CSV 导出在 Excel 里中文乱码？**
不会。主题在 CSV 开头写了 UTF-8 BOM（`\xEF\xBB\xBF`），Excel 打开能正确识别编码。用「简报 → 导出 CSV」菜单或列表行里的「导出 CSV」链接，别自己拼 URL（需要 Nonce）。

**Q：订阅者数据会被搜索引擎收录吗？**
不会。`digest_subscriber` 注册为 `public => false` + `publicly_queryable => false` + `exclude_from_search => true`，前台完全不可访问，WordPress 也不会把它加进站点地图。

**Q：怎么限制某个页面的侧栏？**
最直接的办法是用子主题覆盖 `sidebar.php`，或者在 `is_singular( 'digest' )` 已有内置逻辑之外追加条件：

```php
// 某个页面不显示侧栏：在该页的模板中直接不调用 leafpress_sidebar()，
// 或用子主题覆盖 single.php / page.php。
```

侧栏区域的判定顺序是：简报页优先「侧栏 2（简报专用）」，其次「侧栏 1（主侧栏）」；两者的选择都在 `leafpress_sidebar()` 里，继承逻辑清晰。

**Q：报头导航、分区导航、三栏分类导航有什么区别？**
「报头导航」在通栏右侧，样式最轻，只适合放 1-2 个链接（如「订阅」「关于」）；「分区导航」是主导航，黑色文字 + 底部强调线；「三栏分类导航」预留给三栏区块的分类跳转，主题没有单独使用它，你可以自行分配。

**Q：支持 WooCommerce 吗？**
声明了 `woocommerce` 支持，启用不会报错，但**没有**做模板适配。

**Q：screenshot.png 必须吗？**
上架 WordPress.org 目录必须提供，尺寸 1200 × 900。本地自用可以不放。

## License

MIT License

Copyright (c) 2026 MornRain

详细条款见 [LICENSE](LICENSE) 文件。

本主题为 MornRain 独立开发，不捆绑任何第三方库。所有图标为内联 SVG，字体全部使用系统字体栈，无任何外部资源请求。邮件订阅只负责收集与存储地址，**不发送任何邮件**——发送能力留给对接方的邮件服务。
