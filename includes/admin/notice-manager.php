<?php

namespace MPHB\Admin;

class NoticeManager {

	private $notices = array();

	public function __construct(){
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 *
	 * @param string $message
	 * @param string $type Optional. Type (color) of notice. Possible values: error, warning, success, info. Success by default.
	 * @param bool $dismissable Optional. Whether to show closing icon. It will not prevent a message from re-appearing once the page re-loads, or another page is loaded. True by default.
	 */
	public function addNotice( $message, $type = 'success', $dismissable = true ){
		$this->notices[] = array(
			'message'		 => $message,
			'type'			 => $type,
			'dismissable'	 => $dismissable
		);
	}

	public function render(){
		foreach ( $this->notices as $noticeDetails ) {
			$this->renderNotice( $noticeDetails['message'], $noticeDetails['type'], $noticeDetails['dismissable'] );
		}
	}

	/**
	 *
	 * @param string $message
	 * @param string $type
	 * @param bool $dismissable
	 */
	public function renderNotice( $message, $type, $dismissable ){
		$class = 'notice mphb-admin-notice';
		$class .= ' notice-' . $type;
		$class .= $dismissable ? ' is-dismissible' : '';
		printf( '<div class="%1$s">%2$s</div>', esc_attr( $class ), $message );
	}

}
