<?php
namespace LiveuEventsLog\Config;

use LiveuEventsLog\Loggers\PostLogger;
use LiveuEventsLog\Services\AdminPageLoader;
use LiveuEventsLog\Services\LoggersLoader;
use LiveuEventsLog\Services\NotifiersLoader;
use LiveuEventsLog\Notifiers\SlackNotifier;
use LiveuEventsLog\Notifiers\EmailNotifier;

class Config
{
	private static $instance;

	protected function __construct() { }
	protected function __clone() { }

	public static function get_instance() {
		$cls = static::class;

		if (!isset(self::$instance)) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function get_loggers() {
		return array(
			PostLogger::class
		);
	}

	public function get_notifiers() {
		return array(
			EmailNotifier::class,
			SlackNotifier::class,
		);
	}

	public function get_services() {
		return [
			LoggersLoader::class,
			NotifiersLoader::class,
			AdminPageLoader::class,
		];
	}

	public function get_option(string $option_name) {
		if ( empty( $option_name ) ) {
			return false;
		}

		return get_option($option_name, false);
	}
}
