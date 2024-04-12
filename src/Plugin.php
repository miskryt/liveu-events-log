<?php
namespace LiveuEventsLog;

use LiveuEventsLog\Admin\AdminPage;
use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\Admin\View\View;
use LiveuEventsLog\Config\Config;
use LiveuEventsLog\Services\AdminPageLoader;
use function Crontrol\Schedule\add;

class Plugin {

	private Model $model;
	private Config $config;


	public function __construct() {
		$this->model = new Model();
		$this->view = new View();
		$this->config = Config::get_instance();

		$this->load_services($this->get_services());
	}

	public function run() {


		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 10, 3);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 10, 3);




		add_action( 'admin_menu', [$this, 'add_user_menu_bubble'], 10, 3 );
	}

	public function add_user_menu_bubble(){
		global $menu;

		$notification_count = $this->model->get_events_count();

		if( $notification_count ){
			foreach( $menu as $key => $value ){
				if( $menu[$key][2] === 'liveu-events' ){
					$menu[$key][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $notification_count . '</span></span>';
					break;
				}
			}
		}
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

}
