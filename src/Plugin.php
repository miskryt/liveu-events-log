<?php
namespace LiveuEventsLog;

use LiveuEventsLog\Admin\AdminPage;
use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\Admin\View\View;
use LiveuEventsLog\Config\Config;
use LiveuEventsLog\Services\AdminPageLoader;
use function Crontrol\Schedule\add;

class Plugin {

	protected $notification_count;
	private Model $model;
	private Config $config;
	private $admin_page;

	public function __construct() {
		$this->notification_count = 0;
		$this->model = new Model();
		$this->view = new View();
		$this->config = Config::get_instance();
		$this->admin_page = new AdminPage($this->model, $this->view);
	}

	public function run() {
		if( wp_doing_ajax() )
			add_action('wp_ajax_get_data', [$this->admin_page, 'get_events_list_callback']);

		add_action('admin_menu', [$this, 'init_admin_menu'], 10, 2);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 10, 3);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 10, 3);
		add_action('admin_init', [$this, 'plugin_settings_init']);

		$this->load_services($this->get_services());
		$this->set_menu_notificators();
	}


	private function get_services(){
		return $this->config->get_services();
	}

	public function plugin_settings_init() {
		//todo add settings init
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
		$this->notification_count = $this->model->get_events_count();
	}

	public function enqueue_admin_scripts($hook) {

		if('toplevel_page_liveu-events' !== $hook) {
			return;
		}

		wp_enqueue_script('datatables-js', LEVLOG_PLUGIN_URL. '/assets/admin/lib/datatables/datatables.js');
		wp_enqueue_script('levlog-admin-js', LEVLOG_PLUGIN_URL. '/assets/admin/js/levlog-admin.js', );

		wp_localize_script( 'levlog-admin-js', 'myajax',
			array(
				'nonce' => wp_create_nonce('myajax-nonce')
			)
		);
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style('datatables-css', LEVLOG_PLUGIN_URL. '/assets/admin/lib/datatables/datatables.css');
		wp_enqueue_style('iconoir-css', 'https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css');
		wp_enqueue_style('plugin-css', LEVLOG_PLUGIN_URL. '/assets/admin/css/plugin.css');
	}

	public function init_admin_menu() {

		add_menu_page(
			'Events History',
			$this->notification_count ? sprintf('Events History <span class="awaiting-mod">%d</span>', $this->notification_count) : 'Events History',
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
			[$this, 'init_options_page']
		);
	}

	public function init_options_page() {
		echo "settings page";
	}
}
