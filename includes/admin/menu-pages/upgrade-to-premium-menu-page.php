<?php

namespace MPHB\Admin\MenuPages;

class UpgradeToPremiumMenuPage extends AbstractMenuPage {

	public function render(){
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Go Premium', 'motopress-hotel-booking' ); ?></h1>

			<hr class="wp-header-end" />

			<h3><?php _e( 'Take more control over your lodging business with premium plugin features:', 'motopress-hotel-booking' ); ?></h3>
			<ol>
				<li><?php _e( 'Priority updates (new features released regularly).', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'Priority support - email, live chat, forum.', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'More built-in payment gateways (2Checkout, Braintree, Stripe, Beanstream/Bambora).', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'Bookings synchronization with OTAs (exchange calendars via iCal).', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'Attributes (Adding extra sorting options to the search availability form).', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'Adding reservations from the backend.', 'motopress-hotel-booking' ); ?></li>
				<li><?php _e( 'Different property prices based on a number of guests and nights.', 'motopress-hotel-booking' ); ?></li>
			</ol>
			<a class="button button-primary" href="https://motopress.com/products/hotel-booking/?utm_source=hotel-booking-lite&utm_medium=button-in-dashboard" target="_blank"><?php
				_e( 'Go Premium', 'motopress-hotel-booking' ); ?></a>
		</div>
		<?php
	}

	public function onLoad(){
	}

	protected function getMenuTitle(){
		return __( 'Premium', 'motopress-hotel-booking' );
	}

	protected function getPageTitle(){
		return __( 'Go Premium', 'motopress-hotel-booking' );
	}

}
