<?php
namespace LiveuEventsLog\Services;

use LiveuEventsLog\Admin\Model\Model;
use LiveuEventsLog\Plugin;

abstract class Service
{
	public function __construct(Plugin $plugin) {
		$this->model = new Model();
		$this->plugin = $plugin;
	}

	abstract public function loaded();
}
