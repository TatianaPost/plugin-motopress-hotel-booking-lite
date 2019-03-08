<?php

namespace MPHB;

use MPHB\iCal\BackgroundProcesses\QueuedSynchronizer;

class Upgrader {

	const OPTION_MIN_DB_VERSION		 = '1.0.1';
	const OPTION_DB_VERSION			 = 'mphb_db_version';
	const OPTION_DB_VERSION_HISTORY	 = 'mphb_db_version_history';

	private $isNeedUpgrade = false;

	/**
	 *
	 * @var Upgrades\BackgroundBookingUpgrader_2_0_0
	 */
	private $bgBookingUpgrader2_0_0;

	/**
	 *
	 * @var Upgrades\BackgroundBookingUpgrader_2_2_0
	 */
	private $bgBookingUpgrader2_2_0;

	/**
	 *
	 * @var Upgrades\BackgroundBookingUpgrader_2_3_0
	 */
	private $bgBookingUpgrader2_3_0;

	/**
	 *
	 * @var Upgrades\BackgroundUpgrader
	 */
	private $bgUpgrader;

	/**
	 *
	 * @var array
	 */
	private $upgrades = array(
		'1.1.0'	 => array(
			'fixForV1_1_0'
		),
		'2.0.0'	 => array(
			'fixForV2_0_0'
		),
		'2.2.0'	 => array(
			'fixForV2_2_0',
			'fixSessionOptions'
		),
		'2.3.0'	 => array(
			'fixForV2_3_0',
			'fixGlobalRule',
			'fixCleanOldRules'
		),
		'2.4.2'	 => array(
			'flushRewriteRules'
		),
		'3.0.0' => array(
			'fixForV3_0_0'
		),
		'3.0.2' => array(
			'fixForV3_0_2'
		)
	);

	public function __construct(){

		$this->bgBookingUpgrader2_0_0	 = new Upgrades\BackgroundBookingUpgrader_2_0_0();
		$this->bgBookingUpgrader2_2_0	 = new Upgrades\BackgroundBookingUpgrader_2_2_0();
		$this->bgBookingUpgrader2_3_0	 = new Upgrades\BackgroundBookingUpgrader_2_3_0();
		$this->bgUpgrader				 = new Upgrades\BackgroundUpgrader();

		$this->checkVersion();

		add_action( 'init', array( $this, 'upgrade' ) );
		add_action( 'admin_init', array( $this, 'showUpgradeNotice' ) );
		add_action( 'admin_init', array( $this, 'maybeForceUpgrade' ) );

		add_action( 'mphb_import_end', array( $this, 'upgradeAfterImport' ) );
	}

	public function checkVersion(){
		$dbVersion = $this->bgUpgrader->is_in_progress() ? $this->getScheduledVersion() : $this->getCurrentDBVersion();

		if ( version_compare( MPHB()->getVersion(), $dbVersion, '>' ) ) {
			$this->isNeedUpgrade = true;
		}

		if ( version_compare( '2.0.0', $this->getCurrentDBVersion(), '>' ) ) {
			$this->blockNewBookings();
		}
	}

	private function blockNewBookings(){
		add_filter( 'mphb_block_booking', '__return_true' );
	}

	public function upgrade(){

		if ( !$this->isNeedUpgrade ) {
			return;
		}

		ignore_user_abort( true );
		mphb_set_time_limit( 0 );

		$newBatchesCount = 0;
		foreach ( $this->upgrades as $upgradeVersion => $callbacks ) {
			if ( version_compare( $this->getScheduledVersion(), $upgradeVersion, '<' ) ) {
				$this->bgUpgrader->data( $callbacks )->save()->data( array() );
				$newBatchesCount += count( $callbacks );
				$this->setScheduledVersion( $upgradeVersion );
			}
		}

		$this->bgUpgrader->push_to_queue( 'complete' )->save();
		$this->setScheduledVersion( MPHB()->getVersion() );
		$newBatchesCount++;

		$this->setTotalQueueSize( $this->getTotalQueueSize() + $newBatchesCount );

		$this->bgUpgrader->dispatch();

		return;
	}

	public function maybeForceUpgrade(){
		if ( isset( $_GET['mphb_force_upgrade'] ) && $_GET['mphb_force_upgrade'] ) {
			do_action( $this->bgUpgrader->get_identifier() . '_cron' );
			do_action( $this->bgBookingUpgrader2_0_0->get_identifier() . '_cron' );
			do_action( $this->bgBookingUpgrader2_2_0->get_identifier() . '_cron' );
			do_action( $this->bgBookingUpgrader2_3_0->get_identifier() . '_cron' );
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	public function showUpgradeNotice(){
		if ( $this->bgUpgrader->is_in_progress() ) {

			// fix for bug with extra batches lead to negative procent
			$totalQueueSize		 = max( $this->getTotalQueueSize(), $this->getQueueSize() );
			$completedItemsCount = $totalQueueSize - $this->getQueueSize();

			if ( $totalQueueSize != 0 ) {
				$percent = round( ($completedItemsCount / $totalQueueSize) * 100 );
			} else {
				$percent = 100;
			}

			$forceUpgradeUrl = add_query_arg( 'mphb_force_upgrade', 'true', MPHB()->getSettingsMenuPage()->getUrl() );
			$message		 = '<p>';
			$message .= '<strong>' . __( 'Hotel Booking Plugin', 'motopress-hotel-booking' ) . '</strong> &#8211; ';
			$message .= __( 'Your database is being updated in the background.', 'motopress-hotel-booking' );
			$message .= '<a href="' . esc_url( $forceUpgradeUrl ) . '">' . __( 'Taking a while? Click here to run it now.', 'motopress-hotel-booking' ) . '</a>';
			$message .= " ($percent%)";
			$message .= '</p>';
			MPHB()->notices()->addNotice( $message );
		}
	}

	/**
	 * fix for 1.1.0
	 * - Change abandon cron name
	 * - Change option name for Cancellation Page
	 */
	public function fixForV1_1_0(){

		$deprecatedAction = 'mphb_abandon_bookings';
		if ( wp_next_scheduled( $deprecatedAction ) ) {
			wp_clear_scheduled_hook( $deprecatedAction );
			MPHB()->cronManager()->getCron( 'abandon_booking_pending_user' )->schedule();
		}

		$this->changeOptionName( 'mphb_user_cancel_redirect', 'mphb_user_cancel_redirect_page' );

		$this->updateDBVersion( '1.1.0' );

		return false;
	}

	/**
	 * fix for 2.0.0
	 * - update bookings structure in db ( create reserved rooms )
	 *
	 * @return string|boolean False when update completed, function name otherwise.
	 */
	public function fixForV2_0_0(){

		if ( $this->bgBookingUpgrader2_0_0->is_in_progress() ) {
			$this->bgUpgrader->pause();
			return __FUNCTION__;
		}

		$oldBookingsAtts = array(
			'posts_per_page'		 => -1,
			'post_type'				 => 'mphb_booking',
			'suppress_filters'		 => false,
			'ignore_sticky_posts'	 => true,
			'fields'				 => 'ids',
			'post_status'			 => 'any',
			'meta_query'			 => array(
				array(
					'key'		 => 'mphb_room_id',
					'compare'	 => 'EXISTS',
				)
			),
			'orderby'				 => 'ID',
			'order'					 => 'ASC'
		);

		$oldBookingsIds = get_posts( $oldBookingsAtts );

		if ( empty( $oldBookingsIds ) ) {
			// upgrade 2.0.0 completed
			return false;
		}

		$oldBookingsChunked = array_chunk( $oldBookingsIds, Upgrades\BackgroundBookingUpgrader_2_0_0::BATCH_SIZE );

		foreach ( $oldBookingsChunked as $bookings ) {
			$this->bgBookingUpgrader2_0_0->data( $bookings )->save();
		}

		$this->setTotalQueueSize( $this->getTotalQueueSize() + count( $oldBookingsChunked ) );

		$this->bgUpgrader->wait_action( $this->bgBookingUpgrader2_0_0->get_identifier() . '_complete' );

		$this->bgBookingUpgrader2_0_0->dispatch();

		$this->bgUpgrader->pause();

		return __FUNCTION__;
	}

	/**
	 * fix for 2.2.0
	 * - generate UID for all  ( create reserved rooms )
	 *
	 * @return string|boolean False when update completed, function name otherwise.
	 */
	public function fixForV2_2_0(){

		if ( $this->bgBookingUpgrader2_2_0->is_in_progress() ) {
			$this->bgUpgrader->pause();
			return __FUNCTION__;
		}

		$roomsWithNoUid = get_posts( array(
			'posts_per_page'		 => -1,
			'post_type'				 => 'mphb_reserved_room',
			'suppress_filters'		 => false,
			'ignore_sticky_posts'	 => true,
			'fields'				 => 'ids',
			'post_status'			 => 'any',
			'meta_query'			 => array(
				array(
					'key'		 => '_mphb_uid',
					'compare'	 => 'NOT EXISTS',
				)
			),
			'orderby'				 => 'ID',
			'order'					 => 'ASC'
			) );

		if ( empty( $roomsWithNoUid ) ) {
			// upgrade 2.2.0 completed
			return false;
		}

		$batches = array_chunk( $roomsWithNoUid, Upgrades\BackgroundBookingUpgrader_2_2_0::BATCH_SIZE );

		foreach ( $batches as $batch ) {
			$this->bgBookingUpgrader2_2_0->data( $batch )->save();
		}

		$this->setTotalQueueSize( $this->getTotalQueueSize() + count( $batches ) );

		$this->bgUpgrader->wait_action( $this->bgBookingUpgrader2_2_0->get_identifier() . '_complete' );

		$this->bgBookingUpgrader2_2_0->dispatch();

		$this->bgUpgrader->pause();

		return __FUNCTION__;
	}

	/**
	 *
	 * @global \WPDB $wpdb
	 */
	public function fixSessionOptions(){
		global $wpdb;

		// Length of prefix _mphb_wp_session_expires_
		$prefixLength = 25;

		$expirationKeys	 = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_mphb_wp_session_expires_%'" );
		$notIn			 = $expirationKeys;
		foreach ( $expirationKeys as $optionName ) {
			$session_id	 = substr( $optionName, $prefixLength );
			$notIn[]	 = "_mphb_wp_session_$session_id";
		}

		$notIn = join( "','", $notIn );

		$wpdb->query( "DELETE FROM $wpdb->options WHERE ( option_name LIKE '_mphb_wp_session_%' ) AND ( option_name NOT IN ('$notIn') )" );

		return false;
	}

	/**
	 * Fix for 2.3.0
	 * - generate reservation rules for global booking rules;
	 * - generate new custom rules (with type ID and room number);
	 * - delete global booking rules and old custom rules.
	 *
	 * @return string|boolean False when update completed, function name otherwise.
	 */
	public function fixForV2_3_0(){
		if ( $this->bgBookingUpgrader2_3_0->is_in_progress() ) {
			$this->bgUpgrader->pause();
			return __FUNCTION__;
		}

		$customRules = get_option( 'mphb_custom_booking_rules', array() );

		if ( empty( $customRules ) ) {
			return false;
		}

		$this->bgBookingUpgrader2_3_0->data( $customRules['items'] )->save();

		$this->setTotalQueueSize( $this->getTotalQueueSize() + 1 );

		$this->bgUpgrader->wait_action( $this->bgBookingUpgrader2_3_0->get_identifier() . '_complete' );

		$this->bgBookingUpgrader2_3_0->dispatch();

		$this->bgUpgrader->pause();

		return false;
	}

	public function fixGlobalRule(){
		$globalRules = array(
			'check_in_days'   => get_option( 'mphb_global_check_in_days', array_keys( \MPHB\Utils\DateUtils::getDaysList() ) ),
			'check_out_days'  => get_option( 'mphb_global_check_out_days', array_keys( \MPHB\Utils\DateUtils::getDaysList() ) ),
			'min_stay_length' => (int)get_option( 'mphb_global_min_days', 1 ),
			'max_stay_length' => (int)get_option( 'mphb_global_max_days', 15 )
		);

		// Save parts of reservation rules
		foreach ( $globalRules as $option => $value ) {
			// No need to save default values for "min_stay_length",
			// "check_in_days" and "check_out_days"
			if ( $option == 'min_stay_length' ) {
				if ( $value == 1 ) {
					continue;
				}
			} else if ( $option == 'check_in_days' || $option == 'check_out_days' ) {
				if ( count( $value ) == 7 ) {
					continue;
				}
			}

			$value = array( array(
				$option			 => $value,
				'room_type_ids'	 => array( 0 ),
				'season_ids'	 => array( 0 )
			) );

			update_option( 'mphb_' . $option, $value, 'no' );

		}

		return false;
	}

	public function fixCleanOldRules(){
		delete_option( 'mphb_global_min_days' );
		delete_option( 'mphb_global_max_days' );
		delete_option( 'mphb_global_check_in_days' );
		delete_option( 'mphb_global_check_out_days' );
		delete_option( 'mphb_custom_booking_rules' );

		return false;
	}

    public function flushRewriteRules(){
        flush_rewrite_rules();

        return false;
    }

	/**
	 * Delete unused rules options
	 */
    public function fixForV3_0_0(){
    	delete_option( 'mphb_booking_rules_reservation' );
	    delete_option( 'mphb_booking_rules_seasons' );
	    delete_option( 'mphb_booking_rules_season_priorities');
    }

	/**
     * 1) Create new tables.
	 * 2) Remove outdated sync options.
	 * 3) Move value from "From Email" to "Hotel Administrator Email".
	 */
	public function fixForV3_0_2(){
		global $wpdb;

        // 1) Create new tables
        \HotelBookingPlugin::createTables();

		// 2) Remove outdated sync options (but leave old logs, just in case)
		$wpdb->query("DELETE FROM {$wpdb->options}"
            . " WHERE (`option_name` LIKE '%mphb_ical_upload%' OR `option_name` LIKE '%mphb_ical_sync%')"
            . " AND `option_name` != 'mphb_ical_sync_rooms_queue_processed_data'");

		// 3) Move "From Email" to "Hotel Administrator Email"
		$fromEmail = get_option( 'mphb_email_from_email', '' );

		update_option( 'mphb_email_hotel_admin_email', $fromEmail );
		update_option( 'mphb_email_from_email', '' );
	}

	/**
	 *
	 * @param string $version
	 */
	public function updateDBVersion( $version = null ){

		if ( is_null( $version ) ) {
			$version = MPHB()->getVersion();
		}

		update_option( self::OPTION_DB_VERSION, $version );

		if ( version_compare( $this->getCurrentDBVersion(), $version, '!=' ) ) {
			$this->addDBVersionToHistory( $version );
		}

		if ( version_compare( $this->getScheduledVersion(), $version, '<=' ) ) {
			$this->setScheduledVersion( false );
		}
	}

	/**
	 *
	 * @return string
	 */
	private function getCurrentDBVersion(){
		return get_option( self::OPTION_DB_VERSION, self::OPTION_MIN_DB_VERSION );
	}

	public function setScheduledVersion( $version = null ){
		if ( !empty( $version ) ) {
			update_option( 'mphb_scheduled_version', $version );
		} else {
			delete_option( 'mphb_scheduled_version' );
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getScheduledVersion(){
		return get_option( 'mphb_scheduled_version', $this->getCurrentDBVersion() );
	}

	/**
	 *
	 * @param string $version
	 */
	private function addDBVersionToHistory( $version ){
		$dbVersionHistory	 = get_option( self::OPTION_DB_VERSION_HISTORY, array() );
		$dbVersionHistory[]	 = $version;
		update_option( self::OPTION_DB_VERSION_HISTORY, $dbVersionHistory );
	}

	/**
	 * @todo add support to false value of option
	 *
	 * @param string $oldName
	 * @param string $name
	 */
	private function changeOptionName( $oldName, $name ){
		$optionValue = get_option( $oldName );

		if ( false !== $optionValue ) {
			delete_option( $oldName );
			update_option( $name, $optionValue );
		}
	}

	public function resetDBVersion(){
		delete_option( self::OPTION_DB_VERSION );
	}

	public function upgradeAfterImport(){
		$this->resetDBVersion();
	}

	private function getTotalQueueSize(){
		return (int) get_option( 'mphb_upgrade_queue_size', 0 );
	}

	private function setTotalQueueSize( $size ){
		update_option( 'mphb_upgrade_queue_size', $size );
	}

	private function getQueueSize(){
		return $this->bgUpgrader->get_queue_size() + $this->bgBookingUpgrader2_0_0->get_queue_size();
	}

	public function complete(){
		$this->updateDBVersion( MPHB()->getVersion() );

		// clear total size
		delete_option( 'mphb_upgrade_queue_size' );
	}

}
