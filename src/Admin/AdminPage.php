<?php

namespace LiveuEventsLog\Admin;


use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Admin\Interfaces\IView;

class AdminPage
{
	public function __construct(Api $api, IView $viewer) {
		$this->viewer = $viewer;
		$this->api = $api;
	}

	public function show(): void {
		$data = [
			'title' => 'Admin Page'
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			$data = $this->api->get_event_data_by_id((int)$_REQUEST['event_id']);

			//$diff = $this->get_diff_by_id((int)$_REQUEST['event_id']);
			$this->viewer->render('templates/diff', $data);
		}
		else
		{
			$this->viewer->render('templates/admin', $data);
		}
	}

	private function get_diff_by_id (int $event_id) : array {
		if($event_id === 0)
			return [];

		//$event_data = $this->model->get_event_data_by_id($event_id);

		//var_dump($event_data); die();
	}
}
