<?php
namespace LiveuEventsLog\Services;

use LiveuEventsLog\Config\Config;
use LiveuEventsLog\Loggers\Logger;

class LoggersLoader extends Service
{
	public function loaded (){

		add_action( 'after_setup_theme', array( $this, 'load_loggers' ) );
	}

	public function load_loggers() {

		$arr_loggers_to_instantiate = $this->config->get_loggers();

		foreach ( $arr_loggers_to_instantiate as $one_logger_class ) {
			$is_valid_logger_subclass = is_subclass_of( $one_logger_class, Logger::class );

			if ( ! $is_valid_logger_subclass ) {
				continue;
			}

			$logger_instance = new $one_logger_class();
		}
	}
}
