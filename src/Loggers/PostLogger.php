<?php
namespace LiveuEventsLog\Loggers;

use LiveuEventsLog\EnumActions;
use LiveuEventsLog\EnumLevels;

class PostLogger extends Logger
{
	public $slug = 'SimplePostLogger';

	protected $old_post_data = array();

	public function loaded() : void {
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
		add_filter( "rest_pre_insert_case-study", array( $this, 'on_rest_pre_insert' ), 10, 2 );
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
			// Old status is included because that's the value we get in filter
			// "transition_post_status", when a previous post may not exist.
			'old_status',
		);

		$args = wp_parse_args( $args, $default_args );

		// Bail if needed args not set.
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


		// Set old status to status from old post with fallback to old_status variable.
		$old_status = $args['old_post']->post_status ?? null;
		$old_status = ! isset( $old_status ) && isset( $args['old_status'] ) ? $args['old_status'] : $old_status;

		$old_post = $args['old_post'] ?? null;
		$old_post_meta = $args['old_post_meta'] ?? null;
		$old_post_data = array(
			'post_data' => $old_post,
			'post_meta' => $old_post_meta,
			'post_terms' => $args['old_post_terms'] ?? null,
		);

		// Default to log.
		$ok_to_log = true;

		// Don't log revisions.
		if ( wp_is_post_revision( $post ) ) {
			$ok_to_log = false;
		}

		// Don't log Gutenberg saving meta boxes.
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
		} else {
			// Existing post was updated.

			// Also add diff between previous saved data and new data.
			// Now we have both old and new post data, including custom fields, in the same format
			// So let's compare!
			$context = $this->add_post_data_diff_to_context( $context, $old_post_data, $new_post_data );
			$this->log( EnumLevels::INFO, EnumActions::update, 'post_updated', $context );
		}
	}

	public function add_post_data_diff_to_context( $context, $old_post_data, $new_post_data ) {
		$old_data = $old_post_data['post_data'];
		$new_data = $new_post_data['post_data'];

		// Will contain the differences.
		$post_data_diff = array();

		$arr_keys_to_diff = array(
			'post_title',
			'post_content',
			'post_status'
		);

		foreach ( $arr_keys_to_diff as $key ) {
			if ( isset( $old_data->$key ) && isset( $new_data->$key ) ) {
				$post_data_diff = $this->add_diff( $post_data_diff, $key, $old_data->$key, $new_data->$key );
			}
		}

		// If changes where detected.
		// Save at least 2 values for each detected value change, i.e. the old value and the new value.
		foreach ( $post_data_diff as $diff_key => $diff_values ) {
			$context[ "post_prev_{$diff_key}" ] = $diff_values['old'];
			$context[ "post_new_{$diff_key}" ] = $diff_values['new'];
		}

		// Compare custom fields.
		// Array with custom field keys to ignore because changed every time or very internal.
		$arr_meta_keys_to_ignore = array(
			'_edit_lock',
			'_edit_last',
			'_post_restored_from',
			'_wp_page_template',
			'_thumbnail_id',

			// _encloseme is added to a post when it's published. The wp-cron process should get scheduled shortly thereafter to process the post to look for enclosures.
			// https://wordpress.stackexchange.com/questions/20904/the-encloseme-meta-key-conundrum
			'_encloseme',
		);

		$meta_changes = array(
			'added' => array(),
			'removed' => array(),
			'changed' => array(),
		);

		$old_meta = isset( $old_post_data['post_meta'] ) ? (array) $old_post_data['post_meta'] : array();
		$new_meta = isset( $new_post_data['post_meta'] ) ? (array) $new_post_data['post_meta'] : array();

		// Add post featured thumb data.
		$context = $this->add_post_thumb_diff( $context, $old_meta, $new_meta );

		// Remove fields that we have checked already and other that should be ignored.
		foreach ( $arr_meta_keys_to_ignore as $key_to_ignore ) {
			unset( $old_meta[ $key_to_ignore ] );
			unset( $new_meta[ $key_to_ignore ] );
		}

		// Look for added custom fields/meta.
		foreach ( $new_meta as $meta_key => $meta_value ) {
			if ( ! isset( $old_meta[ $meta_key ] ) ) {
				$meta_changes['added'][ $meta_key ] = true;
			}
		}

		// Look for changed custom fields/meta.
		foreach ( $old_meta as $meta_key => $meta_value ) {
			if ( isset( $new_meta[ $meta_key ] ) && json_encode( $old_meta[ $meta_key ] ) !== json_encode( $new_meta[ $meta_key ] ) ) {
				$meta_changes['changed'][ $meta_key ] = true;
			}
		}

		if ( $meta_changes['added'] ) {
			$context['post_meta_added'] = count( $meta_changes['added'] );
		}

		if ( $meta_changes['removed'] ) {
			$context['post_meta_removed'] = count( $meta_changes['removed'] );
		}

		if ( $meta_changes['changed'] ) {
			$context['post_meta_changed'] = count( $meta_changes['changed'] );
		}



		// Todo: detect sticky.
		// Sticky is stored in option:
		// $sticky_posts = get_option('sticky_posts');.

		// Check for changes in post terms.
		$old_post_terms = $old_post_data['post_terms'] ?? [];
		$new_post_terms = $new_post_data['post_terms'] ?? [];

		// Keys to keep for each term: term_id, name, slug, term_taxonomy_id, taxonomy.
		$term_keys_to_keep = [
			'term_id',
			'name',
			'slug',
			'term_taxonomy_id',
			'taxonomy',
		];

		$old_post_terms = array_map(
			function ( $term ) use ( $term_keys_to_keep ) {
				return array_intersect_key( (array) $term, array_flip( $term_keys_to_keep ) );
			},
			$old_post_terms
		);

		$new_post_terms = array_map(
			function ( $term ) use ( $term_keys_to_keep ) {
				return array_intersect_key( (array) $term, array_flip( $term_keys_to_keep ) );
			},
			$new_post_terms
		);

		// Detect added and removed terms.
		$term_changes = [
			// Added = exists in new but not in old.
			'added' => [],
			// Removed = exists in old but not in new.
			'removed' => [],
		];

		$term_changes['added'] = array_values( array_udiff( $new_post_terms, $old_post_terms, [ $this, 'compare_terms' ] ) );
		$term_changes['removed'] = array_values( array_udiff( $old_post_terms, $new_post_terms, [ $this, 'compare_terms' ] ) );

		// Add old and new terms to context.
		$context['post_terms_added'] = $term_changes['added'];
		$context['post_terms_removed'] = $term_changes['removed'];

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

	public function add_post_thumb_diff( $context, $old_meta, $new_meta ) {
		$prev_post_thumb_id = null;
		$new_post_thumb_id = null;

		// If it was changed from one image to another.
		if ( isset( $old_meta['_thumbnail_id'][0] ) && isset( $new_meta['_thumbnail_id'][0] ) ) {
			if ( $old_meta['_thumbnail_id'][0] !== $new_meta['_thumbnail_id'][0] ) {
				$prev_post_thumb_id = $old_meta['_thumbnail_id'][0];
				$new_post_thumb_id = $new_meta['_thumbnail_id'][0];
			}
		} elseif ( isset( $old_meta['_thumbnail_id'][0] ) ) {
			// Featured image id did not exist on both new and old data. But on any?
			$prev_post_thumb_id = $old_meta['_thumbnail_id'][0];
		} elseif ( isset( $new_meta['_thumbnail_id'][0] ) ) {
			$new_post_thumb_id = $new_meta['_thumbnail_id'][0];
		}

		if ( $prev_post_thumb_id ) {
			$context['post_prev_thumb_id'] = $prev_post_thumb_id;
			$context['post_prev_thumb_title'] = get_the_title( $prev_post_thumb_id );
		}

		if ( $new_post_thumb_id ) {
			$context['post_new_thumb_id'] = $new_post_thumb_id;
			$context['post_new_thumb_title'] = get_the_title( $new_post_thumb_id );
		}

		return $context;
	}

	private function append_date_to_context( $data, $context ) {
		// Allow date to be overridden from context.
		// Date must be in format 'Y-m-d H:i:s'.
		if ( isset( $context['_date'] ) ) {
			$data['date'] = $context['_date'];
			unset( $context['_date'] );
		}

		return array( $data, $context );
	}

	private function append_context( $event_id, $context ) {
		if ( empty( $event_id ) || empty( $context ) ) {
			return false;
		}

		foreach ( $context as $key => $value ) {
			// Everything except strings should be json_encoded, ie. arrays and objects.
			if ( ! is_string( $value ) ) {
				$value = json_encode( $value );
			}

			$data = array(
				'event_id' => $event_id,
				'key' => $key,
				'value' => $value,
			);

			$this->api->insert_sql(EVENTS_CONTEXT_TABLE, $data );
		}

		return true;
	}

	private function log($level, $action, $message, $context) {
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

		[$data, $context] = $this->append_date_to_context( $data, $context );

		$result = $this->api->insert_sql(EVENTS_DATABASE_TABLE, $data );

		// Save context if able to store row.
		if ( false === $result ) {
			$history_inserted_id = null;
		} else {
			$history_inserted_id = $this->api->insert_id_sql();

			// Insert all context values into db.
			$this->append_context( $history_inserted_id, $context );
		} // End if().
	}
}
