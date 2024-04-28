<?php

namespace LiveuEventsLog\Services;


use LiveuEventsLog\Admin\AdminListTable;
use LiveuEventsLog\Admin\AdminPage;
use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Admin\View\View;

class AdminPageLoader extends Service
{
	private AdminPage $admin_page;
	private Api $api;

	public function loaded (){
		$this->api = new Api($this->plugin);
		$this->admin_page = new AdminPage($this->api, new View());

		add_filter( 'set_screen_option_'.'logs_per_page', function( $status, $option, $value ){
			return (int) $value;
		}, 10, 3 );

		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
	}

	public function add_admin_pages() {
		$menu_page = add_menu_page(
			'Events History',
			'Events History',
			'manage_options',
			'liveu-events',
			[$this->admin_page, 'show_admin_page'],
			'dashicons-editor-ul',
			2
		);

		add_submenu_page(
			'liveu-events',
			'Settings',
			'Settings',
			'manage_options',
			'liveu-events-options',
			[$this->admin_page, 'show_options_page']
		);

		add_action( "load-$menu_page", [$this, 'AdminListTable_load'] );
	}



	public function AdminListTable_load(){

		$table = new AdminListTable($this->api);

		$this->admin_page->set_table($table);
	}
}
