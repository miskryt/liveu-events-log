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

	public function get_events_list_callback() {
		check_ajax_referer( 'myajax-nonce', 'nonce_code' );

		$search = $_REQUEST['search'] ?? '';
		$start  = $_REQUEST['start']  ?? 0;
		$length = $_REQUEST['length'] ?? 10;
		$draw   = $_REQUEST['draw']   ?? 1;

		$params = [
			'search' => $search,
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


	public function get_event_data_by_id(int $id): string {
		$event = $this->model->get_event_by_id($id);

		$logger = $this->plugin->get_instantiated_logger_by_slug( $event['logger'] );
		$logger_details_output = $logger->get_event_details_output( $event );

		return $logger_details_output;
	}

}
