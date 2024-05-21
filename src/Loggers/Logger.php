<?php


namespace LiveuEventsLog\Loggers;


abstract class Logger
{

	public function __construct() {

	}

	abstract public function loaded() : void;
	abstract public function get_slug() : string;
	abstract public function get_event_details(array $event);
}
