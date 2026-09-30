<?php
/**
 * Explicit owner action for acquisition-contract qualification.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Provides one bounded Tools page that can qualify the exact current contract.
 */
final class Qualification_Admin_Page {

	const CAPABILITY = 'manage_options';
	const MENU_SLUG  = 'ksh-kanoon-articles-qualification';
	const NONCE      = 'ksh_kanoon_articles_qualification';

	/** @var Acquisition_Qualification */
	private $qualification;

	/** @var Scheduler */
	private $scheduler;

	/**
	 * @param Acquisition_Qualification $qualification Qualification owner.
	 * @param Scheduler                 $scheduler     Schedule owner.
	 */
	public function __construct( Acquisition_Qualification $qualification, Scheduler $scheduler ) {
		$this->qualification = $qualification;
		$this->scheduler     = $scheduler;
	}

	/**
	 * Register a separate bounded Tools surface rather than changing Preview semantics.
	 *
	 * @return void
	 */
	public function register() {
		add_management_page(
			__( 'تأیید قرارداد دریافت مقاله‌های کانون', 'ksh-kanoon-articles' ),
			__( 'تأیید دریافت مقاله‌های کانون', 'ksh-kanoon-articles' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Execute the protected owner action and show current bounded qualification state.
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
			$result = $this->qualification->qualify_current();

			if ( 'qualified' === $result['status'] && $this->qualification->is_qualified() ) {
				$this->scheduler->ensure_scheduled();
			}
		}

		$state      = $this->qualification->state();
		$qualified  = $this->qualification->is_qualified();
		$contract   = $this->qualification->current_contract_id();
		$stored_id  = is_array( $state ) && isset( $state['contract_id'] ) ? (string) $state['contract_id'] : '';
		$stored_at  = is_array( $state ) && isset( $state['qualified_at'] ) ? (string) $state['qualified_at'] : '';
		?>
		<div class="wrap" dir="rtl">
			<h1><?php echo esc_html__( 'تأیید قرارداد دریافت مقاله‌های کانون', 'ksh-kanoon-articles' ); ?></h1>
			<p><?php echo esc_html__( 'این اقدام، همان acquisition/parsing مورد استفاده در Preview را روی قرارداد فعلی اجرا می‌کند. فقط اگر هر دو فهرست کاملاً موفق و غیرخالی باشند، یک وضعیت تأیید نسخه‌دار ثبت می‌شود و Refresh دستی/زمان‌بندی‌شده برای همان قرارداد مجاز خواهد شد.', 'ksh-kanoon-articles' ); ?></p>
			<p><?php echo esc_html__( 'Preview عادی همچنان read-only است و این صفحه Snapshot مقاله، attempt یا run-summary را مستقیماً تغییر نمی‌دهد.', 'ksh-kanoon-articles' ); ?></p>

			<table class="widefat striped" style="max-width: 900px;">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'قرارداد فعلی', 'ksh-kanoon-articles' ); ?></th>
						<td><bdi dir="ltr"><?php echo esc_html( $contract ); ?></bdi></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'وضعیت admission', 'ksh-kanoon-articles' ); ?></th>
						<td><?php echo $qualified ? esc_html__( 'تأییدشده برای همین قرارداد', 'ksh-kanoon-articles' ) : esc_html__( 'تأییدنشده / stale', 'ksh-kanoon-articles' ); ?></td>
					</tr>
					<?php if ( '' !== $stored_id ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html__( 'قرارداد ذخیره‌شده', 'ksh-kanoon-articles' ); ?></th>
							<td><bdi dir="ltr"><?php echo esc_html( $stored_id ); ?></bdi></td>
						</tr>
					<?php endif; ?>
					<?php if ( '' !== $stored_at ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html__( 'زمان تأیید ذخیره‌شده', 'ksh-kanoon-articles' ); ?></th>
							<td><bdi dir="ltr"><?php echo esc_html( $stored_at ); ?></bdi></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( is_array( $result ) ) : ?>
				<?php $this->render_result( $result ); ?>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( self::NONCE ); ?>
				<?php submit_button( __( 'اعتبارسنجی و فعال‌سازی قرارداد دریافت فعلی', 'ksh-kanoon-articles' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render only truthful qualification outcomes.
	 *
	 * @param array<string,mixed> $result Qualification result.
	 * @return void
	 */
	private function render_result( $result ) {
		$status = isset( $result['status'] ) ? (string) $result['status'] : 'failure';
		$reason = isset( $result['reason'] ) ? (string) $result['reason'] : '';
		$class  = 'qualified' === $status ? 'notice-success' : 'notice-error';
		$text   = 'qualified' === $status
			? __( 'تأیید موفق: قرارداد فعلی پس از اجرای موفق همان checks واقعی Preview برای هر دو فهرست، برای Refresh نوشتنی پذیرفته شد.', 'ksh-kanoon-articles' )
			: __( 'تأیید انجام نشد: قرارداد فعلی برای Refresh نوشتنی همچنان مسدود است.', 'ksh-kanoon-articles' );
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> inline">
			<p><strong><?php echo esc_html( $text ); ?></strong></p>
			<?php if ( '' !== $reason ) : ?>
				<p><bdi dir="ltr"><?php echo esc_html( $reason ); ?></bdi></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
