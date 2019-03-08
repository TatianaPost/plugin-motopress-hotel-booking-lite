<?php

namespace MPHB\Persistences;

class RoomPersistence extends RoomTypeDependencedPersistence {

	/**
	 *
	 * @return array
	 */
	protected function getDefaultQueryAtts(){
		$defaultAtts = parent::getDefaultQueryAtts();

		$defaultAtts['orderby']	 = 'menu_order';
		$defaultAtts['order']	 = 'ASC';
		return $defaultAtts;
	}

	/**
	 *
	 * @param array $atts
	 */
	protected function modifyQueryAtts( $atts ){
		$atts = parent::modifyQueryAtts( $atts );
		if ( isset( $atts['post_status'] ) && $atts['post_status'] === 'all' ) {
			$atts['post_status'] = array(
				'publish',
				'pending',
				'draft',
				'future',
				'private'
			);
		}
		return $atts;
	}

	/**
	 *
	 * @global \WPDB $wpdb
	 * @param array $atts
	 * @param string $atts['availability']	Optional. Accepts 'free', 'locked', 'booked', 'pending'. Default 'free'
	 * 											free - has no bookings with status complete or pending for this days x room
	 * 											locked - has bookings with status complete or pending for this days x room
	 * 											booked - has bookings with status complete for this days x room
	 * 											pending - has bookings with status pending for this days x rooms
	 * @param \DateTime $atts['from_date']			Optional. Default today.
	 * @param \DateTime $atts['to_date']			Optional. Default today.
	 * @param int		$atts['count']				Optional. Count of rooms for search.
	 * @param int		$atts['room_type_id']		Optional. Type of rooms for search.
	 * @param int		$atts['exclude_booking']	Optional. Id of booking to exclude from locked rooms list.
	 * @param array		$atts['exclude_rooms']		Optional. Ids of rooms to exclude from locked rooms list.
	 * @return string[] Array of room ids.
	 */
	public function searchRooms( $atts = array() ){
		global $wpdb;

		$defaultAtts = array(
			'availability'		 => 'free',
			'from_date'			 => new \DateTime( current_time( 'mysql' ) ),
			'to_date'			 => new \DateTime( current_time( 'mysql' ) ),
			'count'				 => null,
			'room_type_id'		 => null,
			'exclude_booking'	 => null,
			'exclude_rooms'		 => null
		);

		$atts = array_merge( $defaultAtts, $atts );

		switch ( $atts['availability'] ) {
			case 'free':
				$bookingStatuses = MPHB()->postTypes()->booking()->statuses()->getLockedRoomStatuses();
				break;
			case 'booked':
				$bookingStatuses = MPHB()->postTypes()->booking()->statuses()->getBookedRoomStatuses();
				break;
			case 'pending':
				$bookingStatuses = MPHB()->postTypes()->booking()->statuses()->getPendingRoomStatuses();
				break;
			case 'locked':
				$bookingStatuses = MPHB()->postTypes()->booking()->statuses()->getLockedRoomStatuses();
				break;
		}
		$bookingStatuses = "'" . join( "','", $bookingStatuses ) . "'";

		$query = "SELECT DISTINCT reservedRoomsRoomId.meta_value AS ID
				FROM $wpdb->posts AS reservedRooms ";

		$join = "INNER JOIN $wpdb->posts AS bookings
					ON ( reservedRooms.post_parent = bookings.ID )
				INNER JOIN $wpdb->postmeta AS bookingCheckIn
					ON ( bookingCheckIn.post_id = bookings.ID  )
				INNER JOIN $wpdb->postmeta AS bookingCheckOut
					ON ( bookingCheckOut.post_id = bookings.ID )
				INNER JOIN $wpdb->postmeta AS reservedRoomsRoomId
					ON ( reservedRooms.ID = reservedRoomsRoomId.post_id )";

		$where = " WHERE 1=1
					AND reservedRooms.post_type = '" . MPHB()->postTypes()->reservedRoom()->getPostType() . "'
					AND reservedRooms.post_status = 'publish'
					AND reservedRoomsRoomId.meta_key = '_mphb_room_id'
					AND bookingCheckIn.meta_key = 'mphb_check_in_date'
					AND bookingCheckOut.meta_key = 'mphb_check_out_date'
					AND bookings.post_type = '" . MPHB()->postTypes()->booking()->getPostType() . "'
					AND bookings.post_status IN ( $bookingStatuses )
					AND CAST( bookingCheckOut.meta_value AS DATE ) > '%s'
					AND CAST( bookingCheckIn.meta_value AS DATE ) < '%s'";

		$orderBy = "";

		if ( !is_null( $atts['exclude_booking'] ) ) {
			$where .= $wpdb->prepare( " AND bookings.ID != '%d'", $atts['exclude_booking'] );
		}

		if ( !is_null( $atts['room_type_id'] ) && $atts['availability'] !== 'free' ) {
			$whereRoomType = " AND EXISTS(
								SELECT 1
								FROM $wpdb->postmeta AS roomMeta
								WHERE 1=1
									AND roomMeta.post_id = reservedRoomsRoomId.meta_value
									AND roomMeta.meta_value = '%d'
								LIMIT 1
								)";
			$where .= $wpdb->prepare( $whereRoomType, $atts['room_type_id'] );
		}

		if ( !is_null( $atts['count'] ) && $atts['availability'] !== 'free' ) {
			$orderBy .= $wpdb->prepare( " LIMIT %d", $atts['count'] );
		}

		$query .= $join . $where . $orderBy;

		$query = $wpdb->prepare( $query, $atts['from_date']->format( 'Y-m-d' ), $atts['to_date']->format( 'Y-m-d' ) );

		$rooms = $wpdb->get_col( $query );

		if ( $atts['availability'] === 'free' ) {

			$bookedRooms = $rooms;

			$roomAtts = array(
				'fields' => 'ids',
			);

			if ( !empty( $bookedRooms ) ) {
				$roomAtts['post__not_in'] = $bookedRooms;
			}
			if ( !empty( $atts['exclude_rooms'] ) ) {
				if ( isset( $roomAtts['post__not_in'] ) ) {
					$roomAtts['post__not_in'] = array_merge( $roomAtts['post__not_in'], $atts['exclude_rooms'] );
				} else {
					$roomAtts['post__not_in'] = $atts['exclude_rooms'];
				}
			}
			if ( !is_null( $atts['room_type_id'] ) ) {
				$roomAtts['room_type_id'] = $atts['room_type_id'];
			}
			if ( !is_null( $atts['count'] ) ) {
				$roomAtts['posts_per_page'] = $atts['count'];
			}
			// @todo is needs order
//			$orderBy = " ORDER BY rooms.menu_order
//						DESC";

			$rooms = $this->getPosts( $roomAtts );
		}

		return $rooms;
	}

	public function isExistsRooms( \DateTime $checkInDate, \DateTime $checkOutDate, $count, $roomTypeId = null ){
		$searchAtts = array(
			'availability'	 => 'free',
			'from_date'		 => $checkInDate,
			'to_date'		 => $checkOutDate,
			'count'			 => (int) $count
		);
		if ( !is_null( $roomTypeId ) ) {
			$searchAtts['room_type_id'] = (int) $roomTypeId;
		}

		$rooms = $this->searchRooms( $searchAtts );

		return count( $rooms ) >= $count;
	}

	/**
	 *
	 * @param \DateTime $checkInDate
	 * @param \DateTime $checkOutDate
	 * @param array $rooms
	 * @param int|null $roomTypeId
	 *
	 * @return boolean
	 */
	public function isRoomsFree( \DateTime $checkInDate, \DateTime $checkOutDate, $rooms, $roomTypeId = null ){
		$searchAtts = array(
			'availability'	 => 'free',
			'from_date'		 => $checkInDate,
			'to_date'		 => $checkOutDate
		);

		if ( !is_null( $roomTypeId ) ) {
			$searchAtts['room_type_id'] = (int)$roomTypeId;
		}

		$freeRooms = $this->searchRooms( $searchAtts );

		$availableRooms = array_intersect( $rooms, $freeRooms );

		return ( count( $rooms ) == count( $availableRooms ) );
	}

	/**
	 *
	 * @param int $typeId
	 * @return int[]
	 */
	public function findAllIdsByType( $typeId ){
		$allRoomIds = $this->getPosts(
			array(
				'room_type_id'	 => $typeId,
				'post_status'	 => 'all',
				'fields'		 => 'ids',
				'posts_per_page' => -1
			)
		);

		return $allRoomIds;
	}

}
