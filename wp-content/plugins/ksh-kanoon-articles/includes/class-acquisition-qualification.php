<?php
/**
 * Versioned acquisition-contract qualification admission.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Owns one bounded qualification record for the current acquisition contract.
 */
final class Acquisition_Qualification {

	const SCHEMA_VERSION = 1;
	const OPTION_STATE   = 'ksh_kanoon_articles_acquisition_qualification';

	/**
	 * Read-only candidate producer used only by the explicit qualification action.
	 *
	 * @var Preview_Service|null
	 */
	private $preview;

	/**
	 * WordPress option reader.
	 *
	 * @var callable
	 */
	private $get_option;

	/**
	 * WordPress option creator.
	 *
	 * @var callable
	 */
	private $add_option;

	/**
	 * WordPress option updater.
	 *
	 * @var callable
	 */
	private $update_option;

	/**
	 * UTC timestamp producer.
	 *
	 * @var callable
	 */
	private $clock;

	/**
	 * Build qualification state access. Preview is optional for read-only status checks.
	 *
	 * @param Preview_Service|null $preview       Exact current Preview service.
	 * @param callable|null        $get_option    Option reader.
	 * @param callable|null        $add_option    Option creator.
	 * @param callable|null        $update_option Option updater.
	 * @param callable|null        $clock         UTC timestamp producer.
	 */
	public function __construct( Preview_Service $preview = null, $get_option = null, $add_option = null, $update_option = null, $clock = null ) {
		$this->preview       = $preview;
		$this->get_option    = $get_option ? $get_option : 'get_option';
		$this->add_option    = $add_option ? $add_option : 'add_option';
		$this->update_option = $update_option ? $update_option : 'update_option';
		$this->clock         = $clock ? $clock : array( __CLASS__, 'utc_now' );
	}

	/**
	 * Return the explicit current acquisition-contract identity.
	 *
	 * @return string
	 */
	public function current_contract_id() {
		return Source_Config::ACQUISITION_CONTRACT_ID;
	}

	/**
	 * Determine whether the exact current contract has a valid persisted qualification.
	 *
	 * @return bool
	 */
	public function is_qualified() {
		$state = $this->state();

		return is_array( $state )
			&& self::SCHEMA_VERSION === ( isset( $state['schema_version'] ) ? (int) $state['schema_version'] : 0 )
			&& 'qualified' === ( isset( $state['status'] ) ? (string) $state['status'] : '' )
			&& $this->current_contract_id() === ( isset( $state['contract_id'] ) ? (string) $state['contract_id'] : '' )
			&& ! empty( $state['qualified_at'] );
	}

	/**
	 * Read the bounded persisted qualification state without mutation.
	 *
	 * @return array<string,mixed>|null
	 */
	public function state() {
		$get   = $this->get_option;
		$state = $get( self::OPTION_STATE, null );

		return is_array( $state ) ? $state : null;
	}

	/**
	 * Execute the exact current Preview checks and qualify only when both lists pass.
	 *
	 * Failed, partial, ambiguous, or zero-item Preview results never write a current
	 * qualification state. Historical/stale state is preserved as historical data but
	 * remains unusable because is_qualified() binds admission to the exact contract id.
	 *
	 * @return array<string,mixed>
	 */
	public function qualify_current() {
		if ( ! $this->preview instanceof Preview_Service ) {
			return $this->qualification_result( 'failure', 'qualification_preview_unavailable', null, false );
		}

		$preview = $this->preview->run();
		if ( ! $this->preview_succeeds( $preview ) ) {
			return $this->qualification_result( 'rejected', 'preview_not_fully_successful', $preview, false );
		}

		$clock = $this->clock;
		$state = array(
			'schema_version' => self::SCHEMA_VERSION,
			'status'         => 'qualified',
			'contract_id'    => $this->current_contract_id(),
			'qualified_at'   => (string) $clock(),
			'latest'         => $this->list_evidence( $preview['latest'] ),
			'weekly_popular' => $this->list_evidence( $preview['weekly_popular'] ),
		);

		if ( ! $this->write_state( $state ) ) {
			return $this->qualification_result( 'failure', 'qualification_state_write_failed', $preview, false );
		}

		return $this->qualification_result( 'qualified', '', $preview, true );
	}

	/**
	 * Verify the exact success predicate used for admission.
	 *
	 * @param mixed $preview Combined Preview result.
	 * @return bool
	 */
	private function preview_succeeds( $preview ) {
		if (
			! is_array( $preview ) ||
			'success' !== ( isset( $preview['overall_status'] ) ? (string) $preview['overall_status'] : '' ) ||
			! isset( $preview['latest'], $preview['weekly_popular'] ) ||
			! is_array( $preview['latest'] ) ||
			! is_array( $preview['weekly_popular'] )
		) {
			return false;
		}

		foreach ( array( $preview['latest'], $preview['weekly_popular'] ) as $result ) {
			if (
				'success' !== ( isset( $result['status'] ) ? (string) $result['status'] : '' ) ||
				! isset( $result['count'] ) ||
				(int) $result['count'] < 1 ||
				empty( $result['items'] ) ||
				! is_array( $result['items'] )
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Keep only bounded successful qualification evidence.
	 *
	 * @param array<string,mixed> $result One Preview list result.
	 * @return array<string,mixed>
	 */
	private function list_evidence( $result ) {
		$http_code = isset( $result['request']['http_code'] ) ? $result['request']['http_code'] : null;

		return array(
			'count'      => isset( $result['count'] ) ? (int) $result['count'] : 0,
			'http_code'  => is_numeric( $http_code ) ? (int) $http_code : null,
			'source_url' => isset( $result['source_url'] ) ? (string) $result['source_url'] : '',
		);
	}

	/**
	 * Persist one non-autoloaded current qualification record and verify readback.
	 *
	 * @param array<string,mixed> $state Qualification state.
	 * @return bool
	 */
	private function write_state( $state ) {
		$get    = $this->get_option;
		$add    = $this->add_option;
		$update = $this->update_option;

		$existing = $get( self::OPTION_STATE, null );
		if ( null === $existing ) {
			$add( self::OPTION_STATE, $state, '', false );
		} else {
			$update( self::OPTION_STATE, $state, false );
		}

		return $state === $get( self::OPTION_STATE, null );
	}

	/**
	 * Build a bounded action result without implying qualification on failure.
	 *
	 * @param string                   $status         Result status.
	 * @param string                   $reason         Bounded reason.
	 * @param array<string,mixed>|null $preview        Preview evidence when executed.
	 * @param bool                     $state_recorded Whether current qualification state persisted.
	 * @return array<string,mixed>
	 */
	private function qualification_result( $status, $reason, $preview, $state_recorded ) {
		return array(
			'status'         => $status,
			'reason'         => $reason,
			'contract_id'    => $this->current_contract_id(),
			'state_recorded' => (bool) $state_recorded,
			'preview'        => $preview,
		);
	}

	/**
	 * Produce a stable UTC qualification timestamp.
	 *
	 * @return string
	 */
	public static function utc_now() {
		return gmdate( 'c' );
	}
}
