<?php
namespace LiveuEventsLog\Admin\Api;

use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\EnumActions;
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
				$post_data = $this->model->get_event_context($event->id);
				var_dump($post_data[0]['value']);
				$d = [
					"id" => $event->id,
					"user" => get_user_by('id', $event->user_id)->user_login,
					"action" => EnumActions::get($event->action),
					"post_url" =>
						$post_data[2]['value'] . "(ID {$post_data[0]['value']} completely deleted)",

					"date" => $event->date,
					"post_type" => $event->post_type,
					"new" => $event->new,
				];
			}

			$data[] = $d;
		}

		return $data;
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

	public function get_diff_table_by_id(int $id): string {
		$event = $this->model->get_event($id);

		return $this->plugin->get_instantiated_logger_by_slug( $event['logger'] )->get_event_details_output($event );
	}

	public function set_event_viewed(int $id) {
		global $wpdb;
		$table = EVENTS_DATABASE_TABLE;

		$wpdb->update($table, [ 'new' => 0 ], ["id" => $id]);
	}

}
