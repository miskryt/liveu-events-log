<?php
namespace LiveuEventsLog\Services;

use LiveuEventsLog\Admin\Api\Api;
use LiveuEventsLog\Config\Config;

abstract class Service
{
	protected $config;
	protected $api;

	public function __construct(Api $api, Config $config) {
		$this->config = $config;
		$this->api = $api;
	}

	abstract public function loaded();
}
