<?php
namespace LiveuEventsLog\Loggers;

use LiveuEventsLog\EnumActions;
use LiveuEventsLog\EnumLevels;
use LiveuEventsLog\Helpers\DiffParser;

class PostLogger extends Logger
{
	public $slug = 'PostLogger';

	protected $old_post_data = array();

	public function loaded() : void {
		add_action( 'admin_action_editpost', array( $this, 'on_admin_action_editpost' ) );
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

		$args = array(
			'new_post' => $post,
			'new_post_meta' => get_post_custom( $post->ID ),
			'new_post_terms' => wp_get_object_terms( $post->ID, get_object_taxonomies( $post->post_type ) ),
			'old_post' => $old_post,
			'old_post_meta' => $old_post_meta,
			'old_post_terms' => $old_post_terms,
			'old_status' => $old_status
		);



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

		// Will contain the differences.
		$post_data_diff = array();

		$arr_keys_to_diff = array(
			'post_title',
			'post_name',
			'post_content',
			'post_status',
			'menu_order',
			'post_date',
			'post_date_gmt',
			'post_excerpt',
			'comment_status',
			'ping_status',
			'post_parent', // only id, need to get context for that, like name of parent at least?
			'post_author', // only id, need to get more info for user.
		);

		//$arr_keys_to_diff = $this->add_keys_to_diff( $arr_keys_to_diff );

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

			// If post_author then get more author info,
			// because just a user ID does not get us far.
			if ( 'post_author' === $diff_key ) {
				$old_author_user = get_userdata( (int) $diff_values['old'] );
				$new_author_user = get_userdata( (int) $diff_values['new'] );

				if ( is_a( $old_author_user, 'WP_User' ) && is_a( $new_author_user, 'WP_User' ) ) {
					$context[ "post_prev_{$diff_key}/user_login" ] = $old_author_user->user_login;
					$context[ "post_prev_{$diff_key}/user_email" ] = $old_author_user->user_email;
					$context[ "post_prev_{$diff_key}/display_name" ] = $old_author_user->display_name;

					$context[ "post_new_{$diff_key}/user_login" ] = $new_author_user->user_login;
					$context[ "post_new_{$diff_key}/user_email" ] = $new_author_user->user_email;
					$context[ "post_new_{$diff_key}/display_name" ] = $new_author_user->display_name;
				}
			}
		}

		$arr_meta_keys_to_ignore = array(
			'_edit_lock',
			'_edit_last',
			'_post_restored_from',
			'_wp_page_template',
			'_thumbnail_id',
			'_encloseme',
		);

		$meta_changes = array(
			'added' => array(),
			'removed' => array(),
			'changed' => array(),
		);

		$old_meta = isset( $old_post_data['post_meta'] ) ? (array) $old_post_data['post_meta'] : array();
		$new_meta = isset( $new_post_data['post_meta'] ) ? (array) $new_post_data['post_meta'] : array();

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

		$old_post_has_password = ! empty( $old_data->post_password );
		$old_post_password = $old_post_has_password ? $old_data->post_password : null;
		$old_post_status = $old_data->post_status ?? null;

		$new_post_has_password = ! empty( $new_data->post_password );
		$new_post_password = $new_post_has_password ? $new_data->post_password : null;
		$new_post_status = $new_data->post_status ?? null;

		if ( false === $old_post_has_password && 'publish' === $new_post_status && $new_post_has_password ) {
			$context['post_password_protected'] = true;
		} elseif (
			$old_post_has_password &&
			'publish' === $old_post_status &&
			false === $new_post_has_password &&
			'publish' === $new_post_status
		) {
			$context['post_password_unprotected'] = true;
		} elseif ( $old_post_has_password && $new_post_has_password && $old_post_password !== $new_post_password ) {
			$context['post_password_changed'] = true;
		} elseif ( 'private' === $new_post_status && 'private' !== $old_post_status ) {
			$context['post_private'] = true;

			if ( $old_post_has_password ) {
				$context['post_password_unprotected'] = true;
			}
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

		$term_changes = [
			'added' => [],
			'removed' => [],
		];

		$term_changes['added'] = array_values( array_udiff( $new_post_terms, $old_post_terms, [ $this, 'compare_terms' ] ) );
		$term_changes['removed'] = array_values( array_udiff( $old_post_terms, $new_post_terms, [ $this, 'compare_terms' ] ) );


		$context['post_terms_added'] = $term_changes['added'];
		$context['post_terms_removed'] = $term_changes['removed'];


		//return apply_filters( 'simple_history/post_logger/context', $context, $old_data, $new_data, $old_meta, $new_meta );

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

	public function get_event_details_output (array $event): string
	{
		$context = $event['context'];
		$message = $event['message'];

		$out = '';

		if( 'post_updated' === $message ) {

			$diff_table_output = '';
			$has_diff_values = false;

			foreach ( $context as $key => $val ) {

				if ( strpos( $key, 'post_prev_' ) !== false ) {
					$key_to_diff = substr( $key, strlen( 'post_prev_' ) );
					$key_for_new_val = "post_new_{$key_to_diff}";

					if ( isset( $context[ $key_for_new_val ] ) ) {
						$post_old_value = $context[ $key ];
						$post_new_value = $context[ $key_for_new_val ];

						if ( $post_old_value !== $post_new_value ) {
							if ( 'post_title' === $key_to_diff ) {
								$has_diff_values = true;
								$label = 'Title';

								$diff_table_output .= sprintf(
									'<tr><td>%1$s</td><td>%2$s</td></tr>',
									$label,
									DiffParser::text_diff( $post_old_value, $post_new_value )
								);
							}
							elseif ( 'post_content' === $key_to_diff ) {
								$has_diff_values = true;
								$label = 'Content';

								$key_text_diff = DiffParser::text_diff( $post_old_value, $post_new_value );

								if ( $key_text_diff ) {
									$diff_table_output .= sprintf(
										'<tr><td>%1$s</td><td>%2$s</td></tr>',
										$label,
										$key_text_diff
									);
								}
							}
							elseif ( 'post_status' === $key_to_diff ) {
								$has_diff_values = true;
								$label = 'Status';

								$diff_table_output .= sprintf(
									'<tr>
										<td>%1$s</td>
										<td>Changed from %2$s to %3$s</td>
									</tr>',
									$label,
									esc_html( $post_old_value ),
									esc_html( $post_new_value )
								);
							}
							elseif ( 'post_date' === $key_to_diff ) {
								$has_diff_values = true;
								$label = 'Publish date';

								$diff_table_output .= sprintf(
									'<tr>
										<td>%1$s</td>
										<td>Changed from %2$s to %3$s</td>
									</tr>',
									$label,
									esc_html( $post_old_value ),
									esc_html( $post_new_value )
								);
							}
							elseif ( 'post_name' === $key_to_diff ) {
								$has_diff_values = true;
								$label = 'Permalink';

								// $diff = new FineDiff($post_old_value, $post_new_value, FineDiff::$wordGranularity);
								$diff_table_output .= sprintf(
									'<tr>
										<td>%1$s</td>
										<td>%2$s</td>
									</tr>',
									$label,
									DiffParser::text_diff( $post_old_value, $post_new_value )
								);
							}
						}
					}
				}
			}

			if (
				isset( $context['post_meta_added'] ) ||
				isset( $context['post_meta_removed'] ) ||
				isset( $context['post_meta_changed'] )
			) {
				$meta_changed_out = '';
				$has_diff_values = true;

				if ( isset( $context['post_meta_added'] ) ) {
					$meta_changed_out .=
						"<span class=''>" .
						(int) $context['post_meta_added'] .
						' added</span> ';
				}

				if ( isset( $context['post_meta_removed'] ) ) {
					$meta_changed_out .=
						"<span class=''>" .
						(int) $context['post_meta_removed'] .
						' removed</span> ';
				}

				if ( isset( $context['post_meta_changed'] ) ) {
					$meta_changed_out .=
						"<span class=''>" .
						(int) $context['post_meta_changed'] .
						' changed</span> ';
				}

				$diff_table_output .= sprintf(
					'<tr>
						<td>%1$s</td>
						<td>%2$s</td>
					</tr>',
					'Custom fields',
					$meta_changed_out
				);
			}

			if ( $has_diff_values || $diff_table_output ) {
				$diff_table_output =
					'<table class="SimpleHistoryLogitem__keyValueTable">' . $diff_table_output . '</table>';
			}

			$out .= $diff_table_output;
		}

		return $out;
	}

	public function compare_terms( $a, $b ) {
		return $a['term_id'] <=> $b['term_id'];
	}
}
