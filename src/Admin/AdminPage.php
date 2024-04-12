<?php

namespace LiveuEventsLog\Admin;

use LiveuEventsLog\Admin\Interfaces\IModel;
use LiveuEventsLog\Admin\Interfaces\IView;
use LiveuEventsLog\EnumActions;

class AdminPage
{
	private IModel $model;
	private IView $viewer;

	public function __construct() {
		$this->model = new Model\Model();
		$this->viewer = new View\View();
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

		$data = $this->prepare_response($events_list);

		$result['data'] = $data;
		$result['draw'] = $draw;
		$result['recordsTotal'] = $events_count;
		$result['recordsFiltered'] = $events_count;

		echo json_encode($result);
		wp_die();

	}

	public function show(): void {
		$data = [
			'title' => 'Admin Page'
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			$diff = $this->get_diff_by_id((int)$_REQUEST['event_id']);
			$this->viewer->render('templates/diff', $data);
		}
		else
		{
			$this->viewer->render('templates/admin', $data);
		}
	}

	private function get_diff_by_id (int $event_id) : array {
		if($event_id === 0) return [];

		$event_data = $this->model->get_event_data_by_id($event_id);

		var_dump($event_data); die();
	}

	/**
	 * @param array $events_list
	 * @param array $data
	 * @return array
	 */
	private function prepare_response (array $events_list): array
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
}
