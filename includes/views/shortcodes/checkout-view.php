<?php

namespace MPHB\Views\Shortcodes;

/**
 * @todo add actions & filters
 * @todo move html to internal templates
 */
class CheckoutView {

	public static function renderCoupon(){
		if ( !MPHB()->settings()->main()->isCouponsEnabled() ) {
			return;
		}

		$couponTitle	 = apply_filters( 'mphb_sc_checkout_coupon_title', '' );
		$couponLabel	 = apply_filters( 'mphb_sc_checkout_coupon_label', __( 'Coupon Code:', 'motopress-hotel-booking' ) );
		$applyCouponText = apply_filters( 'mphb_sc_checkout_coupon_apply_text', __( 'Apply', 'motopress-hotel-booking' ) );
		?>
		<section id="mphb-coupon-details" class="mphb-coupon-code-wrapper mphb-checkout-section">

			<?php do_action( 'mphb_sc_checkout_coupon_top' ); ?>

			<?php if ( !empty( $couponTitle ) ) { ?>
				<h3>
					<?php echo $couponTitle; ?>
				</h3>
			<?php } ?>

			<?php
			/**
			 * @hooked \MPHB\Views\Shortcodes\CheckoutView::_renderCouponCodeParagraphOpen() - 10
			 */
			do_action( 'mphb_sc_checkout_coupon_before_label' );
			?>

			<?php if ( !empty( $couponLabel ) ) { ?>
				<label for="mphb_coupon_code" class="mphb-coupon-code-title">
					<?php echo $couponLabel; ?>
				</label>
			<?php } ?>

			<?php do_action( 'mphb_sc_checkout_coupon_after_label' ); ?>

			<?php do_action( 'mphb_sc_checkout_coupon_before_input' ); ?>

			<input type="hidden" id="mphb_applied_coupon_code" name="mphb_applied_coupon_code" />
			<input type="text" id="mphb_coupon_code" name="mphb_coupon_code" />

			<?php
			/**
			 * @hooked \MPHB\Views\Shortcodes\CheckoutView::_renderCouponCodeParagraphClose() - 10
			 */
			do_action( 'mphb_sc_checkout_coupon_after_input' );
			?>

			<?php
			/**
			 * @hooked \MPHB\Views\Shortcodes\CheckoutView::_renderCouponButtonParagraphOpen() - 10
			 */
			do_action( 'mphb_sc_checkout_coupon_before_button' );
			?>

			<button class="button btn mphb-apply-coupon-code-button">
				<?php echo $applyCouponText; ?>
			</button>

			<?php
			/**
			 * @hooked \MPHB\Views\Shortcodes\CheckoutView::_renderCouponButtonParagraphClose() - 10
			 */
			do_action( 'mphb_sc_checkout_coupon_after_button' );
			?>

			<p class="mphb-coupon-message mphb-hide"></p>

			<?php do_action( 'mphb_sc_checkout_coupon_bottom' ); ?>

		</section>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\Booking $booking
	 * @param array $roomDetails
	 */
	public static function renderBookingDetails( $booking, $roomDetails ){
		?>
		<section id="mphb-booking-details" class="mphb-booking-details mphb-checkout-section">
			<h3 class="mphb-booking-details-title">
				<?php _e( 'Booking Details', 'motopress-hotel-booking' ); ?>
			</h3>
			<?php do_action( 'mphb_sc_checkout_form_booking_details', $booking, $roomDetails ); ?>
		</section>
		<?php
	}

	/**
	 *
	 * @param Entities\RoomType $roomType
	 * @param int $roomIndex
	 */
	public static function renderRoomTypeTitle( $roomType, $roomIndex ){
		?>
		<h3 class="mphb-room-number">
			<?php printf( __( 'Accommodation #%d', 'motopress-hotel-booking' ), $roomIndex + 1 ); ?>
		</h3>
		<p class="mphb-room-type-title">
			<span>
				<?php _e( 'Accommodation Type:', 'motopress-hotel-booking' ); ?>
			</span>
			<a href="<?php echo esc_url( $roomType->getLink() ); ?>" target="_blank">
				<?php echo $roomType->getTitle(); ?>
			</a>
		</p>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\RoomType $roomType
	 * @param string $roomIndex
	 * @param \MPHB\Entities\Booking $booking
	 */
	public static function renderGuestsChooser( $roomType, $roomIndex, $booking ){
		$namePrefix	 = 'mphb_room_details[' . esc_attr( $roomIndex ) . ']';
		$idPrefix	 = 'mphb_room_details-' . esc_attr( $roomIndex );
		$maxAdults	 = $roomType->getAdultsCapacity();
		$maxChildren = $roomType->getChildrenCapacity();
		?>
		<?php if ( MPHB()->settings()->main()->isAdultsAllowed() ) { ?>
			<p class="mphb-adults-chooser">
				<label for="<?php echo esc_attr( $idPrefix . '-adults' ); ?>">
					<?php
						if ( MPHB()->settings()->main()->isChildrenAllowed() ) {
							_e( 'Adults', 'motopress-hotel-booking' );
						} else {
							_e( 'Guests', 'motopress-hotel-booking' );
						}
					?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<select name="<?php echo esc_attr( $namePrefix . '[adults]' ); ?>" id="<?php echo esc_attr( $idPrefix . '-adults' ); ?>" class="mphb_sc_checkout-guests-chooser mphb_checkout-guests-chooser" required="required">
					<option value=""><?php _e( '— Select —', 'motopress-hotel-booking' ); ?></option>
					<?php
					for ( $i = 1; $i <= $maxAdults; $i++ ) {
						?>
						<option value="<?php echo $i; ?>">
							<?php echo $i; ?>
						</option>
					<?php } ?>
				</select>
			</p>
		<?php } else { ?>
			<input type="hidden" id="<?php echo esc_attr( $idPrefix . '-adults' ); ?>" name="<?php echo esc_attr( $namePrefix . '[adults]' ); ?>" value="<?php echo esc_attr( $maxAdults ); ?>" />
		<?php } ?>
		<?php if ( $roomType->getChildrenCapacity() > 0 && MPHB()->settings()->main()->isChildrenAllowed() ) { ?>
			<p class="mphb-children-chooser">
				<label for="<?php echo esc_attr( $idPrefix . '-children' ); ?>">
					<?php printf( __( 'Children %s', 'motopress-hotel-booking' ), MPHB()->settings()->main()->getChildrenAgeText() ); ?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<select name="<?php echo esc_attr( $namePrefix . '[children]' ); ?>" id="<?php echo esc_attr( $idPrefix . '-children' ); ?>" class="mphb_sc_checkout-guests-chooser mphb_checkout-guests-chooser" required="required">
					<option value=""><?php _e( '— Select —', 'motopress-hotel-booking' ); ?></option>
					<?php
					for ( $i = 0; $i <= $maxChildren; $i++ ) {
						?>
						<option value="<?php echo $i; ?>">
							<?php echo $i; ?>
						</option>
					<?php } ?>
				</select>
			</p>
		<?php } else { ?>
			<input type="hidden" id="<?php echo esc_attr( $idPrefix . '-children' ); ?>" name="<?php echo esc_attr( $namePrefix . '[children]' ); ?>" value="<?php echo esc_attr( $maxChildren ); ?>" />
		<?php } ?>
		<p class="mphb-guest-name-wrapper">
			<label for="<?php echo esc_attr( $idPrefix . '-guest-name' ); ?>">
				<?php _e( 'Full Guest Name', 'motopress-hotel-booking' ); ?>
			</label>
			<input
				type="text"
				name="<?php echo esc_attr( $namePrefix . '[guest_name]' ); ?>"
				id="<?php echo esc_attr( $idPrefix . '-guest-name' ); ?>" />
		</p>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\RoomType $roomType
	 * @param int $roomIndex
	 * @param \MPHB\Entities\Booking $booking
	 * @param array $roomDetails
	 */
	public static function renderRateChooser( $roomType, $roomIndex, $booking, $roomDetails ){
		$idPrefix	 = 'mphb_room_details-' . esc_attr( $roomIndex );
		$namePrefix	 = 'mphb_room_details[' . esc_attr( $roomIndex ) . ']';

		$allowedRates	 = $roomDetails[$roomIndex]['allowed_rates'];
		$adults			 = $roomDetails[$roomIndex]['adults'];
		$children		 = $roomDetails[$roomIndex]['children'];

		if ( count( $allowedRates ) > 1 ) :
			?>
			<section class="mphb-rate-chooser mphb-checkout-item-section">
				<h4 class="mphb-room-rate-chooser-title">
					<?php _e( 'Choose Rate', 'motopress-hotel-booking' ); ?>
				</h4>
				<?php
				$isFirst = true;
				foreach ( $allowedRates as $rate ) {
					$rate = apply_filters( '_mphb_translate_rate', $rate );
					$occupancyParams = mphb_occupancy_parameters( array(
						'adults'		 => $adults,
						'children'		 => $children,
						'check_in_date'	 => $booking->getCheckInDate(),
						'check_out_date' => $booking->getCheckOutDate()
					) );
					$ratePrice = mphb_format_price( $rate->calcPrice( $booking->getCheckInDate(), $booking->getCheckOutDate(), $occupancyParams ) );
					?>

					<p class="mphb-room-rate-variant">
						<label for="<?php echo $idPrefix . '-rate-id-' . $rate->getOriginalId(); ?>">
							<input
								type="radio"
								name="<?php echo $namePrefix . '[rate_id]'; ?>"
								id="<?php echo $idPrefix . '-rate-id-' . $rate->getOriginalId(); ?>"
								class="mphb_sc_checkout-rate mphb_checkout-rate mphb-radio-label"
								value="<?php echo $rate->getOriginalId(); ?>" <?php checked( $isFirst ) ?>
								/>
							<strong>
								<?php echo esc_html( $rate->getTitle() ) . ', ' . $ratePrice; ?>
							</strong>
						</label>
						<br />
						<?php echo esc_html( $rate->getDescription() ); ?>
					</p>
					<?php $isFirst	 = false; ?>
				<?php } ?>
			</section>
		<?php else : ?>
			<?php $defaultRate = reset( $allowedRates ); ?>
			<input type="hidden" name="<?php echo $namePrefix ?>[rate_id]" value="<?php echo esc_attr( $defaultRate->getOriginalId() ); ?>" />
		<?php
		endif;
	}

	/**
	 *
	 * @param Entities\RoomType $roomType
	 * @return type
	 */
	public static function renderServiceChooser( $roomType, $roomIndex ){

		if ( !$roomType->hasServices() ) {
			return;
		}

		$servicesAtts = array(
			'post__in' => $roomType->getServices()
		);

		$services = MPHB()->getServiceRepository()->findAll( $servicesAtts );
		?>
		<section id="mphb-services-details-<?php echo $roomIndex; ?>" class="mphb-services-details mphb-checkout-item-section">
			<h4 class="mphb-services-details-title">
				<?php _e( 'Choose Additional Services', 'motopress-hotel-booking' ); ?>
			</h4>
			<ul class="mphb_sc_checkout-services-list mphb_checkout-services-list">

				<?php foreach ( $services as $index => $service ) { ?>
					<?php
					$namePrefix	 = 'mphb_room_details[' . esc_attr( $roomIndex ) . '][services][' . esc_attr( $index ) . ']';
					$idPrefix	 = 'mphb_room_details-' . esc_attr( $roomIndex ) . '-service-' . $service->getOriginalId();
					?>

					<?php $service	 = apply_filters( '_mphb_translate_service', $service ); ?>

					<li>
						<label for="<?php echo $idPrefix; ?>-id" class="mphb-checkbox-label">
							<input type="checkbox"
								   name="<?php echo $namePrefix; ?>[id]"
								   id ="<?php echo $idPrefix; ?>-id"
								   class="mphb_sc_checkout-service mphb_checkout-service"
								   value="<?php echo $service->getOriginalId(); ?>" />

							<?php echo $service->getTitle(); ?>
							<em>(<?php echo $service->getPriceWithConditions( false ); ?>)</em>
						</label>
						<?php if ( $service->isPayPerAdult() && $roomType->getAdultsCapacity() > 1 ) { ?>
							<label for="<?php echo $idPrefix; ?>-adults">
								<?php _e( 'for ', 'motopress-hotel-booking' ); ?>
								<select name="<?php echo $namePrefix; ?>[adults]" id ="<?php echo $idPrefix; ?>-adults" class="mphb_sc_checkout-service-adults mphb_checkout-service-adults">
									<?php
									for ( $i = 1; $i <= $roomType->getAdultsCapacity(); $i++ ) {
										?>
										<option value="<?php echo $i; ?>" <?php selected( $roomType->getAdultsCapacity(), $i ); ?> >
											<?php echo $i; ?>
										</option>
									<?php } ?>
								</select>
								<?php echo _x( ' guest(s)', 'Example: Breakfast for X guest(s)', 'motopress-hotel-booking' ); ?>
							</label>
						<?php } else { ?>
							<input type="hidden" name="<?php echo $namePrefix; ?>[adults]" value="1" />
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
		</section>
		<?php
	}

	public static function renderPriceBreakdown( $booking ){
		?>
		<section id="mphb-price-details" class="mphb-room-price-breakdown-wrapper mphb-checkout-section">
			<h4 class="mphb-price-breakdown-title">
				<?php _e( 'Price Breakdown', 'motopress-hotel-booking' ); ?>
			</h4>
			<?php \MPHB\Views\BookingView::renderPriceBreakdown( $booking ); ?>
		</section>
		<?php
	}

	public static function renderCheckoutText(){
		$checkoutText = MPHB()->settings()->main()->getCheckoutText();
		if ( !empty( $checkoutText ) ) {
			?>
			<section class="mphb-checkout-text-wrapper mphb-checkout-section">
				<?php echo $checkoutText; ?>
			</section>
			<?php
		}
	}

	public static function renderTermsAndConditions(){
		$termsPageId = MPHB()->settings()->pages()->getTermsAndConditionsPageId();
		$termsHtml   = MPHB()->settings()->main()->getTermsAndConditionsText();

		if ( !empty( $termsHtml ) ) {
			?>
			<section class="mphb-checkout-terms-wrapper mphb-checkout-section">
				<div class="mphb-terms-and-conditions">
					<?php echo $termsHtml; ?>
				</div>
				<p class="mphb-terms-and-conditions-accept">
					<label>
						<input type="checkbox" id="mphb_accept_terms" name="mphb_accept_terms" value="1" required="required" />
						<?php
							$termsPageUrl  = get_permalink( $termsPageId );
							$termsPagelink = '<a class="mphb-terms-and-conditions-link" href="' . esc_url( $termsPageUrl ) . '" target="_blank">' . _x( 'terms & conditions', 'I\'ve read and accept the terms & conditions', 'motopress-hotel-booking' ) . '</a>';

							printf( _x( 'I\'ve read and accept the %s', 'I\'ve read and accept the <tag>terms & conditions</tag>', 'motopress-hotel-booking' ), $termsPagelink );
						?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
				</p>
			</section>
			<?php
		}
	}

	public static function renderCustomerDetails(){
		?>
		<section id="mphb-customer-details" class="mphb-checkout-section">
			<h3 class="mphb-customer-details-title"><?php _e( 'Your Information', 'motopress-hotel-booking' ); ?></h3>
			<p class="mphb-required-fields-tip">
				<small>
					<?php printf( __( 'Required fields are followed by %s', 'motopress-hotel-booking' ), '<abbr title="required">*</abbr>' ); ?>
				</small>
			</p>
			<?php do_action( 'mphb_sc_checkout_form_customer_details' ); ?>
			<p class="mphb-customer-name">
				<label for="mphb_first_name">
					<?php _e( 'First Name', 'motopress-hotel-booking' ); ?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<br />
				<input type="text" id="mphb_first_name" name="mphb_first_name" required="required" />
			</p>
			<p class="mphb-customer-last-name">
				<label for="mphb_last_name">
					<?php _e( 'Last Name', 'motopress-hotel-booking' ); ?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<br />
				<input type="text" name="mphb_last_name" id="mphb_last_name" required="required" />
			</p>
			<p class="mphb-customer-email">
				<label for="mphb_email">
					<?php _e( 'Email', 'motopress-hotel-booking' ); ?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<br />
				<input type="email" name="mphb_email" required="required" id="mphb_email" />
			</p>
			<p class="mphb-customer-phone">
				<label for="mphb_phone">
					<?php _e( 'Phone', 'motopress-hotel-booking' ); ?>
					<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
				</label>
				<br />
				<input type="text" name="mphb_phone" required="required" id="mphb_phone" />
			</p>
			<?php if ( MPHB()->settings()->main()->isRequireCountry() ) : ?>
				<?php $defaultCountry = MPHB()->settings()->main()->getDefaultCountry(); ?>
				<p class="mphb-customer-country">
					<label for="mphb_country">
						<?php _e( 'Country of residence', 'motopress-hotel-booking' ); ?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
					<br />
					<select name="mphb_country" required="required" id="mphb_country">
						<option value="" <?php selected( $defaultCountry, '' ); ?>></option>
						<?php foreach ( MPHB()->settings()->main()->getCountriesBundle()->getCountriesList() as $countryCode => $countryLabel ) { ?>
							<option value="<?php echo esc_attr( $countryCode ); ?>" <?php selected( $defaultCountry, $countryCode ); ?>>
								<?php echo $countryLabel; ?>
							</option>
						<?php } ?>
					</select>
				</p>
			<?php endif; // country		  ?>
			<?php if ( MPHB()->settings()->main()->isRequireFullAddress() ) : ?>
				<p class="mphb-customer-address1">
					<label for="mphb_address1">
						<?php _e( 'Address', 'motopress-hotel-booking' ); ?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
					<br />
					<input type="text" name="mphb_address1" required="required" id="mphb_address1" />
				</p>
				<p class="mphb-customer-city">
					<label for="mphb_city">
						<?php _e( 'City', 'motopress-hotel-booking' ); ?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
					<br />
					<input type="text" name="mphb_city" required="required" id="mphb_city" />
				</p>
				<p class="mphb-customer-state">
					<label for="mphb_state">
						<?php _e( 'State / County', 'motopress-hotel-booking' ); ?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
					<br />
					<input type="text" name="mphb_state" required="required" id="mphb_state" />
				</p>
				<p class="mphb-customer-zip">
					<label for="mphb_zip">
						<?php _e( 'Postcode', 'motopress-hotel-booking' ); ?>
						<abbr title="<?php _e( 'Required', 'motopress-hotel-booking' ); ?>">*</abbr>
					</label>
					<br />
					<input type="text" name="mphb_zip" required="required" id="mphb_zip" />
				</p>
			<?php endif; // full address			?>
			<p class="mphb-customer-note">
				<label for="mphb_note"><?php _e( 'Notes', 'motopress-hotel-booking' ); ?></label><br />
				<textarea name="mphb_note" id="mphb_note" rows="4"></textarea>
			</p>
		</section>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\Booking $booking
	 */
	public static function renderBillingDetails( $booking ){
		$gateways = MPHB()->gatewayManager()->getListActive();
		?>
		<section id="mphb-billing-details" class="mphb-checkout-section">
			<h3 class="mphb-gateway-chooser-title">
				<?php _e( 'Payment Method', 'motopress-hotel-booking' ); ?>
			</h3>
			<?php if ( empty( $gateways ) ) { ?>
				<p>
					<?php _e( 'Sorry, it seems that there are no available payment methods.', 'motopress-hotel-booking' ); ?>
				</p>
			<?php } else { ?>
				<?php
				$defaultGatewayId = MPHB()->settings()->payment()->getDefaultGateway();
				if ( !array_key_exists( $defaultGatewayId, $gateways ) ) {
					$defaultGatewayId = current( array_keys( $gateways ) );
				}
				if ( count( $gateways ) > 1 ) {
					?>
					<ul class="mphb-gateways-list">
						<?php
						foreach ( $gateways as $gateway ) {
							$gatewayDescription = $gateway->getDescription();
							?>
							<li class="mphb-gateway mphb-gateway-<?php echo $gateway->getId(); ?>">
								<input
									id="mphb_gateway_<?php echo $gateway->getId(); ?>"
									type="radio"
									name="mphb_gateway_id"
									value="<?php echo esc_attr( $gateway->getId() ); ?>"
									<?php checked( $defaultGatewayId, $gateway->getId() ); ?> />
								<label for="mphb_gateway_<?php echo $gateway->getId(); ?>" class="mphb-gateway-title mphb-radio-label">
									<strong><?php echo $gateway->getTitle(); ?></strong>
								</label>
								<?php
								if ( !empty( $gatewayDescription ) ) {
									?>
									<p class="mphb-gateway-description">
										<?php echo $gatewayDescription; ?>
									</p>
									<?php
								}
								?>
							</li>
						<?php } ?>
					</ul>
				<?php } else { ?>
					<?php $gateway = reset( $gateways ); ?>
					<input
						id="mphb_gateway_<?php echo $gateway->getId(); ?>"
						type="hidden"
						name="mphb_gateway_id"
						value="<?php echo esc_attr( $gateway->getId() ); ?>" />
					<label for="mphb_gateway_<?php echo $gateway->getId(); ?>" class="mphb-gateway-title">
						<strong><?php echo $gateway->getTitle(); ?></strong>
					</label>
					<?php
					$gatewayDescription = $gateway->getDescription();
					if ( !empty( $gatewayDescription ) ) {
						?>
						<p class="mphb-gateway-description">
							<?php echo $gatewayDescription; ?>
						</p>
						<?php
					}
					?>
				<?php } ?>
				<?php $billingFieldsWrapperClass = $gateways[$defaultGatewayId]->hasVisiblePaymentFields() ? '' : 'mphb-billing-fields-hidden'; ?>
				<fieldset class="mphb-billing-fields <?php echo $billingFieldsWrapperClass; ?>">
					<?php $gateways[$defaultGatewayId]->renderPaymentFields( $booking ); ?>
				</fieldset>
			<?php } ?>
		</section>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\Booking $booking
	 */
	public static function renderBillingDetailsHidden( $booking ){
		$gateways = MPHB()->gatewayManager()->getListActive();
		if (empty($gateways)){
			return;
		}
		$gateway = reset( $gateways );
		?>
		<input
			id="mphb_gateway_<?php echo $gateway->getId(); ?>"
			type="hidden"
			name="mphb_gateway_id"
			value="<?php echo esc_attr( $gateway->getId() ); ?>" />
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\Booking $booking
	 */
	public static function renderTotalPrice( $booking ){
		$deposit		 = $booking->calcDepositAmount();
		$totalPrice		 = $booking->getTotalPrice();
		$isShowDeposit	 = MPHB()->settings()->main()->getConfirmationMode() === 'payment' && MPHB()->settings()->payment()->getAmountType() === 'deposit';
		?>
		<p class="mphb-total-price">
			<output>
				<?php _e( 'Total Price:', 'motopress-hotel-booking' ); ?>
				<strong class="mphb-total-price-field">
					<?php echo mphb_format_price( $totalPrice ); ?>
				</strong>
				<span class="mphb-preloader mphb-hide"></span>
			</output>
		</p>
		<?php if ( $isShowDeposit ) { ?>
			<p class="mphb-deposit-amount">
				<output>
					<?php _e( 'Deposit:', 'motopress-hotel-booking' ); ?>
					<strong class="mphb-deposit-amount-field">
						<?php echo mphb_format_price( $deposit ); ?>
					</strong>
				</output>
			</p>
		<?php } ?>
		<p class="mphb-errors-wrapper mphb-hide"></p>
		<?php
	}

	/**
	 *
	 * @param Entities\Booking $booking
	 */
	public static function renderCheckInDate( $booking ){
		?>
		<p class="mphb-check-in-date">
			<span><?php _e( 'Check-in:', 'motopress-hotel-booking' ); ?></span>
			<time datetime="<?php echo $booking->getCheckInDate()->format( 'Y-m-d' ); ?>">
				<strong>
					<?php echo \MPHB\Utils\DateUtils::formatDateWPFront( $booking->getCheckInDate() ); ?>
				</strong>
			</time>,
			<span>
				<?php _ex( 'from', 'from 10:00 am', 'motopress-hotel-booking' ); ?>
			</span>
			<time datetime="<?php echo MPHB()->settings()->dateTime()->getCheckInTime(); ?>">
				<?php echo MPHB()->settings()->dateTime()->getCheckInTimeWPFormatted(); ?>
			</time>
		</p>
		<?php
	}

	/**
	 *
	 * @param Entities\Booking $booking
	 */
	public static function renderCheckOutDate( $booking ){
		?>
		<p class="mphb-check-out-date">
			<span><?php _e( 'Check-out:', 'motopress-hotel-booking' ); ?></span>
			<time datetime="<?php echo $booking->getCheckOutDate()->format( 'Y-m-d' ); ?>">
				<strong>
					<?php echo \MPHB\Utils\DateUtils::formatDateWPFront( $booking->getCheckOutDate() ); ?>
				</strong>
			</time>,
			<span>
				<?php _ex( 'until', 'until 10:00 am', 'motopress-hotel-booking' ); ?>
			</span>
			<time datetime="<?php echo MPHB()->settings()->dateTime()->getCheckOutTime(); ?>">
				<?php echo MPHB()->settings()->dateTime()->getCheckOutTimeWPFormatted(); ?>
			</time>
		</p>
		<?php
	}

	/**
	 *
	 * @param Entities\Booking $booking
	 * @param array $roomDetails
	 */
	public static function renderBookingDetailsInner( $booking, $roomDetails ){
		?>
		<div class="mphb-reserve-rooms-details">
			<?php
			foreach ( $booking->getReservedRooms() as $index => $reservedRoom ) {
				$roomTypeId	 = apply_filters( '_mphb_translate_post_id', $reservedRoom->getRoomTypeId() );
				$roomType	 = MPHB()->getRoomTypeRepository()->findById( $roomTypeId );
				?>
				<div class="mphb-room-details" data-index="<?php echo esc_attr( $index ); ?>">
					<input type="hidden"
						   name="mphb_room_details[<?php echo esc_attr( $index ); ?>][room_type_id]"
						   value="<?php echo esc_attr( $roomType->getOriginalId() ) ?>"
						   />

					<?php do_action( 'mphb_sc_checkout_room_details', $roomType, $index, $booking, $roomDetails ); ?>

				</div>
			<?php } ?>

		</div>
		<?php
	}

	/**
	 *
	 * @param \MPHB\Entities\Booking $booking
	 * @param array $roomDetails
	 */
	public static function renderCheckoutForm( $booking, $roomDetails ){
		$actionUrl = add_query_arg( 'step', \MPHB\Shortcodes\CheckoutShortcode::STEP_BOOKING, MPHB()->settings()->pages()->getCheckoutPageUrl() );
		$checkoutId = mphb_generate_uuid4();
		$nonceAction = \MPHB\Shortcodes\CheckoutShortcode::NONCE_ACTION_BOOKING . '-' . $checkoutId;
		?>
		<form class="mphb_sc_checkout-form" method="POST" action="<?php echo esc_url( $actionUrl ); ?>">

			<?php wp_nonce_field( $nonceAction, \MPHB\Shortcodes\CheckoutShortcode::NONCE_NAME ); ?>

			<input type="hidden"
				   name="<?php echo esc_attr( \MPHB\Shortcodes\CheckoutShortcode::BOOKING_CID_NAME ); ?>"
				   value="<?php echo esc_attr( $checkoutId ); ?>"
				   />
			<input type="hidden"
				   name="mphb_check_in_date"
				   value="<?php echo $booking->getCheckInDate()->format( MPHB()->settings()->dateTime()->getDateTransferFormat() ); ?>"
				   />
			<input type="hidden"
				   name="mphb_check_out_date"
				   value="<?php echo $booking->getCheckOutDate()->format( MPHB()->settings()->dateTime()->getDateTransferFormat() ); ?>"
				   />
			<input type="hidden"
				   name="mphb_checkout_step"
				   value="<?php echo \MPHB\Shortcodes\CheckoutShortcode::STEP_BOOKING; ?>"
				   />

			<?php do_action( 'mphb_sc_checkout_form', $booking, $roomDetails ); ?>

			<p class="mphb_sc_checkout-submit-wrapper">
				<input type="submit" class="button" value="<?php _e( 'Book Now', 'motopress-hotel-booking' ); ?>"/>
			</p>

		</form>
		<?php
	}

	public static function _renderCouponCodeParagraphOpen(){
		echo '<p>';
	}

	public static function _renderCouponCodeParagraphClose(){
		echo '</p>';
	}

	public static function _renderCouponButtonParagraphOpen(){
		echo '<p>';
	}

	public static function _renderCouponButtonParagraphClose(){
		echo '</p>';
	}

}
