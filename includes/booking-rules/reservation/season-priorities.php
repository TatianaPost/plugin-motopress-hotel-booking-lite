<?php

namespace MPHB\BookingRules\Reservation;

class SeasonPriorities {

	/**
	 * Order sums for each season ID.
	 *
	 * @var array
	 */
	private $sums = array();

	/**
	 *
	 * @var array
	 */
	private $counts = array();

	/**
	 *
	 * @param array $rules Reservation rules (check-in days, check-out days, min
	 * stay length or max stay length).
	 */
	public function addRules( $rules ){
		foreach ( $rules as $order => $rule ) {
			foreach ( $rule['season_ids'] as $seasonId ) {
				if ( !isset( $this->sums[$seasonId] ) ) {
					$this->sums[$seasonId] = $order;
					$this->counts[$seasonId] = 1;
				} else {
					$this->sums[$seasonId] += $order;
					$this->counts[$seasonId]++;
				}
			} // For each season
		} // For each rule
	}

	/**
	 *
	 * @return array The smaller the order number, the higher the priority.
	 */
	public function calcPriorities(){
		$priorities = array();

		foreach ( $this->sums as $seasonId => $sum ) {
			$priorities[$seasonId] = $sum / $this->counts[$seasonId];
		}

		ksort( $priorities );

		return $priorities;
	}

}
