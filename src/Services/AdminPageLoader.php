<?php

namespace LiveuEventsLog\Services;


use LiveuEventsLog\Admin\AdminPage;
use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Admin\View\View;

class AdminPageLoader extends Service
{
	private $admin_page;

	public function loaded (){
		$this->admin_page = new AdminPage(new Api($this->plugin), new View());

		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
	}

	public function add_admin_pages() {
		add_menu_page(
			'Events History',
			'Events History',
			'manage_options',
			'liveu-events',
			[$this->admin_page, 'show'],
			'dashicons-editor-ul',
			2
		);

		add_submenu_page(
			'liveu-events',
			'Settings',
			'Settings',
			'manage_options',
			'liveu-events-options',
			[$this->admin_page, 'init_options_page']
		);
	}
}
