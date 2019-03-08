<?php

namespace MPHB\ScriptManagers;

class AdminScriptManager extends ScriptManager {

	private $roomIds = array();

	public function __construct(){
		parent::__construct();
		add_action( 'admin_enqueue_scripts', array( $this, 'register' ), 9 );
	}

	public function register(){
		parent::register();

		wp_register_script( 'mphb-jquery-serialize-json', $this->scriptUrl( 'vendors/jquery.serializeJSON/jquery.serializejson.min.js' ), array( 'jquery' ), MPHB()->getVersion() );
		wp_register_script( 'mphb-bgrins-spectrum', $this->scriptUrl( 'vendors/bgrins-spectrum/build/spectrum-min.js' ), array( 'jquery' ), MPHB()->getVersion(), true );
		$this->addDependency( 'mphb-bgrins-spectrum' );

		wp_register_script( 'mphb-admin', $this->scriptUrl( 'assets/js/admin/admin.min.js' ), $this->scriptDependencies, MPHB()->getVersion(), true );
	}

	protected function registerStyles(){
		parent::registerStyles();

		$this->registerDatepickTheme();

		wp_register_style( 'mphb-bgrins-spectrum', $this->scriptUrl( 'vendors/bgrins-spectrum/build/spectrum_theme.css' ), null, MPHB()->getVersion() );
		$this->addStyleDependency( 'mphb-bgrins-spectrum' );

		wp_register_style( 'mphb-admin-css', $this->scriptUrl( 'assets/css/admin.min.css' ), $this->styleDependencies, MPHB()->getVersion() );
	}

	protected function registerDatepickTheme(){
		$theme = MPHB()->settings()->main()->getDatepickerAdminTheme();
		$themeFile = $this->locateDatepickFile( $theme );

		if ( $themeFile !== false ) {
			wp_register_style( 'mphb-kbwood-datepick-admin-theme', $themeFile, array( 'mphb-kbwood-datepick-css' ), MPHB()->getVersion() );
			$this->addStyleDependency( 'mphb-kbwood-datepick-admin-theme' );
		}
	}

	public function enqueue(){
		if ( !wp_script_is( 'mphb-admin' ) ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'localize' ), 5 );
		}
		wp_enqueue_script( 'mphb-admin' );

		wp_enqueue_style( 'mphb-admin-css' );
	}

	public function addRoomData( $roomId ){
		if ( !in_array( $roomId, $this->roomIds ) ) {
			$this->roomIds[] = $roomId;
		}
	}

	public function localize(){
		wp_localize_script( 'mphb-admin', 'MPHBAdmin', $this->getLocalizeData() );
	}

	public function getLocalizeData(){
		$currencySymbol = MPHB()->settings()->currency()->getCurrencySymbol();
		$currencyPosition = MPHB()->settings()->currency()->getCurrencyPosition();
		$data = array(
			'_data' => array(
				'version'			 => MPHB()->getVersion(),
				'prefix'			 => MPHB()->getPrefix(),
				'ajaxUrl'			 => MPHB()->getAjaxUrl(),
				'today'				 => mphb_current_time( 'Y-m-d' ),
				'nonces'			 => MPHB()->getAjax()->getAdminNonces(),
				'translations'		 => array(
					'roomTypeGalleryTitle'	 => __( 'Accommodation Type Gallery', 'motopress-hotel-booking' ),
					'addGalleryToRoomType'	 => __( 'Add Gallery To Accommodation Type', 'motopress-hotel-booking' ),
					'errorHasOccured'		 => __( 'An error has occurred', 'motopress-hotel-booking' ),
					'all'					 => __( 'All', 'motopress-hotel-booking' ),
					'none'					 => __( 'None', 'motopress-hotel-booking' ),
					'edit'					 => __( 'Edit', 'motopress-hotel-booking' ),
					'done'					 => __( 'Done', 'motopress-hotel-booking' ),
					'adults'				 => __( 'Adults: ', 'motopress-hotel-booking' ),
					'children'				 => __( 'Children: ', 'motopress-hotel-booking' ),
					'removePeriod'			 => __( 'Remove', 'motopress-hotel-booking' )
				),
				'settings'			 => array(
					'firstDay'					 => MPHB()->settings()->dateTime()->getFirstDay(),
					'numberOfMonthCalendar'		 => 2,
					'numberOfMonthDatepicker'	 => 2,
					'dateFormat'				 => MPHB()->settings()->dateTime()->getDateFormatJS(),
					'dateTransferFormat'		 => MPHB()->settings()->dateTime()->getDateTransferFormatJS(),
					'datepickerClass'			 => MPHB()->settings()->main()->getDatepickerThemeClass(),
					'upgradeToPremiumMsgHtml'	 => mphb_upgrade_to_premium_message( '<span class="description">', '</span>' ),
					'currency'					 => array(
						'price_format'				 => MPHB()->settings()->currency()->getPriceFormat( $currencySymbol, $currencyPosition ),
						'decimals'					 => MPHB()->settings()->currency()->getPriceDecimalsCount(),
						'decimal_separator'			 => MPHB()->settings()->currency()->getPriceDecimalsSeparator(),
						'thousand_separator'		 => MPHB()->settings()->currency()->getPriceThousandSeparator()
					)
				)
			),
		);

		// Maybe enable custom order for room attributes?
		$isAttributesCustomOrder = false;

		if ( mphb_is_attribute_taxonomy_edit_page()
			&& !isset( $_GET['orderby'] )
			&& ( !isset( $_GET['lang'] ) || $_GET['lang'] != 'all' )
			&& MPHB()->isWpSupportsTermmeta()
		) {
			$isAttributesCustomOrder = true;
		}

		$data['_data']['settings']['isAttributesCustomOrder'] = $isAttributesCustomOrder;
		$data['_data']['settings']['editTaxonomyName'] = isset( $_GET['taxonomy'] ) ? mphb_clean( wp_unslash( $_GET['taxonomy'] ) ) : '';

		return $data;
	}

}
