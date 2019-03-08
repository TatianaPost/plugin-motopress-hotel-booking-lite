<?php

namespace MPHB\Entities;

class Room {

	private $id;

	/**
	 *
	 * @param int|\WP_POST $id
	 */
	public function __construct( $post ){
		if ( is_a( $post, '\WP_Post' ) ) {
			$this->id = $post->ID;
		} else {
			$this->id = absint( $post );
		}
	}

	/**
	 *
	 * @return int
	 */
	public function getRoomTypeId(){
		return absint( get_post_meta( $this->id, 'mphb_room_type_id', true ) );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 *
	 * @return string
	 */
	public function getTitle(){
		return get_the_title( $this->id );
	}

	/**
	 * Retrieve link for room post.
	 *
	 * @return string|false
	 */
	public function getLink(){
		return get_permalink( $this->id );
	}

	/**
	 *
	 * @return array
	 */
	public function getSyncData(){
		$syncData = get_post_meta( $this->id, 'mphb_sync_urls', true );
		return is_array( $syncData ) ? $syncData : array();
	}

	/**
	 *
	 * @return string[]
	 */
	public function getSyncUrls(){
		$urls = array_map( function( $item ) {
			return isset( $item['url'] ) ? $item['url'] : '';
		}, $this->getSyncData() );
		$urls = array_filter( $urls );

		return $urls;
	}

	public function setSyncData( $newData ){
		$oldData = $this->getSyncData();
		if ( $newData != $oldData ) {
			if ( empty( $newData ) ) {
				delete_post_meta( $this->getId(), 'mphb_sync_urls' );
			} else {
				update_post_meta( $this->getId(), 'mphb_sync_urls', $newData, $oldData );
			}
		}
	}

}
