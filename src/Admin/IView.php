<?php


namespace LiveuEventsLog\Admin;


interface IView
{
	public function render(string $template, array $data = null) : void;
}
