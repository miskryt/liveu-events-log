<?php


namespace LiveuEventsLog\Services;


use LiveuEventsLog\Admin\AdminPage;

class AdminPageLoader extends Service
{
	private $admin_page;

	public function loaded (){
		$this->admin_page = new AdminPage();

		if( wp_doing_ajax() )
			add_action('wp_ajax_get_data', [$this->admin_page, 'get_events_list_callback']);

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
