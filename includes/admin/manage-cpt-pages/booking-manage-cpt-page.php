<?php

namespace MPHB\Admin\ManageCPTPages;

use \MPHB\PostTypes\BookingCPT;
use \MPHB\Views;
use \MPHB\Entities;

class BookingManageCPTPage extends ManageCPTPage {

	protected function addActionsAndFilters(){
		parent::addActionsAndFilters();

		$this->addTitleAction( __( 'New Booking', 'motopress-hotel-booking' ), '#', array( 'class' => 'button-disabled', 'after' => mphb_upgrade_to_premium_message( '<span class="description">', '</span>' ) ) );

		add_filter( 'request', array( $this, 'filterCustomOrderBy' ) );

		add_filter( 'post_row_actions', array( $this, 'filterRowActions' ) );
		add_action( 'restrict_manage_posts', array( $this, 'editPostsFilters' ) );
		add_action( 'restrict_manage_posts', array( $this, 'fillDependencedData' ) );

		if ( is_admin() ) {
			add_action( 'parse_query', array( $this, 'setQueryVarsSearchEmail' ) );
			add_filter( 'posts_join', array( $this, 'extendSearchPostsJoin' ), 10, 2 );
			add_filter( 'posts_search', array( $this, 'extendPostsSearch' ), 10, 2 );
			add_filter( 'posts_search_orderby', array( $this, 'extendPostsSearchOrderBy' ), 10, 2 );
			add_filter( 'posts_distinct', array( $this, 'searchDistinct' ) );
		}

		// Bulk actions
		add_filter( 'bulk_actions-edit-' . $this->postType, array( $this, 'filterBulkActions' ) );
		add_action( 'admin_notices', array( $this, 'bulkAdminNotices' ) );
		add_action( 'admin_footer', array( $this, 'bulkAdminScript' ) );
		add_action( 'load-edit.php', array( $this, 'bulkAction' ) );
	}

	public function filterColumns( $columns ){

		if ( isset( $columns['title'] ) ) {
			unset( $columns['title'] );
		}

		$customColumns = array(
			'id'				 => __( 'ID', 'motopress-hotel-booking' ),
			'status'			 => __( 'Status', 'motopress-hotel-booking' ),
			'check_in_out_date'	 => __( 'Check-in / Check-out', 'motopress-hotel-booking' ),
			'guests'			 => __( 'Guests', 'motopress-hotel-booking' ),
			'customer_info'		 => __( 'Customer Info', 'motopress-hotel-booking' ),
			'price'				 => __( 'Price', 'motopress-hotel-booking' ),
			'mphb_date'			 => __( 'Date', 'motopress-hotel-booking' )
		);

		$offset	 = array_search( 'date', array_keys( $columns ) ); // Set custom columns position before "DATE" column
		$columns = array_slice( $columns, 0, $offset, true ) + $customColumns + array_slice( $columns, $offset, count( $columns ) - 1, true );

		unset( $columns['date'] );
		return $columns;
	}

	public function filterSortableColumns( $columns ){

		$columns['id']				 = 'ID';
		$columns['check_in_date']	 = 'mphb_check_in_date';
		$columns['check_out_date']	 = 'mphb_check_out_date';

		return $columns;
	}

	public function renderColumns( $column, $postId ){
		$booking = MPHB()->getBookingRepository()->findById( $postId );
		switch ( $column ) {
			case 'id':
				printf( '<a href="%s"><strong>' . __( 'Booking #%s', 'motopress-hotel-booking' ) . '</strong></a>', esc_url( get_edit_post_link( $postId ) ), $postId );
				break;
			case 'status':
				$status = $booking->getStatus();
				?>
				<span class="column-status-<?php echo $status; ?>"><?php echo esc_html( mphb_get_status_label( $status ) ); ?></span>
				<?php
				if ( $status === BookingCPT\Statuses::STATUS_PENDING_PAYMENT ) {
					$payments = MPHB()->getPaymentRepository()->findAll( array(
						'booking_id'	 => $booking->getId(),
						'post_status'	 => \MPHB\PostTypes\PaymentCPT\Statuses::STATUS_PENDING
						)
					);
					foreach ( $payments as $payment ) {
						echo sprintf( '(<small><a href="%s">#%s</a></small>)', esc_url( get_edit_post_link( $payment->getId() ) ), $payment->getId() );
					}
				}
				if ( in_array( $status, array( BookingCPT\Statuses::STATUS_PENDING_USER, BookingCPT\Statuses::STATUS_PENDING_PAYMENT ) ) ) {
					$expireTime = $booking->retrieveExpiration( $status === BookingCPT\Statuses::STATUS_PENDING_USER ? 'user' : 'payment'  );
					if ( $expireTime ) {
						$currentTime = current_time( 'timestamp', true );
						echo '<br/>';
						echo '<small>';
						if ( $expireTime > $currentTime ) {
							printf( __( 'Expire %s', 'motopress-hotel-booking' ), human_time_diff( $currentTime, $expireTime ) );
						} else {
							_e( 'Expired', 'motopress-hotel-booking' );
						}
						echo '</small>';
					}
				}
				break;
			case 'guests':
				$reservedRooms = $booking->getReservedRooms();
				if ( !empty( $reservedRooms ) && !$booking->isImported() ) {
					$adultsTotal   = 0;
					$childrenTotal = 0;
					foreach ( $reservedRooms as $reservedRoom ) {
						$adultsTotal   += $reservedRoom->getAdults();
						$childrenTotal += $reservedRoom->getChildren();
					}
					_e( 'Adults: ', 'motopress-hotel-booking' );
					echo $adultsTotal . '<br/>';
					if ( $childrenTotal > 0 ) {
						_e( 'Children: ', 'motopress-hotel-booking' );
						echo $childrenTotal . '<br/>';
					}
					echo '<br/>';
				} else {
					echo static::EMPTY_VALUE_PLACEHOLDER;
				}
				break;
			case 'check_in_out_date' :

				$checkInDate	 = $booking->getCheckInDate();
				$checkOutDate	 = $booking->getCheckOutDate();

				echo (!is_null( $checkInDate )) ? '<time title="' . \MPHB\Utils\DateUtils::formatDateWPFront( $checkInDate ) . '">' . date_i18n( 'M j', $checkInDate->format( 'U' ) ) . '</time>' : '<span aria-hidden="true">' . static::EMPTY_VALUE_PLACEHOLDER . '</span>';
				echo ' - ';
				echo (!is_null( $checkOutDate )) ? '<time title="' . \MPHB\Utils\DateUtils::formatDateWPFront( $checkOutDate ) . '">' . date_i18n( 'M j', $checkOutDate->format( 'U' ) ) . '</time>' : '<span aria-hidden="true">' . static::EMPTY_VALUE_PLACEHOLDER . '</span>';
				echo '<br/>';

				if ( !is_null( $checkInDate ) && !is_null( $checkOutDate ) ) {
					// There is a bug on Windows platforms: the result is always 6015 days.
					// See http://php.net/manual/ru/datetime.diff.php#102507
					// (Found on Windows 7, PHP 5.3.5)
					$nights = \MPHB\Utils\DateUtils::calcNights( $checkInDate, $checkOutDate );
					?><em><?php printf( _n( '%s night', '%s nights', $nights, 'motopress-hotel-booking' ), $nights ); ?></em><?php
				}

				break;
			case 'customer_info':
				$customer = $booking->getCustomer();
				?>
				<p>
					<?php if ( $customer ) : ?>
						<?php echo esc_html( $customer->getFirstName() . ' ' . $customer->getLastName() ); ?>
						<br>
						<a href="mailto:<?php echo esc_html( $customer->getEmail() ); ?>">
							<?php echo esc_html( $customer->getEmail() ); ?>
						</a>
						<br>
						<a href="tel:<?php echo esc_html( $customer->getPhone() ); ?>">
							<?php echo esc_html( $customer->getPhone() ); ?>
						</a>
					<?php else: ?>
						<span aria-hidden="true"><?php echo static::EMPTY_VALUE_PLACEHOLDER; ?></span>
					<?php endif; ?>
				</p>
				<?php
				break;
			case 'price':
				Views\BookingView::renderTotalPriceHTML( $booking );
				echo '<br/>';
				$payments	 = MPHB()->getPaymentRepository()->findAll( array(
					'booking_id'	 => $booking->getId(),
					'post_status'	 => \MPHB\PostTypes\PaymentCPT\Statuses::STATUS_COMPLETED
					)
				);
				$totalPaid	 = 0.0;
				foreach ( $payments as $payment ) {
					$totalPaid += $payment->getAmount();
				}
				$paidLabel = sprintf( __( 'Paid: %s', 'motopress-hotel-booking' ), mphb_format_price( $totalPaid ) );
				printf( '<a href="%1$s">%2$s</a>', esc_url( MPHB()->postTypes()->payment()->getManagePage()->getUrl( array( 's' => $booking->getId() ) ) ), $paidLabel );
				break;
			case 'mphb_date':
				?>
				<abbr title="<?php echo esc_attr( get_the_date( MPHB()->settings()->dateTime()->getDateTimeFormatWP(), $postId ) ); ?>">
					<?php echo get_the_date( 'Y/m/d', $postId ); ?>
				</abbr>
				<?php
				break;
		}
	}

	public function filterRowActions( $actions ){
		// Prevent Quick Edit
		if ( $this->isCurrentPage() ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
		}

		return $actions;
	}

	public function editPostsFilters(){
		if ( !$this->isCurrentPage() ) {
			return;
		}
		$email = isset( $_GET['mphb_email'] ) ? sanitize_email( $_GET['mphb_email'] ) : '';
		echo '<input type="text" name="mphb_email" placeholder="' . esc_attr__( 'Email', 'motopress-hotel-booking' ) . '" value="' . esc_attr( $email ) . '" />';
	}

	/**
	 *
	 * @global \WP_Query $wp_query
	 */
	public function fillDependencedData(){
		global $wp_query;

		if ( !$this->isCurrentPage() ) {
			return;
		}

		MPHB()->getReservedRoomRepository()->fillBookingReservedRooms( $wp_query->posts );
	}

	public function filterBulkActions( $bulkActions ){
		if ( isset( $bulkActions['edit'] ) ) {
			unset( $bulkActions['edit'] );
		}
		return $bulkActions;
	}

	/**
	 * Add extra bulk action options to change booking status.
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031.
	 */
	public function bulkAdminScript(){
		if ( $this->isCurrentPage() ) {
			$optionText = __( 'Set to %s', 'motopress-hotel-booking' );
			?>
			<script type="text/javascript">
				(function( $ ) {
					$( function() {
						var options = [
							$( '<option />', {
								value: 'set-status-<?php echo BookingCPT\Statuses::STATUS_PENDING; ?>',
								text: '<?php printf( $optionText, mphb_get_status_label( BookingCPT\Statuses::STATUS_PENDING ) ); ?>'
							} ),
							$( '<option />', {
								value: 'set-status-<?php echo BookingCPT\Statuses::STATUS_PENDING_USER; ?>',
								text: '<?php printf( $optionText, mphb_get_status_label( BookingCPT\Statuses::STATUS_PENDING_USER ) ); ?>'
							} ),
							$( '<option />', {
								value: 'set-status-<?php echo BookingCPT\Statuses::STATUS_ABANDONED; ?>',
								text: '<?php printf( $optionText, mphb_get_status_label( BookingCPT\Statuses::STATUS_ABANDONED ) ); ?>'
							} ),
							$( '<option />', {
								value: 'set-status-<?php echo BookingCPT\Statuses::STATUS_CONFIRMED; ?>',
								text: '<?php printf( $optionText, mphb_get_status_label( BookingCPT\Statuses::STATUS_CONFIRMED ) ); ?>'
							} ),
							$( '<option />', {
								value: 'set-status-<?php echo BookingCPT\Statuses::STATUS_CANCELLED; ?>',
								text: '<?php printf( $optionText, mphb_get_status_label( BookingCPT\Statuses::STATUS_CANCELLED ) ); ?>'
							} )
						];

						var topBulkSelect = $( 'select[name="action"]' );
						var bottomBulkSelect = $( 'select[name="action2"]' );

						$.each( options, function( index, option ) {
							topBulkSelect.append( option.clone() );
							bottomBulkSelect.append( option.clone() );
						} );

					} );
				})( jQuery )
			</script>
			<?php
		}
	}

	/**
	 * Process the new bulk actions for changing booking status.
	 */
	public function bulkAction(){
		$wp_list_table	 = _get_list_table( 'WP_Posts_List_Table' );
		$action			 = $wp_list_table->current_action();

		if ( strpos( $action, 'set-status-' ) === false ) {
			return;
		}

		$allowedStatuses = MPHB()->postTypes()->booking()->statuses()->getStatuses();

		$newStatus		 = substr( $action, 11 );
		$reportAction	 = 'setted-status-' . $newStatus;

		if ( !isset( $allowedStatuses[$newStatus] ) ) {
			return;
		}

		check_admin_referer( 'bulk-posts' );

		$postIds = isset( $_REQUEST['post'] ) ? array_map( 'absint', (array) $_REQUEST['post'] ) : array();

		if ( empty( $postIds ) ) {
			return;
		}

		$changed = 0;
		foreach ( $postIds as $postId ) {

			$booking = MPHB()->getBookingRepository()->findById( $postId, true );
			$booking->setStatus( $newStatus );

			if ( MPHB()->getBookingRepository()->save( $booking ) ) {
				$changed++;
			}
		}

		$queryArgs = array(
			$reportAction	 => true,
			'changed'		 => $changed,
			'ids'			 => join( ',', $postIds ),
			'paged'			 => $wp_list_table->get_pagenum()
		);

		if ( isset( $_GET['post_status'] ) ) {
			$queryArgs['post_status'] = sanitize_text_field( $_GET['post_status'] );
		}

		$sendback = $this->getUrl( $queryArgs );

		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	/**
	 * Show message that booking status changed for number of bookings.
	 */
	public function bulkAdminNotices(){
		if ( $this->isCurrentPage() ) {
			// Check if any status changes happened
			foreach ( MPHB()->postTypes()->booking()->statuses()->getStatuses() as $slug => $details ) {

				if ( isset( $_REQUEST['setted-status-' . $slug] ) ) {

					$number	 = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
					$message = sprintf( _n( 'Booking status changed.', '%s booking statuses changed.', $number, 'motopress-hotel-booking' ), number_format_i18n( $number ) );
					echo '<div class="updated"><p>' . $message . '</p></div>';

					break;
				}
			}
		}
	}

	/**
	 *
	 * @param \WP_Query $query
	 */
	public function setQueryVarsSearchEmail( $query ){
		if ( $this->isCurrentPage() && $query->is_main_query() ) {
			if ( isset( $_GET['mphb_email'] ) && $_GET['mphb_email'] != '' ) {
				$query->query_vars['meta_key']		 = 'mphb_email';
				$query->query_vars['meta_value']	 = sanitize_text_field( $_GET['mphb_email'] );
				$query->query_vars['meta_compare']	 = '=';
			}
		}
	}

	/**
	 *
	 * @global \WPDB $wpdb
	 * @param string $join
	 * @param \WP_Query $wp_query
	 * @return string
	 */
	public function extendSearchPostsJoin( $join, $wp_query ){
		global $wpdb;
		if ( $this->isCurrentPage() && !empty( $wp_query->query['s'] ) ) {
			$joinCount = (int) $wp_query->get( '_mphb_join_meta', 0 );
			for ( $i = 1; $i <= $joinCount; $i++ ) {
				$join .= " LEFT JOIN $wpdb->postmeta AS mphb_postmeta_{$i} ON $wpdb->posts.ID = mphb_postmeta_{$i}.post_id ";
			}
		}
		return $join;
	}

	/**
	 *
	 * @global \WPDB $wpdb
	 * @param string $where
	 * @param \WP_Query $wp_query
	 * @return string
	 */
	public function extendPostsSearch( $where, $wp_query ){
		global $wpdb;

		if ( $this->isCurrentPage() && !empty( $wp_query->query['s'] ) ) {

			$search		 = trim( $wp_query->query['s'] );
			$customWhere = '';

			if ( is_email( $search ) ) {
				$joinCount = $wp_query->get( '_mphb_join_meta', 0 ) + 1;
				$wp_query->set( '_mphb_join_meta', $joinCount );

				$customWhere = $wpdb->prepare( "( mphb_postmeta_{$joinCount}.meta_key = %s AND CAST( mphb_postmeta_{$joinCount}.meta_value as CHAR ) = %s )", 'mphb_email', $search );
			} else if ( is_numeric( $search ) ) {
				$customWhere = $wpdb->prepare( "($wpdb->posts.ID = %d)", (int) $search );
			}

			if ( !empty( $customWhere ) ) {
				$where = " AND ({$customWhere}) ";
			}
		}

		return $where;
	}

	public function extendPostsSearchOrderBy( $orderBy, $wp_query ){
		// Prevent OrderBy Search terms
		return '';
	}

	public function filterCustomOrderBy( $vars ){
		if ( $this->isCurrentPage() && isset( $vars['orderby'] ) ) {
			switch ( $vars['orderby'] ) {
				case 'mphb_check_in_date':
					$vars	 = array_merge( $vars, array(
						'meta_key'	 => 'mphb_check_in_date',
						'orderby'	 => 'meta_value',
						'meta_type'	 => 'DATE'
						) );
					break;
				case 'mphb_check_out_date':
					$vars	 = array_merge( $vars, array(
						'meta_key'	 => 'mphb_check_out_date',
						'orderby'	 => 'meta_value',
						'meta_type'	 => 'DATE'
						) );
					break;
				case 'mphb_room_id':
					$vars	 = array_merge( $vars, array(
						'meta_key'	 => '',
						'orderby'	 => 'mphb_room_id'
						) );
					break;
			}
		}
		return $vars;
	}

	/**
	 *
	 * @param array $views
	 */
	public function filterViews( $views ){

		if ( isset( $views['mine'] ) ) {
			unset( $views['mine'] );
		}

		$viewsOrder = array(
			'all'										 => 0,
			BookingCPT\Statuses::STATUS_CONFIRMED		 => 10,
			BookingCPT\Statuses::STATUS_ABANDONED		 => 20,
			BookingCPT\Statuses::STATUS_CANCELLED		 => 30,
			BookingCPT\Statuses::STATUS_PENDING_USER	 => 40,
			BookingCPT\Statuses::STATUS_PENDING_PAYMENT	 => 50,
			BookingCPT\Statuses::STATUS_PENDING			 => 60,
			'trash'										 => 500
		);

		uksort( $views, function( $view1, $view2 ) use ($viewsOrder) {
			$view1Order	 = array_key_exists( $view1, $viewsOrder ) ? $viewsOrder[$view1] : 999;
			$view2Order	 = array_key_exists( $view2, $viewsOrder ) ? $viewsOrder[$view2] : 999;
			return $view1Order > $view2Order;
		} );

		return $views;
	}

	/**
	 * Prevent duplicates
	 *
	 * @global \WPDB $wpdb
	 * @param string $where
	 * @return string
	 */
	function searchDistinct( $where ){

		if ( is_search() ) {
			return "DISTINCT";
		}

		return $where;
	}

}
