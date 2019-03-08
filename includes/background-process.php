<?php

namespace MPHB;

/**
 * @note Action name length must be less than or equal to 16 symbols. Length of option name is 64.
 * 			Option name consist of:
 * 			+ (1-4) blog id
 * 			+ (23) wp's transient prefix "_site_transient_timeout"
 * 			+ (7) prefix "mphb_bp"
 * 			+ (1) prefix separator "_"
 * 			+ action name of background process
 * 			+ (13) suffix "_process_lock"
 *
 */
abstract class BackgroundProcess extends Libraries\WP_Background_Processing\WP_Background_Process {

	/**
	 *
	 * @var bool
	 */
	protected $paused = false;

	/**
	 *
	 * @var string
	 */
	protected $prefix = 'mphb_bp';

	/**
	 *
	 * @var array
	 */
	protected $wait_actions_option;

	public function __construct(){

		$this->prefix = $this->prefix . get_current_blog_id();

		parent::__construct();

		$this->wait_actions_option = $this->identifier . '_wait_actions';

		foreach ( $this->get_wait_actions() as $action ) {
			add_action( $action, array( $this, 'wait_action_handle' ) );
		}
	}

	protected function complete(){
		parent::complete();
		$this->clear_total_queue_size();
		do_action( $this->identifier . '_complete' );
	}

	/**
	 *
	 * @return string
	 */
	public function get_identifier(){
		return $this->identifier;
	}

	/**
	 *
	 * @param string $action
	 */
	public function wait_action( $action ){
		$wait_actions = $this->get_wait_actions();

		if ( !in_array( $action, $wait_actions ) ) {
			$wait_actions[] = $action;
			update_option( $this->wait_actions_option, $wait_actions );
		}
	}

	public function wait_action_handle(){
		$actions = array_filter( $this->get_wait_actions(), function( $action ) {
			return !doing_action( $action ) && !did_action( $action );
		} );

		if ( empty( $actions ) ) {
			delete_option( $this->wait_actions_option );
			$this->dispatch();
		} else {
			update_option( $this->wait_actions_option, $actions );
		}
	}

	public function pause(){

		$this->paused = true;

		$this->unlock_process();

		$this->schedule_event();

		return;
	}

	/**
	 * @return bool
	 */
	public function is_in_progress(){
		return !$this->is_queue_empty();
	}

	/**
	 *
	 * @return bool
	 */
	protected function is_process_running(){
		$wait_actions = $this->get_wait_actions();
		return parent::is_process_running() || !empty( $wait_actions );
	}

	/**
	 *
	 * @return array
	 */
	protected function get_wait_actions(){
		return get_option( $this->wait_actions_option, array() );
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck(){

		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			do_action( $this->identifier . '_complete' );
			return;
		}

		$this->handle();
	}

	/**
	 * Override parent method for make possible handle without wp_die in the end
	 *
	 * @return bool
	 */
	protected function handle(){
		$this->lock_process();

		do {
			$batch = $this->get_batch();

			foreach ( $batch->data as $key => $value ) {
				$task = $this->task( $value );

				if ( false !== $task ) {
					$batch->data[$key] = $task;
				} else {
					unset( $batch->data[$key] );
				}

				if ( $this->time_exceeded() || $this->memory_exceeded() ) {
					// Batch limits reached.
					break;
				}

				if ( $this->paused ) {
					// Process is paused
					break;
				}
			}

			// Update or delete current batch.
			if ( !empty( $batch->data ) ) {
				$this->update( $batch->key, $batch->data );
			} else {
				$this->delete( $batch->key );
			}
		} while ( !$this->time_exceeded() && !$this->memory_exceeded() && !$this->is_queue_empty() && !$this->paused );

		$this->unlock_process();

		// Start next batch or complete process.
		if ( !$this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
		}

		return;
	}

	/**
	 *
	 * @return int
	 */
	public function get_queue_size(){
		global $wpdb;

		$table	 = $wpdb->options;
		$column	 = 'option_name';

		if ( is_multisite() ) {
			$table	 = $wpdb->sitemeta;
			$column	 = 'meta_key';
		}

		$key = $this->identifier . '_batch_%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		return (int) $count;
	}

	/**
	 * Retrieve full queue size (include completed batches)
	 *
	 * @return int
	 */
	public function get_total_queue_size(){
		$totalSize = (int) get_option( $this->identifier . '_total', 0 );
		if ( !$totalSize ) {
			$totalSize = $this->get_queue_size();
		}
		return max( 0, $totalSize );
	}

	/**
	 *
	 * @param int $size
	 */
	protected function update_total_queue_size( $size ){
		update_option( $this->identifier . '_total', $size );
	}

	/**
	 * Increase total queue size
	 *
	 * @param int $inc Optional. Default 1;
	 */
	public function inc_total_queue_size( $inc = 1 ){
		$this->update_total_queue_size( $this->get_total_queue_size() + $inc );
	}

	public function clear_total_queue_size(){
		delete_option( $this->identifier . '_total' );
	}

	/**
	 *
	 * @return float
	 */
	public function get_progress_percent(){
		$total		 = max( $this->get_total_queue_size(), 0 );
		$progress	 = 100;

		if ( $total > 0 ) {
			$queueSize	 = $this->get_queue_size();
			$completed	 = max( $total - $queueSize, 0 );
			$progress	 = round( $completed / $total * 100, 2 );
		}

		return $progress;
	}

	public function save(){
		parent::save();
		if ( !empty( $this->data ) ) {
			$this->inc_total_queue_size();
		}
		return $this;
	}

}
