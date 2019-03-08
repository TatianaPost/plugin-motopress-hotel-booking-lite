<?php

namespace MPHB\Crons;

class CronManager {

	const INTERVAL_PENDING_USER_APPROVAL		 = 'mphb_pending_user_approval';
	const INTERVAL_PENDING_PAYMENT			 = 'mphb_pending_payment';
	// Default WordPress intervals
	const INTERVAL_DAILY					 = 'daily';
	const INTERVAL_TWICE_DAILY				 = 'twicedaily';
	const INTERVAL_HOURLY					 = 'hourly';

	/**
	 *
	 * @var Cron[]
	 */
	private $crons = array();

	public function __construct(){
		add_filter( 'cron_schedules', array( $this, 'createCronIntervals' ) );

		$this->initCrons();
	}

	public function initCrons(){
		$crons = array(
			new AbandonBookingPendingUserCron( 'abandon_booking_pending_user', self::INTERVAL_PENDING_USER_APPROVAL ),
			new AbandonBookingPendingPaymentCron( 'abandon_booking_pending_payment', self::INTERVAL_PENDING_PAYMENT ),
			new AbandonPaymentPendingCron( 'abandon_payment_pending', self::INTERVAL_PENDING_PAYMENT ),
		);

		foreach ( $crons as $cron ) {
			$this->addCron( $cron );
		}
	}

	/**
	 *
	 * @param Cron $cron
	 */
	public function addCron( $cron ){
		$this->crons[$cron->getId()] = $cron;
	}

	/**
	 *
	 * @param string $id
	 * @return Cron|null
	 */
	public function getCron( $id ){
		return isset( $this->crons[$id] ) ? $this->crons[$id] : null;
	}

	/**
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function createCronIntervals( $schedules ){

		$schedules[self::INTERVAL_PENDING_USER_APPROVAL] = array(
			'interval'	 => MPHB()->settings()->main()->getUserApprovalTime() * MINUTE_IN_SECONDS,
			'display'	 => __( 'User Approval Time setted in Hotel Booking Settings', 'motopress-hotel-booking' )
		);

		$schedules[self::INTERVAL_PENDING_PAYMENT] = array(
			'interval'	 => MPHB()->settings()->payment()->getPendingTime() * MINUTE_IN_SECONDS,
			'display'	 => __( 'Pending Payment Time set in Hotel Booking Settings', 'motopress-hotel-booking' )
		);

		return $schedules;
	}


}
