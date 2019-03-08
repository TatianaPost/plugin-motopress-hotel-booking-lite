<?php

namespace MPHB\Persistences;

class CouponPersistence extends CPTPersistence {

	protected function modifyQueryAtts( $atts ){
		$atts = parent::modifyQueryAtts( $atts );
		return $atts;
	}

}
