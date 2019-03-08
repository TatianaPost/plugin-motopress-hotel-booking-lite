<?php

namespace MPHB\BookingRules\Reservation;

/*
 * Examples time.
 *
 * Room type IDs: 100, 200.
 * Season IDs: 10, 20.
 *
 * Saved rules:
 *	  $mphb_min_stay_length = [
 *		  [ "min_stay_length" => 7,  "room_type_ids" => [100], "season_ids" => [10] ],
 *		  [ "min_stay_length" => 3,  "room_type_ids" => [100], "season_ids" => [0]  ],
 *		  [ "min_stay_length" => 5,  "room_type_ids" => [0],   "season_ids" => [0]  ]
 *	  ];
 *	  $mphb_max_stay_length = [
 *		  [ "max_stay_length" => 14, "room_type_ids" => [0],   "season_ids" => [0]  ]
 *	  ];
 *	  ...
 *
 * After RulesBuilder::addRules():
 *	  RulesBuilder::$rules = [
 *		  [ "name" => "min_stay_length", "value" => 7,  "order" => 0, "type_id" => 100, "season_id" => 10 ],
 *		  [ "name" => "min_stay_length", "value" => 3,  "order" => 1, "type_id" => 100, "season_id" => 0  ],
 *		  [ "name" => "min_stay_length", "value" => 5,  "order" => 2, "type_id" => 0,   "season_id" => 0  ],
 *		  [ "name" => "max_stay_length", "value" => 14, "order" => 0, "type_id" => 0,   "season_id" => 0  ]
 *	  ];
 *
 * After RulesBuilder::combineRules():
 *	  $rules = [
 *		  100 => [
 *			  10 => [ "min_stay_length" => 7 ],
 *			  0  => [ "min_stay_length" => 3 ]
 *		  ],
 *		  0   => [
 *			  0 =>  [ "min_stay_length" => 5, "max_stay_length" => 14 ]
 *		  ]
 *	  ];
 *
 * After RulesBuilder::build():
 *	  $reservationRules = [
 *		  100 => [
 *			  10 => [ "min_stay_length" => 7 ],
 *			  20 => [ "min_stay_length" => 3 ],
 *			  0  => [ "min_stay_length" => 3 ]
 *		  ],
 *		  0   => [
 *			  10 => [ "min_stay_length" => 5, "max_stay_length" => 14 ],
 *			  20 => [ "min_stay_length" => 5, "max_stay_length" => 14 ],
 *			  0  => [ "min_stay_length" => 3, "max_stay_length" => 14 ]
 *		  ]
 *	  ];
 */

class RulesBuilder {

	/**
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 *
	 * @param array $rules
	 *
	 * @return RulesBuilder
	 */
	public function addRules( $rules ){
		foreach ( $rules as $order => $params ) {
			$typeIds   = $params['room_type_ids'];
			$seasonIds = $params['season_ids'];

			if ( empty( $typeIds ) || empty( $seasonIds ) ) {
				continue;
			}

			// Leave only check-in, check-out, min stay and max
			// stay parameters, remove all others
			unset( $params['room_type_ids'] );
			unset( $params['season_ids'] );

			foreach ( $typeIds as $typeId ) {
				foreach ( $seasonIds as $seasonId ) {
					foreach ( $params as $name => $value ) {
						$this->rules[] = array(
							'name'		 => $name,
							'value'		 => $this->sanitizeParam( $value ),
							'order'		 => $order,
							'type_id'	 => $typeId,
							'season_id'	 => $seasonId
						);
					} // For each $params
				} // For each $seasonIds
			} // For each $typeIds

		} // For each $rules

		return $this;
	}

	/**
	 * Convert all values to integers.
	 *
	 * @param mixed $value Scalar or array.
	 *
	 * @return mixed
	 */
	protected function sanitizeParam( $value ){
		if ( is_array( $value ) ) {
			return array_map( function( $item ) { return (int)$item; }, $value );
		} else {
			return (int)$value;
		}
	}

	/**
	 *
	 * @return array Reservation rules (type/season array).
	 */
	public function build(){
		$rules = $this->rules;
		$rules = $this->combineRules( $rules );
		$rules = $this->addTypes( $rules );
		$rules = $this->addSeasons( $rules );
		$rules = $this->optimizeGlobals( $rules );
		$rules = $this->addSeasons( $rules ); // New global rules could appear after
											  // optimizeGlobals(); also add new seasons
		return $rules;
	}

	/**
	 * Combine all rules into type => season array.
	 *
	 * @param array $splittedRules [ ["name", "value", "order", "type_id",
	 * "season_id"], ... ]
	 *
	 * @return array Type/season array.
	 */
	protected function combineRules( $splittedRules ){
		// Sort rules by order
		usort( $splittedRules, function( $a, $b ) {
			if ( $a['order'] == $b['order'] ) {
				return 0;
			} else {
				return ( $a['order'] < $b['order'] ) ? -1 : 1;
			}
		} );

		$rules = array();

		foreach ( $splittedRules as $rule ) {
			$name		 = $rule['name'];
			$value		 = $rule['value'];
			$typeId		 = $rule['type_id'];
			$seasonId	 = $rule['season_id'];

			// Don't add Type/X rules when Type/All or All/All already exist and
			// have bigger priority
			if ( isset( $rules[$typeId][0][$name] ) || isset( $rules[0][0][$name] ) ) {
				continue;
			}

			// Don't replace existing values (they have bigger priority)
			if ( isset( $rules[$typeId][$seasonId][$name] ) ) {
				continue;
			}

			$rules[$typeId][$seasonId][$name] = $value;
		}

		return $rules;
	}

	/**
	 * Add types, that not presented in existing rules (use global value for new
	 * types). (Fix [MB-432])
	 *
	 * @param array $rules Type/season array.
	 *
	 * @return array Type/season array.
	 */
	protected function addTypes( $rules ){
		if ( !isset( $rules[0][0] ) ) {
			return $rules;
		}

		$typeIds = array_keys( MPHB()->getRoomTypePersistence()->getIdTitleList() );
		$missedTypes = array_diff( $typeIds, array_keys( $rules ) );
		$allAll = $rules[0][0];

		foreach ( $missedTypes as $typeId ) {
			$rules[$typeId][0] = $allAll;
		}

		return $rules;
	}

	/**
	 * Add seasons, that not presented in existing rules (use global values for
	 * new rules).
	 *
	 * @param array $rules Type/season array.
	 *
	 * @return array Type/season array.
	 */
	protected function addSeasons( $rules ){
		$seasonIds = array_keys( MPHB()->getSeasonPersistence()->getIdTitleList() );

		foreach ( $rules as $typeId => $seasonRules ) {
			if ( !isset( $seasonRules[0] ) ) {
				continue;
			}

			$typeAll		 = $seasonRules[0];
			$missedSeasons	 = array_diff( $seasonIds, array_keys( $seasonRules ) );

			foreach ( $missedSeasons as $seasonId ) {
				$rules[$typeId][$seasonId] = $typeAll;
			}
		}

		return $rules;
	}

	/**
	 * Get min and max values for "min stay" and "max stay" params respectively,
	 * and get merged days for "check-in days" and "check-out days" params.
	 *
	 * @param array $rules Type/season array.
	 *
	 * @return array Type/season array.
	 */
	protected function optimizeGlobals( $rules ){
		// Optimize Type/All first
		foreach ( $rules as $typeId => $seasonRules ) {
			$optimalValues = array();

			foreach ( $seasonRules as $params ) {
				$optimalValues = $this->optimizeParams( $optimalValues, $params );
			}

			if ( !empty( $optimalValues ) ) {
				$rules[$typeId][0] = $optimalValues;
			}
		}

		// Now optimize All/All
		$optimalValues = array();

		foreach ( $rules as $seasonRules ) {
			if ( !isset( $seasonRules[0] ) ) {
				continue;
			}

			$optimalValues = $this->optimizeParams( $optimalValues, $seasonRules[0] );
		}

		if ( !empty( $optimalValues ) ) {
			$rules[0][0] = $optimalValues;
		}

		return $rules;
	}

	/**
	 *
	 * @param array $optimalValues Current optimal values.
	 * @param type $newValues New param values.
	 *
	 * @return array New optimal values.
	 */
	protected function optimizeParams( $optimalValues, $newValues ){
		foreach ( $newValues as $name => $value ) {
			if ( !isset( $optimalValues[$name] ) ) {
				$optimalValues[$name] = $value;
			} else {
				$optimalValues[$name] = $this->optimizeParam( $name, $value, $optimalValues[$name] );
			}
		}

		return $optimalValues;
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
