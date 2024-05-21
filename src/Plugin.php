<?php
namespace LiveuEventsLog;

use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Config\Config;

class Plugin {

	private Config $config;
	private Api $api;
	private array $instantiated_loggers = [];

	public function __construct() {
		$this->config = Config::get_instance();
		$this->api = new Api($this);
	}

	public function run() {
		$this->load_services();

		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 10, 3);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 10, 3);
		add_action( 'admin_menu', [$this, 'add_user_menu_bubble'], 10, 3 );
	}

	public function add_user_menu_bubble(){
		global $menu;

		$notification_count = $this->api->get_new_events_count();

		if( $notification_count ){
			foreach( $menu as $key => $value ){
				if( $menu[$key][2] === 'liveu-events' ){
					$menu[$key][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $notification_count . '</span></span>';
					break;
				}
			}
		}
	}

	private function load_services() {
		$services = $this->config->get_services();

		foreach ($services  as $service_classname )
		{
			if ( ! class_exists( $service_classname ) )
			{
				return;
			}

			$service = new $service_classname($this);

			//$this->instantiated_services[] = $service;
			$service->loaded();
		}
	}

	public function enqueue_admin_scripts($hook) {

		if('toplevel_page_liveu-events' !== $hook) {
			return;
		}

		wp_enqueue_script('bootstrap-js', LEVLOG_PLUGIN_URL. '/assets/admin/lib/bootstrap-5.3.3-dist/js/bootstrap.js');
		//wp_enqueue_script('datatables-js', LEVLOG_PLUGIN_URL. '/assets/admin/lib/datatables/datatables.js');
		//wp_enqueue_script('levlog-admin-js', LEVLOG_PLUGIN_URL. '/assets/admin/js/levlog-admin.js', );

		/*
		wp_localize_script( 'levlog-admin-js', 'myajax',
			array(
				'nonce' => wp_create_nonce('myajax-nonce')
			)
		);
		*/

	}

	public function enqueue_admin_styles() {
		//wp_enqueue_style('datatables-css', LEVLOG_PLUGIN_URL. '/assets/admin/lib/datatables/datatables.css');
		wp_enqueue_style('bootstrap-css', LEVLOG_PLUGIN_URL. '/assets/admin/lib/bootstrap-5.3.3-dist/css/bootstrap.css');
		wp_enqueue_style('iconoir-css', 'https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css');
		wp_enqueue_style('plugin-css', LEVLOG_PLUGIN_URL. '/assets/admin/css/plugin.css');
	}

	public function get_instantiated_loggers() {
		return $this->instantiated_loggers;
	}

	public function set_instantiated_loggers( $instantiated_loggers ) {
		$this->instantiated_loggers = $instantiated_loggers;
	}

	public function get_instantiated_logger_by_slug( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		foreach ( $this->get_instantiated_loggers() as $one_logger ) {
			if ( $slug === $one_logger['instance']->get_slug() ) {
				return $one_logger['instance'];
			}
		}

		return false;
	}
}
