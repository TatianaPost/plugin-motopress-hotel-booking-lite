<?php

namespace MPHB\Entities;

class ReservedService extends Service {

	/**
	 *
	 * @var int
	 */
	private $adults;

	/**
	 *
	 * @param array $atts
	 * @param int	$atts['id'] Id of service
	 * @param int	$atts['adults'] Number of adults reserved service. For service per room equal 1.
	 */
	protected function __construct( $atts ){
		parent::__construct( $atts );
		$this->adults = $atts['adults'];
	}

	/**
	 *
	 * @param array $atts
	 * @return ReservedService|null
	 */
	public static function create( $atts ){

		if ( !isset( $atts['id'], $atts['adults'] ) ) {
			return null;
		}

		$service = MPHB()->getServiceRepository()->findById( $atts['id'] );
		if ( !$service ) {
			return null;
		}

		$serviceAtts = array(
			'original_id'	 => $service->getOriginalId(),
			'title'			 => $service->getTitle(),
			'description'	 => $service->getDescription(),
			'periodicity'	 => $service->getPeriodicity(),
			'quantity'		 => $service->getQuantity(),
			'price'			 => $service->getPrice(),
		);

		$atts = array_merge( $serviceAtts, $atts );

		return new self( $atts );
	}

	/**
	 *
	 * @return int
	 */
	public function getAdults(){
		return $this->adults;
	}

	/**
	 *
	 * @param \DateTime $checkInDate
	 * @param \DateTime $checkOutDate
	 * @return float
	 */
	public function calcPrice( $checkInDate, $checkOutDate ){
		$multiplier = 1;
		if ( $this->isPayPerNight() ) {
			$nights		 = \MPHB\Utils\DateUtils::calcNights( $checkInDate, $checkOutDate );
			$multiplier	 = $multiplier * $nights;
		}

		if ( $this->isPayPerAdult() ) {
			$multiplier = $multiplier * $this->adults;
		}

		return $multiplier * $this->getPrice();
	}

	/**
	 * @param \DateTime $checkInDate
	 * @param \DateTime $checkOutDate
	 * @param string [$language=null]
	 *
	 * @return array
	 */
	public function getPriceBreakdown( $checkInDate, $checkOutDate, $language = null ){
		if ( !$language ) {
			$language = MPHB()->translation()->getCurrentLanguage();
		}
		$serviceId	 = apply_filters( '_mphb_translate_post_id', $this->getId(), $language );
		$service	 = MPHB()->getServiceRepository()->findById( $serviceId );

		return array(
			'title'		 => $service->getTitle(),
			'details'	 => $this->generatePriceDetailsString( $checkInDate, $checkOutDate ),
			'total'		 => $this->calcPrice( $checkInDate, $checkOutDate ),
		);
	}

	/**
	 *
	 * @param \DateTime $checkInDate
	 * @param \DateTime $checkOutDate
	 * @return string
	 */
	public function generatePriceDetailsString( $checkInDate, $checkOutDate ){

		$priceDetails = mphb_format_price( $this->getPrice() );

		if ( $this->isPayPerNight() ) {
			$nights = \MPHB\Utils\DateUtils::calcNights( $checkInDate, $checkOutDate );
			$priceDetails .= sprintf( _n( ' &#215; %d night', ' &#215; %d nights', $nights, 'motopress-hotel-booking' ), $nights );
		}

		if ( $this->isPayPerAdult() ) {
			$priceDetails .= sprintf( _n( ' &#215; %d adult', ' &#215; %d adults', $this->adults, 'motopress-hotel-booking' ), $this->adults );
		}

		return $priceDetails;
	}

}
