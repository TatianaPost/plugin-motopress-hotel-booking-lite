<?php

namespace MPHB\Admin\ManageCPTPages;

use \MPHB\Entities;

class ServiceManageCPTPage extends ManageCPTPage {

	public function __construct( $postType, $atts = array() ){
		parent::__construct( $postType, $atts );
		$this->description = __( 'Services are extra offers that you can sell or give for free. E.g. Thai massage, transfer, babysitting. Guests can pre-oder them when placing a booking.', 'motopress-hotel-booking' );
	}

	public function filterColumns( $columns ){
		$customColumns	 = array(
			'price'				 => __( 'Price', 'motopress-hotel-booking' ),
			'price_periodicity'	 => __( 'Periodicity', 'motopress-hotel-booking' ),
			'price_quantity'	 => __( 'Charge', 'motopress-hotel-booking' ),
		);
		$offset			 = array_search( 'date', array_keys( $columns ) ); // Set custom columns position before "DATE" column
		$columns		 = array_slice( $columns, 0, $offset, true ) + $customColumns + array_slice( $columns, $offset, count( $columns ) - 1, true );

		return $columns;
	}

	public function filterSortableColumns( $columns ){
		$columns['price'] = 'mphb_price';

		return $columns;
	}

	public function renderColumns( $column, $postId ){
		$service = MPHB()->getServiceRepository()->findById( $postId );
		switch ( $column ) {
			case 'price' :
				echo $service->getPriceHTML();
				break;
			case 'price_periodicity' :
				echo $service->isPayPerNight() ? __( 'Per Day', 'motopress-hotel-booking' ) : __( 'Once', 'motopress-hotel-booking' );
				break;
			case 'price_quantity' :
				echo $service->isPayPerAdult() ? __( 'Per Guest', 'motopress-hotel-booking' ) : __( 'Per Accommodation', 'motopress-hotel-booking' );
				break;
		}
	}

}
