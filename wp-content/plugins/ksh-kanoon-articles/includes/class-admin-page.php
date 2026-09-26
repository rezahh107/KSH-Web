<?php
/**
 * Native wp-admin Preview and local refresh surface.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Tools submenu for explicit Preview plus bounded operational refresh/status.
 */
final class Admin_Page {

	const CAPABILITY    = 'manage_options';
	const MENU_SLUG     = 'ksh-kanoon-articles-preview';
	const NONCE         = 'ksh_kanoon_articles_preview';
	const REFRESH_NONCE = 'ksh_kanoon_articles_refresh';

	/**
	 * Read-only Preview service.
	 *
	 * @var Preview_Service
	 */
	private $preview;

	/**
	 * Canonical local refresh service.
	 *
	 * @var Refresh_Service
	 */
	private $refresh;

	/**
	 * Local snapshot/status store.
	 *
	 * @var Snapshot_Store
	 */
	private $store;

	/**
	 * Native schedule owner.
	 *
	 * @var Scheduler
	 */
	private $scheduler;

	/**
	 * Create the native admin controller.
	 *
	 * @param Preview_Service $preview   Read-only Preview service.
	 * @param Refresh_Service $refresh   Canonical local refresh service.
	 * @param Snapshot_Store  $store     Local snapshot/status store.
	 * @param Scheduler       $scheduler Native schedule owner.
	 */
	public function __construct( Preview_Service $preview, Refresh_Service $refresh, Snapshot_Store $store, Scheduler $scheduler ) {
		$this->preview   = $preview;
		$this->refresh   = $refresh;
		$this->store     = $store;
		$this->scheduler = $scheduler;
	}

	/**
	 * Register native Tools submenu.
	 *
	 * @return void
	 */
	public function register() {
		add_management_page(
			__( 'پیش‌نمایش اتصال مقاله‌های کانون', 'ksh-kanoon-articles' ),
			__( 'آزمون اتصال مقاله‌های کانون', 'ksh-kanoon-articles' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Run the same canonical refresh service used by the scheduled callback.
	 *
	 * Request authorization/nonce enforcement belongs to render().
	 *
	 * @return array<string,mixed>
	 */
	public function run_manual_refresh() {
		return $this->refresh->run();
	}

	/**
	 * Render page and run remote actions only on explicit protected POSTs.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این ابزار را ندارید.', 'ksh-kanoon-articles' ) );
		}

		$preview_result = null;
		$refresh_result = null;
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		if ( 'POST' === $request_method ) {
			$action = isset( $_POST['ksh_action'] ) ? sanitize_key( wp_unslash( $_POST['ksh_action'] ) ) : 'preview';
			if ( 'refresh' === $action ) {
				check_admin_referer( self::REFRESH_NONCE );
				$refresh_result = $this->run_manual_refresh();
			} else {
				check_admin_referer( self::NONCE );
				$preview_result = $this->preview->run();
			}
		}
		?>
		<div class="wrap" dir="rtl">
			<h1><?php echo esc_html__( 'اتصال و دادهٔ محلی مقاله‌های کانون', 'ksh-kanoon-articles' ); ?></h1>

			<h2><?php echo esc_html__( 'پیش‌نمایش / آزمون اتصال', 'ksh-kanoon-articles' ); ?></h2>
			<p><?php echo esc_html__( 'این بخش فقط با اقدام صریح شما دو صفحه عمومی تأییدشده در kanoon.ir را می‌خواند و نتیجه استخراج را نشان می‌دهد.', 'ksh-kanoon-articles' ); ?></p>
			<ul>
				<li><?php echo esc_html__( 'Snapshot محلی را تغییر نمی‌دهد.', 'ksh-kanoon-articles' ); ?></li>
				<li><?php echo esc_html__( 'Post یا CPT ایجاد نمی‌کند.', 'ksh-kanoon-articles' ); ?></li>
				<li><?php echo esc_html__( 'فرانت‌اند یا داده‌های workflow/business را تغییر نمی‌دهد.', 'ksh-kanoon-articles' ); ?></li>
			</ul>

			<form method="post">
				<input type="hidden" name="ksh_action" value="preview">
				<?php wp_nonce_field( self::NONCE ); ?>
				<?php submit_button( __( 'اجرای پیش‌نمایش / آزمون اتصال', 'ksh-kanoon-articles' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( is_array( $preview_result ) ) : ?>
				<?php $this->render_preview_overall( $preview_result ); ?>
				<?php $this->render_list_result( $preview_result['latest'], __( 'تازه‌ها', 'ksh-kanoon-articles' ) ); ?>
				<?php $this->render_list_result( $preview_result['weekly_popular'], __( 'پربازدید هفته', 'ksh-kanoon-articles' ) ); ?>
			<?php endif; ?>

			<hr>
			<h2><?php echo esc_html__( 'دادهٔ محلی و Refresh عملیاتی', 'ksh-kanoon-articles' ); ?></h2>
			<p><?php echo esc_html__( 'این بخش وضعیت Snapshot محلی را نشان می‌دهد. Refresh دستی یک درخواست واقعی به منابع کانون انجام می‌دهد و فقط candidate معتبر هر فهرست را جایگزین Snapshot همان فهرست می‌کند.', 'ksh-kanoon-articles' ); ?></p>
			<p><strong><?php echo esc_html__( 'اثر جانبی:', 'ksh-kanoon-articles' ); ?></strong> <?php echo esc_html__( 'با اجرای Refresh، Snapshot و وضعیت آخرین تلاش ثبت‌شده در WordPress Options به‌روزرسانی می‌شوند. شکست یا ابهام یک فهرست Snapshot معتبر قبلی آن فهرست را پاک نمی‌کند؛ اگر ثبت وضعیت تلاش ناموفق باشد، نتیجهٔ همین اجرا به‌صورت ناقص عملیاتی گزارش می‌شود.', 'ksh-kanoon-articles' ); ?></p>

			<?php if ( is_array( $refresh_result ) ) : ?>
				<?php $this->render_refresh_result( $refresh_result ); ?>
			<?php endif; ?>

			<?php $this->render_local_status(); ?>

			<form method="post">
				<input type="hidden" name="ksh_action" value="refresh">
				<?php wp_nonce_field( self::REFRESH_NONCE ); ?>
				<?php submit_button( __( 'Refresh دادهٔ محلی اکنون', 'ksh-kanoon-articles' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Preview state without implying persistence behavior.
	 *
	 * @param array<string,mixed> $result Combined Preview result.
	 * @return void
	 */
	private function render_preview_overall( $result ) {
		$status = isset( $result['overall_status'] ) ? $result['overall_status'] : 'failure';
		$map    = array(
			'success'   => array( 'notice-success', __( 'موفق: هر دو فهرست در این Preview قابل دریافت و اعتبارسنجی بودند.', 'ksh-kanoon-articles' ) ),
			'partial'   => array( 'notice-warning', __( 'موفقیت جزئی: فقط یکی از دو فهرست در این Preview معتبر بود.', 'ksh-kanoon-articles' ) ),
			'ambiguous' => array( 'notice-warning', __( 'نامشخص: مرز لازم برای تشخیص قابل دفاع یکی از فهرست‌ها اثبات نشد.', 'ksh-kanoon-articles' ) ),
			'failure'   => array( 'notice-error', __( 'ناموفق: این Preview نتوانست فهرست‌های لازم را اثبات کند.', 'ksh-kanoon-articles' ) ),
		);
		$entry  = isset( $map[ $status ] ) ? $map[ $status ] : $map['failure'];
		?>
		<div class="notice <?php echo esc_attr( $entry[0] ); ?> inline">
			<p><strong><?php echo esc_html( $entry[1] ); ?></strong></p>
			<p><?php echo esc_html__( 'این نتیجه فقط acquisition/parsing همین اجرا را توصیف می‌کند و Snapshot محلی را تغییر نداده است.', 'ksh-kanoon-articles' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render one Preview list result and bounded normalized evidence.
	 *
	 * @param array<string,mixed> $result List result.
	 * @param string              $label  Human label.
	 * @return void
	 */
	private function render_list_result( $result, $label ) {
		$status      = isset( $result['status'] ) ? (string) $result['status'] : 'failure';
		$status_text = $this->status_text( $status );
		$http_code   = isset( $result['request']['http_code'] ) ? $result['request']['http_code'] : null;
		$reason      = isset( $result['reason'] ) ? (string) $result['reason'] : '';
		$guidance    = '' !== $reason ? $this->reason_guidance( $reason ) : null;
		?>
		<h3><?php echo esc_html( $label ); ?></h3>
		<p>
			<strong><?php echo esc_html__( 'نتیجه:', 'ksh-kanoon-articles' ); ?></strong>
			<?php echo esc_html( $status_text ); ?>
			— <strong><?php echo esc_html__( 'تعداد رکورد معتبر:', 'ksh-kanoon-articles' ); ?></strong>
			<?php echo esc_html( (string) ( isset( $result['count'] ) ? $result['count'] : 0 ) ); ?>
		</p>

		<?php if ( is_array( $guidance ) ) : ?>
			<p><strong><?php echo esc_html__( 'معنی عملی:', 'ksh-kanoon-articles' ); ?></strong> <?php echo esc_html( $guidance['meaning'] ); ?></p>
			<p><strong><?php echo esc_html__( 'گام بعدی:', 'ksh-kanoon-articles' ); ?></strong> <?php echo esc_html( $guidance['next'] ); ?></p>
		<?php endif; ?>

		<p>
			<strong><?php echo esc_html__( 'منبع:', 'ksh-kanoon-articles' ); ?></strong>
			<bdi dir="ltr"><a href="<?php echo esc_url( $result['source_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $result['source_url'] ); ?></a></bdi>
			<?php if ( null !== $http_code ) : ?>
				— <strong><?php echo esc_html__( 'HTTP:', 'ksh-kanoon-articles' ); ?></strong> <bdi dir="ltr"><?php echo esc_html( (string) $http_code ); ?></bdi>
			<?php endif; ?>
		</p>

		<?php if ( '' !== $reason ) : ?>
			<p><strong><?php echo esc_html__( 'جزئیات فنی:', 'ksh-kanoon-articles' ); ?></strong> <bdi dir="ltr"><?php echo esc_html( $reason ); ?></bdi></p>
		<?php endif; ?>

		<?php if ( ! empty( $result['items'] ) ) : ?>
			<table class="widefat striped">
				<thead><tr>
					<th scope="col"><?php echo esc_html__( 'ردیف', 'ksh-kanoon-articles' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'عنوان', 'ksh-kanoon-articles' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'نشانی canonical', 'ksh-kanoon-articles' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'زمینه تاریخ/روز', 'ksh-kanoon-articles' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $result['items'] as $index => $item ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
							<td><?php echo esc_html( $item['title'] ); ?></td>
							<td><bdi dir="ltr"><a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['url'] ); ?></a></bdi></td>
							<td><?php echo esc_html( $item['date_context'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render one manual refresh outcome per list and combined truth state.
	 *
	 * @param array<string,mixed> $result Refresh result.
	 * @return void
	 */
	private function render_refresh_result( $result ) {
		$status = isset( $result['overall_status'] ) ? (string) $result['overall_status'] : 'failure';
		$labels = array(
			'success'   => __( 'Refresh کامل: هر دو Snapshot با candidate معتبر به‌روزرسانی شدند و وضعیت تلاش هر دو فهرست ثبت شد.', 'ksh-kanoon-articles' ),
			'degraded'  => __( 'Refresh ناقص عملیاتی: وضعیت Snapshotها مطابق نتیجهٔ واقعی حفظ/به‌روزرسانی شد، اما ثبت وضعیت تلاش برای حداقل یک فهرست کامل نشد.', 'ksh-kanoon-articles' ),
			'partial'   => __( 'Refresh جزئی: یک Snapshot به‌روزرسانی شد و فهرست دیگر دادهٔ معتبر قبلی را حفظ کرد یا بدون Snapshot باقی ماند.', 'ksh-kanoon-articles' ),
			'ambiguous' => __( 'Refresh به‌روزرسانی نداشت و حداقل یک candidate مبهم بود؛ Snapshot قبلی در صورت وجود حفظ شد.', 'ksh-kanoon-articles' ),
			'failure'   => __( 'Refresh به‌روزرسانی معتبری نداشت؛ Snapshot قبلی در صورت وجود حفظ شد.', 'ksh-kanoon-articles' ),
		);
		$notice_class = 'notice-error';
		if ( 'success' === $status ) {
			$notice_class = 'notice-success';
		} elseif ( 'partial' === $status || 'degraded' === $status ) {
			$notice_class = 'notice-warning';
		}
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?> inline">
			<p><strong><?php echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['failure'] ); ?></strong></p>
		</div>
		<?php
		$this->render_refresh_list_outcome( $result['latest'], __( 'تازه‌ها', 'ksh-kanoon-articles' ) );
		$this->render_refresh_list_outcome( $result['weekly_popular'], __( 'پربازدید هفته', 'ksh-kanoon-articles' ) );
	}

	/**
	 * Render practical persistence outcome for one list.
	 *
	 * @param array<string,mixed> $outcome Refresh outcome.
	 * @param string              $label   Human label.
	 * @return void
	 */
	private function render_refresh_list_outcome( $outcome, $label ) {
		$action_labels = array(
			'updated'                     => __( 'Snapshot با دادهٔ معتبر جدید به‌روزرسانی شد.', 'ksh-kanoon-articles' ),
			'preserved_previous'          => __( 'Candidate معتبر نبود یا write کامل نشد؛ Snapshot معتبر قبلی حفظ شد.', 'ksh-kanoon-articles' ),
			'no_valid_snapshot_available' => __( 'Candidate معتبر نبود یا write کامل نشد و Snapshot معتبر قبلی نیز وجود ندارد.', 'ksh-kanoon-articles' ),
		);
		$action        = isset( $outcome['action'] ) ? (string) $outcome['action'] : 'no_valid_snapshot_available';
		?>
		<p>
			<strong><?php echo esc_html( $label ); ?>:</strong>
			<?php echo esc_html( isset( $action_labels[ $action ] ) ? $action_labels[ $action ] : $action_labels['no_valid_snapshot_available'] ); ?>
			— <strong><?php echo esc_html__( 'Candidate:', 'ksh-kanoon-articles' ); ?></strong>
			<bdi dir="ltr"><?php echo esc_html( strtoupper( (string) $outcome['candidate_status'] ) ); ?></bdi>
			— <strong><?php echo esc_html__( 'دادهٔ محلی:', 'ksh-kanoon-articles' ); ?></strong>
			<?php echo ! empty( $outcome['local_available'] ) ? esc_html__( 'موجود', 'ksh-kanoon-articles' ) : esc_html__( 'ناموجود', 'ksh-kanoon-articles' ); ?>
		</p>
		<?php if ( ! empty( $outcome['reason'] ) ) : ?>
			<p><strong><?php echo esc_html__( 'Reason:', 'ksh-kanoon-articles' ); ?></strong> <bdi dir="ltr"><?php echo esc_html( $outcome['reason'] ); ?></bdi></p>
		<?php endif; ?>
		<?php if ( empty( $outcome['attempt_recorded'] ) ) : ?>
			<p>
				<strong><?php echo esc_html( $label ); ?> — <?php echo esc_html__( 'ثبت وضعیت تلاش:', 'ksh-kanoon-articles' ); ?></strong>
				<?php echo esc_html__( 'ناموفق؛ Snapshot action بالا معتبر است اما این اجرای عملیاتی به‌طور کامل ثبت نشد.', 'ksh-kanoon-articles' ); ?>
				— <bdi dir="ltr"><?php echo esc_html( isset( $outcome['attempt_reason'] ) ? $outcome['attempt_reason'] : 'attempt_write_failed' ); ?></bdi>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render current snapshot + latest recorded attempt + next schedule without remote work.
	 *
	 * @return void
	 */
	private function render_local_status() {
		$next = $this->scheduler->next_run();
		?>
		<table class="widefat striped">
			<thead><tr>
				<th scope="col"><?php echo esc_html__( 'فهرست', 'ksh-kanoon-articles' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Snapshot محلی', 'ksh-kanoon-articles' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'آخرین موفقیت', 'ksh-kanoon-articles' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'آخرین تلاش ثبت‌شده', 'ksh-kanoon-articles' ); ?></th>
			</tr></thead>
			<tbody>
				<?php $this->render_status_row( 'latest', __( 'تازه‌ها', 'ksh-kanoon-articles' ) ); ?>
				<?php $this->render_status_row( 'weekly_popular', __( 'پربازدید هفته', 'ksh-kanoon-articles' ) ); ?>
			</tbody>
		</table>
		<p>
			<strong><?php echo esc_html__( 'Refresh زمان‌بندی‌شده بعدی:', 'ksh-kanoon-articles' ); ?></strong>
			<?php if ( false === $next ) : ?>
				<?php echo esc_html__( 'ثبت نشده؛ بررسی self-healing در init دوباره تلاش می‌کند.', 'ksh-kanoon-articles' ); ?>
			<?php else : ?>
				<bdi dir="ltr"><?php echo esc_html( gmdate( 'c', (int) $next ) ); ?></bdi>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Render one local status row.
	 *
	 * @param string $source List identity.
	 * @param string $label  Human label.
	 * @return void
	 */
	private function render_status_row( $source, $label ) {
		$snapshot = $this->store->get_snapshot( $source );
		$attempt  = $this->store->get_attempt( $source );
		?>
		<tr>
			<td><?php echo esc_html( $label ); ?></td>
			<td>
				<?php if ( is_array( $snapshot ) ) : ?>
					<?php
					/* translators: %d: number of stored article records. */
					echo esc_html( sprintf( __( 'موجود — %d رکورد', 'ksh-kanoon-articles' ), (int) $snapshot['count'] ) );
					?>
				<?php else : ?>
					<?php echo esc_html__( 'هنوز Snapshot معتبر نداریم', 'ksh-kanoon-articles' ); ?>
				<?php endif; ?>
			</td>
			<td><?php $this->render_timestamp( is_array( $snapshot ) && isset( $snapshot['updated_at'] ) ? $snapshot['updated_at'] : '' ); ?></td>
			<td>
				<?php if ( is_array( $attempt ) ) : ?>
					<bdi dir="ltr"><?php echo esc_html( strtoupper( (string) $attempt['candidate_status'] ) ); ?></bdi>
					— <?php echo esc_html( (string) $attempt['action'] ); ?>
					<?php if ( ! empty( $attempt['reason'] ) ) : ?>
						— <bdi dir="ltr"><?php echo esc_html( (string) $attempt['reason'] ); ?></bdi>
					<?php endif; ?>
					<br><?php $this->render_timestamp( isset( $attempt['attempted_at'] ) ? $attempt['attempted_at'] : '' ); ?>
				<?php else : ?>
					<?php echo esc_html__( 'هنوز تلاش ثبت‌شده‌ای نداریم.', 'ksh-kanoon-articles' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a copyable LTR ISO timestamp or a Persian empty state.
	 *
	 * @param string $timestamp Timestamp.
	 * @return void
	 */
	private function render_timestamp( $timestamp ) {
		if ( '' === (string) $timestamp ) {
			echo esc_html__( '—', 'ksh-kanoon-articles' );
			return;
		}
		?>
		<bdi dir="ltr"><?php echo esc_html( (string) $timestamp ); ?></bdi>
		<?php
	}

	/**
	 * Explain a bounded failure/ambiguity in plain language.
	 *
	 * @param string $reason Machine-readable reason code.
	 * @return array<string,string>
	 */
	private function reason_guidance( $reason ) {
		$guidance = array(
			'transport_error'                 => array(
				'meaning' => __( 'این میزبان نتوانست درخواست HTTP را تا دریافت پاسخ کامل کند.', 'ksh-kanoon-articles' ),
				'next'    => __( 'اتصال خروجی، DNS، TLS و محدودیت‌های فایروال/هاست را بررسی کنید و سپس دوباره اجرا کنید.', 'ksh-kanoon-articles' ),
			),
			'http_error'                      => array(
				'meaning' => __( 'منبع پاسخ HTTP موفق 2xx برنگرداند.', 'ksh-kanoon-articles' ),
				'next'    => __( 'کد HTTP و دسترس‌پذیری منبع را بررسی کنید و پس از رفع وضعیت دوباره اجرا کنید.', 'ksh-kanoon-articles' ),
			),
			'empty_body'                      => array(
				'meaning' => __( 'درخواست HTTP پاسخ گرفت اما بدنهٔ قابل تحلیل خالی بود.', 'ksh-kanoon-articles' ),
				'next'    => __( 'رفتار منبع یا محدودیت‌های واسط/WAF را بررسی کنید.', 'ksh-kanoon-articles' ),
			),
			'dom_extension_unavailable'       => array(
				'meaning' => __( 'افزونهٔ PHP DOM برای تحلیل HTML روی این میزبان در دسترس نیست.', 'ksh-kanoon-articles' ),
				'next'    => __( 'DOM/XML را در PHP میزبان فعال کنید و سپس دوباره اجرا کنید.', 'ksh-kanoon-articles' ),
			),
			'empty_html'                      => array(
				'meaning' => __( 'محتوای HTML قابل تحلیل وجود نداشت.', 'ksh-kanoon-articles' ),
				'next'    => __( 'پاسخ منبع را بررسی کنید و بعد از دریافت HTML واقعی دوباره اجرا کنید.', 'ksh-kanoon-articles' ),
			),
			'malformed_html'                  => array(
				'meaning' => __( 'HTML دریافتی با parser فعلی قابل بارگذاری نبود.', 'ksh-kanoon-articles' ),
				'next'    => __( 'ساختار پاسخ منبع را بررسی و فقط مرز parser لازم را اصلاح کنید.', 'ksh-kanoon-articles' ),
			),
			'latest_date_boundary_missing'    => array(
				'meaning' => __( 'مرز معنایی روز برای فهرست تازه‌ها پیدا نشد.', 'ksh-kanoon-articles' ),
				'next'    => __( 'HTML فعلی صفحهٔ تازه‌ها را بررسی و مرز استخراج را به‌صورت محدود اصلاح کنید.', 'ksh-kanoon-articles' ),
			),
			'latest_zero_valid_items'         => array(
				'meaning' => __( 'مرز تازه‌ها پیدا شد اما هیچ لینک مقالهٔ معتبر قابل قبول نبود.', 'ksh-kanoon-articles' ),
				'next'    => __( 'ساختار لینک‌ها و قواعد validation را با منبع فعلی مقایسه کنید.', 'ksh-kanoon-articles' ),
			),
			'popular_tab_labels_not_unique'   => array(
				'meaning' => __( 'برچسب‌های پربازدید هفته/ماه به‌صورت یکتا قابل تشخیص نبودند.', 'ksh-kanoon-articles' ),
				'next'    => __( 'مرز DOM این دو فهرست را در HTML فعلی بررسی کنید.', 'ksh-kanoon-articles' ),
			),
			'popular_tab_target_missing'      => array(
				'meaning' => __( 'هدف DOM مستقل یکی از تب‌های پربازدید پیدا نشد.', 'ksh-kanoon-articles' ),
				'next'    => __( 'رابطهٔ برچسب تب و کانتینر مقصد را در HTML فعلی بررسی کنید.', 'ksh-kanoon-articles' ),
			),
			'weekly_monthly_target_collision' => array(
				'meaning' => __( 'Weekly و Monthly به یک هدف DOM اشاره می‌کنند؛ نتیجه عمداً مبهم در نظر گرفته شد.', 'ksh-kanoon-articles' ),
				'next'    => __( 'مرز مستقل Weekly را تعمیر کنید؛ Refresh نامعتبر Snapshot معتبر قبلی را جایگزین نمی‌کند.', 'ksh-kanoon-articles' ),
			),
			'weekly_anchor_query_failed'      => array(
				'meaning' => __( 'جست‌وجوی لینک‌های داخل کانتینر Weekly کامل نشد.', 'ksh-kanoon-articles' ),
				'next'    => __( 'ساختار کانتینر Weekly و قابلیت DOM روی میزبان را بررسی کنید.', 'ksh-kanoon-articles' ),
			),
			'weekly_zero_valid_items'         => array(
				'meaning' => __( 'کانتینر Weekly پیدا شد اما هیچ لینک مقالهٔ معتبر قابل قبول نبود.', 'ksh-kanoon-articles' ),
				'next'    => __( 'ساختار لینک‌های Weekly را با قواعد canonical URL مقایسه کنید.', 'ksh-kanoon-articles' ),
			),
		);

		if ( isset( $guidance[ $reason ] ) ) {
			return $guidance[ $reason ];
		}

		return array(
			'meaning' => __( 'این فهرست در این اجرا قابل اثبات نبود.', 'ksh-kanoon-articles' ),
			'next'    => __( 'جزئیات فنی را بررسی کنید و پس از رفع علت دوباره اجرا کنید.', 'ksh-kanoon-articles' ),
		);
	}

	/**
	 * Human-readable Preview status that does not rely on color.
	 *
	 * @param string $status Machine status.
	 * @return string
	 */
	private function status_text( $status ) {
		$labels = array(
			'success'   => __( 'PASS', 'ksh-kanoon-articles' ),
			'failure'   => __( 'FAIL', 'ksh-kanoon-articles' ),
			'ambiguous' => __( 'AMBIGUOUS / NOT PROVEN', 'ksh-kanoon-articles' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'FAIL', 'ksh-kanoon-articles' );
	}
}
