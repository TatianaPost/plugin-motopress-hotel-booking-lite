<?php

namespace MPHB\BookingRules\Custom;

use \MPHB\BookingRules\RuleVerifiable;
use \MPHB\Utils\DateUtils;

class CustomRule implements RuleVerifiable {

	/**
	 *
	 * @var \DateTime
	 */
	protected $dateFrom;

	/**
	 *
	 * @var \DateTime
	 */
	protected $dateTo;

	/**
	 *
	 * @var bool
	 */
	protected $notCheckIn = false;

	/**
	 *
	 * @var bool
	 */
	protected $notCheckOut = false;

	/**
	 *
	 * @var bool
	 */
	protected $notStayIn = false;

	/**
	 *
	 * @var string
	 */
	protected $comment = '';

	/**
	 *
	 * @param array $atts
	 * @param \DateTime $atts['date_from']
	 * @param \DateTime $atts['date_to']
	 * @param array $atts['restrictions']
	 */
	protected function __construct( $atts ){
		$this->dateFrom		 = $atts['date_from'];
		$this->dateTo		 = $atts['date_to'];
		$this->notCheckIn	 = in_array( 'check-in', $atts['restrictions'] );
		$this->notCheckOut	 = in_array( 'check-out', $atts['restrictions'] );
		$this->notStayIn	 = in_array( 'stay-in', $atts['restrictions'] );
		$this->comment		 = $atts['comment'];
	}

	public function getRestrictions(){
		return array(
			'not_check_in'	 => $this->notCheckIn,
			'not_check_out'	 => $this->notCheckOut,
			'not_stay_in'	 => $this->notStayIn
		);
	}

	/**
	 *
	 * @return array ["2017-01-01" => ["not_check_in" => true,
	 * "not_check_out" => true, "not_stay_in" => true], "2017-01-02" => ...]
	 */
	public function getRestrictionsByDays(){
		$dateFormat   = MPHB()->settings()->dateTime()->getDateTransferFormat();
		$restrictions = $this->getRestrictions();

		$dates = array();

		foreach ( $this->getPeriodDates() as $date ) {
			$date = $date->format( $dateFormat );
			$dates[$date] = $restrictions;
		}

		return $dates;
	}

	public function getBlockedDates(){
		$restrictions = $this->getRestrictions();

		if ( !$restrictions['not_stay_in'] ) {
			return array();
		}

		$dates = $this->getPeriodDates();

		// Format all dates
		$dateFormat = MPHB()->settings()->dateTime()->getDateTransferFormat();
		foreach ( $dates as &$date ) {
			$date = $date->format( $dateFormat );
		}
		unset( $date );

		return $dates;
	}

	public function getPeriodDates(){
		$period = DateUtils::createDatePeriod( $this->dateFrom, $this->dateTo, true );
		return iterator_to_array( $period );
	}

	public function getComment(){
		return $this->comment;
	}

	/**
	 *
	 * @return bool
	 */
	public function isBlocked(){
		return $this->notStayIn;
	}

	public function verify( \DateTime $checkInDate, \DateTime $checkOutDate, $roomTypeId = 0 ){
		if ( $this->noCheckIn( $checkInDate ) || $this->noCheckOut( $checkOutDate ) || $this->noStayIn( $checkInDate, $checkOutDate ) ) {
			return false;
		} else {
			return true;
		}
	}

	protected function noCheckIn( \DateTime $checkInDate ){
		return $this->notCheckIn
			&& $this->compareDates( $checkInDate, '>=', $this->dateFrom )
			&& $this->compareDates( $checkInDate, '<=', $this->dateTo );
	}

	protected function noCheckOut( \DateTime $checkOutDate ){
		return $this->notCheckOut
			&& $this->compareDates( $checkOutDate, '>=', $this->dateFrom )
			&& $this->compareDates( $checkOutDate, '<=', $this->dateTo );
	}

	protected function noStayIn( \DateTime $checkInDate, \DateTime $checkOutDate ){
		if ( !$this->notStayIn ) {
			return false;
		}

		$beforeCheckOut = clone $checkOutDate;
		$beforeCheckOut->modify( '-1 day' );

		return $this->compareDates( $this->dateFrom, '<=', $beforeCheckOut )
			&& $this->compareDates( $this->dateTo, '>=', $checkInDate );
	}

	protected function compareDates( \DateTime $date1, $operator, \DateTime $date2 ){
		$date1 = $date1->format( 'Ymd' );
		$date2 = $date2->format( 'Ymd' );

		switch ( $operator ) {
			case '>=': return ( $date1 >= $date2 ); break;
			case '<=': return ( $date1 <= $date2 ); break;
		}

		return false;
	}

	/**
	 *
	 * @param array $atts
	 * @param string $atts['date_from']
	 * @param string $atts['date_to']
	 * @param array $atts['restrictions']
	 *
	 * @return \MPHB\BookingRules\Custom\CustomRule
	 */
	public static function create( $atts ){
		$dateFormat = MPHB()->settings()->dateTime()->getDateTransferFormat(); // Y-m-d

		$atts['date_from'] = \DateTime::createFromFormat( $dateFormat, $atts['date_from'] );
		$atts['date_to']   = \DateTime::createFromFormat( $dateFormat, $atts['date_to'] );

		if ( !$atts['date_from'] || !$atts['date_to'] ) {
			return null;
		}

		if ( DateUtils::calcNights( $atts['date_from'], $atts['date_to'] ) < 0 ) {
			return null;
		}

		if ( !isset( $atts['comment'] ) ) {
			$atts['comment'] = '';
		}

		return new self( $atts );
	}

}
