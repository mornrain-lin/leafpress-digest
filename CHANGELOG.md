# Changelog

本项目遵循 [Semantic Versioning](https://semver.org/lang/zh-CN/) 规范。

## [Unreleased]

### 修复

- `leafpress_sidebar()` 修复 `<aside>` 未闭合：原实现在简报页会`return`，跳过了收尾的 `echo '</aside>'`。
- `leafpress_sidebar()` 修复简报专用侧栏从未被输出：原实现只把它用于「是否追加内置小组件」的判断，`dynamic_sidebar()` 始终输出 `sidebar-1`。现改为先确定目标侧栏再输出，自定义侧栏有内容时不再追加内置小组件。
- `leafpress_headline_section()` 中的 `the_permalink()` / `the_title()` 改为 `esc_url()` / `esc_html()`。
- `single-digest.php` 的 `get_previous_post( true, ... )` 改为 `get_previous_post()`，避免触发 `_deprecated_argument()`。
- `--lp-muted` 由 `#86847e` 调整为 `#777570`，正文对比度从 3.74:1 提升到 4.60:1（WCAG AA）。
- 资源版本号改用 `filemtime()`。


## [1.0.0] - 2026-10-02

### 新增

- 完整模板层级：`index.php` / `front-page.php` / `single.php` / `single-digest.php` / `page.php` / `archive.php` / `archive-digest.php` / `search.php` / `404.php` / `comments.php` / `sidebar.php` / `searchform.php` / `header.php` / `footer.php`。
- `theme.json`：衬线标题 + 无衬线正文双字体栈、4 档字号、8 组配色（含墨黑与砖红，报刊感基调）。
- 注册两个自定义文章类型：
  - `digest`（简报）：独立的归档与详情模板，自动计算期号（第 N 期）。
  - `digest_subscriber`（订阅者）：完全私有类型，`public => false` + `exclude_from_search`，前台与搜索均不可见。
- 三段式报头：通栏（日期 + 快捷导航 + 搜索）→ 报头主体（Logo / 站名 + 描述）→ 分区导航（支持二级下拉，移动端折叠）。
- 首页头条区：大图头条（置顶优先）+ 右侧「今日要闻」编号列表，可配置次条数量与标签文案。
- 首页最新文章区块与三栏分类区块，分类可指定 ID 或自动按文章数取前三个。
- 四种文章卡片样式：`standard` 标准图文 / `feature` 大图 / `horizontal` 横向图文 / `compact` 紧凑文字。
- 邮件订阅系统完整链路：
  - 前台表单：AJAX 提交（无 JS 时降级为普通提交）、蜜罐反 spam、Nonce 校验、30 秒频率限制、邮箱双重校验、IP 只存哈希。
  - 数据落库：邮箱存 `post_title`，状态 / 来源 / 称呼 / IP 哈希 / User-Agent 存 `post_meta`。
  - 后台管理：自定义列表列（邮箱 / 来源 / 状态 / 订阅时间）、订阅时间可排序、搜索框同时匹配邮箱与来源、顶部统计提示、禁止手工新增。
  - CSV 导出：分页流式输出，UTF-8 BOM 保证 Excel 中文不乱码，导出链接带 Nonce 与 `export_private_posts` 权限校验。
  - 订阅者状态：`active` / `pending` / `unsubscribed`，支持双 opt-in。
  - 短代码 `[leafpress_subscribe]`，支持 `source` / `compact` / `title` / `text` 参数。
  - 订阅成功触发 `leafpress_subscribed` 动作，可对接任意邮件服务。
- 侧栏小组件：热门文章（浏览数优先，评论数回退）、标签云、订阅表单，可在自定义 Widget 之后自动追加。
- 「侧栏 2（简报专用）」：只在单篇简报页显示。
- 简报详情页顶部「本期信息条」：显示覆盖分类与涉及话题，并提供上/下期导航。
- Widget 区域：主侧栏 1 个 + 简报专用 1 个 + 页脚 3 个。
- 快捷键：按 `/` 聚焦搜索框；报头搜索抽屉支持 `Esc` 关闭。
- 全站转义输出，PHP 7.4 兼容，零外部资源依赖，无第三方库。
