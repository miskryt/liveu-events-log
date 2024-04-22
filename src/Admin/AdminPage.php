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
			$diff_table = $this->api->get_event_data_by_id((int)$_REQUEST['event_id']);
			$data[] = $diff_table;

			$this->viewer->render('templates/diff', $data);
		}
		else
		{
			$this->viewer->render('templates/admin', $data);
		}
	}
}
