<?php
namespace LiveuEventsLog\Services;

use LiveuEventsLog\Config\Config;

abstract class Service
{
	protected $config;

	public function __construct(Config $config) {
		$this->config = $config;
	}

	abstract public function loaded();
}
