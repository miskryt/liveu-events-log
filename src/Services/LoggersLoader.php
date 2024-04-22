<?php
namespace LiveuEventsLog\Services;

use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Config\Config;
use LiveuEventsLog\Loggers\Logger;

class LoggersLoader extends Service
{
	public function loaded (){

		add_action( 'after_setup_theme', array( $this, 'load_loggers' ) );
	}

	public function load_loggers() {

		$arr_loggers_to_instantiate = Config::get_instance()->get_loggers();
		$instantiated_loggers = [];

		foreach ( $arr_loggers_to_instantiate as $one_logger_class ) {
			$is_valid_logger_subclass = is_subclass_of( $one_logger_class, Logger::class );

			if ( ! $is_valid_logger_subclass ) {
				continue;
			}

			$logger_instance = new $one_logger_class(new Api($this->plugin));
			$logger_instance->loaded();

			$instantiated_loggers[ $logger_instance->get_slug() ] = array(
				'instance' => $logger_instance,
			);

			$this->plugin->set_instantiated_loggers($instantiated_loggers);
		}
	}
}
