<?php


namespace LiveuEventsLog\Admin\Interfaces;


interface IModel
{
	public function get_events_list() : array;
	public function get_records_count() : int;
}
