<?php

namespace LiveuEventsLog\Admin\View;

use LiveuEventsLog\Admin\IView;

class View implements IView
{
	public function render (string $template, array $data = null): void {
		if(file_exists(LEVLOG_PATH . $template . '.php')) {
			require_once LEVLOG_PATH . $template . '.php';
		}
	}
}
