<?php
namespace LiveuEventsLog\Config;

use LiveuEventsLog\Loggers\PostLogger;
use LiveuEventsLog\Services\LoggersLoader;
use LiveuEventsLog\Services\NotifiersLoader;
use LiveuEventsLog\Notifiers\SlackNotifier;
use LiveuEventsLog\Notifiers\EmailNotifier;

class Config
{
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
			NotifiersLoader::class
		];
	}

	public function get_option(string $option_name) {
		if ( empty( $option_name ) ) {
			return false;
		}

		return get_option($option_name, false);
	}
}
