<?php

namespace MPHB\Payments\Gateways;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class TestGateway extends Gateway {

	protected function setupProperties(){
		parent::setupProperties();
		$this->adminTitle = __( 'Test Payment', 'motopress-hotel-booking' );
	}

	protected function initDefaultOptions(){
		$defaults = array(
			'title'			 => __( 'Test Payment', 'motopress-hotel-booking' ),
			'description'	 => '',
			'enabled'		 => false,
		);
		return array_merge( parent::initDefaultOptions(), $defaults );
	}

	protected function initId(){
		return 'test';
	}

	public function processPayment( \MPHB\Entities\Booking $booking, \MPHB\Entities\Payment $payment ){
		$isComplete	 = $this->paymentCompleted( $payment );
		$redirectUrl = $isComplete ? MPHB()->settings()->pages()->getPaymentSuccessPageUrl() : MPHB()->settings()->pages()->getPaymentFailedPageUrl();
		wp_redirect( $redirectUrl );
		exit;
	}

}
