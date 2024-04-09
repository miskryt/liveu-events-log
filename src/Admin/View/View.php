<?php

namespace LiveuEventsLog\Admin\View;


use LiveuEventsLog\Admin\Interfaces\IView;

class View implements IView
{
	private $template_path;

	public function __construct ($template_path = LEVLOG_PATH)
	{
		$this->template_path = $template_path;
	}

	public function render (string $template, array $data = null): void {
		if(file_exists($this->template_path . $template . '.php')) {
			require_once $this->template_path . $template . '.php';
		}
	}
}
