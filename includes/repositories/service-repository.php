<?php

namespace MPHB\Repositories;

use \MPHB\Entities;

class ServiceRepository extends AbstractPostRepository {

	protected $type = 'season';

	/**
	 *
	 * @param int $id
	 * @param bool $force Optional. FALSE by defautl.
	 * @return Entities\Service
	 */
	public function findById( $id, $force = false ){
		return parent::findById( $id, $force );
	}

	public function mapPostToEntity( $post ){

		if ( is_a( $post, '\WP_Post' ) ) {
			$id = $post->ID;
		} else {
			$id		 = absint( $post );
			$post	 = get_post( $id );
		}

		$price = get_post_meta( $id, 'mphb_price', true );

		$periodicity = get_post_meta( $id, 'mphb_price_periodicity', true );
		if ( empty( $periodicity ) ) {
			$periodicity = 'once';
		}

		$quantity = get_post_meta( $id, 'mphb_price_quantity', true );
		if ( empty( $quantity ) ) {
			$quantity = 'once';
		}

		$atts = array(
			'id'			 => $id,
			'original_id'	 => MPHB()->translation()->getOriginalId( $id, MPHB()->postTypes()->service()->getPostType() ),
			'title'			 => get_the_title( $id ),
			'description'	 => get_post_field( 'post_content', $id ),
			'price'			 => $price ? floatval( $price ) : 0.0,
			'periodicity'	 => $periodicity,
			'quantity'		 => $quantity
		);

		return Entities\Service::create( $atts );
	}

	/**
	 *
	 * @param Entities\Service $entity
	 * @return \MPHB\Entities\WPPostData
	 */
	public function mapEntityToPostData( $entity ){

		$postAtts = array(
			'ID'		 => $entity->getId(),
			'post_metas' => array(),
//			'post_status'	 => $entity->getStatus(),
			'post_type'	 => MPHB()->postTypes()->service()->getPostType(),
		);

		$postAtts['post_metas'] = array(
			'mphb_price'		 => $entity->getPrice(),
			'mphb_periodicity'	 => $entity->getPeriodicity(),
			'mphb_quantity'		 => $entity->getQuantity()
		);

		return new Entities\WPPostData( $postAtts );
	}

}
