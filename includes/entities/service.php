<?php

namespace MPHB\Entities;

class Service {

	/**
	 *
	 * @var int
	 */
	protected $id;

	/**
	 *
	 * @var int
	 */
	protected $originalId;

	/**
	 *
	 * @var string
	 */
	protected $title;

	/**
	 *
	 * @var string
	 */
	protected $description;

	/**
	 *
	 * @var float
	 */
	protected $price;

	/**
	 *
	 * @var string
	 */
	protected $periodicity;

	/**
	 *
	 * @var string
	 */
	protected $quantity;

	/**
	 *
	 * @param array $atts
	 */
	protected function __construct( $atts ){
		$this->id			 = $atts['id'];
		$this->originalId	 = $atts['original_id'];
		$this->title		 = $atts['title'];
		$this->description	 = $atts['description'];
		$this->periodicity	 = $atts['periodicity'];
		$this->quantity		 = $atts['quantity'];
		$this->price		 = $atts['price'];
	}

	/**
	 *
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 *
	 * @return int
	 */
	public function getOriginalId(){
		return $this->originalId;
	}

	/**
	 *
	 * @return string
	 */
	public function getTitle(){
		return $this->title;
	}

	/**
	 *
	 * @return string
	 */
	public function getDescription(){
		return $this->description;
	}

	/**
	 *
	 * @return float
	 */
	public function getPrice(){
		return $this->price;
	}

	/**
	 *
	 * @return string
	 */
	public function getPeriodicity(){
		return $this->periodicity;
	}

	/**
	 *
	 * @return string
	 */
	public function getQuantity(){
		return $this->quantity;
	}

	/**
	 *
	 * @return bool
	 */
	public function isPayPerNight(){
		return $this->periodicity === 'per_night';
	}

	/**
	 *
	 * @return bool
	 */
	public function isPayPerAdult(){
		return $this->quantity === 'per_adult';
	}

	/**
	 *
	 * @return bool
	 */
	public function isFree(){
		return $this->price == 0;
	}

	/**
	 *
	 * @param bool $quantity Whether to show conditions of quantity. Default TRUE.*
	 * @param bool $periodicity Whether to show conditions of periodicity. Default TRUE.
	 * @param bool $literalFree Whether to replace 0 price to free label. Default TRUE.
	 *
	 * @return string
	 */
	public function getPriceWithConditions( $quantity = true, $periodicity = true, $literalFree = true ){

		$price = $this->getPriceHTML( $literalFree );

		if ( !$this->isFree() ) {
			if ( $periodicity ) {
				$price .= ' / ';
				if ( $this->isPayPerNight() ) {
					$price .= __( 'Per Day', 'motopress-hotel-booking' );
				} else {
					$price .= __( 'Once', 'motopress-hotel-booking' );
				}
			}
			if ( $quantity ) {
				$price .= ' / ';
				if ( $this->isPayPerAdult() ) {
					$price .= __( 'Per Guest', 'motopress-hotel-booking' );
				} else {
					$price .= __( 'Per Accommodation', 'motopress-hotel-booking' );
				}
			}
		}

		return $price;
	}

	/**
	 *
	 * @param bool $literalFree
	 * @return string
	 */
	public function getPriceHTML( $literalFree = true ){
		return mphb_format_price( $this->getPrice(), array( 'literal_free' => $literalFree ) );
	}

	/**
	 *
	 * @param array $atts
	 * @return Service
	 */
	public static function create( $atts ){
		return new self( $atts );
	}

}
