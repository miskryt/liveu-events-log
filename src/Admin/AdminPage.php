<?php

namespace LiveuEventsLog\Admin;


use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Admin\Interfaces\IView;

class AdminPage
{
	private AdminListTable $admin_table;

	public function __construct(Api $api, IView $viewer) {
		$this->viewer = $viewer;
		$this->api = $api;
	}

	public function set_table(AdminListTable $table) {
		$this->admin_table = $table;
	}

	public function show_admin_page(): void {
		$data = [
			'title' => 'Admin Page'
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			$event_details = $this->api->get_event_details((int)$_REQUEST['event_id']);
			$diff_data = $this->api->get_event_diff_table((int)$_REQUEST['event_id']);

			$this->api->set_event_viewed((int)$_REQUEST['event_id']);

			$data['event'] = $event_details;
			$data['diff_data'] = $diff_data;

			$this->viewer->render('templates/diff', $data);
		}
		else
		{
			$data['table'] = $this->admin_table;
			$this->viewer->render('templates/admin', $data);
		}
	}

	public function show_options_page():void {

	}
}
