<?php
namespace AIOSEO\Plugin\Pro\SearchStatistics;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\SearchStatistics as Main;
use AIOSEO\Plugin\Pro\Models\SearchStatistics as Models;

/**
 * Handles the Inspection Result scan.
 *
 * @since 4.5.0
 */
class UrlInspection {
	/**
	 * The action name.
	 *
	 * @since 4.5.0
	 *
	 * @var string
	 */
	public $action = 'aioseo_search_statistics_url_inspection_scan';

	/**
	 * Class constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {
		if ( ! aioseo()->license->hasCoreFeature( 'search-statistics', 'index-status' ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( $this->action, [ $this, 'scan' ] );
	}

	/**
	 * Initialize the objects.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function init() {
		if ( ! aioseo()->searchStatistics->api->auth->isConnected() ) {
			return;
		}

		$this->scheduleInitialScan();
	}

	/**
	 * Schedules the initial scan.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	private function scheduleInitialScan() {
		if ( aioseo()->actionScheduler->isScheduled( $this->action ) ) {
			return;
		}

		aioseo()->actionScheduler->scheduleAsync( $this->action );
	}

	/**
	 * Resets all the inspection_results and force scanning again.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function reset() {
		aioseo()->core->db->update( 'aioseo_search_statistics_objects as asso' )
			->set(
				[
					'inspection_result'      => null,
					'inspection_result_date' => null,
					'indexed'                => 0
				]
			)
			->run();

		as_unschedule_all_actions( $this->action );
		$this->scheduleInitialScan();
	}

	/**
	 * Scans for the objects that are missing the inspection_result.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	public function scan() {
		// We will rescan a post if:
		// - inspection_result is NULL which means was never scanned; or
		// - inspection_result_date is older than 30 days; or
		// - indexed is 0 and inspection_result_date is older than 1 day and created is not older than 14 days and object ID is not null.
		$missingInspectionResult = aioseo()->core->db->start( 'aioseo_search_statistics_objects as asso' )
			->select( 'DISTINCT asso.id, asso.object_path' )
			->whereRaw( '
				asso.inspection_result IS NULL OR
				asso.inspection_result_date < \'' . aioseo()->helpers->timeToMysql( strtotime( '-1 month' ) ) . '\' OR
				(
					asso.indexed = 0 AND
					asso.inspection_result_date < \'' . aioseo()->helpers->timeToMysql( strtotime( '-1 day' ) ) . '\' AND
					asso.created > \'' . aioseo()->helpers->timeToMysql( strtotime( '-14 days' ) ) . '\' AND
					asso.id IS NOT NULL
				)
			' )
			->limit( 20 )
			->run()
			->result();

		if ( empty( $missingInspectionResult ) ) {
			aioseo()->actionScheduler->scheduleSingle( $this->action, DAY_IN_SECONDS );

			return;
		}

		$api      = new Main\Api\Request( 'google-search-console/url-inspection/', [ 'paths' => wp_list_pluck( $missingInspectionResult, 'object_path' ) ], 'GET' );
		$response = $api->request();

		if ( is_wp_error( $response ) || empty( $response['data'] ) ) {
			aioseo()->actionScheduler->scheduleSingle( $this->action, HOUR_IN_SECONDS );

			return;
		}

		foreach ( $missingInspectionResult as $missingObject ) {
			if ( empty( $response['data'][ $missingObject->object_path ] ) ) {
				continue;
			}

			$data   = $response['data'][ $missingObject->object_path ];
			$object = [
				'id'                     => $missingObject->id,
				'object_path'            => $missingObject->object_path,
				'inspection_result'      => $data,
				'inspection_result_date' => current_time( 'mysql' ),
				'indexed'                => ! empty( $data['indexStatusResult']['verdict'] ) && 'PASS' === $data['indexStatusResult']['verdict'] ? 1 : 0,
			];

			Models\WpObject::update( $object );
		}

		aioseo()->actionScheduler->scheduleSingle( $this->action, MINUTE_IN_SECONDS );
	}
}