<?php
namespace LiveuEventsLog\Admin\Api;

use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\EnumActions;
use LiveuEventsLog\Helpers\DiffParser;
use LiveuEventsLog\Plugin;


class Api
{
	private Plugin $plugin;

	public function __construct(Plugin $plugin) {
		$this->model = new Model();
		$this->plugin = $plugin;

		if( wp_doing_ajax() )
			add_action('wp_ajax_get_events_list', [$this, 'get_events_list_callback']);
	}

	public function get_events_count() {
		return $this->model->get_events_count();
	}

	public function get_new_events_count() {
		return $this->model->get_new_events_count();
	}

	public function get_events_list($params, $return_type = OBJECT) {

		$events_list = $this->model->get_events_list($params, $return_type);
		$data = [];


		foreach ($events_list as $event)
		{
			if( $post = get_post($event->post_id) )
			{
				$d = [
					"id" => $event->id,
					"user" => get_user_by('id', $event->user_id)->user_login,
					"action" => EnumActions::get($event->action),
					"post_url" =>
						'<a  href="?page=liveu-events&action=show_diff&event_id=' . $event->id . '">' .
						get_post($event->post_id)->post_title .
						'</a>&nbsp;<a target="_blank" href="' . get_edit_post_link($event->post_id) . '"><i class="levlog-list-share-icon iconoir-open-in-window"></i></a>',

					"date" => $event->date,
					"post_type" => $event->post_type,
					"new" => $event->new,
				];
			}
			else
			{
				$post_data = $this->model->get_event_context($event->id, ARRAY_A);
				//var_dump($post_data);die();
				$d = [
					"id" => $event->id,
					"user" => get_user_by('id', $event->user_id)->user_login,
					"action" => EnumActions::get($event->action),
					"post_url" =>
						'<a  href="?page=liveu-events&action=show_diff&event_id=' . $event->id . '">' .
						$post_data[2]['value'] . "(ID {$post_data[0]['value']} completely deleted)" .
						'</a>',

					"date" => $event->date,
					"post_type" => $event->post_type,
					"new" => $event->new,
				];
			}

			$data[] = $d;
		}

		return $data;
	}

	public function get_event_diff_table($id) {
		$diff_table_output = '';
		$event = $this->model->get_event($id);
		$context = $event->context;

		foreach ($context as $key => $value)
		{
			if($key === 'post_title') continue;
			if($key === 'post_id') continue;
			if($key === 'post_type') continue;


			$key_to_diff = substr( $key, strlen( 'prev#' ) );
			$key_for_new_val = "new#{$key_to_diff}";

			if ( isset( $context[ $key_for_new_val ] ) )
			{
				$old_value = $context[ $key ];
				$new_value = $context[ $key_for_new_val ];

				if ( $old_value !== $new_value )
				{
					$field = substr($key, strpos($key, "#")+1);

					if(strpos($field, '_') === 0) continue;

					$label = $field;

					$acf_object = get_field_object(substr($key, strpos($key, "#")+1), $event->post_id);

					if(!empty($acf_object))
					{
						$label = $acf_object['label'] . ' <span style="font-size: 9px">(' . $field . ')</span>';
					}

					$diff_table_output .= sprintf(
						'<tr><td>%1$s</td><td>%2$s</td></tr>',
						$label,
						DiffParser::text_diff( $old_value, $new_value )
					);
				}
			}
		}

		return $diff_table_output;
	}

	public function get_event_details($id) {

		$event = $this->model->get_event($id);

		if( $post = get_post($event->post_id) )
		{
			$d = [
				"id" => $event->id,
				"user" => get_user_by('id', $event->user_id)->user_login,
				"action" => EnumActions::get($event->action),
				"post_url" =>
					'<a  href="?page=liveu-events&action=show_diff&event_id=' . $event->id . '">' .
					get_post($event->post_id)->post_title .
					'</a>&nbsp;<a target="_blank" href="' . get_edit_post_link($event->post_id) . '"><i class="levlog-list-share-icon iconoir-open-in-window"></i></a>',

				"date" => $event->date,
				"post_type" => $event->post_type,
				"post_id" => $event->post_id,
			];
		}
		else
		{
			$post_data = $this->model->get_event_context($event->id, ARRAY_A);

			$d = [
				"id" => $event->id,
				"user" => get_user_by('id', $event->user_id)->user_login,
				"action" => EnumActions::get($event->action),
				"post_url" =>
					'<a  href="?page=liveu-events&action=show_diff&event_id=' . $event->id . '">' .
					$post_data[2]['value'] . "(ID {$post_data[0]['value']} completely deleted)" .
					'</a>',

				"date" => $event->date,
				"post_type" => $event->post_type,
				"new" => $event->new,
			];
		}

		return $d;
	}

	public function get_events_list_callback() {
		check_ajax_referer( 'myajax-nonce', 'nonce_code' );

		$start  = $_REQUEST['start']  ?? 0;
		$length = $_REQUEST['length'] ?? 10;
		$draw   = $_REQUEST['draw']   ?? 1;

		$params = [
			'start'  => $start,
			'length' => $length,
			'draw'   => $draw
		];

		$events_list = $this->model->get_events_list($params);
		$events_count = $this->model->get_events_count();

		$data = $this->prepare_get_events_list_response($events_list);

		$result['data'] = $data;
		$result['draw'] = $draw;
		$result['recordsTotal'] = $events_count;
		$result['recordsFiltered'] = $events_count;

		echo wp_send_json_success($result);
		wp_die();
	}

	private function prepare_get_events_list_response (array $events_list): array
	{
		$data = [];

		foreach ($events_list as $event)
		{

			$d = [
				"id" => $event->id,
				"user" => get_user_by('id', $event->user_id)->user_login,
				"action" => EnumActions::get($event->action),
				"post_url" =>
					'<a  href="?page=liveu-events&action=show_diff&event_id=' . $event->id . '">' .
					get_post($event->post_id)->post_title .
					'</a>&nbsp;<a target="_blank" href="' . get_edit_post_link($event->post_id) . '"><i class="levlog-list-share-icon iconoir-open-in-window"></i></a>',

				"datetime" => $event->date,
				"post_type" => $event->post_type,
				"new" => $event->new,
			];

			$data[] = $d;
		}
		return $data;
	}

	public function set_events_viewed(array $ids) {
		global $wpdb;
		$table = EVENTS_DATABASE_TABLE;

		$wpdb->query(
			"UPDATE $table SET `new` = 0
			WHERE ID IN (". implode(',', array_map('absint',$ids) ) .")"
		);
	}

	public function set_events_not_viewed(array $ids) {
		global $wpdb;
		$table = EVENTS_DATABASE_TABLE;

		$wpdb->query(
			"UPDATE $table SET `new` = 1
			WHERE ID IN (". implode(',', array_map('absint',$ids) ) .")"
		);
	}

	public function delete_events(array $ids) {
		global $wpdb;
		$events_table = EVENTS_DATABASE_TABLE;
		$context_table = EVENTS_CONTEXT_TABLE;

		$ids = implode( ',', array_map( 'absint', $ids ) );

		if($wpdb->query( "DELETE FROM $context_table WHERE event_id IN($ids)" )){
			$wpdb->query( "DELETE FROM $events_table WHERE ID IN($ids)" );
		}
	}

}
