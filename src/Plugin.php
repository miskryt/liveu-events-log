<?php
namespace LiveuEventsLog;

use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\Admin\View\View;
use LiveuEventsLog\Config\Config;

class Plugin {

	protected $notification_count;
	private Model $model;
	private Config $config;

	public function __construct() {
		$this->notification_count = 0;
		$this->model = new Model();
		$this->config = new Config();

		//$slack = true;
		//$email = true;
		//$web = true;

		//$stack = new Notifier();
		//$stack = new SlackNotifier($stack);
		//$stack = new AdminWebNotifier($stack);
		//$stack->send('Alarm!');

	}

	public function run() {
		if( wp_doing_ajax() )
			add_action('wp_ajax_get_data', [$this, 'get_events_list_callback']);

		add_action('admin_menu', [$this, 'init_admin_menu'], 10, 2);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 10, 3);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 10, 3);

		$this->load_services($this->get_services());
		$this->set_menu_notificators();
	}

	public function get_events_list_callback() {
		$result = $this->model->get_events_list();
		echo ($result);
		wp_die();
	}


	private function get_services(){
		return $this->config->get_services();
	}

	private function load_services($services) {
		foreach ($services  as $service_classname )
		{
			if ( ! class_exists( $service_classname ) )
			{
				return;
			}

			$service = new $service_classname($this->config);
			$service->loaded();
		}
	}

	private function set_menu_notificators() {
		$this->notification_count = $this->model->records_count();
	}

	public function enqueue_admin_scripts($hook) {

		if('toplevel_page_liveu-events' !== $hook) {
			return;
		}

		wp_enqueue_script('datatables', LEVLOG_PLUGIN_URL. '/assets/admin/js/datatables.min.js');
		wp_enqueue_script('levlog-admin', LEVLOG_PLUGIN_URL. '/assets/admin/js/levlog-admin.js', );

		wp_localize_script( 'levlog-admin', 'myajax',
			array(
				'nonce' => wp_create_nonce('myajax-nonce')
			)
		);
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style('datatables', LEVLOG_PLUGIN_URL. '/assets/admin/css/datatables.min.css');
	}

	public function init_admin_menu() {

		add_menu_page(
			'Events History',
			$this->notification_count ? sprintf('Events History <span class="awaiting-mod">%d</span>', $this->notification_count) : 'Events History',
			'manage_options',
			'liveu-events',
			[new Admin\AdminPage($this->model, new View), 'show'],
			plugin_dir_url(__FILE__) . 'images/icon_wporg.png',
			2
		);

		add_submenu_page(
			'liveu-events',
			'Settings',
			'Settings',
			'manage_options',
			'liveu-events-options',
			[$this, 'init_options_page']
		);
	}

	public function init_options_page() {
		echo "settings page";
	}
}
