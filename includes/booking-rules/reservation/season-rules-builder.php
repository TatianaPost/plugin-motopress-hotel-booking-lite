<?php

namespace MPHB\BookingRules\Reservation;

/*
 * Examples time.
 *
 * Room type IDs: 100, 200.
 * Season IDs: 10, 20.
 *
 * Saved reservation rules:
 *	 $mphb_min_stay_length = [
 *		 [ "min_stay_length" => 7,  "room_type_ids" => [100], "season_ids" => [10] ],
 *		 [ "min_stay_length" => 3,  "room_type_ids" => [100], "season_ids" => [0]  ],
 *		 [ "min_stay_length" => 5,  "room_type_ids" => [0],   "season_ids" => [0]  ]
 *	 ];
 *	 $mphb_max_stay_length = [
 *		 [ "max_stay_length" => 14, "room_type_ids" => [0],   "season_ids" => [0]  ]
 *	 ];
 *	 ...
 *
 * After SeasonRulesBuilder::addRules():
 *	 SeasonRulesBuilder::$rules = [
 *		 [ "name" => "min_stay_length", "value" => 7,  "season_id" => 10 ],
 *		 [ "name" => "min_stay_length", "value" => 3,  "season_id" => 0  ],
 *		 [ "name" => "min_stay_length", "value" => 5,  "season_id" => 0  ],
 *		 [ "name" => "max_stay_length", "value" => 14, "season_id" => 0  ]
 *	 ];
 *
 * After SeasonRulesBuilder::build():
 *	 $rules = [
 *		 10 => [ "min_stay_length" => 7 ]
 *		 0  => [ "min_stay_length" => 3, "max_stay_length" => 14 ]
 *	 ];
 */

class SeasonRulesBuilder {

	/**
	 * [ "name" => ..., "value" => ..., "season_id" => ... ]
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 *
	 * @param array $rules Reservation rules (check-in days, check-out days, min
	 * stay length or max stay length).
	 *
	 * @return SeasonRulesBuilder
	 */
	public function addRules( $rules ){
		foreach ( $rules as $params ) {
			if ( !$this->isCorrectRule( $params ) ) {
				continue;
			}

			$seasonIds = $params['season_ids'];

			// Leave only check-in, check-out, min stay and max stay parameters,
			// remove all others
			unset( $params['room_type_ids'] );
			unset( $params['season_ids'] );

			foreach ( $seasonIds as $seasonId ) {
				foreach ( $params as $name => $value ) {
					$this->rules[] = array(
						'name'		 => $name,
						'value'		 => $value,
						'season_id'	 => $seasonId
					);
				}
			}

		} // For each rule

		return $this;
	}

	/**
	 *
	 * @return array Season reservation rules.
	 */
	public function build(){
		$build = array();

		$existingSeasons = array_keys( MPHB()->getSeasonPersistence()->getIdTitleList() );

		// Group rules by season ID
		foreach ( $this->rules as $rule ) {
			$name		 = $rule['name'];
			$value		 = $rule['value'];
			$seasonId	 = $rule['season_id'];

			if ( $seasonId == 0 ) {
				foreach ( $existingSeasons as $addSeasonId ) {
					if ( isset( $build[$addSeasonId][$name] ) ) {
						continue; // ... Then it's priority higher than the current one
					} else {
						$this->addParamToBuild( $build, $addSeasonId, $name, $value );
					}
				}
			} else {
				$this->addParamToBuild( $build, $seasonId, $name, $value );
			}
		}

		return $build;
	}

	private function addParamToBuild( &$build, $seasonId, $param, $value ){
		if ( !isset( $build[$seasonId] ) ) {
			$build[$seasonId] = array( $param => $value );
		} else if ( !isset( $build[$seasonId][$param] ) ) {
			$build[$seasonId][$param] = $value;
		} else {
			$oldValue = $build[$seasonId][$param];
			$build[$seasonId][$param] = $this->optimizeParam( $param, $value, $oldValue );
		}
	}

	/**
	 * @param array $rule
	 *
	 * @return bool
	 */
	protected function isCorrectRule( $rule ){
		return !empty( $rule['season_ids'] );
	}

	/**
	 *
	 * @param string $name Param name.
	 * @param mixed $new New param value.
	 * @param mixed $old Old param value.
	 *
	 * @return mixed
	 */
	protected function optimizeParam( $name, $new, $old ){
		switch ( $name ) {
			case 'check_in_days':
			case 'check_out_days':
				$merge = array_merge( $new, $old );
				$merge = array_unique( $merge );
				return array_values( $merge );
				break;

			case 'min_stay_length':
				return min( $new, $old );
				break;

			case 'max_stay_length':
				return max( $new, $old );
				break;
		}

		// Or just return new value for unknown params
		return $new;
	}

}
