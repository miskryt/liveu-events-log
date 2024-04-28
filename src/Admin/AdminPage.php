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
			$diff_table = $this->api->get_event_data_by_id((int)$_REQUEST['event_id']);
			$this->api->set_event_viewed((int)$_REQUEST['event_id']);
			$data[] = $diff_table;

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
