<?php


namespace LiveuEventsLog\Admin\Interfaces;


interface IView
{
	public function render(string $template, array $data = null) : void;
}
