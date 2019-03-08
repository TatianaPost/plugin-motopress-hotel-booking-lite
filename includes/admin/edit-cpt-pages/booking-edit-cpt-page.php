<?php

namespace MPHB\Admin\EditCPTPages;

class BookingEditCPTPage extends EditCPTPage {

	protected function addActions(){
		parent::addActions();
		add_action( '_mphb_admin_after_field_render', array( $this, 'renderPaymentsDetails' ) );
	}

	public function customizeMetaBoxes(){
		remove_meta_box( 'submitdiv', $this->postType, 'side' );
		remove_meta_box( 'commentsdiv', $this->postType, 'normal' );
		remove_meta_box( 'commentstatusdiv', $this->postType, 'normal' );

		add_meta_box( 'mphb_rooms', __( 'Reserved Accommodations', 'motopress-hotel-booking' ), array( $this, 'renderRoomsDetailsMetaBox' ), $this->postType, 'advanced' );
		add_meta_box( 'submitdiv', __( 'Update Booking', 'motopress-hotel-booking' ), array( $this, 'renderSubmitMetaBox' ), $this->postType, 'side' );
		add_meta_box( 'logs', __( 'Logs', 'motopress-hotel-booking' ), array( $this, 'renderLogMetaBox' ), $this->postType, 'side' );
	}

	public function renderSubmitMetaBox( $post, $metabox ){
		$postTypeObject	 = get_post_type_object( $this->postType );
		$can_publish	 = current_user_can( $postTypeObject->cap->publish_posts );
		$postStatus		 = get_post_status( $post->ID );
		?>
		<div class="submitbox" id="submitpost">
			<div id="minor-publishing">
				<div id="minor-publishing-actions">
				</div>
				<div id="misc-publishing-actions">
					<div class="misc-pub-section">
						<label for="mphb_post_status">Status:</label>
						<select name="mphb_post_status" id="mphb_post_status">
							<?php foreach ( MPHB()->postTypes()->booking()->statuses()->getStatuses() as $statusName => $statusDetails ) { ?>
								<option value="<?php echo esc_attr( $statusName ); ?>" <?php selected( $statusName, $postStatus ); ?>>
									<?php echo esc_html( mphb_get_status_label( $statusName ) ); ?>
								</option>
							<?php } ?>
						</select>
					</div>
					<div class="misc-pub-section">
						<span><?php _e( 'Created on:', 'motopress-hotel-booking' ); ?></span>
						<strong><?php echo date_i18n( MPHB()->settings()->dateTime()->getDateTimeFormatWP( ' @ ' ), strtotime( $post->post_date ) ); ?></strong>
					</div>
				</div>
			</div>
			<div id="major-publishing-actions">
				<div id="delete-action">
					<?php
					if ( current_user_can( "delete_post", $post->ID ) ) {
						if ( !EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete Permanently', 'motopress-hotel-booking' );
						} else {
							$delete_text = __( 'Move to Trash', 'motopress-hotel-booking' );
						}
						?>
						<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a>
					<?php } ?>
				</div>
				<div id="publishing-action">
					<span class="spinner"></span>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update Booking', 'motopress-hotel-booking' ); ?>" />
					<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php
					in_array( $post->post_status, array( 'new', 'auto-draft' ) ) ? esc_attr_e( 'Create Booking', 'motopress-hotel-booking' ) : esc_attr_e( 'Update Booking', 'motopress-hotel-booking' );
					?>" />
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public function renderRoomsDetailsMetaBox( $post, $metabox ){
		// @todo add possibility of manage and edit reserved rooms

		$reservedRooms = MPHB()->getReservedRoomRepository()->findAllByBooking( $post->ID );

		foreach ( $reservedRooms as $key => $reservedRoom ) {
			$room				 = MPHB()->getRoomRepository()->findById( $reservedRoom->getRoomId() );
			$rate				 = MPHB()->getRateRepository()->findById( $reservedRoom->getRateId() );
			$reservedServices	 = $reservedRoom->getReservedServices();
			$guestName			 = $reservedRoom->getGuestName();

			_e( 'Accommodation:', 'motopress-hotel-booking' );
			if ( $room ) {
				echo ' <a href="' . esc_url( get_edit_post_link( $room->getId() ) ) . '">';
				echo $room->getTitle();
				echo '</a>';
			} else {
				self::EMPTY_VALUE_PLACEHOLDER;
			}
			echo '<br/>';
			_e( 'Rate:', 'motopress-hotel-booking' );
			if ( $rate ) {
				echo ' <a href="' . esc_url( get_edit_post_link( $rate->getOriginalId() ) ) . '">';
				echo $rate->getTitle();
				echo '</a>';
			} else {
				self::EMPTY_VALUE_PLACEHOLDER;
			}
			echo '<br/>';
			_e( 'Adults:', 'motopress-hotel-booking' );
			echo ' ' . $reservedRoom->getAdults();
			echo '<br/>';
			_e( 'Children:', 'motopress-hotel-booking' );
			echo ' ' . $reservedRoom->getChildren();
			echo '<br/>';
			_e( 'Services:', 'motopress-hotel-booking' );
			if ( !empty( $reservedServices ) ) {
				echo '<ol>';
				foreach ( $reservedServices as $reservedService ) {
					echo '<li>';
					echo '<a href="' . esc_url( get_edit_post_link( $reservedService->getOriginalId() ) ) . '">';
					echo $reservedService->getTitle();
					echo '</a>';
					if ( $reservedService->isPayPerAdult() ) {
						echo ' <em>';
						printf( _n( 'x %d guest', 'x %d guests', $reservedService->getAdults(), 'motopress-hotel-booking' ), $reservedService->getAdults() );
						echo '</em>';
					}
					echo '</li>';
				}
				echo '</ol>';
			} else {
				echo ' ' . static::EMPTY_VALUE_PLACEHOLDER;
			}

			if ( !empty( $guestName ) ) {
				echo '<br/>';
				_e( 'Guest:', 'motopress-hotel-booking' );
				echo $reservedRoom->getGuestName();
			}
			echo '<hr/>';
		}
	}

	public function renderLogMetaBox( $post, $metabox ){
		$booking = MPHB()->getBookingRepository()->findById( $post->ID );

		foreach ( array_reverse( $booking->getLogs() ) as $log ) {
			?>
			<strong> <?php _e( 'Date:', 'motopress-hotel-booking' ); ?></strong>
			<span>
				<?php comment_date( MPHB()->settings()->dateTime()->getDateTimeFormatWP( ' @ ' ), $log->comment_ID ); ?>
			</span>
			<br/>
			<strong><?php _e( 'Author:', 'motopress-hotel-booking' ); ?></strong>
			<?php
			if ( !empty( $log->user_id ) ) {
				$userInfo	 = get_userdata( $log->user_id );
				$authorName	 = sprintf( '<a target="_blank" href="%s">%s</a>', $userInfo->user_url, $userInfo->display_name );
			} else {
				$authorName = '<i>' . __( 'Auto', 'motopress-hotel-booking' ) . '</i>';
			}
			?>
			<span><?php echo $authorName; ?></span>
			<br/>
			<strong><?php _e( 'Message:', 'motopress-hotel-booking' ); ?></strong>
			<span> <?php echo $log->comment_content; ?></span>
			<hr/>
			<?php
		}
	}

	public function renderPaymentsDetails( $fieldName ){

		// Show payments only for existing bookings
		if ( !$this->isCurrentEditPage() ) {
			return;
		}

		// Show payments after total price
		if ( $fieldName !== 'mphb_total_price' ) {
			return;
		}

		$booking = MPHB()->getBookingRepository()->findById( get_the_ID() );

		$payments = MPHB()->getPaymentRepository()->findAll( array(
			'booking_id' => $booking->getId()
			)
		);

		echo '<br/>';

		echo '<table class="mphb-payments-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Payment ID', 'motopress-hotel-booking' ) . '</th>';
		echo '<th>' . __( 'Status', 'motopress-hotel-booking' ) . '</th>';
		echo '<th>' . __( 'Amount', 'motopress-hotel-booking' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$totalPaid = 0.00;
		if ( empty( $payments ) ) {
			$emptyPlaceholder = '&#8212;';
			echo '<tr><td>' . $emptyPlaceholder . '</td><td>' . $emptyPlaceholder . '</td><td>' . $emptyPlaceholder . '</td></tr>';
		} else {
			foreach ( $payments as $payment ) {
				if ( $payment->getStatus() === \MPHB\PostTypes\PaymentCPT\Statuses::STATUS_COMPLETED ) {
					$totalPaid += $payment->getAmount();
				}
				$paymentAmount	 = mphb_format_price( $payment->getAmount() );
				$paymentStatus	 = mphb_get_status_label( $payment->getStatus() );
				$rowClass		 = 'mphb-payment mphb-payment-status-' . $payment->getStatus();

				printf( '<tr class="%s">', $rowClass );
				echo '<td>' . sprintf( '<a href="%1$s">#%2$s</a>', esc_url( get_edit_post_link( $payment->getId() ) ), $payment->getId() ) . '</td>';
				echo '<td>' . $paymentStatus . '</td>';
				echo '<td>' . $paymentAmount . '</td>';
				echo '</tr>';
			}
		}

		$needToPay = $booking->getTotalPrice() - $totalPaid;
		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<th class="mphb-total-label" colspan="2">' . __( 'Total Paid', 'motopress-hotel-booking' ) . '</th>';
		echo '<th>' . mphb_format_price( $totalPaid ) . '</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th class="mphb-to-pay-label" colspan="2">' . __( 'To Pay', 'motopress-hotel-booking' ) . '</th>';
		echo '<th>' . mphb_format_price( $needToPay ) . '</th>';
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';

		$createManualPaymentUrl = MPHB()->postTypes()->payment()->getEditPage()->getUrl( array(
			'mphb_defaults' => array(
				'_mphb_booking_id'	 => $booking->getId(),
				'_mphb_gateway'		 => 'manual',
				'_mphb_gateway_mode' => 'live',
				'_mphb_amount'		 => $needToPay
			) ), true
		);

		printf( '<a href="%1$s" class="button button-primary">%2$s</a>', $createManualPaymentUrl, __( 'Add Payment Manually', 'motopress-hotel-booking' ) );
	}

	public function saveMetaBoxes( $postId, $post, $update ){
		$success = parent::saveMetaBoxes( $postId, $post, $update );

		if ( !$success ) {
			return false;
		}

		$status = isset( $_POST['mphb_post_status'] ) ? sanitize_text_field( $_POST['mphb_post_status'] ) : '';

		if ( !array_key_exists( $status, MPHB()->postTypes()->booking()->statuses()->getStatuses() ) ) {
			$status = '';
		}

		$bookingRepository = MPHB()->getBookingRepository();

		$booking = $bookingRepository->findById( $postId, true );
		$booking->setStatus( $status );
		$bookingRepository->save( $booking );
	}

	public function enqueueAdminScripts(){
		parent::enqueueAdminScripts();
		if ( $this->isCurrentPage() ) {
			wp_enqueue_script( 'mphb-jquery-serialize-json' );
		}
	}

}
