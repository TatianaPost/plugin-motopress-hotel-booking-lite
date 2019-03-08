<?php

namespace MPHB\Views;

use \MPHB\Entities;

class BookingView {

	public static function renderPriceBreakdown( Entities\Booking $booking ){
		echo self::generatePriceBreakdown( $booking );
	}

	public static function generatePriceBreakdown( Entities\Booking $booking ){
		$priceBreakdown = $booking->getPriceBreakdown();
		return self::generatePriceBreakdownArray( $priceBreakdown );
	}

	/**
	 * @param array $priceBreakdown
	 *
	 * @return string
	 */
	public static function generatePriceBreakdownArray( $priceBreakdown ){
		ob_start();
		if ( !empty( $priceBreakdown ) ) :

			$hasServices = false !== array_search( true, array_map( function( $roomBreakdown ) {
						return isset( $roomBreakdown['services'] ) && !empty( $roomBreakdown['services']['list'] );
					}, $priceBreakdown['rooms'] ) );

			$useThreeColumns = $hasServices;

            $unfoldByDefault = MPHB()->settings()->main()->isPriceBreakdownUnfoldedByDefault();
            if (is_admin() && !MPHB()->isAjax()) {
                $unfoldByDefault = false;
            }
            $foldedClass = $unfoldByDefault ? '' : 'mphb-hide';
            $unfoldedClass = $unfoldByDefault ? 'mphb-hide' : '';
			?>
			<table class="mphb-price-breakdown" cellspacing="0">
				<tbody>
					<?php foreach ( $priceBreakdown['rooms'] as $key => $roomBreakdown ) : ?>
						<?php if ( isset( $roomBreakdown['room'] ) ) : ?>
							<tr class="mphb-price-breakdown-group">
								<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>">
									<a href="#" class="mphb-price-breakdown-expand" title="<?php _e( 'Expand', 'motopress-hotel-booking' ); ?>">
                                        <span class="mphb-inner-icon <?php echo esc_attr($unfoldedClass); ?>">&plus;</span><span
											class="mphb-inner-icon <?php echo esc_attr($foldedClass); ?>">&minus;</span><?php
										printf( _x( '#%d %s', 'Accommodation type in price breakdown table. Example: #1 Double Room', 'motopress-hotel-booking' ), $key + 1, $roomBreakdown['room']['type'] ); ?>
									</a>
									<div class="mphb-price-breakdown-rate <?php echo esc_attr($foldedClass); ?>"><?php printf( __( 'Rate: %s', 'motopress-hotel-booking' ), $roomBreakdown['room']['rate'] ); ?></div>
								</td>
								<td class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['total'] ); ?></td>
							</tr>
							<?php if ( MPHB()->settings()->main()->isAdultsAllowed() ) { ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php
										if ( MPHB()->settings()->main()->isChildrenAllowed() ) {
											_e( 'Adults', 'motopress-hotel-booking' );
										} else {
											_e( 'Guests', 'motopress-hotel-booking' );
										}
									?></td>
									<td><?php echo $roomBreakdown['room']['adults']; ?></td>
								</tr>
							<?php } ?>
							<?php if ( $roomBreakdown['room']['children_capacity'] > 0 && MPHB()->settings()->main()->isChildrenAllowed() ) { ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Children', 'motopress-hotel-booking' ); ?></td>
									<td><?php echo $roomBreakdown['room']['children']; ?></td>
								</tr>
							<?php } ?>
							<tr class="<?php echo esc_attr($foldedClass); ?>">
								<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Nights', 'motopress-hotel-booking' ); ?></td>
								<td><?php echo count( $roomBreakdown['room']['list'] ); ?></td>
							</tr>
							<tr class="<?php echo esc_attr($foldedClass); ?>">
								<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Dates', 'motopress-hotel-booking' ); ?></th>
								<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
							</tr>
							<?php foreach ( $roomBreakdown['room']['list'] as $date => $datePrice ) : ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php echo \MPHB\Utils\DateUtils::formatDateWPFront( \DateTime::createFromFormat( 'Y-m-d', $date ) ); ?></td>
									<td class="mphb-table-price-column"><?php echo mphb_format_price( $datePrice ); ?></td>
								</tr>
							<?php endforeach; ?>
							<tr class="<?php echo esc_attr($foldedClass); ?>">
								<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Dates Subtotal', 'motopress-hotel-booking' ); ?></th>
								<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['room']['total'] ); ?></th>
							</tr>
							<?php if ( $roomBreakdown['room']['discount'] > 0 ) { ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Discount', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php echo mphb_format_price( -$roomBreakdown['room']['discount'] ); ?></th>
								</tr>
							<?php } ?>
							<tr class="<?php echo esc_attr($foldedClass); ?>">
								<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Accommodation Subtotal', 'motopress-hotel-booking' ); ?></th>
								<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['room']['discount_total'] ); ?></th>
							</tr>

							<?php if ( isset( $roomBreakdown['taxes']['room'] ) && !empty( $roomBreakdown['taxes']['room']['list'] ) ) { ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Accommodation Taxes', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
								</tr>
								<?php foreach ( $roomBreakdown['taxes']['room']['list'] as $roomTax ) { ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php echo $roomTax['label']; ?></td>
										<td class="mphb-table-price-column"><?php echo mphb_format_price( $roomTax['price'] ); ?></td>
									</tr>
								<?php } ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Accommodation Taxes Subtotal', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['taxes']['room']['total'] ); ?></th>
								</tr>
							<?php } ?>

							<?php if ( isset( $roomBreakdown['services'] ) && !empty( $roomBreakdown['services']['list'] ) ) : ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 3 : 2 ); ?>">
										<?php _e( 'Services', 'motopress-hotel-booking' ); ?>
									</th>
								</tr>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th><?php _e( 'Service', 'motopress-hotel-booking' ); ?></th>
									<th><?php _e( 'Details', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
								</tr>
								<?php foreach ( $roomBreakdown['services']['list'] as $serviceDetails ) : ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<td><?php echo $serviceDetails['title']; ?></td>
										<td><?php echo $serviceDetails['details']; ?></td>
										<td class="mphb-table-price-column"><?php echo mphb_format_price( $serviceDetails['total'] ); ?></td>
									</tr>
								<?php endforeach; ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>">
										<?php _e( 'Services Subtotal', 'motopress-hotel-booking' ); ?>
									</th>
									<th class="mphb-table-price-column">
										<?php echo mphb_format_price( $roomBreakdown['services']['total'] ); ?>
									</th>
								</tr>

								<?php if ( isset( $roomBreakdown['taxes']['services'] ) && !empty( $roomBreakdown['taxes']['services']['list'] ) ) { ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Service Taxes', 'motopress-hotel-booking' ); ?></th>
										<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
									</tr>
									<?php foreach ( $roomBreakdown['taxes']['services']['list'] as $serviceTax ) { ?>
										<tr class="<?php echo esc_attr($foldedClass); ?>">
											<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php echo $serviceTax['label']; ?></td>
											<td class="mphb-table-price-column"><?php echo mphb_format_price( $serviceTax['price'] ); ?></td>
										</tr>
									<?php } ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Service Taxes Subtotal', 'motopress-hotel-booking' ); ?></th>
										<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['taxes']['services']['total'] ); ?></th>
									</tr>
								<?php } ?>
							<?php endif; ?>

							<?php if ( isset( $roomBreakdown['fees'] ) && !empty( $roomBreakdown['fees']['list'] ) ) { ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Fees', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
								</tr>
								<?php foreach ( $roomBreakdown['fees']['list'] as $fee ) { ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php echo $fee['label']; ?></td>
										<td class="mphb-table-price-column"><?php echo mphb_format_price( $fee['price'] ); ?></td>
									</tr>
								<?php } ?>
								<tr class="<?php echo esc_attr($foldedClass); ?>">
									<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Fees Subtotal', 'motopress-hotel-booking' ); ?></th>
									<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['fees']['total'] ); ?></th>
								</tr>

								<?php if ( isset( $roomBreakdown['taxes']['fees'] ) && !empty( $roomBreakdown['taxes']['fees']['list'] ) ) { ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Fee Taxes', 'motopress-hotel-booking' ); ?></th>
										<th class="mphb-table-price-column"><?php _e( 'Amount', 'motopress-hotel-booking' ); ?></th>
									</tr>
									<?php foreach ( $roomBreakdown['taxes']['fees']['list'] as $feeTax ) { ?>
										<tr class="<?php echo esc_attr($foldedClass); ?>">
											<td colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php echo $feeTax['label']; ?></td>
											<td class="mphb-table-price-column"><?php echo mphb_format_price( $feeTax['price'] ); ?></td>
										</tr>
									<?php } ?>
									<tr class="<?php echo esc_attr($foldedClass); ?>">
										<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Fee Taxes Subtotal', 'motopress-hotel-booking' ); ?></th>
										<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['taxes']['fees']['total'] ); ?></th>
									</tr>
								<?php } ?>
							<?php } ?>

						<?php endif; ?>
						<tr class="<?php echo esc_attr($foldedClass); ?>">
							<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php _e( 'Subtotal', 'motopress-hotel-booking' ); ?></th>
							<th class="mphb-table-price-column"><?php echo mphb_format_price( $roomBreakdown['discount_total'] ); ?></th>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<?php if ( !empty( $priceBreakdown['coupon'] ) ) : ?>
						<tr>
							<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>"><?php printf( __( 'Coupon: %s' ), $priceBreakdown['coupon']['code'] ); ?></th>
							<td class="mphb-table-price-column">
								<?php echo mphb_format_price( -1 * $priceBreakdown['coupon']['discount'] ); ?>
								<a href="#" class="mphb-remove-coupon"><?php _e( 'Remove', 'motopress-hotel-booking' ); ?></a>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>">
							<?php _e( 'Total', 'motopress-hotel-booking' ); ?>
						</th>
						<th class="mphb-table-price-column">
							<?php
							echo mphb_format_price( $priceBreakdown['total'] );
							?>
						</th>
					</tr>
					<?php if ( !empty( $priceBreakdown['deposit'] ) ) : ?>
						<tr>
							<th colspan="<?php echo ( $useThreeColumns ? 2 : 1 ); ?>">
								<?php _e( 'Deposit', 'motopress-hotel-booking' ); ?>
							</th>
							<th class="mphb-table-price-column">
								<?php
								echo mphb_format_price( $priceBreakdown['deposit'] );
								?>
							</th>
						</tr>
					<?php endif; ?>
				</tfoot>
			</table>
			<?php
		endif;
		return ob_get_clean();
	}

	public static function renderCheckInDateWPFormatted( Entities\Booking $booking ){
		echo date_i18n( MPHB()->settings()->dateTime()->getDateFormatWP(), $booking->getCheckInDate()->getTimestamp() );
	}

	public static function renderCheckOutDateWPFormatted( Entities\Booking $booking ){
		echo date_i18n( MPHB()->settings()->dateTime()->getDateFormatWP(), $booking->getCheckOutDate()->getTimestamp() );
	}

	public static function renderTotalPriceHTML( Entities\Booking $booking ){
		echo mphb_format_price( $booking->getTotalPrice() );
	}

}
