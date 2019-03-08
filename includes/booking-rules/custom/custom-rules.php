<?php

namespace MPHB\BookingRules\Custom;

use \MPHB\BookingRules\RuleVerifiable;

class CustomRules implements RuleVerifiable {

	/**
	 *
	 * @var CustomRule[] [%Type ID% => CustomRule[]]
	 */
	private $globals = array();

	/**
	 * Also <b>contains</b> global rules.
	 * @var array [%Type ID% => ["room_id" => ..., "rule" => CustomRule]]
	 */
	private $rules = array();

	public function __construct( $customRules ){
		foreach ( $customRules as $customRule ) {
			$ruleInstance = CustomRule::create( $customRule );

			if ( is_null( $ruleInstance ) ) {
				continue;
			}

			$typeId = (int)$customRule['room_type_id'];
			$roomId = (int)$customRule['room_id'];

			if ( $roomId == 0 ) {
				if ( !isset( $this->globals[$typeId] ) ) {
					$this->globals[$typeId] = array();
				}

				$this->globals[$typeId][] = $ruleInstance;
			}

			if ( $typeId != 0 ) {
				if ( !isset( $this->rules[$typeId] ) ) {
					$this->rules[$typeId] = array();
				}

				$this->rules[$typeId][] = array(
					'room_id'	 => $roomId,
					'rule'		 => $ruleInstance
				);
			}
		} // For each custom rule
	}

	/**
	 *
	 * @param \DateTime $checkIn
	 * @param \DateTime $checkOut
	 * @param int $typeId Optional. 0 by default. Already translated in RulesChecker.
	 * @return bool
	 */
	public function verify( \DateTime $checkIn, \DateTime $checkOut, $typeId = 0 ){
		$verified = true;

		$verifyTypes = array( 0 );
		if ( $typeId != 0 ) {
			$verifyTypes[] = $typeId;
		}

		foreach ( $verifyTypes as $_typeId ) {
			if ( !isset( $this->globals[$_typeId] ) ) {
				continue;
			}

			foreach ( $this->globals[$_typeId] as $rule ) {
				if ( !$rule->verify( $checkIn, $checkOut ) ) {
					$verified = false;
					break 2;
				}
			}
		}

		return $verified;
	}

	/**
	 *
	 * @param \DateTime $checkIn
	 * @param \DateTime $checkOut
	 * @param int $typeId
	 * @return int[]
	 */
	public function getUnavailableRooms( \DateTime $checkIn, \DateTime $checkOut, $typeId ){
		$typeId = MPHB()->translation()->getOriginalId( $typeId, MPHB()->postTypes()->roomType()->getPostType() );

		if ( !isset( $this->rules[$typeId] ) ) {
			return array();
		} else if ( !$this->verify( $checkIn, $checkOut, $typeId ) ) {
			// All unavailable when global rule fails
			return MPHB()->getRoomPersistence()->findAllIdsByType( $typeId );
		}

		$unavailableRooms = array();

		foreach ( $this->rules[$typeId] as $ruleWrapper ) {
			$roomId = $ruleWrapper['room_id'];
			$customRule = $ruleWrapper['rule'];

			if ( $roomId == 0 ) {
				continue; // Already verified globally for type
			}

			if ( !$customRule->verify( $checkIn, $checkOut ) ) {
				$unavailableRooms[] = $roomId;
			}
		}

		$unavailableRooms = array_unique( $unavailableRooms );
		sort( $unavailableRooms ); // Will also reset keys after array_unique()

		return $unavailableRooms;
	}

	/**
	 *
	 * @param \DateTime $checkIn
	 * @param \DateTime $checkOut
	 * @param int $typeId
	 * @return int
	 */
	public function getUnavailableRoomsCount( \DateTime $checkIn, \DateTime $checkOut, $typeId ){
		$unavailableRooms = $this->getUnavailableRooms( $checkIn, $checkOut, $typeId );
		return count( $unavailableRooms );
	}

	public function getBlockedRoomsCounts( $typeId ){
		$typeId = MPHB()->translation()->getOriginalId( $typeId, MPHB()->postTypes()->roomType()->getPostType() );

		if ( !isset( $this->rules[$typeId] ) ) {
			return array();
		}

		$counts = array();

		$foundRooms = array();
		$dateFormat = MPHB()->settings()->dateTime()->getDateTransferFormat();

		foreach ( $this->rules[$typeId] as $ruleWrapper ) {
			$roomId = $ruleWrapper['room_id'];
			$rule = $ruleWrapper['rule'];

			if ( $roomId == 0 || !$rule->isBlocked() ) {
				continue;
			}

			$isNewRoom = !in_array( $roomId, $foundRooms );

			foreach ( $rule->getPeriodDates() as $date ) {
				$date = $date->format( $dateFormat );
				if ( isset( $counts[$date] ) ) {
					$counts[$date] += $isNewRoom ? 1 : 0;
				} else {
					$counts[$date] = 1;
				}
			}

			if ( $isNewRoom ) {
				$foundRooms[] = $roomId;
			}
		}

		return $counts;
	}

	/**
	 *
	 * @return array ["2017-01-01" => ["not_check_in" => true,
	 * "not_check_out" => true, "not_stay_in" => true], ...]
	 */
	public function getGlobalRestrictions(){
		if ( !isset( $this->globals[0] ) ) {
			return array();
		}

		$dates = array();

		foreach ( $this->globals[0] as $rule ) {
			$newDates = $rule->getRestrictionsByDays();
			$dates = $this->mergeRestrictions( $dates, $newDates );
		}

		ksort( $dates );

		return $dates;
	}

	/**
	 *
	 * @return array ["2017-01-01" => ["not_check_in" => true,
	 * "not_check_out" => true, "not_stay_in" => true], ...]
	 */
	public function getGlobalTypeRestrictions(){
		$dates = array();

		foreach ( $this->globals as $typeId => $rules ) {
			if ( $typeId == 0 ) {
				continue;
			}

			$typeDates = array();

			foreach ( $rules as $rule ) {
				$newDates = $rule->getRestrictionsByDays();
				$typeDates = $this->mergeRestrictions( $typeDates, $newDates );
			}

			$dates[$typeId] = $typeDates;
		}

		return $dates;
	}

	/**
	 *
	 * @param array $dates1 [%date% => %restrictions%]
	 * @param array $dates2 [%date% => %restrictions%]
	 *
	 * @return array [%date% => %merged restrictions%]
	 */
	private function mergeRestrictions( $dates1, $dates2 ){
		$dates = $dates1;

		foreach ( $dates2 as $date => $restrictions ) {
			if ( !isset( $dates[$date] ) ) {
				$dates[$date] = $restrictions;
			} else {
				foreach ( $restrictions as $param => $value ) {
					$dates[$date][$param] = $dates[$date][$param] || $value;
				}
			}
		}

		return $dates;
	}

	/**
	 * @param int $typeId Booking Calendar queries only original types.
	 * @param array $roomIds
	 *
	 * @return array
	 */
	public function getCommentsByDates( $typeId, $roomIds ){
		$globals = array();

		foreach ( $this->globals as $_typeId => $rules ) {
			if ( $_typeId != $typeId && $_typeId != 0 ) {
				continue;
			}

			foreach ( $rules as $rule ) {
				$dates = $rule->getBlockedDates();
				$comment = $rule->getComment();

				foreach ( $dates as $dateYmd ) {
					if ( empty( $globals[$dateYmd] ) ) {
						$globals[$dateYmd] = $comment;
					} else if ( !empty( $comment ) ) {
						$globals[$dateYmd] .= ', ' . $comment;
					}
				}
			}
		}

		$rooms = array_fill_keys( $roomIds, $globals );

		if ( !isset( $this->rules[$typeId] ) ) { // Also: if ( $typeId == 0 )
			return $rooms;
		}

		foreach ( $this->rules[$typeId] as $ruleWrapper ) {
			$roomId = $ruleWrapper['room_id'];
			$rule = $ruleWrapper['rule'];

			if ( $roomId == 0 || !in_array( $roomId, $roomIds ) ) {
				continue;
			}

			$dates = $rule->getBlockedDates();
			$comment = $rule->getComment();

			foreach ( $dates as $dateYmd ) {
				if ( empty( $rooms[$roomId][$dateYmd] ) ) {
					$rooms[$roomId][$dateYmd] = $comment;
				} else if ( !empty( $comment ) ) {
					$rooms[$roomId][$dateYmd] .= ', ' . $comment;
				}
			}
		}

		return $rooms;
	}

}
