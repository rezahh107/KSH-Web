<?php
/**
 * Native wp-admin Preview surface.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Tools submenu for explicit read-only qualification.
 */
final class Admin_Page {

	const CAPABILITY = 'manage_options';
	const MENU_SLUG  = 'ksh-kanoon-articles-preview';
	const NONCE      = 'ksh_kanoon_articles_preview';

	/**
	 * Read-only Preview service.
	 *
	 * @var Preview_Service
	 */
	private $preview;

	/**
	 * Create the native admin Preview controller.
	 *
	 * @param Preview_Service $preview Preview service.
	 */
	public function __construct( Preview_Service $preview ) {
		$this->preview = $preview;
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
	 * Render page and, only on explicit protected POST, run remote Preview.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'شما اجازه دسترسی به این ابزار را ندارید.', 'ksh-kanoon-articles' ) );
		}

		$result         = null;
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
		if ( 'POST' === $request_method ) {
			check_admin_referer( self::NONCE );
			$result = $this->preview->run();
		}
		?>
		<div class="wrap" dir="rtl">
			<h1><?php echo esc_html__( 'پیش‌نمایش اتصال مقاله‌های کانون', 'ksh-kanoon-articles' ); ?></h1>
			<p><?php echo esc_html__( 'این ابزار فقط با اقدام صریح شما دو صفحه عمومی تأییدشده در kanoon.ir را می‌خواند و نتیجه استخراج را در همین پاسخ نشان می‌دهد.', 'ksh-kanoon-articles' ); ?></p>
			<ul>
				<li><?php echo esc_html__( 'دادهٔ مقاله را ذخیره نمی‌کند.', 'ksh-kanoon-articles' ); ?></li>
				<li><?php echo esc_html__( 'Post یا CPT ایجاد نمی‌کند.', 'ksh-kanoon-articles' ); ?></li>
				<li><?php echo esc_html__( 'کار زمان‌بندی‌شده ایجاد نمی‌کند.', 'ksh-kanoon-articles' ); ?></li>
				<li><?php echo esc_html__( 'فرانت‌اند یا داده‌های workflow/business را تغییر نمی‌دهد.', 'ksh-kanoon-articles' ); ?></li>
			</ul>

			<form method="post">
				<?php wp_nonce_field( self::NONCE ); ?>
				<?php submit_button( __( 'اجرای پیش‌نمایش / آزمون اتصال', 'ksh-kanoon-articles' ), 'primary', 'submit', false ); ?>
			</form>

			<?php if ( is_array( $result ) ) : ?>
				<?php $this->render_overall( $result ); ?>
				<?php $this->render_list_result( $result['latest'], __( 'تازه‌ها', 'ksh-kanoon-articles' ) ); ?>
				<?php $this->render_list_result( $result['weekly_popular'], __( 'پربازدید هفته', 'ksh-kanoon-articles' ) ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render overall truthful state.
	 *
	 * @param array<string,mixed> $result Combined result.
	 * @return void
	 */
	private function render_overall( $result ) {
		$status = isset( $result['overall_status'] ) ? $result['overall_status'] : 'failure';
		$map    = array(
			'success'   => array( 'notice-success', __( 'موفق: هر دو فهرست در این اجرا قابل دریافت و اعتبارسنجی بودند.', 'ksh-kanoon-articles' ) ),
			'partial'   => array( 'notice-warning', __( 'موفقیت جزئی: فقط یکی از دو فهرست با موفقیت اثبات شد. فعال‌سازی persistence/scheduling هنوز مجاز نیست.', 'ksh-kanoon-articles' ) ),
			'ambiguous' => array( 'notice-warning', __( 'نامشخص: مرز لازم برای تشخیص قابل دفاع یکی از فهرست‌ها اثبات نشد.', 'ksh-kanoon-articles' ) ),
			'failure'   => array( 'notice-error', __( 'ناموفق: این اجرا نتوانست فهرست‌های لازم را اثبات کند.', 'ksh-kanoon-articles' ) ),
		);
		$entry  = isset( $map[ $status ] ) ? $map[ $status ] : $map['failure'];
		?>
		<div class="notice <?php echo esc_attr( $entry[0] ); ?> inline">
			<p><strong><?php echo esc_html( $entry[1] ); ?></strong></p>
			<p><?php echo esc_html__( 'گام معتبر بعدی فقط زمانی بررسی persistence/scheduling است که هر دو فهرست روی همین میزبان واقعی PASS شوند.', 'ksh-kanoon-articles' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render one list result and bounded normalized evidence.
	 *
	 * @param array<string,mixed> $result List result.
	 * @param string              $label  Human label.
	 * @return void
	 */
	private function render_list_result( $result, $label ) {
		$status      = isset( $result['status'] ) ? (string) $result['status'] : 'failure';
		$status_text = $this->status_text( $status );
		$http_code   = isset( $result['request']['http_code'] ) ? $result['request']['http_code'] : null;
		?>
		<hr>
		<h2><?php echo esc_html( $label ); ?></h2>
		<p>
			<strong><?php echo esc_html__( 'نتیجه:', 'ksh-kanoon-articles' ); ?></strong>
			<?php echo esc_html( $status_text ); ?>
			—
			<strong><?php echo esc_html__( 'تعداد رکورد معتبر:', 'ksh-kanoon-articles' ); ?></strong>
			<?php echo esc_html( (string) ( isset( $result['count'] ) ? $result['count'] : 0 ) ); ?>
		</p>
		<p>
			<strong><?php echo esc_html__( 'منبع:', 'ksh-kanoon-articles' ); ?></strong>
			<bdi dir="ltr"><a href="<?php echo esc_url( $result['source_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $result['source_url'] ); ?></a></bdi>
			<?php if ( null !== $http_code ) : ?>
				— <strong><?php echo esc_html__( 'HTTP:', 'ksh-kanoon-articles' ); ?></strong> <bdi dir="ltr"><?php echo esc_html( (string) $http_code ); ?></bdi>
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $result['reason'] ) ) : ?>
			<p><strong><?php echo esc_html__( 'دلیل فنی محدود:', 'ksh-kanoon-articles' ); ?></strong> <bdi dir="ltr"><?php echo esc_html( $result['reason'] ); ?></bdi></p>
		<?php endif; ?>

		<?php if ( ! empty( $result['items'] ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'ردیف', 'ksh-kanoon-articles' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'عنوان', 'ksh-kanoon-articles' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'نشانی canonical', 'ksh-kanoon-articles' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'زمینه تاریخ/روز', 'ksh-kanoon-articles' ); ?></th>
					</tr>
				</thead>
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
	 * Human-readable status that does not rely on color.
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
