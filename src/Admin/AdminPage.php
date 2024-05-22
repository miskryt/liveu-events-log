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

	public function loaded(){
		$this->load_table();
	}

	private function load_table() {
		$this->admin_table = new AdminListTable($this->api);
	}

	public function show_admin_page(): void {
		$data = [
			'title' => 'Admin Page'
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			$event_details = $this->api->get_event_details((int)$_REQUEST['event_id']);
			$diff_data = $this->api->get_event_diff_table((int)$_REQUEST['event_id']);

			$this->api->set_events_viewed([(int)$_REQUEST['event_id']]);

			$data['event'] = $event_details;
			$data['diff_data'] = $diff_data;

			$this->viewer->render('templates/diff', $data);
		}
		else
		{
			if( isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'set_read' )
			{
				$events_ids = array_map('absint',$_REQUEST['events']);
				$this->api->set_events_viewed($events_ids);
			}

			if( isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'set_unread' )
			{
				$events_ids = array_map('absint',$_REQUEST['events']);
				$this->api->set_events_not_viewed($events_ids);
			}

			if( isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'delete' )
			{
				$events_ids = array_map('absint',$_REQUEST['events']);
				$this->api->delete_events($events_ids);
			}

			$this->load_table();

			$data['table'] = $this->admin_table;
			$this->viewer->render('templates/admin', $data);
		}
	}

	public function show_options_page():void {

	}
}
