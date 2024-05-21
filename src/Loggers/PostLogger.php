<?php
namespace LiveuEventsLog\Loggers;

use LiveuEventsLog\EnumActions;
use LiveuEventsLog\EnumLevels;
use LiveuEventsLog\Helpers\ArrayDiffMultidimensional;
use LiveuEventsLog\Helpers\DiffParser;

class PostLogger extends Logger
{
	public $slug = 'PostLogger';

	protected $old_post_data = array();

	public function loaded() : void {
		add_action( 'admin_action_editpost', array( $this, 'on_admin_action_editpost' ) , 10, 3 );
		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		add_action( 'init', array( $this, 'add_rest_hooks' ), 10, 2 );
	}

	public function get_slug (): string
	{
		return $this->slug;
	}

	public function add_rest_hooks() {

		$post_types = get_post_types( array(), 'object' );

		foreach ( $post_types as $post_type ) {
			add_filter( "rest_pre_insert_{$post_type->name}", array( $this, 'on_rest_pre_insert' ), 10, 2 );
			add_filter( "rest_after_insert_{$post_type->name}", array( $this, 'on_rest_after_insert' ), 10, 3 );
		}
	}

	public function on_admin_action_editpost() {

		$post_ID = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

		if ( $post_ID === 0 ) {
			return;
		}

		// Get original post from DB BEFORE it is saved now (how it was BEFORE changes written).
		$prev_post_data = get_post( $post_ID );

		if ( ! $prev_post_data instanceof \WP_Post ) {
			return;
		}

		$this->old_post_data[ $post_ID ] = array(
			'post_data' => $prev_post_data,
			'post_meta' => get_post_custom( $post_ID ),
			'post_terms' => wp_get_object_terms( $post_ID, get_object_taxonomies( $prev_post_data->post_type ) ),
		);
	}

	public function on_transition_post_status( $new_status, $old_status, $post ): void
	{
		$isRestApiRequest = defined( 'REST_REQUEST' ) && REST_REQUEST;

		if ( $isRestApiRequest ) {
			return;
		}


		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		if($post->post_type === 'acf-field' || $post->post_type === 'acf-field-group')
		{
			return;
		}

		$old_post = $this->old_post_data[ $post->ID ]['post_data'] ?? null;
		$old_post_meta = $this->old_post_data[ $post->ID ]['post_meta'] ?? null;
		$old_post_terms = $this->old_post_data[ $post->ID ]['post_terms'] ?? null;

		remove_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => $new_status ) );
		//add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );

		$args = array(
			'new_post' => $post,
			'new_post_meta' => get_post_custom( $post->ID ),
			'new_post_terms' => wp_get_object_terms( $post->ID, get_object_taxonomies( $post->post_type ) ),
			'old_post' => $old_post,
			'old_post_meta' => $old_post_meta,
			'old_post_terms' => $old_post_terms,
			'old_status' => $old_status
		);

		//var_dump($args['new_post_meta']);
		//var_dump(' <br/> ##########################################  <br/> ');
		//var_dump($args['old_post_meta']);
		//die();

		$this->maybe_log_post_change( $args );
	}

	public function on_rest_pre_insert( $prepared_post, $request ) {

		if ( empty( $prepared_post->ID ) ) {
			return $prepared_post;
		}

		$old_post = get_post( $prepared_post->ID );

		$this->old_post_data[ $old_post->ID ] = array(
			'post_data' => $old_post,
			'post_meta' => get_post_custom( $old_post->ID ),
			'post_terms' => wp_get_object_terms( $old_post->ID, get_object_taxonomies( $old_post->post_type ) ),
		);

		return $prepared_post;
	}

	public function on_rest_after_insert( $updated_post, $request, $creating ) {
		$updated_post = get_post( $updated_post->ID );
		$post_meta = get_post_custom( $updated_post->ID );

		$old_post = $this->old_post_data[ $updated_post->ID ]['post_data'] ?? null;
		$old_post_meta = $this->old_post_data[ $updated_post->ID ]['post_meta'] ?? null;
		$old_post_terms = $this->old_post_data[ $updated_post->ID ]['post_terms'] ?? null;

		$args = array(
			'new_post' => $updated_post,
			'new_post_meta' => $post_meta,
			'new_post_terms' => wp_get_object_terms( $updated_post->ID, get_object_taxonomies( $updated_post->post_type ) ),
			'old_post' => $old_post,
			'old_post_meta' => $old_post_meta,
			'old_post_terms' => $old_post_terms,
			'old_status' => $old_post ? $old_post->post_status : null,
			'_debug_caller_method' => __METHOD__,
		);

		$this->maybe_log_post_change( $args );
	}

	public function maybe_log_post_change( $args ) {
		$default_args = array(
			'new_post',
			'new_post_meta',
			'old_post',
			'old_post_meta',
			'old_status',
		);

		$args = wp_parse_args( $args, $default_args );

		if ( ! isset( $args['new_post'] ) || ! isset( $args['new_post_meta'] ) ) {
			return;
		}


		$new_status = $args['new_post']->post_status ?? null;

		$post = $args['new_post'];
		$new_post_data = array(
			'post_data' => $post,
			'post_meta' => $args['new_post_meta'],
			'post_terms' => $args['new_post_terms'],
		);



		$old_status = $args['old_post']->post_status ?? null;
		$old_status = ! isset( $old_status ) && isset( $args['old_status'] ) ? $args['old_status'] : $old_status;

		$old_post = $args['old_post'] ?? null;
		$old_post_meta = $args['old_post_meta'] ?? null;
		$old_post_data = array(
			'post_data' => $old_post,
			'post_meta' => $old_post_meta,
			'post_terms' => $args['old_post_terms'] ?? null,
		);

		$ok_to_log = true;

		if ( wp_is_post_revision( $post ) ) {
			$ok_to_log = false;
		}

		if ( isset( $_GET['meta-box-loader'] ) && sanitize_text_field( wp_unslash( $_GET['meta-box-loader'] ) ) ) {
			$ok_to_log = false;
		}

		if ( ! $ok_to_log ) {
			return;
		}

		/*
		From new to auto-draft <- ignore
		From new to inherit <- ignore
		From auto-draft to draft <- page/post created
		From draft to draft
		From draft to pending
		From pending to publish
		From pending to trash
		From something to publish = post published
		if not from & to = same, then user has changed something
		From draft to publish in future: status = "future"
		*/
		$context = array(
			'post_id' => $post->ID,
			'post_type' => get_post_type( $post ),
			'post_title' => get_the_title( $post ),
		);

		if ( 'auto-draft' === $old_status && ( 'auto-draft' !== $new_status && 'inherit' !== $new_status ) ) {
			// Post created.
			$this->log( EnumLevels::INFO,EnumActions::create, 'post_created', $context );
		} elseif ( 'auto-draft' === $new_status || ( 'new' === $old_status && 'inherit' === $new_status ) ) {
			// Post was automagically saved by WordPress.
			return;
		} elseif ( 'trash' === $new_status ) {
			// Post trashed.
			$this->log( EnumLevels::INFO,EnumActions::trash,'post_trashed', $context );
		} else
		{
			$context = $this->add_post_data_diff_to_context( $context, $old_post_data, $new_post_data );

			$this->log( EnumLevels::INFO, EnumActions::update, 'post_updated', $context );
		}
	}


	public function add_post_data_diff_to_context( $context, $old_post_data, $new_post_data ) {
		$old_data = $old_post_data['post_data'];
		$new_data = $new_post_data['post_data'];


		$post_data_diff = array();

		$arr_keys_to_diff = array(
			'post_title',
			'post_content',
		);

		$disabled_acf_keys = [
			'_edit_lock',
			'_pingme',
			'_encloseme'
		];

		foreach ( $arr_keys_to_diff as $key ) {
			if ( isset( $old_data->$key ) && isset( $new_data->$key ) ) {
				$post_data_diff = $this->add_diff( $post_data_diff, $key, $old_data->$key, $new_data->$key );
			}
		}

		foreach ( $post_data_diff as $diff_key => $diff_values ) {
			$context[ "prev#{$diff_key}" ] = $diff_values['old'];
			$context[ "new#{$diff_key}" ] = $diff_values['new'];
		}



		$old_meta = isset( $old_post_data['post_meta'] ) ? (array) $old_post_data['post_meta'] : array();
		$new_meta = isset( $new_post_data['post_meta'] ) ? (array) $new_post_data['post_meta'] : array();

		if($new_meta !== $old_meta)
		{
			$arr_diff = ArrayDiffMultidimensional::strictComparison($new_meta, $old_meta);

			foreach ($arr_diff as $field_name => $field_value)
			{
				if(in_array($key, $disabled_acf_keys)) continue;

				//var_dump($field_name);die();
				$context[ "prev#{$field_name}" ] = $old_meta[$field_name];
				$context[ "new#{$field_name}" ] = $new_meta[$field_name];
			}
		}

		return $context;
	}

	public function add_diff( $post_data_diff, $key, $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			$post_data_diff[ $key ] = array(
				'old' => $old_value,
				'new' => $new_value,
			);
		}

		return $post_data_diff;
	}


	private function append_context( $event_id, $context ) {
		global $wpdb;

		if ( empty( $event_id ) || empty( $context ) ) {
			return false;
		}

		foreach ( $context as $key => $value ) {

			if ( ! is_string( $value ) ) {
				$value = json_encode( $value );
			}

			$data = array(
				'event_id' => $event_id,
				'key' => $key,
				'value' => $value,
			);

			$wpdb->insert(EVENTS_CONTEXT_TABLE, $data );
		}

		return true;
	}

	private function log($level, $action, $message, $context) {
		global $wpdb;
		$localtime = current_time( 'mysql', 1 );

		$data = array(
			'user_id' => get_current_user_id(),
			'date' => $localtime,
			'message' => $message,
			'post_id' => $context['post_id'],
			'post_type' => $context['post_type'],
			'action'  => $action,
			'logger' => $this->slug
		);

		$result = $wpdb->insert(EVENTS_DATABASE_TABLE, $data );

		if ( false !== $result ) {
			$history_inserted_id = $wpdb->insert_id;
			$this->append_context( $history_inserted_id, $context );
		}
	}

	public function get_event_details (array $event)
	{
		$context = $event['context'];
		$message = $event['message'];

		$out = '';

		if( 'post_updated' === $message )
		{
			return $context;
		}
	}

	public function compare_terms( $a, $b ) {
		return $a['term_id'] <=> $b['term_id'];
	}
}
