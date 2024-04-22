<?php


namespace LiveuEventsLog\Loggers;


use LiveuEventsLog\Admin\Api\Api;

abstract class Logger
{
	protected Api $api;

	public function __construct(Api $api) {
		$this->api = $api;
	}

	abstract public function loaded() : void;
	abstract public function get_slug() : string;
}
